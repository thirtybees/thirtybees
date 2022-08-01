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
    /**
     * File where classes index is stored
     */
    const INDEX_FILE = 'cache/class_index.php';

    /**
     * Namespace delimiter
     */
    const NAMESPACE_DELIMITER = "\\";

    /**
     * @var PrestaShopAutoload singleton instance
     */
    protected static $instance;

    /**
     * @var array Mapping for legacy purposes
     */
    protected static $class_aliases = [
        'collection' => 'PrestaShopCollection',
        'autoload'   => 'PrestaShopAutoload',
        'backup'     => 'PrestaShopBackup',
        'logger'     => 'PrestaShopLogger',
    ];

    /**
     * @var array Map from class name to class information
     */
    public $index = [];

    /**
     * @var bool indicates, if override files should be included in the index as well
     */
    public $_include_override_path = true;

    /**
     * @var string Root directory
     */
    protected $root_dir;

    /**
     * PrestaShopAutoload constructor.
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function __construct()
    {
        $this->root_dir = rtrim(_PS_ROOT_DIR_, '/\\').DIRECTORY_SEPARATOR;
        $file = $this->root_dir . PrestaShopAutoload::INDEX_FILE;
        if (@filemtime($file) && is_readable($file)) {
            $this->index = include($file);
        } else {
            $this->generateIndex();
        }
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
                $this->getClassesFromDir('override/classes/'),
                $this->getClassesFromDir('override/controllers/')
            );
        }

        ksort($classes);
        $content = '<?php return '.var_export($classes, true).'; ?>';

        // Write classes index on disc to cache it
        $filename = $this->root_dir . PrestaShopAutoload::INDEX_FILE;
        $dirname = dirname($filename);
        $filenameTmp = tempnam($dirname, basename($filename.'.'));
        if ($filenameTmp !== false && file_put_contents($filenameTmp, $content) !== false) {
            if (!@rename($filenameTmp, $filename)) {
                unlink($filenameTmp);
                error_log('Cannot rename temp autoload file');
            } else {
                @chmod($filename, 0666);
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($filenameTmp);
                }
            }
        } else {
            // $filename_tmp couldn't be written. $filename should be there anyway (even if outdated), no need to die.
            error_log('Cannot create temporary autoload file in directory '.$dirname);
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
    protected function getClassesFromDir($path)
    {
        $classes = [];
        $rootDir = $this->root_dir;

        foreach (scandir($rootDir.$path) as $file) {
            if ($file[0] != '.') {
                if (is_dir($rootDir.$path.$file)) {
                    $classes = array_merge($classes, $this->getClassesFromDir($path.$file.'/'));
                } elseif (substr($file, -4) == '.php') {
                    $content = file_get_contents($rootDir.$path.$file);

                    $fileNamespace = $this->resolveNamespace($content);

                    $namespacePattern = '[\\a-z0-9_]*[\\]';
                    $pattern = '#\W((abstract\s+)?class|interface)\s+(?P<classname>'.basename($file, '.php').'(?:Core)?)'
                        .'(?:\s+extends\s+'.$namespacePattern.'[a-z][a-z0-9_]*)?(?:\s+implements\s+'.$namespacePattern.'[a-z][\\a-z0-9_]*(?:\s*,\s*'.$namespacePattern.'[a-z][\\a-z0-9_]*)*)?\s*\{#i';

                    if (preg_match($pattern, $content, $m)) {
                        $className = strtolower($fileNamespace . $m['classname']);
                        $classes[$className] = [
                            'name'  => $m['classname'],
                            'ns'    => rtrim($fileNamespace, static::NAMESPACE_DELIMITER),
                            'path'  => $path.$file,
                            'type'  => trim($m[1])
                        ];

                        if (substr($className, -4) == 'core') {
                            $overrideClass = substr($className, 0, -4);
                            $classes[$overrideClass] = [
                                'name' => substr($m['classname'], 0, -4),
                                'ns'   => rtrim($fileNamespace, static::NAMESPACE_DELIMITER),
                                'path' => '',
                                'type' => $classes[$className]['type']
                            ];
                        }
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Extracts namespace from the php file, if exists
     *
     * @param string $content php file content
     * @return string namespace or empty string
     */
    protected function resolveNamespace($content)
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (preg_match('#^\s*namespace\s+([^\s;]+)\s*;\s*$#', $line, $matches)) {
                $fileNamespace = trim($matches[1], static::NAMESPACE_DELIMITER) . static::NAMESPACE_DELIMITER;
                return $fileNamespace;
            }
        }
        return "";
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
        $className = strtolower($className);

        // Retrocompatibility
        if (isset(PrestaShopAutoload::$class_aliases[$className]) && !interface_exists($className, false) && !class_exists($className, false)) {
            return eval('class '.$className.' extends '.PrestaShopAutoload::$class_aliases[$className].' {}');
        }

        // regenerate the class index if the requested file doesn't exists
        if ((isset($this->index[$className]) && $this->index[$className]['path'] && !is_file($this->root_dir.$this->index[$className]['path']))
            || (isset($this->index[$className.'core']) && $this->index[$className.'core']['path'] && !is_file($this->root_dir.$this->index[$className.'core']['path']))
        ) {
            $this->generateIndex();
        }

        // If $classname has not core suffix (E.g. Shop, Product)
        if (substr($className, -4) != 'core') {
            // If requested class does not exist, load associated core class
            if (isset($this->index[$className]) && !$this->index[$className]['path']) {
                require_once($this->root_dir.$this->index[$className.'core']['path']);

                if ($this->index[$className.'core']['type'] != 'interface') {
                    $coreDefinition = $this->index[$className.'core'];
                    $overrideDefinition = $this->index[$className];
                    if (isset($coreDefinition['ns']) && $coreDefinition['ns']) {
                        $dynamicOverride = (
                            'namespace '.$overrideDefinition['ns'] . ";\n" .
                            $coreDefinition['type'].' '.$overrideDefinition['name'].' extends '.$coreDefinition['name']. ' {}'
                        );
                    } else {
                        $dynamicOverride = $coreDefinition['type'].' '.$overrideDefinition['name'].' extends '.$coreDefinition['name'] . ' {}';
                    }
                    eval($dynamicOverride);
                }
            } else {
                // request a non Core Class load the associated Core class if exists
                if (isset($this->index[$className.'core'])) {
                    require_once($this->root_dir.$this->index[$className.'core']['path']);
                }

                if (isset($this->index[$className])) {
                    require_once($this->root_dir.$this->index[$className]['path']);
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
     * @return string | null
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getClassPath($className)
    {
        $className = strtolower($className);
        return (isset($this->index[$className]) && isset($this->index[$className]['path']))
            ? $this->index[$className]['path']
            : null;
    }
}
