<?php

namespace Tests\Unit;

use AdminImportController;
use Codeception\Test\Unit;
use Product;
use ReflectionClass;
use Tests\Support\UnitTester;

class AdminImportControllerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var array|null
     */
    private $validators;

    /**
     * @return void
     */
    protected function _before()
    {
        $this->validators = AdminImportController::$validators;
        AdminImportController::$validators = [
            'image' => function ($value) {
                return explode(',', $value);
            },
            'image_alt' => function ($value) {
                return explode(',', $value);
            },
            'price_tin' => 'floatval',
        ];
    }

    /**
     * @return void
     */
    protected function _after()
    {
        AdminImportController::$validators = $this->validators;
    }

    /**
     * @return void
     */
    public function testProductImportDataIsExtractedBeforeProductHydration()
    {
        $info = [
            'name' => 'Test product',
            'active' => '1',
            'supplier' => ' Supplier name ',
            'price_tin' => ' 12.50 ',
            'image' => ' first.jpg,second.jpg ',
            'image_alt' => ' First image,Second image ',
            'delete_existing_images' => '0',
            'features' => ' ',
        ];

        $importData = $this->tester->invokeStaticMethod(
            AdminImportController::class,
            'extractProductImportData',
            [&$info]
        );

        static::assertSame(
            [
                'name' => 'Test product',
                'active' => '1',
            ],
            $info
        );
        static::assertSame('Supplier name', $importData['supplier']);
        static::assertSame(12.5, $importData['price_tin']);
        static::assertSame(['first.jpg', 'second.jpg'], $importData['image']);
        static::assertSame(['First image', 'Second image'], $importData['image_alt']);
        static::assertSame('0', $importData['delete_existing_images']);
        static::assertArrayNotHasKey('features', $importData);
    }

    /**
     * @return void
     */
    public function testImportOnlyFieldsDoNotPolluteProductModel()
    {
        $productReflection = new ReflectionClass(Product::class);
        $importOnlyFields = array_merge(
            ['id_category'],
            AdminImportController::PRODUCT_IMPORT_DATA_FIELDS
        );

        foreach ($importOnlyFields as $field) {
            static::assertFalse(
                $productReflection->hasProperty($field),
                sprintf('Import-only field Product::$%s must remain in the import process', $field)
            );
        }
    }
}
