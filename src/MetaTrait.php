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

		if (is_null($meta) or $meta->isDeleted())
		{
			$return = null;
		}
		else
		{
			$return = ($raw) ? $meta : $meta->value;
		}

		return $return;
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
	 * Get an attribute from the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		$inAttributes = array_key_exists($key, $this->attributes);

		// If the key references an attribute, we can just go ahead and return the
		// plain attribute value from the model. This allows every attribute to
		// be dynamically accessed through the _get method without accessors.
		if ($inAttributes || $this->hasGetMutator($key))
		{
			return $this->getAttributeValue($key);
		}

		// If the key already exists in the relationships array, it just means the
		// relationship has already been loaded, so we'll just return it out of
		// here because there is no need to query within the relations twice.
		if (array_key_exists($key, $this->relations))
		{
			return $this->relations[$key];
		}

		// If the "attribute" exists as a method on the model, we will just assume
		// it is a relationship and will load and return results from the query
		// and hydrate the relationship's value on the "relationships" array.
		$camelKey = camel_case($key);

		if (method_exists($this, $camelKey))
		{
			return $this->getRelationshipFromMethod($key, $camelKey);
		}

		// If we get here then there was nothing on the model
		// Lets try and retrieve the data from the relationship
		return $this->getMeta($key);
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
