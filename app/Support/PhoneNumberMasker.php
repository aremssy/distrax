<?php

namespace App\Support;

use libphonenumber\Leniency;
use libphonenumber\PhoneNumberUtil;

/**
 * Detects phone numbers typed into free-text chat messages and replaces them with a
 * placeholder, so the per-listing contact-reveal limit can't be bypassed by simply
 * typing a number into the chat body instead of using the reveal action.
 */
class PhoneNumberMasker
{
    private const PLACEHOLDER = '[phone number hidden]';

    public static function mask(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $util = PhoneNumberUtil::getInstance();
        $rawMatches = [];

        foreach ($util->findNumbers($text, 'US', Leniency::POSSIBLE()) as $match) {
            $rawMatches[] = $match->rawString();
        }

        foreach (array_unique($rawMatches) as $raw) {
            $text = str_replace($raw, self::PLACEHOLDER, $text);
        }

        return $text;
    }
}
