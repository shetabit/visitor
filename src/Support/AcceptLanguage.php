<?php

namespace Shetabit\Visitor\Support;

/**
 * Reads the languages out of an `Accept-Language` header.
 */
final class AcceptLanguage
{
    /**
     * The languages of the given header value, the most preferred one first.
     *
     * @return array<int, string>
     */
    public static function parse(string|null $header): array
    {
        if ($header === null || trim($header) === '') {
            return [];
        }

        $languages = [];

        foreach (explode(',', $header) as $piece) {
            $parts = explode(';', $piece);
            $language = strtolower(trim($parts[0]));

            if ($language === '') {
                continue;
            }

            $languages[$language] = empty($parts[1]) ? 1. : (float) str_replace('q=', '', $parts[1]);
        }

        arsort($languages);

        return array_keys($languages);
    }
}
