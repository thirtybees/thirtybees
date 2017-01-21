<?php


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
    }

    public function testScoreQuery()
    {
        $this->assertEquals(
            '( IF (`idGroup` = 1, 2, 0) +  IF (`idCountry` = 1, 4, 0) +  IF (`idCurrency` = 1, 8, 0) +  IF (`idShop` = 1, 16, 0) +  IF (`idCustomer` = 1, 32, 0)) AS `score`',
            $this->tester->invokeStaticMethod('SpecificPrice', '_getScoreQuery', [1, 1, 1, 1, 1, 1])
        );
    }
}
