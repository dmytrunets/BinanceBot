<?php
/**
 * StrategyDenisTest
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\Tests\Trading;

use App\BinanceBot\Binance\Balance;
use App\BinanceBot\Binance\BalanceCollection;
use App\BinanceBot\Binance\CandlestickCollection;
use App\BinanceBot\Trading\Strategy2;
use App\BinanceBot\Trading\StrategyDenis;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class StrategyDenisTest
 */
class Strategy2Test extends KernelTestCase
{
    /**
     * @var \App\BinanceBot\Trading\Strategy2
     */
    private $testObj;

    /** @var \App\BinanceBot\Trading\AbstractStrategy */
    private $strategy;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->strategy = self::$container->get(Strategy2::class);
    }

    /**
     * @dataProvider candleStickDataProvider
     * @covers       \App\BinanceBot\Trading\Strategy2::execute
     *
     * @param $candlesticks
     */
    public function testExecute($candlesticks)
    {
        $this->strategy = self::$container->get(Strategy2::class);

        /** @var CandlestickCollection $candlestickCollection */
        $candlestickCollection = self::$container->get(CandlestickCollection::class);
        $candlestickCollection->setRawData($candlesticks);
        $result = $this->strategy->execute($candlestickCollection);

        $this->assertEquals(true, $result);
    }

    /**
     * @return array
     */
    public function candleStickDataProvider()
    {
        $content = file_get_contents(__DIR__. '/candlesticks_data.json');
        $candlesticks = json_decode($content, true);

        return [
          [$candlesticks]
        ];
    }

    public function testCandleSticksCollection()
    {
        $app = new \App\BinanceBot\BinanceBot();
        $connection = $app ->api;

        $candlestickCollection = new CandlestickCollection($connection);
        $candlestickCollection->setSymbol('BTCUSDT');
        $candlestickCollection->setInterval('1m');
        $candlestickCollection->setLimit(10);

        $this->assertEquals(true, count($candlestickCollection->getItems()) > 1);

        $balanceCollection = new BalanceCollection($connection);
        $balanceCollection->getItems();

        $balanceBTC = [
            'asset'     => 'BTC',
            'available' => 0.00000000,
            'onOrder'   => 0.00000000,
            'btcValue'  => 0.00000000,
            'btcTotal'  => 0.00000000,
        ];

        $balanceUSDT = [
            'asset'     => 'USDT',
            'available' => 0.00000000,
            'onOrder'   => 0.00000000,
            'btcValue'  => 0.00000000,
            'btcTotal'  => 0.00000000,
        ];

        $balanceCollection->addItem(new Balance($balanceBTC));
        $balanceCollection->addItem(new Balance($balanceUSDT));
    }
}
