# l5-api
L5 Building REST APIs generators.

## Usage

### Step 1: Installation

Add this in composer.json

```
    "require": {
    ...
        "fromz/l5api": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:pkfrom/l5-api.git"
        }
    ],
```

### Step 2: Run composer
```
composer update
```

### Step 3: Add the Service Provider

Open `config/app.php` and, to your **providers** array at the bottom, add:

```
Fromz\L5Api\ApiServiceProvider::class,
```

### Step 4: Run Artisan!

```
php artisan vendor:publish --provider="Fromz\L5Api\ApiServiceProvider"
```

You're all set. Run `php artisan` from the console, and you'll see the new commands `make:api`.

## Examples


```
php artisan make:api User
```

1) You may have noticed that after installation you already have a routes file `app/Api/routes.php` which looks like that:

```php
<?php

Route::group(['prefix' => 'api/v1', 'namespace' => 'App\Api\Controllers'], function () {
    //
});

```

Feel free to change it if you like.

The generator adds ```Route::resource('users', 'UserController');``` to the end of this file.

```php
<?php

Route::group(['prefix' => 'api/v1', 'namespace' => 'App\Api\Controllers'], function () {
    //
    Route::resource('users', 'UserController');
});

```

As you can see it's smart enough to detect some route groups and treat this situation properly.

2) Then the generator creates a controller that extends base api controller.

```php
<?php namespace App\Api\Controllers;

use App\User;
use App\Api\Transformers\UserTransformer;

class UserController extends Controller
{
    /**
     * Eloquent model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function model()
    {
        return new User;
    }

    /**
     * Transformer for the current model.
     *
     * @return \League\Fractal\TransformerAbstract
     */
    protected function transformer()
    {
        return new UserTransformer;
    }
}

```
You can customize this stub as much as you want.

3) Finally the generator creates a fractal Transformer

```php
<?php namespace App\Api\Transformers;

use App\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    /**
     * Resource key.
     *
     * @var string
     */
    protected $resourceKey = null;
    
    /**
     * Turn this item object into a generic array.
     *
     * @param $item
     * @return array
     */
    public function transform(User $item)
    {
        return [
            'id'         => (int)$item->id,
            'created_at' => (string)$item->created_at,
            'updated_at' => (string)$item->updated_at,
        ];
    }
}

```

### Skeleton

You may have noticed that controller which has just been generated includes two public methods - `model()` and `transformer()`
That's because those methods are the only thing that you need in your controller to set up a basic REST API if you use the Skeleton.

The list of routes that are available out of the box:

1. `GET api/v1/users`
2. `GET api/v1/users/{id}`
3. `POST  api/v1/users`
4. `PUT api/v1/users/{id}`
5. `DELETE  api/v1/users/{id}`

Request and respone format is json
Fractal includes are supported via $_GET['include'].
Validation rules for create and update can be set by overwriting `rulesForCreate` and `rulesForUpdate` in your controller.
