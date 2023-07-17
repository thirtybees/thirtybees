<?php

namespace Integration;

use Codeception\Test\Unit;
use ObjectModel;
use PrestaShopException;
use ReflectionException;
use Tests\Support\UnitTester;
use Tests\Support\Utils\ObjectModelUtils;
use WebserviceRequest;

class WebserviceMetadataTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    const NOT_EXPOSED = [
        \AddressFormat::class,
        \Alias::class,
        \Attachment::class,
        \CMSCategory::class,
        \CMSRole::class,
        \CompareProduct::class,
        \ConfigurationKPI::class,
        \Connection::class,
        \ConnectionsSource::class,
        \DateRange::class,
        \Gender::class,
        \GroupReduction::class,
        \Hook::class,
        \Image::class, // specific handler
        \Mail::class,
        \Message::class,
        \Meta::class,
        \OrderDetailPack::class,
        \OrderMessage::class,
        \OrderReturn::class,
        \OrderReturnState::class,
        \Pack::class,
        \Page::class,
        \PaymentCC::class,
        \PrestaShopLogger::class,
        \ProductDownload::class,
        \Profile::class,
        \QuickAccess::class,
        \Referrer::class,
        \RequestSql::class,
        \Risk::class,
        \Scene::class,
        \StockMvt::class, // exposed as StockMvtWs
        \Tab::class,
        \Theme::class,
        \UrlRewrite::class,
        \WebserviceKey::class,
    ];

    /**
     * This tests verifies ObjectModel class contains properties for all fields in $definition
     *
     * @dataProvider getObjectModels
     *
     * @param string $className object model class name
     * @param array $definition object model definition
     *
     */
    public function testObjectModelExposed($className, $definition, $reflection)
    {
        $exposedResource = $this->getExposedResources();

        if (in_array($className, static::NOT_EXPOSED)) {
            if (in_array($className, $exposedResource)) {
                $this->fail("Object model $className is exposed to webservice, please remove it from " . __CLASS__ . '::NOT_EXPOSED array');
            } else {
                return;
            }
        }

        if (in_array($className, $this->getExposedResources())) {
            return;
        }

        $this->fail("Object model $className is not exposed to webservice");
    }

    /**
     * Method returns array of all ObjectModel subclasses in the system
     *
     * @return ObjectModel[]
     * @throws ReflectionException
     * @throws PrestaShopException
     */
    public function getObjectModels()
    {
         return ObjectModelUtils::getObjectModels();
    }

    /**
     * @return array
     */
    protected function getExposedResources(): array
    {
        $classes = [];
        foreach (WebserviceRequest::getResources() as $resource) {
            if (isset($resource['class'])) {
                $classes[] = $resource['class'];
            }
        }
        return $classes;
    }
}
