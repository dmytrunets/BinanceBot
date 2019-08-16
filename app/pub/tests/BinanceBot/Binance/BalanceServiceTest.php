<?php
/**
 * StrategyDenisTest
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\Tests\BinanceBot\Binance;

use App\BinanceBot\Binance\BalanceCollection;
use App\BinanceBot\Binance\BalanceService;
use App\BinanceBot\Binance\Data\DataObject;
use App\BinanceBot\Binance\Order;
use App\BinanceBot\Binance\StopLimit;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BalanceServiceTest
 */
class BalanceServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testBalanceService()
    {
        $container = self::$kernel->getContainer();

        $data = [
            'side' => 'BUY',
            'quantity' => 100,
            'price' => 1000,
            'type' => Order::TYPE_MARKET,
            'createdDate' => 15555555555
        ];

        $order = new Order($data);

        $observer = new DataObject(['order' => $order]);

        /** @var BalanceService $balanceService */
        $balanceService = $container->get(BalanceService::class);
        $balanceService->updateBalance($observer);

        /** @var BalanceCollection $balanceCollection */
        $balanceCollection = $container->get(BalanceCollection::class);

        $this->assertEquals(400, $balanceCollection->getByAsset('USDT')->getAvailable());
        $this->assertEquals(100, $balanceCollection->getByAsset('USDT')->getOnOrder());
        $this->assertEquals(500, $balanceCollection->getByAsset('USDT')->getTotalBalance());

        $this->assertEquals(0, $balanceCollection->getByAsset('BTC')->getAvailable());
        $this->assertEquals(0, $balanceCollection->getByAsset('BTC')->getOnOrder());

        $order->setStatus(Order::STATUS_COMPLETE);
        $balanceService->updateBalance($observer);

        $this->assertEquals(400, $balanceCollection->getByAsset('USDT')->getAvailable());
        $this->assertEquals(0, $balanceCollection->getByAsset('USDT')->getOnOrder());
        $this->assertEquals(400, $balanceCollection->getByAsset('USDT')->getTotalBalance());

        $this->assertEquals(0.1, $balanceCollection->getByAsset('BTC')->getAvailable());
        $this->assertEquals(0, $balanceCollection->getByAsset('BTC')->getOnOrder());

        // stop limit sell executed
        $data = [
            'side' => 'SELL',
            'quantity' => 0.031279458587373,
            'price' => 1000,
            'type' => Order::TYPE_MARKET,
            'createdDate' => 15555555555
        ];

        $order = new StopLimit($data);
        $observer = new DataObject(['order' => $order]);

        $balanceService->updateBalance($observer);

        $order->setStatus(StopLimit::STATUS_STOP_LIMIT_COMPLETED);
        $balanceService->updateBalance($observer);

        $this->assertEquals(431.27945858737, $balanceCollection->getByAsset('USDT')->getAvailable());
        $this->assertEquals(0, $balanceCollection->getByAsset('USDT')->getOnOrder());
        $this->assertEquals(431.27945858737, $balanceCollection->getByAsset('USDT')->getTotalBalance());

        $this->assertEquals(0.068720541412627, $balanceCollection->getByAsset('BTC')->getAvailable());
        $this->assertEquals(0, $balanceCollection->getByAsset('BTC')->getOnOrder());
    }
}
