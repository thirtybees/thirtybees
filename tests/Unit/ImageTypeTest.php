<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use ImageType;
use Tests\Support\UnitTester;

class ImageTypeTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var string[]
     */
    static $imageTypes1 = [
        'Niara_cart',
        'Niara_small',
    ];

    /**
     * @var string[]
     */
    static $imageTypes2 = [
        'Niara_cart_default',
        'Niara_small_default',
    ];

    /**
     * @var string[]
     */
    static $imageTypes3 = [
        'Niara_cart',
        'Niara_small',
        'community-theme-default_cart',
        'community-theme-default_small',
        'panda_cart_default',
        'cart',
        'small',
        'home',
    ];

    /**
     * @return string[][]
     */
    public function imageTypeTestDataProvider()
    {
        return [
            ['cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['Niara_cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['cart_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['default_cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['default_cart_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['cart_niara', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['cart_Niara_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['niara_cart_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['niara_cart_niara', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['niara_cart_niara_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['Niara_cart_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['Niara_cart_niara', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['Niara_cart_Niara', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['Niara_cart_niara_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['Niara_cart_Niara_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['niara_cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],
            ['Niara_cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes1'],

            ['cart', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['Niara_cart', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['cart_default', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['default_cart', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['default_cart_default', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['cart_niara', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['cart_Niara_default', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['niara_cart_default', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['niara_cart_niara', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['niara_cart_niara_default', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['Niara_cart_default', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['Niara_cart_niara', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['Niara_cart_Niara', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['Niara_cart_niara_default', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['Niara_cart_Niara_default', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['niara_cart', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],
            ['Niara_cart', 'Niara_cart_default', 'Niara', 'niara', 'imageTypes2'],

            ['cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['Niara_cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['cart_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['default_cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['default_cart_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['cart_niara', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['cart_Niara_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['niara_cart_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['niara_cart_niara', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['niara_cart_niara_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['Niara_cart_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['Niara_cart_niara', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['Niara_cart_Niara', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['Niara_cart_niara_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['Niara_cart_Niara_default', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['niara_cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],
            ['Niara_cart', 'Niara_cart', 'Niara', 'niara', 'imageTypes3'],

            ['cart', 'panda_cart_default', 'panda', 'panda', 'imageTypes3'],
            ['panda_cart_default', 'panda_cart_default', 'Niara', 'niara', 'imageTypes3'],
            ['Niara_home', 'home', 'Niara', 'niara', 'imageTypes3'],
        ];
    }

    /**
     * @dataProvider imageTypeTestDataProvider
     *
     * @param string $imageTypeName
     * @param string $expected
     * @param string $themeName
     * @param string $themeDir
     * @param string $imageTypesRef
     */
    public function testImageTypeResolving(
        $imageTypeName,
        $expected,
        $themeName,
        $themeDir,
        $imageTypesRef
    )
    {
        $imageTypes = [];
        foreach (static::$$imageTypesRef as $imageType) {
            $imageTypes[$imageType] = $imageType;
        }
        $actual = $this->tester->invokeStaticMethod(
            ImageType::class,
            'resolveImageTypeNameWithoutCache',
            [$imageTypeName, $themeName, $themeDir, $imageTypes]
        );
        $this->assertEquals(
            $expected,
            $actual,
            "Failed to resolve '$imageTypeName' to '$expected', got '$actual' instead, image types = [" . implode(', ', static::$$imageTypesRef) . ']'
        );
    }
}
