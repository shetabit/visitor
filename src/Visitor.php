<?php

namespace Shetabit\Visitor;

use Shetabit\Visitor\Contracts\Driver;
use Shetabit\Visitor\Exceptions\DriverNotFoundException;

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
     * @return string
     *
     * @throws \Exception
     */
    public  function ip() : string
    {
        return $this->getDriverInstance()->ip();
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
