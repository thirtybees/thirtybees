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
 * Class PrestaShopAutoload
 *
 * @since 1.0.0
 */
class PrestaShopAutoload
{
    // @codingStandardsIgnoreStart
    /**
     * File where classes index is stored
     */
    const INDEX_FILE = 'cache/class_index.php';

    /**
     * @var PrestaShopAutoload
     */
    protected static $instance;
    protected static $class_aliases = [
        'Collection' => 'PrestaShopCollection',
        'Autoload'   => 'PrestaShopAutoload',
        'Backup'     => 'PrestaShopBackup',
        'Logger'     => 'PrestaShopLogger',
    ];
    /**
     * @var array array('classname' => 'path/to/override', 'classnamecore' => 'path/to/class/core')
     */
    public $index = [];

    public $_include_override_path = true;
    /**
     * @var string Root directory
     */
    protected $root_dir;
    // @codingStandardsIgnoreEnd

    /**
     * PrestaShopAutoload constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function __construct()
    {
        $this->root_dir = _PS_CORE_DIR_.'/';
        $file = $this->normalizeDirectory(_PS_ROOT_DIR_).PrestaShopAutoload::INDEX_FILE;
        if (@filemtime($file) && is_readable($file)) {
            $this->index = include($file);
        } else {
            $this->generateIndex();
        }
    }

    /**
     * @param $directory
     *
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function normalizeDirectory($directory)
    {
        return rtrim($directory, '/\\').DIRECTORY_SEPARATOR;
    }

    /**
     * Generate classes index
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function generateIndex()
    {
        $classes = array_merge(
            $this->getClassesFromDir('classes/'),
            $this->getClassesFromDir('controllers/'),
            $this->getClassesFromDir('Adapter/'),
            $this->getClassesFromDir('Core/')
        );

        if ($this->_include_override_path) {
            $classes = array_merge(
                $classes,
                $this->getClassesFromDir('override/classes/', false),
                $this->getClassesFromDir('override/controllers/', false)
            );
        }

        ksort($classes);
        $content = '<?php return '.var_export($classes, true).'; ?>';

        // Write classes index on disc to cache it
        $filename = $this->normalizeDirectory(_PS_ROOT_DIR_).PrestaShopAutoload::INDEX_FILE;
        $filenameTmp = tempnam(dirname($filename), basename($filename.'.'));
        if ($filenameTmp !== false && file_put_contents($filenameTmp, $content) !== false) {
            if (!@rename($filenameTmp, $filename)) {
                unlink($filenameTmp);
            } else {
                @chmod($filename, 0666);
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($filenameTmp);
                }
            }
        } // $filename_tmp couldn't be written. $filename should be there anyway (even if outdated), no need to die.
        else {
            Tools::error_log('Cannot write temporary file '.$filenameTmp);
        }
        $this->index = $classes;
    }

    /**
     * Retrieve recursively all classes in a directory and its subdirectories
     *
     * @param string $path Relativ path from root to the directory
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getClassesFromDir($path, $hostMode = false)
    {
        $classes = [];
        $rootDir = $hostMode ? $this->normalizeDirectory(_PS_ROOT_DIR_) : $this->root_dir;

        foreach (scandir($rootDir.$path) as $file) {
            if ($file[0] != '.') {
                if (is_dir($rootDir.$path.$file)) {
                    $classes = array_merge($classes, $this->getClassesFromDir($path.$file.'/', $hostMode));
                } elseif (substr($file, -4) == '.php') {
                    $content = file_get_contents($rootDir.$path.$file);

                    $namespacePattern = '[\\a-z0-9_]*[\\]';
                    $pattern = '#\W((abstract\s+)?class|interface)\s+(?P<classname>'.basename($file, '.php').'(?:Core)?)'
                        .'(?:\s+extends\s+'.$namespacePattern.'[a-z][a-z0-9_]*)?(?:\s+implements\s+'.$namespacePattern.'[a-z][\\a-z0-9_]*(?:\s*,\s*'.$namespacePattern.'[a-z][\\a-z0-9_]*)*)?\s*\{#i';

                    if (preg_match($pattern, $content, $m)) {
                        $classes[$m['classname']] = [
                            'path'     => $path.$file,
                            'type'     => trim($m[1]),
                            'override' => $hostMode,
                        ];

                        if (substr($m['classname'], -4) == 'Core') {
                            $classes[substr($m['classname'], 0, -4)] = [
                                'path'     => '',
                                'type'     => $classes[$m['classname']]['type'],
                                'override' => $hostMode,
                            ];
                        }
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Get instance of autoload (singleton)
     *
     * @return PrestaShopAutoload
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getInstance()
    {
        if (!PrestaShopAutoload::$instance) {
            PrestaShopAutoload::$instance = new PrestaShopAutoload();
        }

        return PrestaShopAutoload::$instance;
    }

    /**
     * Retrieve informations about a class in classes index and load it
     *
     * @param string $className
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     */
    public function load($className)
    {
        // Retrocompatibility
        if (isset(PrestaShopAutoload::$class_aliases[$className]) && !interface_exists($className, false) && !class_exists($className, false)) {
            return eval('class '.$className.' extends '.PrestaShopAutoload::$class_aliases[$className].' {}');
        }

        // regenerate the class index if the requested file doesn't exists
        if ((isset($this->index[$className]) && $this->index[$className]['path'] && !is_file($this->root_dir.$this->index[$className]['path']))
            || (isset($this->index[$className.'Core']) && $this->index[$className.'Core']['path'] && !is_file($this->root_dir.$this->index[$className.'Core']['path']))
        ) {
            $this->generateIndex();
        }

        // If $classname has not core suffix (E.g. Shop, Product)
        if (substr($className, -4) != 'Core') {
            $classDir = (isset($this->index[$className]['override'])
                && $this->index[$className]['override'] === true) ? $this->normalizeDirectory(_PS_ROOT_DIR_) : $this->root_dir;

            // If requested class does not exist, load associated core class
            if (isset($this->index[$className]) && !$this->index[$className]['path']) {
                require_once($classDir.$this->index[$className.'Core']['path']);

                if ($this->index[$className.'Core']['type'] != 'interface') {
                    eval($this->index[$className.'Core']['type'].' '.$className.' extends '.$className.'Core {}');
                }
            } else {
                // request a non Core Class load the associated Core class if exists
                if (isset($this->index[$className.'Core'])) {
                    require_once($this->root_dir.$this->index[$className.'Core']['path']);
                }

                if (isset($this->index[$className])) {
                    require_once($classDir.$this->index[$className]['path']);
                }
            }
        } // Call directly ProductCore, ShopCore class
        elseif (isset($this->index[$className]['path']) && $this->index[$className]['path']) {
            require_once($this->root_dir.$this->index[$className]['path']);
        }
    }

    /**
     * @param string $className
     *
     * @return null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getClassPath($className)
    {
        return (isset($this->index[$className]) && isset($this->index[$className]['path'])) ? $this->index[$className]['path'] : null;
    }
}
