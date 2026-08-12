<?php

namespace Shetabit\Visitor;

use BadMethodCallException;
use Closure;
use Detection\Exception\MobileDetectException;
use Detection\MobileDetect;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Shetabit\Visitor\Support\AcceptLanguage;

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

    /**
     * The crawler detector, shared by every agent.
     */
    protected static CrawlerDetect|null $crawlerDetect = null;

    /**
     * Key value store for resolved strings.
     *
     * @var array<string, mixed>
     */
    protected array $store = [];

    /**
     * The rules this class adds to the ones of the parent.
     *
     * They are deliberately kept out of `getRules()`: the parent answers
     * `isMobile()` by matching the user agent against every rule that method
     * hands out, so adding the desktop devices and the desktop browsers to it
     * made every desktop a mobile device — and with that every desktop a phone.
     *
     * @return array<string, array<int, string>|string>
     */
    public function getAdditionalRules(): array
    {
        return $this->mergeRules(
            static::$desktopDevices,
            static::$additionalOperatingSystems,
            static::$additionalBrowsers
        );
    }

    /**
     * Get accept languages.
     *
     * @return array<int, string> the languages, the most preferred one first
     */
    public function languages(string|null $acceptLanguage = null): array
    {
        return AcceptLanguage::parse($acceptLanguage ?? $this->getHttpHeader('HTTP_ACCEPT_LANGUAGE'));
    }

    /**
     * Get the browser name.
     */
    public function browser(): bool|string
    {
        return $this->retrieveUsingCacheOrResolve(
            'visitor.browser',
            fn (): bool|string => $this->findDetectionRulesAgainstUserAgent(
                $this->mergeRules(static::$additionalBrowsers, MobileDetect::getBrowsers())
            ) ?? false
        );
    }

    /**
     * Get the platform name.
     */
    public function platform(): bool|string
    {
        return $this->retrieveUsingCacheOrResolve(
            'visitor.platform',
            fn (): bool|string => $this->findDetectionRulesAgainstUserAgent(
                $this->mergeRules(static::$additionalOperatingSystems, MobileDetect::getOperatingSystems())
            ) ?? false
        );
    }

    /**
     * Get the device name.
     */
    public function device(): bool|string
    {
        return $this->retrieveUsingCacheOrResolve(
            'visitor.device',
            fn (): bool|string => $this->findDetectionRulesAgainstUserAgent(
                $this->mergeRules(
                    static::getDesktopDevices(),
                    static::getPhoneDevices(),
                    static::getTabletDevices()
                )
            ) ?? false
        );
    }

    /**
     * Retrieve the list of known Desktop devices.
     *
     * @return array<string, string> List of Desktop devices.
     */
    public static function getDesktopDevices(): array
    {
        return static::$desktopDevices;
    }

    /**
     * Get the robot name.
     */
    public function robot(): bool|string
    {
        if ($this->isRobot()) {
            return ucfirst((string) $this->getCrawlerDetect()->getMatches());
        }

        return false;
    }

    /**
     * The crawler detector, created once and then shared.
     */
    public function getCrawlerDetect(): CrawlerDetect
    {
        return static::$crawlerDetect ??= new CrawlerDetect();
    }

    /**
     * Get the device type
     *
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
     *
     * @throws MobileDetectException
     */
    public function isDesktop(): bool
    {
        $userAgent = $this->getUserAgent();

        return $this->retrieveUsingCacheOrResolve('visitor.desktop', function () use ($userAgent): bool {
            // Check specifically for cloudfront headers if the useragent === 'Amazon CloudFront'
            if ($userAgent === static::$cloudFrontUA
                && $this->getHttpHeader('HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER') === 'true'
            ) {
                return true;
            }

            return !$this->isMobile() && !$this->isTablet() && !$this->isRobot();
        });
    }

    /**
     * Check if device is a robot.
     */
    public function isRobot(): bool
    {
        return $this->getCrawlerDetect()->isCrawler($this->getUserAgent() ?: $this->userAgent);
    }

    /**
     * Check if the device is a mobile phone.
     *
     * @throws MobileDetectException
     */
    public function isPhone(): bool
    {
        return $this->isMobile() && !$this->isTablet();
    }

    /**
     * @inheritdoc
     *
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): bool
    {
        // Make sure the name starts with 'is', otherwise
        if (!str_starts_with($name, 'is')) {
            throw new BadMethodCallException("No such method exists: $name");
        }

        $rule = substr($name, 2);

        return $this->matchUserAgentWithRule($rule) || $this->matchUserAgentWithAdditionalRule($rule);
    }

    /**
     * Match the user agent against a rule this class adds, `isMacintosh()` for
     * instance, which the parent does not know about.
     */
    protected function matchUserAgentWithAdditionalRule(string $ruleName): bool
    {
        $rules = array_change_key_case($this->getAdditionalRules());
        $regex = $rules[strtolower($ruleName)] ?? null;

        if (empty($regex)) {
            return false;
        }

        return $this->match(is_array($regex) ? implode('|', $regex) : $regex, (string) $this->getUserAgent());
    }

    /**
     * Retrieve from the given key from the cache or resolve the value.
     *
     * @param \Closure():mixed $callback
     */
    protected function retrieveUsingCacheOrResolve(string $key, Closure $callback): mixed
    {
        $storeKey = $this->storeKey($key);

        if (array_key_exists($storeKey, $this->store)) {
            return $this->store[$storeKey];
        }

        return $this->store[$storeKey] = $callback();
    }

    /**
     * The key the resolved value of the given key is kept under.
     *
     * The user agent is part of it, so that an agent that is handed a second
     * user agent does not answer with what it resolved for the first one.
     *
     * This is deliberately not called `createCacheKey()`: that method belongs
     * to the parent, which builds the keys of its own cache with it. Taking it
     * over made every call that reaches the cache of the parent — `isMobile()`,
     * `isTablet()` and with them `isDesktop()`, `isPhone()` and `deviceType()`
     * — fail with a "cache key is invalid".
     */
    protected function storeKey(string $key): string
    {
        $userAgentKey = $this->hasUserAgent() ? $this->userAgent : '';

        return base64_encode("$key:$userAgentKey");
    }

    /**
     * Match a detection rule and return the matched key.
     *
     * @param array<string, array<int, string>|string> $rules
     */
    protected function findDetectionRulesAgainstUserAgent(array $rules): string|null
    {
        $userAgent = (string) $this->getUserAgent();

        foreach ($rules as $key => $regex) {
            if (empty($regex)) {
                continue;
            }

            // regex is an array of "strings"
            if (is_array($regex)) {
                foreach ($regex as $regexString) {
                    if ($this->match($regexString, $userAgent)) {
                        return $key ?: (string) reset($this->matchesArray);
                    }
                }

                continue;
            }

            if ($this->match($regex, $userAgent)) {
                return $key ?: (string) reset($this->matchesArray);
            }
        }

        return null;
    }

    /**
     * Merge multiple rules into one array.
     *
     * @param array<string, array<int, string>|string> ...$all
     * @return array<string, array<int, string>|string>
     */
    protected function mergeRules(array ...$all): array
    {
        $merged = [];

        foreach ($all as $rules) {
            foreach ($rules as $key => $value) {
                if (empty($merged[$key])) {
                    $merged[$key] = $value;

                    continue;
                }

                $merged[$key] = is_array($merged[$key]) || is_array($value)
                    ? [...(array) $merged[$key], ...(array) $value]
                    : $merged[$key].'|'.$value;
            }
        }

        return $merged;
    }
}
