<?php

namespace Shetabit\Visitor;

use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Contracts\Driver;
use Shetabit\Visitor\Exceptions\DriverNotFoundException;
use Shetabit\Visitor\Models\Visit;

class Visitor implements Driver
{
    /**
     * Configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Driver Name.
     *
     * @var string
     */
    protected $driver;

    /**
     * Driver Instance.
     *
     * @var object
     */
    protected $driverInstance;

    /**
     * Visitor constructor.
     *
     * @param $config
     *
     * @throws \Exception
     */
    public function __construct($config)
    {
        $this->config = $config;

        $this->via($this->config['default']);
    }

    /**
     * Change the driver on the fly.
     *
     * @param $driver
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function via($driver)
    {
        $this->driver = $driver;
        $this->validateDriver();

        return $this;
    }

    /**
     * Retrieve request's data
     *
     * @return array
     */
    public function request() : array
    {
        return $this->getDriverInstance()->request();
    }

    /**
     * Retrieve agent.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function userAgent() : string
    {
        return $this->getDriverInstance()->userAgent();
    }

    /**
     * Retrieve http headers.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function httpHeaders() : array
    {
        return $this->getDriverInstance()->httpHeaders();
    }

    /**
     * Retrieve device's name.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function device() : string
    {
        return $this->getDriverInstance()->device();
    }

    /**
     * Retrieve platform's name.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function platform() : string
    {
        return $this->getDriverInstance()->platform();
    }

    /**
     * Retrieve browser's name.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function browser() : string
    {
        return $this->getDriverInstance()->browser();
    }

    /**
     * Retrieve languages.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function languages() : array
    {
        return $this->getDriverInstance()->languages();
    }

    /**
     * Retrieve user's ip.
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public  function ip() : ?string
    {
        return $this->getDriverInstance()->ip();
    }

    /**
     * Retrieve request's url
     *
     * @return string
     *
     * @throws \Exception
     */
    public function url() : string
    {
        return $this->getDriverInstance()->url();
    }

    /**
     * Retrieve request's referer
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public function referer() : ?string
    {
        return $this->getDriverInstance()->referer();
    }

    /**
     * Retrieve request's method.
     *
     * @return string
     */
    public function method() : string
    {
        return $this->getDriverInstance()->method();
    }

    /**
     * Create a visit log.
     *
     * @param Model $model
     */
    public function visit(Model $model = null)
    {
        if (method_exists($model,  'visit')) {
            $visit = $model->visit();
        } else {
            $visit = Visit::create([
                'method' => $this->method(),
                'request' => $this->request(),
                'url' => $this->url(),
                'referer' => $this->referer(),
                'languages' => $this->languages(),
                'useragent' => $this->userAgent(),
                'headers' => $this->httpHeaders(),
                'device' => $this->device(),
                'platform' => $this->platform(),
                'browser' => $this->browser(),
                'ip' => $this->ip(),
                'user_id' => request()->user() ? request()->user()->id : null,
                'user_type' => request()->user() ? get_class(request()->user()): null
            ]);
        }

        return $visit;
    }

    /**
     * Alias for visit.
     *
     * @param Model $model
     */
    public function view(Model $model)
    {
        return $this->visit($model);
    }

    /**
     * Retrieve current driver instance or generate new one.
     *
     * @return mixed|object
     *
     * @throws \Exception
     */
    protected function getDriverInstance()
    {
        if (!empty($this->driverInstance)) {
            return $this->driverInstance;
        }

        return $this->getFreshDriverInstance();
    }

    /**
     * Get new driver instance
     *
     * @return Driver
     *
     * @throws \Exception
     */
    protected function getFreshDriverInstance()
    {
        $this->validateDriver();

        $driverClass = $this->config['drivers'][$this->driver];

        return app($driverClass);
    }

    /**
     * Validate driver.
     *
     * @throws \Exception
     */
    protected function validateDriver()
    {
        if (empty($this->driver)) {
            throw new DriverNotFoundException('Driver not selected or default driver does not exist.');
        }

        $driverClass = $this->config['drivers'][$this->driver];

        if (empty($driverClass) || !class_exists($driverClass)) {
            throw new DriverNotFoundException('Driver not found in config file. Try updating the package.');
        }

        $reflect = new \ReflectionClass($driverClass);

        if (!$reflect->implementsInterface(Driver::class)) {
            throw new \Exception("Driver must be an instance of Contracts\Driver.");
        }
    }
}
