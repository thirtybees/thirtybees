<?php
/**
 * 2017 Thirty Bees
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * This class is a shim between class ObjectModel and a class using ObjectModel.
 * It's purpose is to not access database storage, but a regular file instead.
 * Accordingly, all methods accessing the DB are overridden; everything else
 * is left for the parent.
 *
 * Storing data in a file is useful for e.g. configuration data (read often,
 * changed rarely). It also allows to change data by a shell script, which is
 * crucial for shop synchronisation.
 */

// This file might not exist. For example, at install time it doesn't.
// Accordingly, we also have to check for the existence of $shopUrlConfig
// on every read access.
@include_once(_PS_ROOT_DIR_.'/config/shop.inc.php');

/**
 * Class ObjectFileModelCore
 *
 * This class is a shim between ObjectModel and inherited classes like
 * ShopUrlCore, which redirects storage to a file instead of the database.
 *
 * @since 1.1.0
 */
abstract class ObjectFileModelCore extends ObjectModel
{
    /**
     * Builds the object
     *
     * @param int|null $id     If specified, loads and existing object from DB (optional).
     * @param int|null $idLang Required if object is multilingual (optional).
     * @param int|null $idShop ID shop for objects with multishop tables.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since   1.1.0
     * @version 1.1.0 Initial version
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        $storageName = static::$definition['storage'];
        global ${$storageName};

        $className = get_class($this);
        if (!isset(ObjectFileModel::$loaded_classes[$className])) {
            $this->def = ObjectFileModel::getDefinition($className);
            ObjectFileModel::$loaded_classes[$className] = get_object_vars($this);
        } else {
            foreach (ObjectFileModel::$loaded_classes[$className] as $key => $value) {
                $this->{$key} = $value;
            }
        }

        $storagePath = _PS_ROOT_DIR_.static::$definition['path'];
        if (!is_writable($storagePath) && !is_writable(dirname($storagePath))) {
            throw new PrestaShopException('Storage file '.$storagePath.' for class '.get_class($this).' not writable.');
        }

        if ($idLang !== null) {
            $this->id_lang = (Language::getLanguage($idLang) !== false) ? $idLang : Configuration::get('PS_LANG_DEFAULT');
        }

        if ($idShop && $this->isMultishop()) {
            $this->id_shop = (int) $idShop;
            $this->get_shop_from_context = false;
        }

        if ($this->isMultishop() && !$this->id_shop) {
            $this->id_shop = Context::getContext()->shop->id;
        }

        if ($id) {
            if (is_array(${$storageName})) {
                // This is what Adapter_EntityMapper does after database access.
                foreach (${$storageName}[$id] as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->{$key} = $value;
                    } else {
                        unset(${$storageName}[$id][$key]);
                    }
                }
            }
            $this->id = $id;
        }
    }

    /**
     * Writes the whole objects array as-is to it's file. Should be executed
     * after any permanent change to that array.
     *
     * @return bool Number of bytes written or false equivalent on failure.
     *
     * @since   1.1.0
     * @version 1.1.0 Initial version
     */
    public static function writeStorage()
    {
        $storageName = static::$definition['storage'];
        global ${$storageName};

        $result = file_put_contents(_PS_ROOT_DIR_.static::$definition['path'],
            "<?php\n\n".
            'global $'.$storageName.';'."\n\n".
            '$'.$storageName.' = '.
              var_export(${$storageName}, true).
            ';'."\n");

        // Clear most citizens in cache-mess-city. Else the include_once()
        // above may well read an old version on the next page load.
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Cache::getInstance()->flush();
        PageCache::flush();
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        return $result;
    }
}
