<?php

namespace Shetabit\Visitor;

use BadMethodCallException;
use Closure;
use Detection\Exception\MobileDetectException;
use Detection\MobileDetect;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Random\RandomException;

class Agent extends MobileDetect
{

    /**
     * List of desktop devices.
     *
     * @var array<string, string>
     */
    protected static array $desktopDevices = [
        'Macintosh' => 'Macintosh',
    ];
    /**
     * List of additional operating systems.
     *
     * @var array<string, string>
     */
    protected static array $additionalOperatingSystems = [
        'Windows' => 'Windows',
        'Windows NT' => 'Windows NT',
        'OS X' => 'Mac OS X',
        'Debian' => 'Debian',
        'Ubuntu' => 'Ubuntu',
        'Macintosh' => 'PPC',
        'OpenBSD' => 'OpenBSD',
        'Linux' => 'Linux',
        'ChromeOS' => 'CrOS',
    ];
    /**
     * List of additional browsers.
     *
     * @var array<string, string>
     */
    protected static array $additionalBrowsers = [
        'Opera Mini' => 'Opera Mini',
        'Opera' => 'Opera|OPR',
        'Edge' => 'Edge|Edg',
        'Coc Coc' => 'coc_coc_browser',
        'UCBrowser' => 'UCBrowser',
        'Vivaldi' => 'Vivaldi',
        'Chrome' => 'Chrome',
        'Firefox' => 'Firefox',
        'Safari' => 'Safari',
        'IE' => 'MSIE|IEMobile|MSIEMobile|Trident/[.0-9]+',
        'Netscape' => 'Netscape',
        'Mozilla' => 'Mozilla',
        'WeChat' => 'MicroMessenger',
    ];

    protected static CrawlerDetect $crawlerDetect;

    /**
     * Key value store for resolved strings.
     *
     * @var array<string, mixed>
     */
    protected array $store = [];

    public function getRules(): array
    {
        static $rules;

        if (!$rules) {
            $rules = array_merge(
                static::$browsers,
                static::$operatingSystems,
                static::$phoneDevices,
                static::$tabletDevices,
                static::$desktopDevices, // NEW
                static::$additionalOperatingSystems, // NEW
                static::$additionalBrowsers // NEW
            );
        }

        return $rules;
    }

    /**
     * Get accept languages.
     * @param string|null $acceptLanguage
     * @return array
     */
    public function languages(string $acceptLanguage = null): array
    {
        if ($acceptLanguage === null) {
            $acceptLanguage = $this->getHttpHeader('HTTP_ACCEPT_LANGUAGE');
        }

        if (!$acceptLanguage) {
            return [];
        }

        $languages = [];

        // Parse accept language string.
        foreach (explode(',', $acceptLanguage) as $piece) {
            $parts = explode(';', $piece);
            $language = strtolower($parts[0]);
            $priority = empty($parts[1]) ? 1. : (float)str_replace('q=', '', $parts[1]);

            $languages[$language] = $priority;
        }

        // Sort languages by priority.
        arsort($languages);

        return array_keys($languages);
    }

    /**
     * Get the browser name.
     * @return string|bool
     */
    public function browser(): bool|string
    {
        return $this->retrieveUsingCacheOrResolve('visitor.browser', function () {
            return $this->findDetectionRulesAgainstUserAgent(
                $this->mergeRules(static::$additionalBrowsers, MobileDetect::getBrowsers())
            );
        });
    }

    /**
     * Retrieve from the given key from the cache or resolve the value.
     *
     * @param string $key
     * @param \Closure():mixed $callback
     * @return mixed
     */
    protected function retrieveUsingCacheOrResolve(string $key, Closure $callback): mixed
    {
        $cacheKey = $this->createCacheKey($key);

        if (!is_null($cacheItem = $this->store[$cacheKey] ?? null)) {
            return $cacheItem;
        }

        return tap($callback(), function ($result) use ($cacheKey) {
            $this->store[$cacheKey] = $result;
        });
    }

    /**
     * @throws RandomException
     */
    protected function createCacheKey(string $key): string
    {
        $userAgentKey = $this->hasUserAgent() ? $this->userAgent : '';
        $randomBytes = random_bytes(16);
        $randomHash = bin2hex($randomBytes);

        return base64_encode("$key:$userAgentKey:$randomHash");
    }

    /**
     * Match a detection rule and return the matched key.
     *
     * @param array $rules
     * @return string|null
     */
    protected function findDetectionRulesAgainstUserAgent(array $rules): ?string
    {
        $userAgent = $this->getUserAgent();

        foreach ($rules as $key => $regex) {
            if (empty($regex)) {
                continue;
            }

            // regex is an array of "strings"
            if (is_array($regex)) {
                foreach ($regex as $regexString) {
                    if ($this->match($regexString, $userAgent)) {
                        return $key ?: reset($this->matchesArray);
                    }
                }
            } else if ($this->match($regex, $userAgent)) {
                return $key ?: reset($this->matchesArray);
            }

        }

        return false;
    }

    /**
     * Merge multiple rules into one array.
     *
     * @param array $all
     * @return array<string, string>
     */
    protected function mergeRules(...$all): array
    {
        $merged = [];

        foreach ($all as $rules) {
            foreach ($rules as $key => $value) {
                if (empty($merged[$key])) {
                    $merged[$key] = $value;
                } elseif (is_array($merged[$key])) {
                    $merged[$key][] = $value;
                } else {
                    $merged[$key] .= '|' . $value;
                }
            }
        }

        return $merged;
    }

    /**
     * Get the platform name.
     *
     * @return string|bool
     */
    public function platform(): bool|string
    {
        return $this->retrieveUsingCacheOrResolve('visitor.platform', function () {
            return $this->findDetectionRulesAgainstUserAgent(
                $this->mergeRules(static::$additionalOperatingSystems, MobileDetect::getOperatingSystems())
            );
        });
    }

    /**
     * Get the device name.
     * @return string|bool
     */
    public function device(): bool|string
    {
        return $this->findDetectionRulesAgainstUserAgent(
            $this->mergeRules(
                static::getDesktopDevices(),
                static::getPhoneDevices(),
                static::getTabletDevices()
            )
        );
    }

    /**
     * Retrieve the list of known Desktop devices.
     *
     * @return array List of Desktop devices.
     */
    public static function getDesktopDevices(): array
    {
        return static::$desktopDevices;
    }

    /**
     * Get the robot name.
     * @return string|bool
     */
    public function robot(): bool|string
    {
        $userAgent = $this->getUserAgent();

        if ($this->getCrawlerDetect()->isCrawler($userAgent ?: $this->userAgent)) {
            return ucfirst($this->getCrawlerDetect()->getMatches());
        }

        return false;
    }

    /**
     * @return CrawlerDetect
     */
    public function getCrawlerDetect(): CrawlerDetect
    {
        if (static::$crawlerDetect === null) {
            static::$crawlerDetect = new CrawlerDetect();
        }

        return static::$crawlerDetect;
    }

    /**
     * Get the device type
     * @return string
     * @throws MobileDetectException
     */
    public function deviceType(): string
    {
        if ($this->isDesktop()) {
            return "desktop";
        }

        if ($this->isPhone()) {
            return "phone";
        }

        if ($this->isTablet()) {
            return "tablet";
        }

        if ($this->isRobot()) {
            return "robot";
        }

        return "other";
    }

    /**
     * Check if the device is a desktop computer.
     * @return bool
     * @throws MobileDetectException
     */
    public function isDesktop(): bool
    {
        $userAgent = $this->getUserAgent();

        return $this->retrieveUsingCacheOrResolve('visitor.desktop', function () use ($userAgent) {

            // Check specifically for cloudfront headers if the useragent === 'Amazon CloudFront'
            if ($userAgent === static::$cloudFrontUA && $this->getHttpHeader('HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER') === 'true') {
                return true;
            }

            return !$this->isMobile() && !$this->isTablet() && !$this->isRobot($userAgent);
        });

    }

    /**
     * Check if device is a robot.
     * @return bool
     */
    public function isRobot(): bool
    {
        $userAgent = $this->getUserAgent();

        return $this->getCrawlerDetect()->isCrawler($userAgent ?: $this->userAgent);
    }

    /**
     * Check if the device is a mobile phone.
     * @return bool
     * @throws MobileDetectException
     */
    public function isPhone(): bool
    {
        return $this->isMobile() && !$this->isTablet();
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $arguments)
    {
        // Make sure the name starts with 'is', otherwise
        if (!str_starts_with($name, 'is')) {
            throw new BadMethodCallException("No such method exists: $name");
        }

        $key = substr($name, 2);

        return $this->matchUserAgentWithRule($key);
    }
}
