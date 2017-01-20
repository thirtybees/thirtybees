<?php

namespace PrestaShop\PrestaShop\Tests\Helper\Mocks;

use Adapter_EntityMapper;
use Exception;
use ObjectModel;

/**
 * Class FakeEntityMapper
 *
 * @package PrestaShop\PrestaShop\Tests\Helper\Mocks
 */
class FakeEntityMapper extends Adapter_EntityMapper
{
    private $fake_db = [];

    private $entity_being_built = null;

    /**
     * Stores the given entity in the fake database, so load call with the same id will fill the entity with it.
     *
     * @param ObjectModel $entity
     *
     * @return $this
     * @throws Exception
     */
    public function willReturn(ObjectModel $entity)
    {
        if ($this->entity_being_built !== null) {
            throw new Exception('Invalid usage of FakeEntityMapper::willReturn : an entity build was already started, please call FakeEntityMapper::forId to finish building your entity.');
        }

        $this->entity_being_built = $entity;

        return $this;
    }

    /**
     * @param int      $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws Exception
     */
    public function forId($id, $idLang = null, $idShop = null)
    {
        if ($this->entity_being_built === null) {
            throw new Exception('Invalid usage of FakeEntityMapper::forId : you need to call willReturn first.');
        }

        $cacheId = $this->buildCacheId($id, get_class($this->entity_being_built), $idLang, $idShop);
        $this->fake_db[$cacheId] = $this->entity_being_built;

        $this->entity_being_built = null;
    }

    /**
     * @param int    $id
     * @param string $className
     * @param int    $idLang
     * @param int    $idShop
     *
     * @return string
     */
    private function buildCacheId($id, $className, $idLang, $idShop)
    {
        return 'objectmodel_'.$className.'_'.(int) $id.'_'.(int) $idShop.'_'.(int) $idLang;
    }

    /**
     * Fills the given entity with fields from the entity stored in the fake database if it exists.
     *
     * @param int    $id
     * @param int    $idLang
     * @param object $entity
     * @param array  $entityDefs
     * @param int    $idShop
     * @param bool   $shouldCacheObjects
     *
     * @throws Exception
     */
    public function load($id, $idLang, $entity, $entityDefs, $idShop, $shouldCacheObjects)
    {
        if ($this->entity_being_built !== null) {
            throw new Exception('Unifinished entity build : an entity build was started with FakeEntityMapper::willReturn, please call FakeEntityMapper::forId to finish building your entity.');
        }

        $cacheId = $this->buildCacheId($id, $entityDefs['classname'], $idLang, $idShop);

        if (isset($this->fake_db[$cacheId])) {
            foreach ($this->fake_db[$cacheId] as $key => $value) {
                $entity->$key = $value;
            }
        }
    }
}
