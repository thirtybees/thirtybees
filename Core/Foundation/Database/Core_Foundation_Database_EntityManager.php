<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class Core_Foundation_Database_EntityManager
 */
class Core_Foundation_Database_EntityManager
{
    /**
     * @var Core_Foundation_Database_DatabaseInterface
     */
    protected $db;

    /**
     * @var Core_Business_ConfigurationInterface
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $entityMetaData = [];

    /**
     * Core_Foundation_Database_EntityManager constructor.
     *
     * @param Core_Foundation_Database_DatabaseInterface $db
     * @param Core_Business_ConfigurationInterface $configuration
     */
    public function __construct(
        Core_Foundation_Database_DatabaseInterface $db,
        Core_Business_ConfigurationInterface $configuration
    ) {
        $this->db = $db;
        $this->configuration = $configuration;
    }

    /**
     * Return current database object used
     *
     * @return Core_Foundation_Database_DatabaseInterface
     */
    public function getDatabase()
    {
        return $this->db;
    }

    /**
     * Return current repository used
     *
     * @param string $className
     *
     * @return mixed
     *
     * @throws PrestaShopException
     */
    public function getRepository($className)
    {
        if (is_callable([$className, 'getRepositoryClassName'])) {
            $repositoryClass = call_user_func([$className, 'getRepositoryClassName']);
        } else {
            $repositoryClass = null;
        }

        if (!$repositoryClass) {
            $repositoryClass = 'Core_Foundation_Database_EntityRepository';
        }

        return new $repositoryClass(
            $this,
            $this->configuration->get('_DB_PREFIX_'),
            $this->getEntityMetaData($className)
        );
    }

    /**
     * Return entity's meta data
     *
     * @param string $className
     *
     * @return mixed
     * @throws PrestaShopException
     */
    public function getEntityMetaData($className)
    {
        if (!array_key_exists($className, $this->entityMetaData)) {
            $metaDataRetriever = new Adapter_EntityMetaDataRetriever();
            $this->entityMetaData[$className] = $metaDataRetriever->getEntityMetaData($className);
        }

        return $this->entityMetaData[$className];
    }

    /**
     * Flush entity to DB
     *
     * @param Core_Foundation_Database_EntityInterface $entity
     *
     * @return static
     */
    public function save(Core_Foundation_Database_EntityInterface $entity)
    {
        $entity->save();

        return $this;
    }

    /**
     * DElete entity from DB
     *
     * @param Core_Foundation_Database_EntityInterface $entity
     *
     * @return static
     */
    public function delete(Core_Foundation_Database_EntityInterface $entity)
    {
        $entity->delete();

        return $this;
    }
}
