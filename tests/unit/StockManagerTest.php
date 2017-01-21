<?php


class StockManagerTest extends \Codeception\TestCase\Test
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

    /**
     * @param $warehouseIds
     * @param $expectedNormalizedWarehouseIds
     * @dataProvider getWarehouseIds
     */
    public function testPopulateQuantityFields($warehouseIds, $expectedNormalizedWarehouseIds) {
        $stockManager = new \StockManagerCore;

        $normalizedWarehouseIds = $stockManager->normalizeWarehouseIds($warehouseIds);
        $this->assertInternalType('array', $normalizedWarehouseIds,
            'The normalized warehouse ids should be of an array');

        $this->assertEquals($expectedNormalizedWarehouseIds, $normalizedWarehouseIds);
    }

    public function getWarehouseIds()
    {
        return [
            [[''], [0]],
            [null, []],
            [["1"], [1]],
            ["1", [1]],
        ];
    }
}
