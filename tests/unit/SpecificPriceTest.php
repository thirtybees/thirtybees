<?php
use AspectMock\Test as test;

class SpecificPriceTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
        \AspectMock\Test::clean();
    }

    public function testScoreQuery()
    {
        // Mock the static function SpecificPrice::getPriority
        test::double(
            'SpecificPrice',
            [
                'getPriority' => [
                    'id_customer',
                    'id_shop',
                    'id_currency',
                    'id_country',
                    'id_group',
                ],
            ]
        );

        // Call protected static function SpecificPrice::_getScoreQuery
        $this->assertEquals(
            '( IF (`id_group` = 1, 2, 0) +  IF (`id_country` = 1, 4, 0) +  IF (`id_currency` = 1, 8, 0) +  IF (`id_shop` = 1, 16, 0) +  IF (`id_customer` = 1, 32, 0)) AS `score`',
            $this->tester->invokeStaticMethod('SpecificPrice', '_getScoreQuery', [1, 1, 1, 1, 1, 1])
        );
    }
}
