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

/**
 * Class SmartyCustomCore
 *
 * @since 1.0.0
 */
class SmartyCustomCore extends Smarty
{
    /**
     * @var array stack trace for currently rendering templates
     */
    public static $trace = [];

    /**
     * SmartyCustomCore constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct()
    {
        parent::__construct();
        $this->template_class = 'Smarty_Custom_Template';
    }

    /**
     * Delete compiled template file (lazy delete if resource_name is not specified)
     *
     * @param  string $resourceName template name
     * @param  string $compileId    compile id
     * @param  int    $expTime      expiration time
     *
     * @return int number of template files deleted
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param  int    $expTime expiration time
     * @param  string $type    resource type
     *
     * @return int number of cache files which needs to be updated
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
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
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
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
     * @param  string $templateName template name
     * @param  string $cacheId      cache id
     * @param  string $compileId    compile id
     * @param  int    $expTime      expiration time
     * @param  string $type         resource type
     *
     * @return int number of cache files which needs to be updated
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function clearCache($templateName, $cacheId = null, $compileId = null, $expTime = null, $type = null)
    {
        return $this->delete_from_lazy_cache($templateName, $cacheId, $compileId);
    }

    /**
     * @param null $template
     * @param null $cacheId
     * @param null $compileId
     * @param null $parent
     * @param bool $display
     * @param bool $mergeTplVars
     * @param bool $noOutputFilter
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function fetch($template = null, $cacheId = null, $compileId = null, $parent = null, $display = false, $mergeTplVars = true, $noOutputFilter = false)
    {
        $this->check_compile_cache_invalidation();

        static::beforeFetch($template);
        $ret = parent::fetch($template, $cacheId, $compileId, $parent, $display, $mergeTplVars, $noOutputFilter);
        static::afterFetch();
        return $ret;
    }

    /**
     * Check the compile cache needs to be invalidated (multi front + local cache compatible)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function check_compile_cache_invalidation()
    {
        static $lastFlush = null;
        if (!file_exists($this->getCompileDir().'last_flush')) {
            @touch($this->getCompileDir().'last_flush', time());
        } elseif (defined('_DB_PREFIX_')) {
            if ($lastFlush === null) {
                $sql = 'SELECT UNIX_TIMESTAMP(last_flush) AS last_flush FROM `'._DB_PREFIX_.'smarty_last_flush` WHERE type=\'compile\'';
                $lastFlush = Db::getInstance()->getValue($sql, false);
            }
            if ((int) $lastFlush && @filemtime($this->getCompileDir().'last_flush') < $lastFlush) {
                @touch($this->getCompileDir().'last_flush', time());
                parent::clearCompiledTemplate();
            }
        }
    }

    /**
     * @param string $template
     * @param null   $cacheId
     * @param null   $compileId
     * @param null   $parent
     * @param bool   $doClone
     *
     * @return object
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function createTemplate($template, $cacheId = null, $compileId = null, $parent = null, $doClone = true)
    {
        $this->check_compile_cache_invalidation();
        if ($this->caching) {
            $this->check_template_invalidation($template, $cacheId, $compileId);

            return parent::createTemplate($template, $cacheId, $compileId, $parent, $doClone);
        } else {
            return parent::createTemplate($template, $cacheId, $compileId, $parent, $doClone);
        }
    }

    /**
     * Handle the lazy template cache invalidation
     *
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function check_template_invalidation($template, $cacheId, $compileId)
    {
        static $lastFlush = null;
        if (!file_exists($this->getCacheDir().'last_template_flush')) {
            @touch($this->getCacheDir().'last_template_flush', time());
        } elseif (defined('_DB_PREFIX_')) {
            if ($lastFlush === null) {
                $sql = 'SELECT UNIX_TIMESTAMP(last_flush) AS last_flush FROM `'._DB_PREFIX_.'smarty_last_flush` WHERE type=\'template\'';
                $lastFlush = Db::getInstance()->getValue($sql, false);
            }

            if ((int) $lastFlush && @filemtime($this->getCacheDir().'last_template_flush') < $lastFlush) {
                @touch($this->getCacheDir().'last_template_flush', time());
                parent::clearAllCache();
            } else {
                if ($cacheId !== null && (is_object($cacheId) || is_array($cacheId))) {
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
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function is_in_lazy_cache($template, $cacheId, $compileId)
    {
        static $isInLazyCache = [];
        $templateMd5 = md5($template);

        if (strlen($compileId) > 32) {
            $compileId = md5($compileId);
        }

        $key = md5($templateMd5.'-'.$cacheId.'-'.$compileId);

        if (isset($isInLazyCache[$key])) {
            return $isInLazyCache[$key];
        } else {
            $sql = 'SELECT UNIX_TIMESTAMP(last_update) AS last_update, filepath FROM `'._DB_PREFIX_.'smarty_lazy_cache`
							WHERE `template_hash`=\''.pSQL($templateMd5).'\'';
            $sql .= ' AND cache_id="'.pSQL((string) $cacheId).'"';
            $sql .= ' AND compile_id="'.pSQL((string) $compileId).'"';

            $result = Db::getInstance()->getRow($sql, false);
            // If the filepath is not yet set, it means the cache update is in progress in another process.
            // In this case do not try to clear the cache again and tell to use the existing cache, if any
            if ($result !== false && $result['filepath'] == '') {
                // If the cache update is stalled for more than 1min, something should be wrong,
                // remove the entry from the lazy cache
                if ($result['last_update'] < time() - 60) {
                    $this->delete_from_lazy_cache($template, $cacheId, $compileId);
                }

                $return = true;
            } else {
                if ($result === false
                    || @filemtime($this->getCacheDir().$result['filepath']) < $result['last_update']
                ) {
                    $return = false;
                } else {
                    $return = $result['filepath'];
                }
            }
            $isInLazyCache[$key] = $return;
        }

        return $return;
    }

    /**
     * Insert the current template in the lazy cache
     *
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function insert_in_lazy_cache($template, $cacheId, $compileId)
    {
        $template_md5 = md5($template);
        $sql = 'INSERT IGNORE INTO `'._DB_PREFIX_.'smarty_lazy_cache`
							(`template_hash`, `cache_id`, `compile_id`, `last_update`)
							VALUES (\''.pSQL($template_md5).'\'';

        $sql .= ',"'.pSQL((string) $cacheId).'"';

        if (strlen($compileId) > 32) {
            $compileId = md5($compileId);
        }
        $sql .= ',"'.pSQL((string) $compileId).'"';
        $sql .= ', FROM_UNIXTIME('.time().'))';

        return Db::getInstance()->execute($sql, false);
    }

    /**
     * Store the cache file path
     *
     * @param  string $filepath  cache file path
     * @param  string $template  template name
     * @param  string $cacheId   cache id
     * @param  string $compileId compile id
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     * @throws PrestaShopException
     */
    public function update_filepath($filepath, $template, $cacheId, $compileId)
    {
        $templateMd5 = md5($template);
        $sql = 'UPDATE `'._DB_PREFIX_.'smarty_lazy_cache`
							SET filepath=\''.pSQL($filepath).'\'
							WHERE `template_hash`=\''.pSQL($templateMd5).'\'';

        $sql .= ' AND cache_id="'.pSQL((string) $cacheId).'"';

        if (strlen($compileId) > 32) {
            $compileId = md5($compileId);
        }
        $sql .= ' AND compile_id="'.pSQL((string) $compileId).'"';
        Db::getInstance()->execute($sql, false);
    }

    /**
     * Callback called before template rendering. It is used to track
     * current template stack
     *
     * @param $template
     */
    public static function beforeFetch($template)
    {
        static::$trace[] = $template;
    }

    /**
     * Callback called after template rendering
     */
    public static function afterFetch()
    {
        array_pop(static::$trace);
    }

    /**
     * Method returns true, if $file is compiled template
     *
     * @param $file string filepath
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
    }
}

/**
 * Class Smarty_Custom_Template
 *
 * @since 1.0.0
 */
class Smarty_Custom_Template extends Smarty_Internal_Template
{
    /** @var SmartyCustom|null */
    public $smarty = null;

    /**
     * @param null $template
     * @param null $cacheId
     * @param null $compileId
     * @param null $parent
     * @param bool $display
     * @param bool $mergeTplVars
     * @param bool $noOutputFilter
     *
     * @return string
     * @throws SmartyException
     *
     * @since 1.0.0
     * @throws Exception
     * @throws Exception
     */
    public function fetch($template = null, $cacheId = null, $compileId = null, $parent = null, $display = false, $mergeTplVars = true, $noOutputFilter = false)
    {
        if ($this->smarty->caching) {
            $tpl = $this->fetchWithRetries($template, $cacheId, $compileId, $parent, $display, $mergeTplVars, $noOutputFilter);
            if (property_exists($this, 'cached')) {
                $filepath = str_replace($this->smarty->getCacheDir(), '', $this->cached->filepath);
                if ($this->smarty->is_in_lazy_cache($this->template_resource, $this->cache_id, $this->compile_id) != $filepath) {
                    $this->smarty->update_filepath($filepath, $this->template_resource, $this->cache_id, $this->compile_id);
                }
            }
            return $tpl;
        } else {
            return $this->fetchWithRetries($template, $cacheId, $compileId, $parent, $display, $mergeTplVars, $noOutputFilter);
        }

    }

    /**
     * Helper method to render template
     *
     * @param $template
     * @param $cacheId
     * @param $compileId
     * @param $parent
     * @param $display
     * @param $mergeTplVars
     * @param $noOutputFilter
     * @return string
     * @throws SmartyException
     */
    public function fetchWithRetries($template, $cacheId, $compileId, $parent, $display, $mergeTplVars, $noOutputFilter)
    {
        $count = 0;
        $maxTries = 3;
        while (true) {
            try {
                SmartyCustom::beforeFetch($this->getTemplateSource());
                $tpl = parent::fetch($template, $cacheId, $compileId, $parent, $display, $mergeTplVars, $noOutputFilter);
                SmartyCustom::afterFetch();
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

    /**
     * Helper method to returns file path to current template
     *
     * @return string
     */
    private function getTemplateSource()
    {
        // first check whether resource descriptor points directly to template file
        if (@file_exists($this->template_resource)) {
            return $this->template_resource;
        }

        // we need to parse resource
        $filePath = Smarty_Resource::source($this)->filepath;
        if ($filePath) {
            return $filePath;
        }

        // return resource descriptor if it does not refers to physical file
        return $this->template_resource;
    }
}
