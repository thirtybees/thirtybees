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
 * Class InstallXmlLoader
 *
 * @since 1.0.0
 */
class InstallXmlLoader
{
    public $path_type;
    /**
     * @var InstallLanguages
     */
    protected $language;
    /**
     * @var array List of languages stored as array(id_lang => iso)
     */
    protected $languages = [];
    /**
     * @var array Store in cache all loaded XML files
     */
    protected $cache_xml_entity = [];
    /**
     * @var array List of errors
     */
    protected $errors = [];
    protected $data_path;
    protected $lang_path;
    protected $img_path;
    protected $ids = [];

    protected $primaries = [];

    protected $delayed_inserts = [];

    /**
     * InstallXmlLoader constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->language = InstallLanguages::getInstance();
        $this->setDefaultPath();
    }

    /**
     * @since 1.0.0
     */
    public function setDefaultPath()
    {
        $this->path_type = 'common';
        $this->data_path = _PS_INSTALL_DATA_PATH_.'xml/';
        $this->lang_path = _PS_INSTALL_LANGS_PATH_;
        $this->img_path = _PS_INSTALL_DATA_PATH_.'img/';
    }

    /**
     * Set list of installed languages
     *
     * @param array $languages array(id_lang => iso)
     *
     * @since 1.0.0
     */
    public function setLanguages(array $languages)
    {
        $this->languages = $languages;
    }

    /**
     * @param string|null $path
     *
     * @since 1.0.0
     */
    public function setFixturesPath($path = null)
    {
        if ($path === null) {
            $path = _PS_INSTALL_FIXTURES_PATH_.'thirtybees/';
        }

        $this->path_type = 'fixture';
        $this->data_path = $path.'data/';
        $this->lang_path = $path.'langs/';
        $this->img_path = $path.'img/';
    }

    /**
     * Get list of errors
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @param $ids
     *
     * @since 1.0.0
     */
    public function setIds($ids)
    {
        $this->ids = $ids;
    }

    /**
     * Read all XML files from data folder and populate tables
     *
     * @param bool  $populate If false, just collect entity identifiers.
     *
     * @version 1.0.0 Initial version.
     * @version 1.1.0 New parameter $populate.
     */
    public function populateFromXmlFiles($populate = true)
    {
        $entities = $this->getSortedEntities();

        // Populate entities
        foreach ($entities as $entity) {
            $this->populateEntity($entity, $populate);
        }
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getSortedEntities()
    {
        // Browse all XML files from data/xml directory
        $entities = [];
        $dependencies = [];
        $fd = opendir($this->data_path);
        while ($file = readdir($fd)) {
            if (preg_match('#^(.+)\.xml$#', $file, $m)) {
                $entity = $m[1];
                $xml = $this->loadEntity($entity);

                // Store entities dependencies (with field type="relation")
                if ($xml->fields) {
                    foreach ($xml->fields->field as $field) {
                        if ($field['relation'] && $field['relation'] != $entity) {
                            if (!isset($dependencies[(string) $field['relation']])) {
                                $dependencies[(string) $field['relation']] = [];
                            }
                            $dependencies[(string) $field['relation']][] = $entity;
                        }
                    }
                }
                $entities[] = $entity;
            }
        }
        closedir($fd);

        // Sort entities to populate database in good order (E.g. zones before countries)
        do {
            $current = (isset($sortEntities)) ? $sortEntities : [];
            $sortEntities = [];
            foreach ($entities as $key => $entity) {
                if (isset($dependencies[$entity])) {
                    $min = count($entities) - 1;
                    foreach ($dependencies[$entity] as $item) {
                        if (($key = array_search($item, $sortEntities)) !== false) {
                            $min = min($min, $key);
                        }
                    }
                    if ($min == 0) {
                        array_unshift($sortEntities, $entity);
                    } else {
                        array_splice($sortEntities, $min, 0, [$entity]);
                    }
                } else {
                    $sortEntities[] = $entity;
                }
            }
            $entities = $sortEntities;
        } while ($current != $sortEntities);

        return $sortEntities;
    }

    /**
     * Load an entity XML file
     *
     * @param string $entity
     *
     * @return SimpleXMLElement
     *
     * @since 1.0.0
     */
    protected function loadEntity($entity, $iso = null)
    {
        if (!isset($this->cache_xml_entity[$this->path_type][$entity][$iso])) {
            if (substr($entity, 0, 1) == '.' || substr($entity, 0, 1) == '_') {
                return;
            }

            $path = $this->data_path.$entity.'.xml';
            if ($iso) {
                $path = $this->lang_path.$this->getFallBackToDefaultLanguage($iso).'/data/'.$entity.'.xml';
            }

            if (!file_exists($path)) {
                throw new PrestashopInstallerException('XML data file '.$entity.'.xml not found');
            }

            $this->cache_xml_entity[$this->path_type][$entity][$iso] = @simplexml_load_file($path, 'InstallSimplexmlElement');
            if (!$this->cache_xml_entity[$this->path_type][$entity][$iso]) {
                throw new PrestashopInstallerException('XML data file '.$entity.'.xml invalid');
            }
        }

        return $this->cache_xml_entity[$this->path_type][$entity][$iso];
    }

    protected function getFallBackToDefaultLanguage($iso)
    {
        return file_exists($this->lang_path.$iso.'/data/') ? $iso : 'en';
    }

    /**
     * Populate an entity
     *
     * @param string  $entity
     * @param bool    $populate If false, just collect entity identifiers.
     *
     * @version 1.0.0 Initial version.
     * @version 1.1.0 New parameter $populate.
     */
    public function populateEntity($entity, $populate = true)
    {
        if (method_exists($this, 'populateEntity'.Tools::toCamelCase($entity))) {
            $this->{'populateEntity'.Tools::toCamelCase($entity)}();

            return;
        }

        if (substr($entity, 0, 1) == '.' || substr($entity, 0, 1) == '_') {
            return;
        }

        $xml = $this->loadEntity($entity);

        // Read list of fields
        if (!is_object($xml) || !$xml->fields) {
            throw new PrestashopInstallerException('List of fields not found for entity '.$entity);
        }

        if ($this->isMultilang($entity)) {
            $multilangColumns = $this->getColumns($entity, true);
            $xmlLangs = [];
            $defaultLang = null;
            foreach ($this->languages as $idLang => $iso) {
                if ($iso == $this->language->getLanguageIso()) {
                    $defaultLang = $idLang;
                }

                try {
                    $xmlLangs[$idLang] = $this->loadEntity($entity, $iso);
                } catch (PrestashopInstallerException $e) {
                    $xmlLangs[$idLang] = null;
                }
            }
        }

        // Load all row for current entity and prepare data to be populated
        foreach ($xml->entities->$entity as $node) {
            $data = [];
            $identifier = (string) $node['id'];

            // Read attributes
            foreach ($node->attributes() as $k => $v) {
                if ($k != 'id') {
                    $data[$k] = (string) $v;
                }
            }

            // Read cdatas
            foreach ($node->children() as $child) {
                $data[$child->getName()] = (string) $child;
            }

            // Load multilang data
            $dataLang = [];
            if ($this->isMultilang($entity)) {
                $xpathQuery = $entity.'[@id="'.$identifier.'"]';
                foreach ($xmlLangs as $idLang => $xmlLang) {
                    if (!$xmlLang) {
                        continue;
                    }

                    if (($nodeLang = $xmlLang->xpath($xpathQuery)) || ($nodeLang = $xmlLangs[$defaultLang]->xpath($xpathQuery))) {
                        $nodeLang = $nodeLang[0];
                        foreach ($multilangColumns as $column => $is_text) {
                            $value = '';
                            if ($nodeLang[$column]) {
                                $value = (string) $nodeLang[$column];
                            }

                            if ($nodeLang->$column) {
                                $value = (string) $nodeLang->$column;
                            }
                            $dataLang[$column][$idLang] = $value;
                        }
                    }
                }
            }

            $data = $this->rewriteRelationedData($entity, $data);
            if (method_exists($this, 'createEntity'.Tools::toCamelCase($entity))) {
                // Create entity with custom method in current class
                $method = 'createEntity'.Tools::toCamelCase($entity);
                $this->$method($identifier, $data, $dataLang);
            } else {
                $this->createEntity($entity, $identifier, (string) $xml->fields['class'], $data, $dataLang);
            }

            if ($populate && $xml->fields['image']) {
                if (method_exists($this, 'copyImages'.Tools::toCamelCase($entity))) {
                    $this->{'copyImages'.Tools::toCamelCase($entity)}($identifier, $data);
                } else {
                    $this->copyImages($entity, $identifier, (string) $xml->fields['image'], $data);
                }
            }
        }

        if ($populate) {
            $this->flushDelayedInserts();
        }
        unset($this->cache_xml_entity[$this->path_type][$entity]);
    }

    /**
     * @param $entity
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isMultilang($entity)
    {
        $tables = $this->getTables();

        return isset($tables[$entity]) && $tables[$entity];
    }

    /**
     * @return array|null
     *
     * @since 1.0.0
     */
    public function getTables()
    {
        static $tables = null;

        if (is_null($tables)) {
            $tables = [];
            foreach (Db::getInstance()->executeS('SHOW TABLES') as $row) {
                $table = current($row);
                if (preg_match('#^'._DB_PREFIX_.'(.+?)(_lang)?$#i', $table, $m)) {
                    $tables[$m[1]] = (isset($m[2]) && $m[2]) ? true : false;
                }
            }
        }

        return $tables;
    }

    /**
     * @param       $table
     * @param bool  $multilang
     * @param array $exclude
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getColumns($table, $multilang = false, array $exclude = [])
    {
        static $columns = [];

        if ($multilang) {
            return ($this->isMultilang($table)) ? $this->getColumns($table.'_lang', false, ['id_'.$table]) : [];
        }

        if (!isset($columns[$table])) {
            $columns[$table] = [];
            $sql = 'SHOW COLUMNS FROM `'._DB_PREFIX_.bqSQL($table).'`';
            foreach (Db::getInstance()->executeS($sql) as $row) {
                $columns[$table][$row['Field']] = $this->checkIfTypeIsText($row['Type']);
            }
        }

        $exclude = array_merge(['id_'.$table, 'date_add', 'date_upd', 'deleted', 'id_lang'], $exclude);

        $list = [];
        foreach ($columns[$table] as $k => $v) {
            if (!in_array($k, $exclude)) {
                $list[$k] = $v;
            }
        }

        return $list;
    }

    /**
     * @param $type
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function checkIfTypeIsText($type)
    {
        if (preg_match('#^(longtext|text|tinytext)#i', $type)) {
            return true;
        }

        if (preg_match('#^varchar\(([0-9]+)\)$#i', $type, $m)) {
            return intval($m[1]) >= 64 ? true : false;
        }

        return false;
    }

    /**
     * Check fields related to an other entity, and replace their values by the ID created by the other entity
     *
     * @param string $entity
     * @param array  $data
     *
     * @since 1.0.0
     */
    protected function rewriteRelationedData($entity, array $data)
    {
        $xml = $this->loadEntity($entity);
        foreach ($xml->fields->field as $field) {
            if ($field['relation']) {
                $id = $this->retrieveId((string) $field['relation'], $data[(string) $field['name']]);
                if (!$id && $data[(string) $field['name']] && is_numeric($data[(string) $field['name']])) {
                    $id = $data[(string) $field['name']];
                }
                $data[(string) $field['name']] = $id;
            }
        }

        return $data;
    }

    /**
     * Retrieve an ID related to an entity and its identifier
     *
     * @param string $entity
     * @param string $identifier
     *
     * @since 1.0.0
     */
    public function retrieveId($entity, $identifier)
    {
        return isset($this->ids[$entity.':'.$identifier]) ? $this->ids[$entity.':'.$identifier] : 0;
    }

    /**
     * Create a simple entity with all its data and lang data
     * If a methode createEntity$entity exists, use it. Else if $classname is given, use it. Else do a simple insert in database.
     *
     * @param string $entity
     * @param string $identifier
     * @param string $classname
     * @param array  $data
     * @param array  $dataLang
     */
    public function createEntity($entity, $identifier, $classname, array $data, array $dataLang = [])
    {
        $xml = $this->loadEntity($entity);
        if ($classname) {
            // Create entity with ObjectModel class
            $object = new $classname();
            $object->hydrate($data);
            if ($dataLang) {
                $object->hydrate($dataLang);
            }
            $object->add(true, (isset($xml->fields['null'])) ? true : false);
            $entityId = $object->id;
            unset($object);
        } else {
            // Generate primary key manually
            $primary = '';
            $entityId = 0;
            if (!$xml->fields['primary']) {
                $primary = 'id_'.$entity;
            } elseif (strpos((string) $xml->fields['primary'], ',') === false) {
                $primary = (string) $xml->fields['primary'];
            }
            unset($xml);

            if ($primary) {
                $entityId = $this->generatePrimary($entity, $primary);
                $data[$primary] = $entityId;
            }

            // Store INSERT queries in order to optimize install with grouped inserts
            $this->delayed_inserts[$entity][] = array_map('pSQL', $data);
            if ($dataLang) {
                $realDataLang = [];
                foreach ($dataLang as $field => $list) {
                    foreach ($list as $idLang => $value) {
                        $realDataLang[$idLang][$field] = $value;
                    }
                }

                foreach ($realDataLang as $idLang => $insertDataLang) {
                    $insertDataLang['id_'.$entity] = $entityId;
                    $insertDataLang['id_lang'] = $idLang;
                    $this->delayed_inserts[$entity.'_lang'][] = array_map('pSQL', $insertDataLang);
                }

                // Store INSERT queries for _shop associations
                $entityAsso = Shop::getAssoTable($entity);
                if ($entityAsso !== false && $entityAsso['type'] == 'shop') {
                    $this->delayed_inserts[$entity.'_shop'][] = [
                        'id_shop'     => 1,
                        'id_'.$entity => $entityId,
                    ];
                }
            }
        }

        $this->storeId($entity, $identifier, $entityId);
    }

    /**
     * @param $entity
     * @param $primary
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    public function generatePrimary($entity, $primary)
    {
        if (!isset($this->primaries[$entity])) {
            $this->primaries[$entity] = (int) Db::getInstance()->getValue('SELECT '.$primary.' FROM '._DB_PREFIX_.$entity.' ORDER BY '.$primary.' DESC');
        }

        return ++$this->primaries[$entity];
    }

    /**
     * Store an ID related to an entity and its identifier (E.g. we want to save that product with ID "ipod_nano" has the ID 1)
     *
     * @param string $entity
     * @param string $identifier
     * @param int    $id
     *
     * @since 1.0.0
     */
    public function storeId($entity, $identifier, $id)
    {
        $this->ids[$entity.':'.$identifier] = $id;
    }

    /**
     * @param        $entity
     * @param        $identifier
     * @param        $path
     * @param array  $data
     * @param string $extension
     *
     * @since 1.0.0
     */
    public function copyImages($entity, $identifier, $path, array $data, $extension = 'jpg')
    {
        // Get list of image types
        $reference = [
            'product'      => 'products',
            'category'     => 'categories',
            'manufacturer' => 'manufacturers',
            'supplier'     => 'suppliers',
            'scene'        => 'scenes',
            'store'        => 'stores',
        ];

        $types = [];
        if (isset($reference[$entity])) {
            $types = ImageType::getImagesTypes($reference[$entity]);
        }

        // For each path copy images
        $path = array_map('trim', explode(',', $path));
        foreach ($path as $p) {
            $fromPath = $this->img_path.$p.'/';
            $dstPath = _PS_IMG_DIR_.$p.'/';
            $entityId = $this->retrieveId($entity, $identifier);

            if (!@copy($fromPath.$identifier.'.'.$extension, $dstPath.$entityId.'.'.$extension)) {
                $this->setError($this->language->l('Cannot create image "%1$s" for entity "%2$s"', $identifier, $entity));

                return;
            }

            foreach ($types as $type) {
                $originFile = $fromPath.$identifier.'-'.$type['name'].'.'.$extension;
                $targetFile = $dstPath.$entityId.'-'.$type['name'].'.'.$extension;

                // Test if dest folder is writable
                if (!is_writable(dirname($targetFile))) {
                    $this->setError($this->language->l('Cannot create image "%1$s" (bad permissions on folder "%2$s")', $identifier.'-'.$type['name'], dirname($targetFile)));
                } // If a file named folder/entity-type.extension exists just copy it, this is an optimisation in order to prevent to much resize
                elseif (file_exists($originFile)) {
                    if (!@copy($originFile, $targetFile)) {
                        $this->setError($this->language->l('Cannot create image "%s"', $identifier.'-'.$type['name']));
                    }
                    @chmod($targetFile, 0644);
                } // Resize the image if no cache was prepared in fixtures
                elseif (!ImageManager::resize($fromPath.$identifier.'.'.$extension, $targetFile, $type['width'], $type['height'])) {
                    $this->setError($this->language->l('Cannot create image "%1$s" for entity "%2$s"', $identifier.'-'.$type['name'], $entity));
                }
            }
        }
        Image::moveToNewFileSystem();
    }

    /**
     * Add an error
     *
     * @param string $error
     *
     * @since 1.0.0
     */
    public function setError($error)
    {
        $this->errors[] = $error;
    }

    /**
     * @since 1.0.0
     */
    public function flushDelayedInserts()
    {
        foreach ($this->delayed_inserts as $entity => $queries) {
            $type = Db::INSERT_IGNORE;
            if ($entity == 'access') {
                $type = Db::REPLACE;
            }

            if (!Db::getInstance()->insert($entity, $queries, false, true, $type)) {
                $this->setError($this->language->l('An SQL error occurred for entity <i>%1$s</i>: <i>%2$s</i>', $entity, Db::getInstance()->getMsgError()));
            }
            unset($this->delayed_inserts[$entity]);
        }
    }

    /**
     * Special case for "tag" entity
     *
     * @since 1.0.0
     */
    public function populateEntityTag()
    {
        foreach ($this->languages as $idLang => $iso) {
            if (!file_exists($this->lang_path.$this->getFallBackToDefaultLanguage($iso).'/data/tag.xml')) {
                continue;
            }

            $xml = $this->loadEntity('tag', $this->getFallBackToDefaultLanguage($iso));
            $tags = [];
            foreach ($xml->tag as $tagNode) {
                $products = trim((string) $tagNode['products']);
                if (!$products) {
                    continue;
                }

                foreach (explode(',', $products) as $product) {
                    $product = trim($product);
                    $productId = $this->retrieveId('product', $product);
                    if (!isset($tags[$productId])) {
                        $tags[$productId] = [];
                    }
                    $tags[$productId][] = trim((string) $tagNode['name']);
                }
            }

            foreach ($tags as $idProduct => $tagList) {
                Tag::addTags($idLang, $idProduct, $tagList);
            }
        }
    }

    /**
     * @param       $identifier
     * @param array $data
     * @param array $dataLang
     *
     *
     * @since 1.0.0
     */
    public function createEntityConfiguration($identifier, array $data, array $dataLang)
    {
        if (Db::getInstance()->getValue('SELECT id_configuration FROM '._DB_PREFIX_.'configuration WHERE name = \''.pSQL($data['name']).'\'')) {
            return;
        }

        $entity = 'configuration';
        $entityId = $this->generatePrimary($entity, 'id_configuration');
        $data['id_configuration'] = $entityId;

        // Store INSERT queries in order to optimize install with grouped inserts
        $this->delayed_inserts[$entity][] = array_map('pSQL', $data);
        if ($dataLang) {
            $realDataLang = [];
            foreach ($dataLang as $field => $list) {
                foreach ($list as $idLang => $value) {
                    $realDataLang[$idLang][$field] = $value;
                }
            }

            foreach ($realDataLang as $idLang => $insert_data_lang) {
                $insert_data_lang['id_'.$entity] = $entityId;
                $insert_data_lang['id_lang'] = $idLang;
                $this->delayed_inserts[$entity.'_lang'][] = array_map('pSQL', $insert_data_lang);
            }
        }

        $this->storeId($entity, $identifier, $entityId);
    }

    /**
     * @param       $identifier
     * @param array $data
     * @param array $data_lang
     *
     * @since 1.0.0
     */
    public function createEntityStockAvailable($identifier, array $data, array $data_lang)
    {
        $stockAvailable = new StockAvailable();
        $stockAvailable->updateQuantity($data['id_product'], $data['id_product_attribute'], $data['quantity'], $data['id_shop']);
    }

    public function createEntityTab($identifier, array $data, array $dataLang)
    {
        static $position = [];

        $entity = 'tab';
        $xml = $this->loadEntity($entity);

        if (!isset($position[$data['id_parent']])) {
            $position[$data['id_parent']] = 0;
        }
        $data['position'] = $position[$data['id_parent']]++;

        // Generate primary key manually
        $primary = '';
        $entityId = 0;
        if (!$xml->fields['primary']) {
            $primary = 'id_'.$entity;
        } elseif (strpos((string) $xml->fields['primary'], ',') === false) {
            $primary = (string) $xml->fields['primary'];
        }

        if ($primary) {
            $entityId = $this->generatePrimary($entity, $primary);
            $data[$primary] = $entityId;
        }

        // Store INSERT queries in order to optimize install with grouped inserts
        $this->delayed_inserts[$entity][] = array_map('pSQL', $data);
        if ($dataLang) {
            $realDataLang = [];
            foreach ($dataLang as $field => $list) {
                foreach ($list as $idLang => $value) {
                    $realDataLang[$idLang][$field] = $value;
                }
            }

            foreach ($realDataLang as $idLang => $insertDataLang) {
                $insertDataLang['id_'.$entity] = $entityId;
                $insertDataLang['id_lang'] = $idLang;
                $this->delayed_inserts[$entity.'_lang'][] = array_map('pSQL', $insertDataLang);
            }
        }

        $this->storeId($entity, $identifier, $entityId);
    }

    /**
     * @param       $identifier
     * @param array $data
     *
     * @since 1.0.0
     */
    public function copyImagesScene($identifier, array $data)
    {
        $this->copyImages('scene', $identifier, 'scenes', $data);

        $fromPath = $this->img_path.'scenes/thumbs/';
        $dstPath = _PS_IMG_DIR_.'scenes/thumbs/';
        $entityId = $this->retrieveId('scene', $identifier);

        if (!@copy($fromPath.$identifier.'-m_scene_default.jpg', $dstPath.$entityId.'-m_scene_default.jpg')) {
            $this->setError($this->language->l('Cannot create image "%1$s" for entity "%2$s"', $identifier, 'scene'));

            return;
        }
    }

    /**
     * @param       $identifier
     * @param array $data
     *
     * @since 1.0.0
     */
    public function copyImagesOrderState($identifier, array $data)
    {
        $this->copyImages('order_state', $identifier, 'os', $data, 'gif');
    }

    /**
     * @param       $identifier
     * @param array $data
     *
     * @since 1.0.0
     */
    public function copyImagesTab($identifier, array $data)
    {
        $fromPath = $this->img_path.'t/';
        $dstPath = _PS_IMG_DIR_.'t/';
        if (file_exists($fromPath.$data['class_name'].'.gif') && !file_exists($dstPath.$data['class_name'].'.gif')) {
            //test if file exist in install dir and if do not exist in dest folder.
            if (!@copy($fromPath.$data['class_name'].'.gif', $dstPath.$data['class_name'].'.gif')) {
                $this->setError($this->language->l('Cannot create image "%1$s" for entity "%2$s"', $identifier, 'tab'));

                return;
            }
        }
    }

    /**
     * @param $identifier
     *
     * @since 1.0.0
     */
    public function copyImagesImage($identifier)
    {
        $path = $this->img_path.'p/';
        $image = new Image($this->retrieveId('image', $identifier));
        $dstPath = $image->getPathForCreation();
        if (!@copy($path.$identifier.'.jpg', $dstPath.'.'.$image->image_format)) {
            $this->setError($this->language->l('Cannot create image "%1$s" for entity "%2$s"', $identifier, 'product'));

            return;
        }
        @chmod($dstPath.'.'.$image->image_format, 0644);

        $types = ImageType::getImagesTypes('products');
        foreach ($types as $type) {
            $originFile = $path.$identifier.'-'.$type['name'].'.jpg';
            $targetFile = $dstPath.'-'.$type['name'].'.'.$image->image_format;

            // Test if dest folder is writable
            if (!is_writable(dirname($targetFile))) {
                $this->setError($this->language->l('Cannot create image "%1$s" (bad permissions on folder "%2$s")', $identifier.'-'.$type['name'], dirname($targetFile)));
            } // If a file named folder/entity-type.jpg exists just copy it, this is an optimisation in order to prevent to much resize
            elseif (file_exists($originFile)) {
                if (!@copy($originFile, $targetFile)) {
                    $this->setError($this->language->l('Cannot create image "%1$s" for entity "%2$s"', $identifier.'-'.$type['name'], 'product'));
                }
                @chmod($targetFile, 0644);
            } // Resize the image if no cache was prepared in fixtures
            elseif (!ImageManager::resize($path.$identifier.'.jpg', $targetFile, $type['width'], $type['height'])) {
                $this->setError($this->language->l('Cannot create image "%1$s" for entity "%2$s"', $identifier.'-'.$type['name'], 'product'));
            }
        }
    }

    /**
     * @param $table
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function hasElements($table)
    {
        return (bool) Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.$table);
    }

    /**
     * @param null $path
     *
     * @return array|null
     *
     * @since 1.0.0
     */
    public function getClasses($path = null)
    {
        static $cache = null;

        if (!is_null($cache)) {
            return $cache;
        }

        $dir = $path;
        if (is_null($dir)) {
            $dir = _PS_CLASS_DIR_;
        }

        $classes = [];
        foreach (scandir($dir) as $file) {
            if ($file[0] != '.' && $file != 'index.php') {
                if (is_dir($dir.$file)) {
                    $classes = array_merge($classes, $this->getClasses($dir.$file.'/'));
                } elseif (preg_match('#^(.+)\.php$#', $file, $m)) {
                    $classes[] = $m[1];
                }
            }
        }

        sort($classes);
        if (is_null($path)) {
            $cache = $classes;
        }

        return $classes;
    }

    /**
     * @param       $entity
     * @param array $fields
     * @param array $config
     *
     * @since 1.0.0
     */
    public function generateEntitySchema($entity, array $fields, array $config)
    {
        if ($this->entityExists($entity)) {
            $xml = $this->loadEntity($entity);
        } else {
            $xml = new InstallSimplexmlElement('<entity_'.$entity.' />');
        }
        unset($xml->fields);

        // Fill <fields> attributes (config)
        $xmlFields = $xml->addChild('fields');
        foreach ($config as $k => $v) {
            if ($v) {
                $xmlFields[$k] = $v;
            }
        }

        // Create list of fields
        foreach ($fields as $column => $info) {
            $field = $xmlFields->addChild('field');
            $field['name'] = $column;
            if (isset($info['relation'])) {
                $field['relation'] = $info['relation'];
            }
        }

        // Recreate entities nodes, in order to have the <entities> node after the <fields> node
        $storeEntities = clone $xml->entities;
        unset($xml->entities);
        $xml->addChild('entities', $storeEntities);

        $xml->asXML($this->data_path.$entity.'.xml');
    }

    /**
     * @param $entity
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function entityExists($entity)
    {
        return file_exists($this->data_path.$entity.'.xml');
    }

    /**
     * ONLY FOR DEVELOPMENT PURPOSE
     *
     * @since 1.0.0
     */
    public function generateAllEntityFiles()
    {
        $entities = [];
        foreach ($this->getEntitiesList() as $entity) {
            $entities[$entity] = $this->getEntityInfo($entity);
        }
        $this->generateEntityFiles($entities);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getEntitiesList()
    {
        $entities = [];
        foreach (scandir($this->data_path) as $file) {
            if ($file[0] != '.' && preg_match('#^(.+)\.xml$#', $file, $m)) {
                $entities[] = $m[1];
            }
        }

        return $entities;
    }

    /**
     * @param $entity
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getEntityInfo($entity)
    {
        $info = [
            'config' => [
                'id'       => '',
                'primary'  => '',
                'class'    => '',
                'sql'      => '',
                'ordersql' => '',
                'image'    => '',
                'null'     => '',
            ],
            'fields' => [],
        ];

        if (!$this->entityExists($entity)) {
            return $info;
        }

        $xml = @simplexml_load_file($this->data_path.$entity.'.xml', 'InstallSimplexmlElement');
        if (!$xml) {
            return $info;
        }

        if ($xml->fields['id']) {
            $info['config']['id'] = (string) $xml->fields['id'];
        }

        if ($xml->fields['primary']) {
            $info['config']['primary'] = (string) $xml->fields['primary'];
        }

        if ($xml->fields['class']) {
            $info['config']['class'] = (string) $xml->fields['class'];
        }

        if ($xml->fields['sql']) {
            $info['config']['sql'] = (string) $xml->fields['sql'];
        }

        if ($xml->fields['ordersql']) {
            $info['config']['ordersql'] = (string) $xml->fields['ordersql'];
        }

        if ($xml->fields['null']) {
            $info['config']['null'] = (string) $xml->fields['null'];
        }

        if ($xml->fields['image']) {
            $info['config']['image'] = (string) $xml->fields['image'];
        }

        foreach ($xml->fields->field as $field) {
            $column = (string) $field['name'];
            $info['fields'][$column] = [];
            if (isset($field['relation'])) {
                $info['fields'][$column]['relation'] = (string) $field['relation'];
            }
        }

        return $info;
    }

    /**
     * ONLY FOR DEVELOPMENT PURPOSE
     *
     * @since 1.0.0
     */
    public function generateEntityFiles($entities)
    {
        $dependencies = $this->getDependencies();

        // Sort entities to populate database in good order (E.g. zones before countries)
        do {
            $current = (isset($sortEntities)) ? $sortEntities : [];
            $sortEntities = [];
            foreach ($entities as $entity) {
                if (isset($dependencies[$entity])) {
                    $min = count($entities) - 1;
                    foreach ($dependencies[$entity] as $item) {
                        if (($key = array_search($item, $sortEntities)) !== false) {
                            $min = min($min, $key);
                        }
                    }
                    if ($min == 0) {
                        array_unshift($sortEntities, $entity);
                    } else {
                        array_splice($sortEntities, $min, 0, [$entity]);
                    }
                } else {
                    $sortEntities[] = $entity;
                }
            }
            $entities = $sortEntities;
        } while ($current != $sortEntities);

        foreach ($sortEntities as $entity) {
            $this->generateEntityContent($entity);
        }
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getDependencies()
    {
        $entities = [];
        foreach ($this->getEntitiesList() as $entity) {
            $entities[$entity] = $this->getEntityInfo($entity);
        }

        $dependencies = [];
        foreach ($entities as $entity => $info) {
            foreach ($info['fields'] as $field => $infoField) {
                if (isset($infoField['relation']) && $infoField['relation'] != $entity) {
                    if (!isset($dependencies[$infoField['relation']])) {
                        $dependencies[$infoField['relation']] = [];
                    }
                    $dependencies[$infoField['relation']][] = $entity;
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param $entity
     *
     * @since 1.0.0
     */
    public function generateEntityContent($entity)
    {
        $xml = $this->loadEntity($entity);
        if (method_exists($this, 'getEntityContents'.Tools::toCamelCase($entity))) {
            $content = $this->{'getEntityContents'.Tools::toCamelCase($entity)}($entity);
        } else {
            $content = $this->getEntityContents($entity);
        }

        unset($xml->entities);
        $entities = $xml->addChild('entities');
        $this->createXmlEntityNodes($entity, $content['nodes'], $entities);
        $xml->asXML($this->data_path.$entity.'.xml');

        // Generate multilang XML files
        if ($content['nodes_lang']) {
            foreach ($content['nodes_lang'] as $idLang => $nodes) {
                if (!isset($this->languages[$idLang])) {
                    continue;
                }

                $iso = $this->languages[$idLang];
                if (!is_dir($this->lang_path.$this->getFallBackToDefaultLanguage($iso).'/data')) {
                    mkdir($this->lang_path.$this->getFallBackToDefaultLanguage($iso).'/data');
                }

                $xmlNode = new InstallSimplexmlElement('<entity_'.$entity.' />');
                $this->createXmlEntityNodes($entity, $nodes, $xmlNode);
                $xmlNode->asXML($this->lang_path.$this->getFallBackToDefaultLanguage($iso).'/data/'.$entity.'.xml');
            }
        }

        if ($xml->fields['image']) {
            if (method_exists($this, 'backupImage'.Tools::toCamelCase($entity))) {
                $this->{'backupImage'.Tools::toCamelCase($entity)}((string) $xml->fields['image']);
            } else {
                $this->backupImage($entity, (string) $xml->fields['image']);
            }
        }
    }

    /**
     * ONLY FOR DEVELOPMENT PURPOSE
     *
     * @since 1.0.0
     */
    public function getEntityContents($entity)
    {
        $xml = $this->loadEntity($entity);
        $primary = (isset($xml->fields['primary']) && $xml->fields['primary']) ? (string) $xml->fields['primary'] : 'id_'.$entity;
        $isMultilang = $this->isMultilang($entity);

        // Check if current table is an association table (if multiple primary keys)
        $isAssociation = false;
        if (strpos($primary, ',') !== false) {
            $isAssociation = true;
            $primary = array_map('trim', explode(',', $primary));
        }

        // Build query
        $sql = new DbQuery();
        $sql->select('a.*');
        $sql->from($entity, 'a');
        if ($isMultilang) {
            $sql->select('b.*');
            $sql->leftJoin($entity.'_lang', 'b', 'a.'.$primary.' = b.'.$primary);
        }

        if (isset($xml->fields['sql']) && $xml->fields['sql']) {
            $sql->where((string) $xml->fields['sql']);
        }

        if (!$isAssociation) {
            $sql->select('a.'.$primary);
            if (!isset($xml->fields['ordersql']) || !$xml->fields['ordersql']) {
                $sql->orderBy('a.'.$primary);
            }
        }

        if ($isMultilang && (!isset($xml->fields['ordersql']) || !$xml->fields['ordersql'])) {
            $sql->orderBy('b.id_lang');
        }

        if (isset($xml->fields['ordersql']) && $xml->fields['ordersql']) {
            $sql->orderBy((string) $xml->fields['ordersql']);
        }

        // Get multilang columns
        $aliasMultilang = [];
        if ($isMultilang) {
            $columns = $this->getColumns($entity);
            $multilangColumns = $this->getColumns($entity, true);

            // If some columns from _lang table have same name than original table, rename them (E.g. value in configuration)
            foreach ($multilangColumns as $c => $is_text) {
                if (isset($columns[$c])) {
                    $alias = $c.'_alias';
                    $aliasMultilang[$c] = $alias;
                    $sql->select('a.'.$c.' as '.$c.', b.'.$c.' as '.$alias);
                }
            }
        }

        // Get all results
        $nodes = $nodesLang = [];
        $results = Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            $this->setError($this->language->l('SQL error on query <i>%s</i>', $sql));
        } else {
            foreach ($results as $row) {
                // Store common columns
                if ($isAssociation) {
                    $id = $entity;
                    foreach ($primary as $key) {
                        $id .= '_'.$row[$key];
                    }
                } else {
                    $id = $this->generateId($entity, $row[$primary], $row, (isset($xml->fields['id']) && $xml->fields['id']) ? (string) $xml->fields['id'] : null);
                }

                if (!isset($nodes[$id])) {
                    $node = [];
                    foreach ($xml->fields->field as $field) {
                        $column = (string) $field['name'];
                        if (isset($field['relation'])) {
                            $sql = 'SELECT `id_'.bqSQL($field['relation']).'`
									FROM `'.bqSQL(_DB_PREFIX_.$field['relation']).'`
									WHERE `id_'.bqSQL($field['relation']).'` = '.(int) $row[$column];
                            $node[$column] = $this->generateId((string) $field['relation'], Db::getInstance()->getValue($sql));

                            // A little trick to allow storage of some hard values, like '-1' for tab.id_parent
                            if (!$node[$column] && $row[$column]) {
                                $node[$column] = $row[$column];
                            }
                        } else {
                            $node[$column] = $row[$column];
                        }
                    }
                    $nodes[$id] = $node;
                }

                // Store multilang columns
                if ($isMultilang && $row['id_lang']) {
                    $node = [];
                    foreach ($multilangColumns as $column => $is_text) {
                        $node[$column] = $row[isset($aliasMultilang[$column]) ? $aliasMultilang[$column] : $column];
                    }
                    $nodesLang[$row['id_lang']][$id] = $node;
                }
            }
        }

        return [
            'nodes'      => $nodes,
            'nodes_lang' => $nodesLang,
        ];
    }

    /**
     * @param       $entity
     * @param       $primary
     * @param array $row
     * @param null  $idFormat
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function generateId($entity, $primary, array $row = [], $idFormat = null)
    {
        static $ids = [];

        if (isset($ids[$entity][$primary])) {
            return $ids[$entity][$primary];
        }

        if (!isset($ids[$entity])) {
            $ids[$entity] = [];
        }

        if (!$primary) {
            return '';
        }

        if (!$idFormat || !$row || !$row[$idFormat]) {
            $ids[$entity][$primary] = $entity.'_'.$primary;
        } else {
            $value = $row[$idFormat];
            $value = preg_replace('#[^a-z0-9_-]#i', '_', $value);
            $value = preg_replace('#_+#', '_', $value);
            $value = preg_replace('#^_+#', '', $value);
            $value = preg_replace('#_+$#', '', $value);

            $storeIdentifier = $value;
            $i = 1;
            while (in_array($storeIdentifier, $ids[$entity])) {
                $storeIdentifier = $value.'_'.$i++;
            }
            $ids[$entity][$primary] = $storeIdentifier;
        }

        return $ids[$entity][$primary];
    }

    /**
     * @param                  $entity
     * @param array            $nodes
     * @param SimpleXMLElement $entities
     *
     * @since 1.0.0
     */
    public function createXmlEntityNodes($entity, array $nodes, SimpleXMLElement $entities)
    {
        $types = array_merge($this->getColumns($entity), $this->getColumns($entity, true));
        foreach ($nodes as $id => $node) {
            $entityNode = $entities->addChild($entity);
            $entityNode['id'] = $id;
            foreach ($node as $k => $v) {
                if (isset($types[$k]) && $types[$k]) {
                    $entityNode->addChild($k, $v);
                } else {
                    $entityNode[$k] = $v;
                }
            }
        }
    }

    /**
     * @param $entity
     * @param $path
     *
     * @since 1.0.0
     */
    public function backupImage($entity, $path)
    {
        $reference = [
            'product'      => 'products',
            'category'     => 'categories',
            'manufacturer' => 'manufacturers',
            'supplier'     => 'suppliers',
            'scene'        => 'scenes',
            'store'        => 'stores',
        ];

        $types = [];
        if (isset($reference[$entity])) {
            $types = [];
            foreach (ImageType::getImagesTypes($reference[$entity]) as $type) {
                $types[] = $type['name'];
            }
        }

        $pathList = array_map('trim', explode(',', $path));
        foreach ($pathList as $p) {
            $backupPath = $this->img_path.$p.'/';
            $fromPath = _PS_IMG_DIR_.$p.'/';

            if (!is_dir($backupPath) && !mkdir($backupPath)) {
                $this->setError(sprintf('Cannot create directory <i>%s</i>', $backupPath));
            }

            foreach (scandir($fromPath) as $file) {
                if ($file[0] != '.' && preg_match('#^(([0-9]+)(-('.implode('|', $types).'))?)\.(gif|jpg|jpeg|png)$#i', $file, $m)) {
                    $fileId = $m[2];
                    $fileType = $m[3];
                    $fileExtension = $m[5];
                    copy($fromPath.$file, $backupPath.$this->generateId($entity, $fileId).$fileType.'.'.$fileExtension);
                }
            }
        }
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getEntityContentsTag()
    {
        $nodesLang = [];

        $sql = 'SELECT t.id_tag, t.id_lang, t.name, pt.id_product
				FROM '._DB_PREFIX_.'tag t
				LEFT JOIN '._DB_PREFIX_.'product_tag pt ON t.id_tag = pt.id_tag
				ORDER BY id_lang';
        foreach (Db::getInstance()->executeS($sql) as $row) {
            $identifier = $this->generateId('tag', $row['id_tag']);
            if (!isset($nodesLang[$row['id_lang']])) {
                $nodesLang[$row['id_lang']] = [];
            }

            if (!isset($nodesLang[$row['id_lang']][$identifier])) {
                $nodesLang[$row['id_lang']][$identifier] = [
                    'name'     => $row['name'],
                    'products' => '',
                ];
            }

            $nodesLang[$row['id_lang']][$identifier]['products'] .= (($nodesLang[$row['id_lang']][$identifier]['products']) ? ',' : '').$this->generateId('product', $row['id_product']);
        }

        return [
            'nodes'      => [],
            'nodes_lang' => $nodesLang,
        ];
    }

    /**
     * @since 1.0.0
     */
    public function backupImageImage()
    {
        $types = [];
        foreach (ImageType::getImagesTypes('products') as $type) {
            $types[] = $type['name'];
        }

        $backupPath = $this->img_path.'p/';
        $fromPath = _PS_PROD_IMG_DIR_;
        if (!is_dir($backupPath) && !mkdir($backupPath)) {
            $this->setError(sprintf('Cannot create directory <i>%s</i>', $backupPath));
        }

        foreach (Image::getAllImages() as $image) {
            $image = new Image($image['id_image']);
            $imagePath = $image->getExistingImgPath();
            if (file_exists($fromPath.$imagePath.'.'.$image->image_format)) {
                copy($fromPath.$imagePath.'.'.$image->image_format, $backupPath.$this->generateId('image', $image->id).'.'.$image->image_format);
            }

            foreach ($types as $type) {
                if (file_exists($fromPath.$imagePath.'-'.$type.'.'.$image->image_format)) {
                    copy($fromPath.$imagePath.'-'.$type.'.'.$image->image_format, $backupPath.$this->generateId('image', $image->id).'-'.$type.'.'.$image->image_format);
                }
            }
        }
    }

    /**
     * @since 1.0.0
     */
    public function backupImageTab()
    {
        $backupPath = $this->img_path.'t/';
        $fromPath = _PS_IMG_DIR_.'t/';
        if (!is_dir($backupPath) && !mkdir($backupPath)) {
            $this->setError(sprintf('Cannot create directory <i>%s</i>', $backupPath));
        }

        $xml = $this->loadEntity('tab');
        foreach ($xml->entities->tab as $tab) {
            if (file_exists($fromPath.$tab->class_name.'.gif')) {
                copy($fromPath.$tab->class_name.'.gif', $backupPath.$tab->class_name.'.gif');
            }
        }
    }
}
