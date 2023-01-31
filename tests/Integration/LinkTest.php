<?php

namespace Tests\Integration;

use Codeception\Test\Unit;
use Configuration;
use Link;
use PrestaShopException;
use Product;
use Tests\Support\UnitTester;
use function PHPUnit\Framework\assertEquals;

class LinkTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var bool
     */
    protected $raised = false;

    protected function getImageLinkData()
    {
        return [
            ['1/name.jpg', 1, 'name', 1, null],
            ['img/p/1/1.jpg', 0, 'name', 1, null],
            ['1-Niara_home/name.jpg', 1, 'name', 1, 'home'],
            ['img/p/1/1-Niara_home.jpg', 0, 'name', 1, 'home'],
            ['img/p/en-default-Niara_home.jpg', 1, 'name', 111111, 'home'],
            ['img/p/en-default-Niara_home.jpg', 0, 'name', 111111, 'home'],
            ['1-Niara_home/name.jpg', 1, 'name', 1, 'home'],
            ['img/p/1/1-Niara_home.jpg', 0, 'name', 1, 'home'],
        ];
    }

    /**
     * @throws PrestaShopException
     *
     * @dataProvider getImageLinkData
     *
     */
    public function testImageLink($expected, $rewrite, $name, $ids, $type)
    {
        try {
            Configuration::updateValue('PS_REWRITING_SETTINGS', $rewrite);
            $link = new Link('http://', 'http://');
            assertEquals($expected, static::getRelativeUrl($link->getImageLink($name, $ids, $type)));
        } finally {
            Configuration::updateValue('PS_REWRITING_SETTINGS', 0);
        }
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function testImageLinkForSingleLangProduct()
    {
        try {
            Configuration::updateValue('PS_REWRITING_SETTINGS', 1);
            $link = new Link('http://', 'http://');
            $product = new Product(1, false, 1);
            $imageId = $product->getCoverWs();
            assertEquals('1-Niara_cart/candle.jpg', static::getRelativeUrl($link->getImageLink($product->link_rewrite, $imageId, 'cart')));
            assertEquals('1-Niara_cart/candle.jpg', static::getRelativeUrl($link->getImageLink($product->link_rewrite, $imageId, 'cart')));
        } finally {
            Configuration::updateValue('PS_REWRITING_SETTINGS', 0);
        }
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function testImageLinkForMultiLangProduct()
    {
        $this->raised = false;
        $previous = set_error_handler(function() {
            $this->raised = true;
            return true;
        });
        try {
            Configuration::updateValue('PS_REWRITING_SETTINGS', 1);
            $link = new Link('http://', 'http://');
            $product = new Product(1);
            $imageId = $product->getCoverWs();
            assertEquals('1-Niara_cart/candle.jpg', static::getRelativeUrl($link->getImageLink($product->link_rewrite, $imageId, 'cart')));
            assertEquals(true, $this->raised, "Error should have been raised");
        } finally {
            Configuration::updateValue('PS_REWRITING_SETTINGS', 0);
            set_error_handler($previous);
        }
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected static function getRelativeUrl($url)
    {
        return str_replace(trim(_PS_BASE_URL_, '/') . '/', '', $url);
    }

}
