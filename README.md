<p align="center"><img width="200" src="resources/images/visitor.png?raw=true"></p>

# Laravel Visitor

[![Software License][ico-license]](LICENSE.md)
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads on Packagist][ico-download]][link-packagist]
[![Tests][ico-tests]][link-tests]
[![Code Style][ico-code-style]][link-code-style]
[![Static Analysis][ico-static-analysis]][link-static-analysis]
[![Code Coverage][ico-coverage]][link-coverage]

This is a laravel package to extract and access visitors' information such as `browser`, `ip`, `device` and etc.

**In this package, you can recognize online users and determine if a user is online or not**

This package requires `PHP 8.4+` and supports `Laravel 12+`.

### Features

- [Visitor's information](#how-to-use): browser, platform, device, languages, IP, user agent and the route of the
  request, through `visitor()` or `$request->visitor()`
- [Visit logs](#store-logs) of pages, of models and of who visited them, with their visits and unique visitors counted
- [Route names](#store-logs) kept with every visit, so the visits of a route can be filtered by it
- [Readable urls](#store-logs) of Persian, Arabic and other pages, decoded for showing them
- [Online users](#retrieve-and-determine-online-users): who is online, and whether a given user is
- [Automatic logging](#automatic-logging) of every request, and of the models the router bound, by a middleware
- [GeoIP enrichment](#geoip-enrichment): the country, region, city and coordinates of a visit's IP
- [Extra attributes](#extra-attributes): data of your own kept beside a visit, and filtered by
- [Custom visit model](#custom-visit-model) with relations, scopes, accessors and casts of your own
- [Database connection and multi-tenancy](#database-connection-and-multi-tenancy): the visits kept in a database of
  their own, the tenant's for instance
- [Two user agent parsers](#configure) to choose from, and paths that are not logged

### Install

Via composer

```bash
composer require shetabit/visitor
```

### Configure

The service provider and the `Visitor` alias are registered by Laravel's package discovery, so there is nothing to add
to `config/app.php` (or to `bootstrap/providers.php`).

Run the below commands to publish the migrations and create the tables

```bash
php artisan vendor:publish --tag=visitor-migrations

php artisan migrate
```

The configuration file can be published as well, if you want to change the driver, the name of the table or the paths
that are not logged

```bash
php artisan vendor:publish --tag=visitor-config
```

### How to use

You can access to `visitor's information` using `$request->visitor()` in your controllers , and  you can access to the visitor's information using `visitor()` helper function any where.

We have the below methods to retrieve a visitor's information:

- `device` : device's name
- `platform` : platform's name
- `browser` : browser's name
- `languages` : language's name
- `ip` : client's ip
- `request` : the whole request inputs
- `route` : the route the request matched
- `routeName` : the name of the route the request matched
- `useragent` : the whole useragent
- `isOnline` : determines if current (or given) user is online

```php
$request->visitor()->browser(); // firefox
$request->visitor()->visit($post); // create log for post
$request->visitor()->setVisitor($user)->visit($post); // create a log which says $user has visited $post
```

#### Store Logs

You can create logs using the `visit` method like the below

```php
visitor()->visit(); // create a visit log
```

use `Shetabit\Visitor\Traits\Visitable` trait in your models, then you can save visit's log for your models like the below

```php
// or you can save log like the below
visitor()->visit($model);
// or like the below
$model->createVisitLog();

// you can say which user has visited the given $model
$model->createVisitLog($user);
// or like the below
visitor()->setVisitor($user)->visit($model);

```

The visits of a model can be loaded using its `visitLogs` relation.

You can count model visits like the below

```php
$model->visitLogs()->count();
```
unique visitors can be counted by their IP and by the model they were signed in as.

```php
// by ip
$model->visitLogs()->distinct('ip')->count('ip');

// by user's model
$model->visitLogs()->distinct('visitor_id')->count('visitor_id');
```

Every visit keeps the name of the route it matched in its `route_name` column (`null` for a route without a name), so
the visits of a route can be filtered by it

```php
use Shetabit\Visitor\Models\Visit;

Visit::query()->where('route_name', 'posts.show')->count(); // visits of the posts.show route
visitor()->routeName(); // the name of the route of the current request
visitor()->route(); // the route of the current request
```

The name is the one the route had when it was visited. If you rename a route, rename it in the visits it already has
as well, so they keep being found under it

```php
Visit::query()->where('route_name', 'posts.show')->update(['route_name' => 'articles.show']);
```

The `url` of a visit is kept as the request had it, so the characters of a Persian or Arabic page, among others, are
percent-encoded in it (`/products/%D9%85%D8%AD%D8%B5%D9%88%D9%84`). Decoding it before keeping it would lose what tells
an encoded `/`, `&` or `=` apart from a real one, so it is decoded for showing it instead, by `decoded_url`

```php
$visit->url;         // https://example.com/products/%D9%85%D8%AD%D8%B5%D9%88%D9%84
$visit->decoded_url; // https://example.com/products/محصول
```

use `Shetabit\Visitor\Traits\Visitor` in your `User` class, then you can run below codes

 ```php
$user->visit(); // create a visit log
$user->visit($model); // create a log which says, $user has visited $model
 ```

#### Retrieve and Determine Online users

use `Shetabit\Visitor\Traits\Visitor` in your `User` class at first.

Then you can retrieve online users which are instance of `User` class and determine if a user is online.

```php
visitor()->onlineVisitors(User::class); // returns collection of online users
User::online()->get(); // another way

visitor()->isOnline($user); // determines if the given user is online
$user->isOnline(); // another way
```

#### Automatic logging

Your application can store visitor's log automatically using `LogVisits` middleware.

Add the `Shetabit\Visitor\Middlewares\LogVisits` middleware if you want to save logs automatically.

The middleware will store logs for models which has binded in router (router model binding) and has used `Shetabit\Visitor\Traits\Visitable` trait.

It has to run after Laravel's `SubstituteBindings` middleware, which is what turns a route parameter into a model —
appending it to the `web` group takes care of that.

#### GeoIP enrichment

A visit can carry the location of the IP it came from, in its `geo_raw` column. It is disabled by default. To enable it,
install [stevebauman/location](https://github.com/stevebauman/location) and turn it on in `config/visitor.php`

```bash
composer require stevebauman/location
```

```php
'geoip' => true,
```

The bundled resolver hands the country, the region, the city and the coordinates of a visit over. You can write your
own by implementing `Shetabit\Visitor\Contracts\GeoIpResolver` and adding it to the `resolvers` array of the config.

#### Extra attributes

A visit can keep data of your own beside it, a name or a mobile number for instance, without a column for each of them:
hand them over as key and value pairs when the visit is logged, and they are kept in its `extra_attributes` JSON column

```php
visitor()->visit($post, ['name' => $request->input('name'), 'mobile' => $request->input('mobile')]);
$post->createVisitLog($user, ['mobile' => $request->input('mobile')]);
$user->visit($post, ['mobile' => $request->input('mobile')]);
```

They are read back as an array, and the visits can be filtered by them with Laravel's JSON path syntax

```php
$visit->extra_attributes['mobile'];

Visit::query()->where('extra_attributes->mobile', '09120000000')->get();
```

They never take the place of the columns the package fills: an `url` among them is kept in `extra_attributes`, beside
the visit's own `url`.

#### Custom visit model

The visits can be logged with a model of your own, to give them relations, scopes, accessors or casts of your
application. Extend the package's model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Shetabit\Visitor\Models\Visit as BaseVisit;

class Visit extends BaseVisit
{
    protected function mobile(): Attribute
    {
        return Attribute::get(fn () => $this->extra_attributes['mobile'] ?? null);
    }
}
```

and name it in `config/visitor.php`

```php
'model' => \App\Models\Visit::class,
```

The visitor, the `visitLogs` and `visits` relations and the online checks all use the model of the configuration, so
`$post->visitLogs()->first()->mobile` works. It has to extend `Shetabit\Visitor\Models\Visit`.

#### Database connection and multi-tenancy

The visits are kept in the default database connection. To keep them in another one, the tenant connection of a
multi-tenant application for instance, name it in `config/visitor.php`

```php
'connection' => 'tenant',
```

The visit model, a model of your own that extends it among them, and the package's migrations all use it: the
migrations create the visits table in that connection. Migrations you published before this setting existed do not
know it, so publish them again, or add a `getConnection()` that answers with the connection to the copies you have.

With a tenancy package that makes the tenant's connection the default one while it is active, stancl/tenancy's
multi-database mode for instance, leave it `null`.

The `online()` scope and `isOnline()` look the visits up together with the table of the visitors, so the visitors (your
`User` model, for instance) have to be in the same database as the visits for them.

## Testing

Every pull request and every push to `master` is checked by [GitHub Actions][link-actions]: the test suite runs on
PHP 8.4 and 8.5, against Laravel 12 and 13 and against both the lowest and the highest supported dependencies, the
coding style is checked with PHP_CodeSniffer, the sources are analysed with PHPStan (level 7, with larastan) and the
code coverage of the test suite is measured and has to stay above 95%.

The suite has two parts: `tests/Unit` covers the classes of the package on their own, and `tests/Feature` sends real
requests through the framework — the middleware, the request macro, the online visitors and the GeoIP enrichment.

You can run the same checks locally. With PHP and Composer installed on your machine:

```bash
composer install

composer test           # run the test suite
composer test-coverage  # run the test suite and report code coverage
composer check-style    # check the coding style
composer fix-style      # fix the coding style where possible
composer analyse        # run static analysis
composer ci             # run all of the checks above
```

If you would rather not install PHP on your machine, the shipped `Dockerfile` and `Makefile` run everything inside a
container:

```bash
make test              # run the test suite
make coverage          # run the test suite and report code coverage
make check-style       # check the coding style
make fix-style         # fix the coding style where possible
make analyse           # run static analysis
make ci                # run all of the checks above
make shell             # open a shell inside the container
make help              # list every available target
```

Another PHP version can be used with `make test PHP_VERSION=8.5`, and a single Laravel version with
`make test-laravel LARAVEL=12`.

## Star History

[![Star History Chart](https://api.star-history.com/svg?repos=shetabit/visitor&type=Date)](https://star-history.com/#shetabit/visitor&Date)

[ico-version]: https://img.shields.io/packagist/v/shetabit/visitor.svg?style=flat-square
[ico-download]: https://img.shields.io/packagist/dt/shetabit/visitor.svg?color=%23F18&style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-tests]: https://img.shields.io/github/actions/workflow/status/shetabit/visitor/tests.yml?branch=master&label=Tests&style=flat-square
[ico-code-style]: https://img.shields.io/github/actions/workflow/status/shetabit/visitor/code-style.yml?branch=master&label=Code%20Style&style=flat-square
[ico-static-analysis]: https://img.shields.io/github/actions/workflow/status/shetabit/visitor/static-analysis.yml?branch=master&label=Static%20Analysis&style=flat-square
[ico-coverage]: https://img.shields.io/codecov/c/github/shetabit/visitor/master?label=Coverage&style=flat-square

[link-packagist]: https://packagist.org/packages/shetabit/visitor
[link-actions]: https://github.com/shetabit/visitor/actions
[link-tests]: https://github.com/shetabit/visitor/actions/workflows/tests.yml
[link-code-style]: https://github.com/shetabit/visitor/actions/workflows/code-style.yml
[link-static-analysis]: https://github.com/shetabit/visitor/actions/workflows/static-analysis.yml
[link-coverage]: https://codecov.io/gh/shetabit/visitor

