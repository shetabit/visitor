# Changelog

All Notable changes to `visitor` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## Unreleased

### Added
- A test suite of 94 tests: unit tests for the user agent parser, both drivers, both GeoIP resolvers, the visitor, the
  visit model, the traits and the service provider, and feature tests that send real requests through the framework —
  the middleware, the request macro, the online visitors and the GeoIP enrichment. It covers 98% of `src/`.
- GitHub Actions workflows running the test suite (PHP 8.4 and 8.5, Laravel 12 and 13, lowest and highest
  dependencies), the coding style check, the static analysis and the code coverage on every pull request and on every
  push to `master`. The coverage has to stay above 95%.
- A `Dockerfile` (with pcov, for coverage) and a `Makefile` to run the test suite and every check inside a container,
  so that no PHP installation is needed on the host.
- A `phpcs.xml.dist` ruleset, a `phpstan.neon.dist` configuration (level 7 with larastan) and a `rector.php`
  configuration, plus the `test-coverage`, `analyse`, `rector` and `ci` composer scripts.
- The visitor can be resolved and injected by its class name (`Shetabit\Visitor\Visitor`) as well, next to the
  `shetabit-visitor` binding the facade and the `visitor()` helper use. The name of that binding is available as
  `Shetabit\Visitor\Facade\Visitor::SERVICE_NAME`.
- The `visitor-config` and `visitor-migrations` publish tags, next to the `config` and `migrations` ones the package
  has always used.
- A visit keeps the name of the route it matched, in a new, indexed `route_name` column, so the visits of a route can
  be filtered by it. `Visitor::route()` answers with the route of the current request and `Visitor::routeName()` with
  its name. An existing table gets the column from the new `add_route_name_to_visits_table` migration: publish the
  migrations again and run them. The package now requires `illuminate/routing` as well, which its middleware has
  always used.
- A visit can keep data of the application beside it, as key and value pairs, in a new `extra_attributes` JSON column:
  `visit()`, `createVisitLog()` and the `visit()` of the `Visitor` trait take them as a new, optional
  `$extraAttributes` argument, and the visits can be filtered by them (`where('extra_attributes->mobile', ...)`). An
  existing table gets the column from the new `add_extra_attributes_to_visits_table` migration: publish the migrations
  again and run them.

### Changed
- **Breaking:** PHP 8.4 is now the minimum required version (was PHP 8.0), and only the last two major versions of
  Laravel are supported: `^12.0|^13.0` (was `9.*` and up). The package now also requires `illuminate/database` and
  `illuminate/http`, which it has always used.
- **Breaking:** the dependencies were narrowed down to their latest versions: `mobiledetect/mobiledetectlib: ^4.11`,
  `ua-parser/uap-php: ^3.10` and `jaybizzle/crawler-detect: ^1.4`.
- The package was modernized for PHP 8.4. Every parameter, return value and property of `src/` declares a type now,
  promoted constructor properties are used, nullable types are spelled `T|null`, class constants are typed and the
  tabs that were mixed into `src/Visitor.php` and `config/visitor.php` are gone.
- The visitor is bound `scoped` instead of `singleton`. It is handed the request it works with while it is being
  built, so a long running worker (Octane, a queue worker) used to keep answering every later request with the data of
  the first one.
- The user agent parser of a visitor is built once and then kept. `device()`, `platform()`, `browser()` and
  `languages()` each used to build one of their own, which parses the user agent again — and for the UAParser driver
  reads its regex file again.
- **Breaking:** the migrations ship as `database/migrations/*.php` (anonymous classes) instead of `*.php.stub` with a
  class name, and are published with `publishesMigrations()`. `geo_raw` was taken out of the create migration, which
  is where it collided with the migration that adds it.
- `stevebauman/location` is a suggested dependency now, so that the GeoIP resolver that needs it says so.

### Fixed
- **`Agent`:** `createCacheKey()` took over a method of `MobileDetect`, which builds the keys of its own cache with it.
  Every call that reaches that cache — `isMobile()`, `isTablet()` and with them `isDesktop()`, `isPhone()` and
  `deviceType()` — failed with a "cache key is invalid". The local store uses `storeKey()` now.
- **`Agent`:** `getRules()` added the desktop devices and the desktop browsers to the rules of the parent, which is
  what the parent answers `isMobile()` with. Every desktop was a mobile device, and with that a phone. The rules of
  the class are in `getAdditionalRules()` now, and `isMacintosh()`, `isWindows()`, `isFirefox()` and the like still
  answer through `__call()`. **Breaking** for anything that read `Agent::getRules()`.
- **`Agent`:** the cache key of a resolved value carried 16 random bytes, so every lookup was a miss and the store
  grew with every call. `$crawlerDetect` had no default, which made `robot()` and `isRobot()` fail with a "typed
  static property must not be accessed before initialization". `languages()` declared an implicitly nullable
  parameter, which PHP 8.4 deprecates.
- **`Visitor`:** `ResolverNotFoundException` was thrown without being imported, so a resolver that is not in the
  configuration ended in a "class not found" instead of the exception.
- **`Visitor`:** `isOnline()` compared the window with `whereDate()`, which only looks at the date and answered
  "online" for every visit of the same day, and `onlineVisitors()` ignored the `$seconds` it was given.
- **`Visitor`:** `referer()` read `$_SERVER` instead of the request.
- **`JenssegersAgent`:** the header bag of the request was handed to the parser cast to an array, which produced keys
  the parser can not read. The languages of a visitor were never detected.
- **`UAParser`:** a request without a user agent ended in a `TypeError`, and the languages were read off `$_SERVER`
  with only the first two characters of the header kept. Both drivers read the languages of the request the same way
  now, ordered by the priority of the header.
- **`Visitable`:** `createVisitLog()` required a visitor, while the readme shows it being called without one.
- **`CanVisit`:** `isOnline()` asked for a `user` relation of the visits and for a `user_id` column, neither of which
  exists, so it always threw.
- **`LogVisits`:** a request that matches no route ended in a call to `parameters()` on `null`.
- **`Visitor` trait:** the `Model` type hint of `visit()` was not imported, so passing a model in ended in a
  `TypeError`, and `scopeOnline()` did not return the query it built.

### Removed
- **Breaking:** `Shetabit\Visitor\Provider\AgentServiceProvider` and the `Agent` facade. The facade was declared
  under a namespace that does not match its path (`Shetabit\Agent\Facades`), so it could never be autoloaded, and
  the provider built a `Shetabit\Agent\Agent` that does not exist — with the constructor of MobileDetect 3 on top.
  Neither was registered, documented or usable. `Visitor::device()`, `platform()` and `browser()` are the way to the
  parser, and `Shetabit\Visitor\Agent` can be built directly.

## Date - 2019-01-09

### Fixed
- Nothing

### Added
- Nothing

### Deprecated
- Nothing

### Fixed
- Nothing

### Removed
- Nothing

### Security
- Nothing
