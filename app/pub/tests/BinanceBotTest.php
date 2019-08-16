<?php
/**
 * Copyright © InComm, Inc. All rights reserved.
 */

namespace App\Tests;

use \App\BinanceBot\BinanceBot;

/**
 * Class BinanceBotTest
 */
class BinanceBotTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BinanceBot
     */
    private $app;

    protected function setUp()
    {
        $this->app = new \App\BinanceBot\BinanceBot();
    }

    public function testTrade()
    {
        $this->assertEquals(true, $this->app->trade());
    }

    /**
     * @dataProvider sellOrdersDataProvider
     */
    public function testSellBTC($order, $expected)
    {
        $this->app->sellBTC($order);
        $this->assertEquals($expected, $this->app->getWallets());
    }

    /**
     * @return array
     */
    public function sellOrdersDataProvider()
    {
        return [
            [
                [
                    'datetime'       => '',
                    'operation_type' => 'SELL_BTC',
                    'price_USDT'     => 4090.09,
                    'amount_BTC'     => 0.000123,
                    'total_USDT'     => 0.50308107
                ],
                [
                    'USDT' => 1000.50308107,
                    'BTC'  => 0.999877
                ]
            ]
        ];
    }

    /**
     * @dataProvider buyBTCOrdersDataProvider
     */
    public function testBuyBTC($order, $expected)
    {
        $this->app->buyBTC($order);
        $this->assertEquals($expected, $this->app->getWallets());
    }

    /**
     * @return array
     */
    public function buyBTCOrdersDataProvider()
    {
        return [
            [
                [
                    'datetime'       => '',
                    'operation_type' => 'BUY_BTC',
                    'price_USDT'     => 4089.98,
                    'amount_BTC'     => 0.024449,
                    'total_USDT'     => 100
                ],
                [
                    'USDT' => 900,
                    'BTC'  => 1.024449
                ]
            ]
        ];
    }

    public function testBuildAnalyticsMatrix()
    {
        $this->app->buildAnalyticsMatrix([]);

        $this->assertEquals(1, 1);
    }

    /**
     * @see \BinanceBot::getBalances
     */
    public function testGetBalances()
    {
        $result = $this->app->getBalances();
    }

    public function testGetAllSymbols()
    {
        $result = $this->app->getAllSymbols();
        $this->assertEquals([], $result);
    }

    /**
     * @see          \BinanceBot::isBTCPair
     * @dataProvider isBTCPairDataProvider
     *
     * @param $symbol
     * @param $expected
     */
    public function testIsBTCPair($symbol, $expected)
    {
        $this->assertEquals($expected, $this->app->isBTCPair($symbol));
    }

    /**
     * @return array
     */
    public function isBTCPairDataProvider(): array
    {
        return [
            ['IOTABTC', true],
            ['BCCBTC', true],
            ['BTCIOTA', true],
            ['BTHETC', false],
            ['IOTXBTC', true],
        ];
    }

    /**
     * @dataProvider saveTradeAllDataProvider
     *
     * @param $interval
     *
     * @throws Exception
     */
    public function testSaveTrades($interval)
    {
        $result = $this->app->saveTradeAll($interval);
        $this->assertEquals(true, $result);
    }

    public function testGetCandleStick()
    {
        $this->app->getLatestPrice('BTCUSDT');
        $this->app->saveTrade('BTCUSDT', '5m');
    }

    /**
     * @return array
     */
    public function saveTradeAllDataProvider(): array
    {
        return [
            ['1m'],['3m'],['5m'],['15m'],['30m'],['1h']/*,['2h'],['4h'],['6h'],['8h'],['12h'],['1d'],['3d'],['1w'],['1M']*/
        ];
    }

    /**
     * @param $interval
     *
     * @throws Exception
     * @dataProvider saveTradeAllDataProvider
     */
    public function testGetInfo($interval)
    {
        $result = $this->app->getInfo($interval);
        $this->assertEquals(true, $result);
    }

    /**
     * @dataProvider tradingDataProvider
     *
     * @param $currentPrice
     */
    public function testEmulateTrading($currentPrice)
    {
        $percent = 0.2;
        $diff = $currentPrice * $percent;
        $price_line_top = $currentPrice + $diff;
        $price_line_botton = $currentPrice - $diff;

        echo $currentPrice;
//        $this->app->emulateTrading();
        
        $this->assertEquals(1, 1);
    }

    /**
     * @return array
     */
    public function tradingDataProvider(): array
    {
        return [
            [100, 100, 100, 100, 100],
            [101, 100, 100, 100, 100],
            [100, 101, 100, 100, 100],
            [100, 100, 101, 100, 100],
            [100, 100, 100, 101, 100],
            [100, 100, 100, 100, 101],
        ];
    }

    /**
     * @param $symbol
     * @param $interval
     *
     * @dataProvider dataProviderReportSymbolTrading
     */
    public function testReportSymbolTrading($symbol, $interval)
    {
        $this->assertEquals(true, $this->app->reportSymbolTrading($symbol, $interval, 20));
        $this->assertEquals(true, $this->app->reportSymbolTrading($symbol, $interval, 4));
        $this->assertEquals(true, $this->app->reportSymbolTrading($symbol, $interval, 2));
        $this->assertEquals(true, $this->app->reportSymbolTrading($symbol, $interval, 1));
        $this->assertEquals(true, $this->app->reportSymbolTrading($symbol, $interval, 0.5));
        $this->assertEquals(true, $this->app->reportSymbolTrading($symbol, $interval, 0.2));
        $this->assertEquals(true, $this->app->reportSymbolTrading($symbol, $interval, 0.1));
    }

    /**
     * @return array
     */
    public function dataProviderReportSymbolTrading(): array
    {
        $app = new BinanceBot();
        $symbols = $app->getAllSymbols();
        $data = [];
        foreach ($symbols as $i => $symbol) {
            if (!$app->isBTCPair($symbol)) continue;
            if ($i >= 10) break;

            $data[] = [$symbol, '1m'];
        }

//        return $data;

        return [
            ['BTCUSDT', '1m'],
//            ['BTCUSDT', '3m'],
//            ['BTCUSDT', '5m'],
//            ['BTCUSDT', '15m'],
//            ['BTCUSDT', '30m'],
//            ['BTCUSDT', '1h'],
        ];
    }

    public function testEmulate()
    {
        $this->app->strategy();
        $this->assertEquals(1, 1);
    }
}
