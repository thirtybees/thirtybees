<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

use Thirtybees\Core\Smarty\Cache\CacheResourceMysql;
use Thirtybees\Core\Smarty\Cache\CacheResourceServerSideCache;

/**
 * Class SmartyCustomCore
 */
class SmartyCustomCore extends Smarty
{
    const CACHING_TYPE_FILESYSTEM = 'filesystem';

    const CACHING_TYPE_MYSQL = 'mysql';

    const CACHING_TYPE_SSC = 'ssc';

    /**
     * @var array stack trace for currently rendering templates
     */
    public static $trace = [];

    /**
     * SmartyCustomCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();
        $this->template_class = 'Smarty_Custom_Template';
        $this->resolveCachingType();
    }

    /**
     * @throws PrestaShopException
     */
    protected function resolveCachingType()
    {
        $cachingType = Configuration::get(Configuration::SMARTY_CACHING_TYPE);
        if ($cachingType === static::CACHING_TYPE_MYSQL) {
            $this->registerCacheResource('mysql', new CacheResourceMysql(Encryptor::getInstance()));
            $this->caching_type = 'mysql';
        }  elseif ($cachingType === static::CACHING_TYPE_SSC && Cache::isEnabled()) {
            $cache = Cache::getInstance();
            if ($cache->isAvailable()) {
                $this->registerCacheResource('ssc', new CacheResourceServerSideCache(Cache::getInstance()));
                $this->caching_type = 'ssc';
            } else {
                $this->caching_type = 'file';
            }
        } else {
            // fallback to built-in cache resource
            $this->caching_type = 'file';
        }
    }

    /**
     * Delete compiled template file (lazy delete if resource_name is not specified)
     *
     * @param string $resourceName template name
     * @param string $compileId compile id
     * @param int $expTime expiration time
     *
     * @return int number of template files deleted
     *
     * @throws PrestaShopException
     */
    public function clearCompiledTemplate($resourceName = null, $compileId = null, $expTime = null)
    {
        if ($resourceName == null) {
            Db::getInstance()->execute('REPLACE INTO `'._DB_PREFIX_.'smarty_last_flush` (`type`, `last_flush`) VALUES (\'compile\', FROM_UNIXTIME('.time().'))');

            return 0;
        } else {
            return parent::clearCompiledTemplate($resourceName, $compileId, $expTime);
        }
    }

    /**
     * Mark all template files to be regenerated
     *
     * @param int $expTime expiration time
     * @param string $type resource type
     *
     * @return bool number of cache files which needs to be updated
     *
     * @throws PrestaShopException
     */
    public function clearAllCache($expTime = null, $type = null)
    {
        Db::getInstance()->execute('REPLACE INTO `'._DB_PREFIX_.'smarty_last_flush` (`type`, `last_flush`) VALUES (\'template\', FROM_UNIXTIME('.time().'))');

        return $this->delete_from_lazy_cache(null, null, null);
    }

    /**
     * Delete the current template from the lazy cache or the whole cache if no template name is given
     *
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return bool|int
     *
     * @throws PrestaShopException
     */
    public function delete_from_lazy_cache($template, $cacheId, $compileId)
    {
        if (!$template) {
            return Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.'smarty_lazy_cache`', false);
        }

        $templateMd5 = md5($template);
        $sql = 'DELETE FROM `'._DB_PREFIX_.'smarty_lazy_cache`
							WHERE template_hash=\''.pSQL($templateMd5).'\'';

        if ($cacheId != null) {
            $sql .= ' AND cache_id LIKE "'.pSQL((string) $cacheId).'%"';
        }

        if ($compileId != null) {
            if (strlen($compileId) > 32) {
                $compileId = md5($compileId);
            }
            $sql .= ' AND compile_id="'.pSQL((string) $compileId).'"';
        }
        Db::getInstance()->execute($sql, false);

        return Db::getInstance()->Affected_Rows();
    }

    /**
     * Mark file to be regenerated for a specific template
     *
     * @param string $templateName template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param int $expTime expiration time
     * @param string $type resource type
     *
     * @return bool|int number of cache files which needs to be updated
     *
     * @throws PrestaShopException
     */
    public function clearCache($templateName, $cacheId = null, $compileId = null, $expTime = null, $type = null)
    {
        return $this->delete_from_lazy_cache($templateName, $cacheId, $compileId);
    }

    /**
     * @param string|null $template
     * @param string|null $cacheId
     * @param string|null $compileId
     * @param object|null $parent
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function fetch($template = null, $cacheId = null, $compileId = null, $parent = null)
    {
        $this->check_compile_cache_invalidation();

        return parent::fetch($template, $cacheId, $compileId, $parent);
    }

    /**
     * Check the compile cache needs to be invalidated (multi front + local cache compatible)
     *
     * @throws PrestaShopException
     */
    public function check_compile_cache_invalidation()
    {
        static $checked = false;
        if (!$checked) {
            $filename = $this->getCompileDir() . 'last_flush';
            if (! @file_exists($filename)) {
                Tools::changeFileMTime($filename);
                parent::clearCompiledTemplate();
            } else {
                $sql = 'SELECT UNIX_TIMESTAMP(last_flush) AS last_flush FROM `' . _DB_PREFIX_ . 'smarty_last_flush` WHERE type=\'compile\'';
                $lastFlush = (int) Db::getInstance()->getValue($sql, false);
                if ($lastFlush && @filemtime($filename) < $lastFlush) {
                    Tools::changeFileMTime($filename);
                    parent::clearCompiledTemplate();
                }
            }
            $checked = true;
        }
    }

    /**
     * @param string $template
     * @param string $cacheId
     * @param string $compileId
     * @param object $parent
     * @param bool $doClone
     *
     * @return object
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function createTemplate($template, $cacheId = null, $compileId = null, $parent = null, $doClone = true)
    {
        $this->check_compile_cache_invalidation();
        if ($this->caching) {
            $this->check_template_invalidation($template, $cacheId, $compileId);

            $tpl = parent::createTemplate($template, $cacheId, $compileId, $parent, $doClone);
        } else {
            $tpl = parent::createTemplate($template, $cacheId, $compileId, $parent, $doClone);
        }
        $tpl->startRenderCallbacks[] = ['SmartyCustom', 'beforeFetch'];
        $tpl->endRenderCallbacks[] = ['SmartyCustom', 'afterFetch'];
        return $tpl;
    }

    /**
     * Handle the lazy template cache invalidation
     *
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function check_template_invalidation($template, $cacheId, $compileId)
    {
        static $lastFlush = null;
        $filename = $this->getCacheDir() . 'last_template_flush';
        if (! @file_exists($filename)) {
            Tools::changeFileMTime($filename);
            parent::clearAllCache();
        } else {
            if ($lastFlush === null) {
                $sql = 'SELECT UNIX_TIMESTAMP(last_flush) AS last_flush FROM `'._DB_PREFIX_.'smarty_last_flush` WHERE type=\'template\'';
                $lastFlush = Db::getInstance()->getValue($sql, false);
            }

            if ((int) $lastFlush && @filemtime($filename) < $lastFlush) {
                Tools::changeFileMTime($filename);
                parent::clearAllCache();
            } else {
                if (is_object($cacheId) || is_array($cacheId)) {
                    $cacheId = null;
                }

                if ($this->is_in_lazy_cache($template, $cacheId, $compileId) === false) {
                    // insert in cache before the effective cache creation to avoid nasty race condition
                    $this->insert_in_lazy_cache($template, $cacheId, $compileId);
                    parent::clearCache($template, $cacheId, $compileId);
                }
            }
        }
    }

    /**
     * Check if the current template is stored in the lazy cache
     * Entry in the lazy cache = no need to regenerate the template
     *
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_in_lazy_cache($template, $cacheId, $compileId)
    {
        $templateMd5 = md5($template);
        if (!is_null($compileId) && strlen($compileId) > 32) {
            $compileId = md5($compileId);
        }
        $key = 'SmartyCustom::lazy_cache_' . md5($templateMd5.$cacheId.$compileId);

        if (! Cache::isStored($key)) {
            Cache::store($key, $this->fetchIsInLazyCache($templateMd5, $cacheId, $compileId, $template));
        }
        return Cache::retrieve($key);
    }

    /**
     * Insert the current template in the lazy cache
     *
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function insert_in_lazy_cache($template, $cacheId, $compileId)
    {
        $template_md5 = md5($template);
        $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.'smarty_lazy_cache`
							(`template_hash`, `cache_id`, `compile_id`, `last_update`)
							VALUES (\''.pSQL($template_md5).'\'';

        $sql .= ',"'.pSQL((string) $cacheId).'"';

        if (!is_null($compileId) && strlen($compileId) > 32) {
            $compileId = md5($compileId);
        }
        $sql .= ',"'.pSQL((string) $compileId).'"';
        $sql .= ', FROM_UNIXTIME('.time().'))';

        return Db::getInstance()->execute($sql, false);
    }

    /**
     * Store the cache file path
     *
     * @param string $filepath cache file path
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @throws PrestaShopException
     */
    public function update_filepath($filepath, $template, $cacheId, $compileId)
    {
        $templateMd5 = md5($template);
        $sql = 'UPDATE `'._DB_PREFIX_.'smarty_lazy_cache`
							SET filepath=\''.pSQL($filepath).'\'
							WHERE `template_hash`=\''.pSQL($templateMd5).'\'';

        $sql .= ' AND cache_id="'.pSQL((string) $cacheId).'"';

        if (!is_null($compileId) && strlen($compileId) > 32) {
            $compileId = md5($compileId);
        }
        $sql .= ' AND compile_id="'.pSQL((string) $compileId).'"';
        Db::getInstance()->execute($sql, false);
    }

    /**
     * Callback called before template rendering. It is used to track
     * current template stack
     *
     * @param Smarty_Internal_Template $template
     * @throws SmartyException
     */
    public static function beforeFetch($template)
    {
        static::$trace[] = static::getTemplateSource($template);
    }

    /**
     * Callback called after template rendering
     *
     * @return void
     */
    public static function afterFetch()
    {
        array_pop(static::$trace);
    }

    /**
     * Helper method to returns file path to current template
     *
     * @param Smarty_Internal_Template $template
     * @return string
     * @throws SmartyException
     */
    private static function getTemplateSource($template)
    {
        // first check whether resource descriptor points directly to template file
        if (@file_exists($template->template_resource)) {
            return $template->template_resource;
        }

        // we need to parse resource
        $filePath = Smarty_Resource::source($template)->filepath;
        if ($filePath) {
            return $filePath;
        }

        // return resource descriptor if it does not refers to physical file
        return $template->template_resource;
    }

    /**
     * Method returns true, if $file is compiled template
     *
     * @param string $file filepath
     * @return bool
     */
    public static function isCompiledTemplate($file)
    {
        // dynamically evaluated templates -- path from stack contains eval()'d
        if (strpos($file, "eval()") > -1 && strpos($file, 'smarty_internal_templatebase.php') > -1) {
            return true;
        }

        // compiled templates are found in compile directory
        if (strpos($file, 'cache/smarty/compile/') > -1) {
            return true;
        }
        return false;
    }

    /**
     * Returns currently rendering template, if any
     *
     * @return string | null
     */
    public static function getCurrentTemplate()
    {
        if (static::$trace) {
            $length = count(static::$trace);
            return static::$trace[$length - 1];
        }

        return null;
    }

    /**
     * @param string $templateMd5
     * @param string $cacheId
     * @param string $compileId
     * @param string $template
     * @return bool | string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function fetchIsInLazyCache($templateMd5, $cacheId, $compileId, $template)
    {
        $sql = (new DbQuery())
            ->select('UNIX_TIMESTAMP(last_update) AS last_update')
            ->select('filepath')
            ->from('smarty_lazy_cache')
            ->where('template_hash="' . pSQL((string)$templateMd5) . '"')
            ->where('cache_id="' . pSQL((string)$cacheId) . '"')
            ->where('compile_id="' . pSQL((string)$compileId) . '"');

        $result = Db::getInstance()->getRow($sql);

        if ($result === false) {
            return false;
        }

        $filepath = trim((string)$result['filepath']);
        $lastUpdate = (int)$result['last_update'];
        if ($filepath === '') {
            // If the cache update is stalled for more than 1min, something should be wrong,
            // remove the entry from the lazy cache
            if ($lastUpdate < time() - 60) {
                $this->delete_from_lazy_cache($template, $cacheId, $compileId);
            }
            return true;
        } else {
            $fullpath = $this->getCacheDir() . $filepath;
            if (! file_exists($fullpath)) {
                return false;
            }
            if (filemtime($fullpath) < $lastUpdate) {
                return false;
            }
            return $filepath;
        }
    }
}

/**
 * Class Smarty_Custom_Template
 */
class Smarty_Custom_Template extends Smarty_Internal_Template
{
    /** @var SmartyCustom|null */
    public $smarty = null;

    /**
     * @param string|null $template
     * @param string|null $cacheId
     * @param string|null $compileId
     * @param object|null $parent
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function fetch($template = null, $cacheId = null, $compileId = null, $parent = null)
    {
        if ($this->smarty->caching) {
            $tpl = $this->fetchWithRetries($template, $cacheId, $compileId, $parent);
            if (property_exists($this, 'cached')) {
                $filepath = str_replace($this->smarty->getCacheDir(), '', $this->cached->filepath);
                if ($this->smarty->is_in_lazy_cache($this->template_resource, $this->cache_id, $this->compile_id) != $filepath) {
                    $this->smarty->update_filepath($filepath, $this->template_resource, $this->cache_id, $this->compile_id);
                }
            }
            return $tpl;
        } else {
            return $this->fetchWithRetries($template, $cacheId, $compileId, $parent);
        }

    }

    /**
     * Helper method to render template
     *
     * @param string $template
     * @param string|null $cacheId
     * @param string|null $compileId
     * @param object|null $parent
     * @return string
     * @throws SmartyException
     * @throws Exception
     */
    public function fetchWithRetries($template, $cacheId, $compileId, $parent)
    {
        $count = 0;
        $maxTries = 3;
        while (true) {
            try {
                $tpl = parent::fetch($template, $cacheId, $compileId, $parent);
                return isset($tpl) ? $tpl : '';
            } catch (SmartyException $e) {
                // handle exception
                if (++$count === $maxTries) {
                    throw $e;
                }
                usleep(1);
            }
        }
    }
}
