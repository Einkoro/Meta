<?php namespace Enginebit\Meta;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

trait MetaTrait {

	/**
	 * @var Collection
	 */
	protected $metaData;

	/**
	 * True when the meta is loaded
	 *
	 * @var
	 */
	protected $metaLoaded = false;

	/**
	 * Boot the trait
	 */
	public static function bootMetaTrait()
	{
		static::saved(function ($model)
		{
			/** @var $model Model */
			$model->saveMeta();
		});
	}

	/**
	 * Load the meta data
	 *
	 * @return BaseCollection|null
	 */
	protected function loadMetaData()
	{
		if (! $this->metaLoaded)
		{


			if ($this->exists)
			{
				$objects = $this->newMetaModel()->where($this->getMetaKeyName(),$this->getKey())->get();

				if (! is_null($objects))
				{
					$this->metaLoaded = true;
					return $this->metaData = $objects->keyBy('key');
				}
			}
			$this->metaLoaded = true;
			$this->metaData = new Collection();
		}

		return null;
	}

	/**
	 * Set a meta value by key
	 *
	 * @param $key
	 * @param $value
	 */
	public function setMeta($key, $value)
	{
		$this->loadMetaData();

		if ($this->metaData->has($key))
		{
			$this->metaData[$key]->value = $value;
		}
		else
		{
			$this->metaData[$key] = $this->newMetaModel([
				'key'   => $key,
				'value' => $value
			]);
		}
	}

	/**
	 * Get a meta value
	 *
	 * @param $key
	 * @param bool $raw
	 * @return null
	 */
	public function getMeta($key, $raw = false)
	{
		$this->loadMetaData();

		$meta = $this->metaData->get($key, null);

		return (is_null($meta) or $meta->isDeleted()) ? null : ($raw) ? $meta : $meta->value ;
	}

	/**
	 * Get all the meta data
	 *
	 * @param bool $raw
	 * @return BaseCollection
	 */
	public function getAllMeta($raw = false)
	{
		$this->loadMetaData();

		$return = new BaseCollection();

		/** @var $meta Data */
		foreach($this->metaData as $meta)
		{
			if(!$meta->isDeleted())
			{
				$return->put($meta->key,$raw ? $meta : $meta->value);
			}

		}

		return $return;
	}

	/**
	 * Remove a meta key
	 *
	 * @param $key
	 */
	public function deleteMeta($key)
	{
		$this->loadMetaData();

		/** @var Data $meta */
		$meta = $this->metaData->get($key, null);

		if (! is_null($meta))
		{
			$meta->deleteOnSave();
		}
	}

	/**
	 * Persist the meta data
	 */
	public function saveMeta()
	{
		$this->loadMetaData();

		/** @var $meta Data */
		foreach ($this->metaData as $meta)
		{
			$meta->setTable($this->getMetaTable());

			if ($meta->isDeleted())
			{
				$meta->delete();
			}
			elseif ($meta->isDirty())
			{
				$meta->setAttribute($this->getMetaKeyName(),$this->getKey());
				$meta->save();
			}
		}
	}

	/**
	 * Return the foreign key name for the meta table
	 *
	 * @return string
	 */
	protected function getMetaKeyName()
	{
		return isset($this->metaKeyName) ? $this->metaKeyName : 'model_id';
	}

	/**
	 * Return the table name
	 *
	 * @return null
	 */
	protected function getMetaTable()
	{
		return isset($this->metaTable) ? $this->metaTable : null;
	}

	/**
	 * Returns a new meta model
	 *
	 * @param array $attributes
	 * @return Data
	 */
	public function newMetaModel(array $attributes = array())
	{
		if(isset($this->metaModel) && !is_null($this->metaModel))
		{
			$class = $this->metaModel;

			$model = new $class($attributes);
		}
		else
		{
			$model = new Data($attributes);
		}

		$model->setTable($this->getMetaTable());

		return $model;
	}

	/**
	 * Return the meta as an array
	 *
	 * @return array
	 */
	public function metaToArray()
	{
		return $this->getAllMeta()->toArray();
	}

	/**
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$attributes = $this->attributesToArray();

		$attributes = array_merge($attributes, $this->relationsToArray());

		return array_merge($attributes, $this->metaToArray());
	}

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		$value = $this->getAttribute($key);

		if(is_null($value))
		{
			$value = $this->getMeta($key);
		}

		return $value;
	}

	/**
	 * Unset the data
	 *
	 * @param string $key
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);

		unset($this->relations[$key]);

		$this->deleteMeta($key);
	}
} 