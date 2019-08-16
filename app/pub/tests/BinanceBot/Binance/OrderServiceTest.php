<?php

namespace App\Tests\BinanceBot\Binance;

use App\BinanceBot\Binance\OrderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class OrderServiceTest
 */
class OrderServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @dataProvider isExecutedStopLimitDataProvider
     */
    public function testIsExecuted($currentPrice, $orderPrice, $changeInPercent, $expected)
    {
        $container = self::$kernel->getContainer();
        /** @var \App\BinanceBot\Binance\OrderService $orderService */
        $orderService = $container->get(OrderService::class);
        $actual = $orderService->isExecuted($currentPrice, $orderPrice, $changeInPercent);
        $this->assertEquals($expected, $actual);
    }

    public function isExecutedStopLimitDataProvider()
    {
        return [
            [1000, 900, 15, true],
            [900, 1000, -15, true],
            [7956.97, 7671.92575, 3.82, true],
        ];
    }
}
