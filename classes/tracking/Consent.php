<?php
/**
 * Copyright (C) 2021-2021 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2021-2021 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Tracking;

use Adapter_Exception;
use Context;
use Db;
use DbQuery;
use ObjectModel;
use PrestaShopDatabaseException;
use PrestaShopException;
use Thirtybees\Core\InitializationCallback;

/**
 * Class ConsentCore
 *
 * @since 1.3.0
 */
class ConsentCore extends ObjectModel implements InitializationCallback
{
    const CONSENT_ALL = "all";
    const PREFIX_GROUP = "group_";
    const PREFIX_EXTRACTOR = "extractor_";

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'tracking_consent',
        'primary' => 'id_tracking_consent',
        'multishop' => false,
        'fields'  => [
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'identifier'  => ['type' => self::TYPE_STRING, 'size' => 80, 'required' => true],
            'consent'     => ['type' => self::TYPE_BOOL, 'required' => true],
            'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
            'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        ],
        'keys' => [
            'tracking_consent' => [
                'identifier' => ['type' => ObjectModel::UNIQUE_KEY, 'columns' => ['identifier']],
            ]
        ]
    ];

    /**
     * @var string Employee that decided
     */
    public $id_employee;

    /**
     * @var string Information identifier
     */
    public $identifier;

    /**
     * @var bool Flat indicating if information can be send or not
     */
    public $consent;

    /**
    /* @var string Object creation date
     */
    public $date_add;

    /**
    /* @var string Object update date
     */
    public $date_upd;


    /**
     * Returns list of allowed extractors
     *
     * @return string[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getAllowedExtractors()
    {
        $consents = static::getConsents(Db::getInstance(_PS_USE_SQL_SLAVE_));
        $groups = DataExtractor::getGroups();
        $allowed = [];
        foreach ($groups as $groupId => $group) {
            foreach ($group['extractors'] as $extractorId) {
                if (static::extractorAllowed($groupId, $extractorId, $consents)) {
                    $allowed[] = $extractorId;
                }
            }
        }
        return $allowed;
    }

    /**
     * Return all consents
     *
     * @param Db $conn
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getConsents(Db $conn)
    {
        $consents = [];
        $result = $conn->executeS((new DbQuery())
            ->select('identifier, consent')
            ->from(static::$definition['table'])
        );
        if (is_array($result)) {
            foreach ($result as $row) {
                $consents[$row['identifier']] = (bool)$row['consent'];
            }
        }
        return $consents;
    }

    /**
     * Returns true, if extractor $extractorId from group $groupId is allowed
     *
     * Extractor is allowed to run, if
     *  - all data are allowed to be sent (consents contains 'all' key)
     *  - entire group is allowed to be send (consents contains 'group_<name>' key
     *  - extractor is specifically allowed (consents contains 'extractor_<name>' key
     *
     * @param $groupId
     * @param $extractorId
     * @param $consents
     * @return bool
     */
    protected static function extractorAllowed($groupId, $extractorId, $consents)
    {
        if (static::hasConsent(static::CONSENT_ALL, $consents)) {
            return true;
        }
        if (static::hasConsent(static::PREFIX_GROUP . $groupId, $consents)) {
            return true;
        }
        return static::hasConsent(static::PREFIX_EXTRACTOR . $extractorId, $consents);
    }

    /**
     * Returns true, if $key exists in $consents and is set to true
     *
     * @param string $key
     * @param array $consents
     * @return bool
     */
    protected static function hasConsent($key, $consents)
    {
        return array_key_exists($key, $consents) && !!$consents[$key];
    }

    /**
     * Callback method to initialize class
     *
     * @param Db $conn
     * @return void
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public static function initializationCallback(Db $conn)
    {
        $consents = static::getConsents($conn);
        $groups = DataExtractor::getGroups();
        static::ensureConsentExists(static::CONSENT_ALL, $consents);
        foreach ($groups as $groupId => $group) {
            static::ensureConsentExists(static::PREFIX_GROUP . $groupId, $consents);
            foreach ($group['extractors'] as $extractorId) {
                static::ensureConsentExists(static::PREFIX_EXTRACTOR . $extractorId, $consents);
            }
        }
    }

    /**
     * Ensures that consent with identifier $key exists in database
     *
     * @param string $key
     * @param array $consents
     *
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    protected static function ensureConsentExists($key, $consents)
    {
        if (! array_key_exists($key, $consents)) {
            $consent = new static();
            $consent->id_employee = Context::getContext()->employee->id;
            $consent->identifier = $key;
            $consent->consent = true;
            $consent->add();
        }
    }
}
