<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | This value determines which of the following driver to use.
    | You can switch to a different driver at runtime.
    |
    */
    'default' => 'jenssegers',

    //except save request or route names
    'except' =>  ['login', 'register'],


    //name of the table which visit records should save in
    'table_name' =>  'shetabit_visits',

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection the visits are kept in, and that the package's
    | migrations create their table in. Leave it null to use the default
    | connection, or name another one of config/database.php: the tenant
    | connection of a multi-tenant application, for instance.
    |
    */
    'connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Visit Model
    |--------------------------------------------------------------------------
    |
    | The model the visits are logged with. Extend
    | Shetabit\Visitor\Models\Visit and name your model here to give the
    | visits relations, scopes, accessors or casts of your own, for instance
    | an accessor that reads a value out of the extra attributes:
    |
    |     visitor()->visit($post, ['mobile' => $request->input('mobile')]);
    |
    | The visitor, the visitLogs and visits relations and the online checks
    | all use it. It has to extend the package's model.
    |
    */
    'model' => \Shetabit\Visitor\Models\Visit::class,

    /*
    |--------------------------------------------------------------------------
    | List of Drivers
    |--------------------------------------------------------------------------
    |
    | This is the array of Classes that maps to Drivers above.
    | You can create your own driver if you like and add the
    | config in the drivers array and the class to use for
    | here with the same name. You will have to implement
    | Shetabit\Visitor\Contracts\UserAgentParser in your driver.
    |
    */
    'drivers' => [
        'jenssegers' => \Shetabit\Visitor\Drivers\JenssegersAgent::class,
        'UAParser' => \Shetabit\Visitor\Drivers\UAParser::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | GeoIP Enrichment
    |--------------------------------------------------------------------------
    |
    | When enabled, visits can be enriched with geolocation data stored
    | in the "geo_raw" JSON column. This package ships with a default
    | resolver (stevebauman/location) and a MaxMind one (geoip2/geoip2),
    | which reads a GeoIP2 or GeoLite2 City database. You may implement
    | your own by adding it to the resolvers array below. Each resolver
    | must implement Shetabit\Visitor\Contracts\GeoIpResolver.
    |
    */
    'geoip'   => false,           // disable enrichment by default
    'resolver'=> 'stevebauman',   // default resolver
    'resolvers' => [
        'stevebauman' => \Shetabit\Visitor\Resolvers\GeoIp\SteveBaumanResolver::class,
        'maxmind'     => \Shetabit\Visitor\Resolvers\GeoIp\MaxMindResolver::class,
        'null'        => \Shetabit\Visitor\Resolvers\GeoIp\NullResolver::class,
    ],

    // the database the maxmind resolver reads, and the languages of the names it answers with, by preference
    'maxmind' => [
        'database' => storage_path('app/geoip/GeoLite2-City.mmdb'),
        'locales'  => ['en'],
    ],
];
