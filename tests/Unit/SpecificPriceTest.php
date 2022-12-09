<?php
namespace Tests\Unit;

use Codeception\Test\Unit;
use Exception;
use Tests\Support\UnitTester;

class SpecificPriceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @return void
     * @throws Exception
     */
    public function testScoreQuery()
    {
        $this->assertEquals(
            '( IF (`id_group` = 1, 2, 0) +  IF (`id_country` = 1, 4, 0) +  IF (`id_currency` = 1, 8, 0) +  IF (`id_shop` = 1, 16, 0) +  IF (`id_customer` = 1, 32, 0)) AS `score`',
            $this->tester->invokeStaticMethod('SpecificPrice', '_getScoreQuery', [1, 1, 1, 1, 1, 1])
        );
    }
}
