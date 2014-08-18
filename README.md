# Meta

The meta trait gives a model the ability to dynamically set and get data on a model.

## Installation

Require through composer
```js
"require": {
    "enginebit/meta": "1.0.*"
}
```

Make sure the meta tables are generated with the following scheme
```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDataTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_data', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('user_id')->unsigned()->index();
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

			$table->string('key')->index();
			$table->text('value');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('user_data');
	}

}
```
## Use the trait on a model

```php
<?php

use Enginebit\Meta\MetaTrait;

class User extends Eloquent {

    use MetaTrait;

	/**
	 *  The Eloquent models table name
	 */
	protected $table = 'users';

	/**
	 *  The table name for the meta data
	 */
	protected $metaTable = 'user_data';

	/**
	 *  Default key name is 'model_id'
	 */
	protected $metaKeyName = 'user_id';

	/**
	 *  To set a custom data model
	 */
	protected $metaModel = 'Acme\User\Data';

}
```

## Setters and Getters
```php
<?php

$user = User::find(1);

$user->setMeta('first_name','John');
$user->setMeta('last_name','Doe');

$user->save();

echo $user->getMeta('first_name');
// or
echo $user->first_name; // This wil first check the model and if null is returned it wil check the meta model
```

## Saving datetime objects
```php

$user = User::find(1);

$user->set('activated_at',Carbon::now());

$user->save();

// On another request

$activated_at = $user->getMeta('activated_at'); // Will return the carbon object
// or
$activated_at = $user->activated_at;
```

## Saving Eloquent models
```php
$post = Post::find(1);

$user->setMeta('post',$post); // Will be saved as a string like 'Acme\Posts\Post#1'

$user->save();

// On another request

$user = User::find(1);

$post = $user->getMeta('post'); // Will return the model
// or
$post = $user->post;
```

## Unset data
The meta data will only really be deleted is the save method is called on the model.

``` php
<?php

$user = User::find(1);

$user->deleteMeta('post');
// or
unset($user->post);

$user->save
```