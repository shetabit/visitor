<p align="center"><img width="200" src="resources/images/visitor.png?raw=true"></p>




# Laravel Visitor

this is a laravel package to extract and access visitors' information such as `browser`, `ip`, `device` and etc.

### Install

via composer

```bash
composer require shetabit/visitor
```

### Configure

If you are using Laravel 5.5 or higher then you don't need to add the provider and alias.

```php
# In your providers array.
'providers' => [
    ...
    Shetabit\Visitor\Provider\VisitorServiceProvider::class,
],

# In your aliases array.
'aliases' => [
    ...
    'Payment' => Shetabit\Visitor\Facade\Visitor::class,
],
```

### How to use

you can access to `visitor's information` using `$request->visitor()` in your controllers , and  you can access to the visitor's information using `visitor()` helper function any where.

we have the below methods to retrieve a visitor's information:

- `device` : device's name
- `platform` : platform's name
- `browser` : browser's name
- `languages` : langauge's name
- `ip` : client's ip
- `request` : the whole request inputs
- `useragent` : the whole useragent

#### Store Logs

you can create logs using the `visit` method like the below

```php
visitor()->visit();
```

use `Shetabit\Visitor\Traits\Visitable` trait in your models, then you can save visit's log for your models like the below

```php
visitor()->visit($model);
// or you can save log like the below
$model->visit();
// or you can do it like the below
$model->view();
```

model views can be loaded using `visits` relation.
you can count model visits like the below

```php
$model->visits()->count();
```

#### Automatic loging

your application can store visitor's log automatically using `LogVisits` middleware.

add the `Shetabit\Visitor\Middlewares\LogVisits` middleware if you want to save logs automatically.

the middleware will store logs for models which has binded in router (router model binding) and has used `Shetabit\Visitor\Traits\Visitable` trait.