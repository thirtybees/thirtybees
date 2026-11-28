<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 */

/**
 * Provides timezone information from PHP's timezone database.
 *
 * The Timezone class is retained as a compatibility API for modules that
 * previously used the timezone database table.
 */
class TimezoneCore
{
    /**
     * Returns the configured shop timezone, falling back to PHP's default.
     *
     * @return string
     */
    public static function getTimeZone()
    {
        $timezone = (string) Configuration::get(Configuration::TIMEZONE);
        if (self::isValid($timezone)) {
            return $timezone;
        }

        $timezone = date_default_timezone_get();

        return self::isValid($timezone) ? $timezone : 'UTC';
    }

    /**
     * Returns all timezone identifiers known by PHP.
     *
     * @return string[]
     */
    public static function getIdentifiers()
    {
        return DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC);
    }

    /**
     * Returns timezone names in the legacy database-result format.
     *
     * @return array[]
     */
    public static function getList()
    {
        return array_map(function ($name) {
            return ['name' => $name];
        }, self::getIdentifiers());
    }

    /**
     * Legacy alias for getIdentifiers().
     *
     * @return string[]
     */
    public static function getTimezones()
    {
        return self::getIdentifiers();
    }

    /**
     * Checks whether a timezone identifier is supported by PHP.
     *
     * @param string $timezone
     *
     * @return bool
     */
    public static function isValid($timezone)
    {
        if (!is_string($timezone) || $timezone === '') {
            return false;
        }

        try {
            new DateTimeZone($timezone);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the current offset for a timezone in MySQL's offset format.
     *
     * @param string $timezone
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function getOffset($timezone)
    {
        try {
            $now = new DateTimeImmutable('now', new DateTimeZone($timezone));
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid timezone: '.$timezone, 0, $e);
        }

        $minutes = (int) ($now->getOffset() / 60);
        $sign = $minutes < 0 ? -1 : 1;
        $minutes = abs($minutes);
        $hours = floor($minutes / 60);
        $minutes -= $hours * 60;

        return sprintf('%+d:%02d', $hours * $sign, $minutes);
    }
}
