<?php

namespace App\BinanceBot;

use App\BinanceBot\Trading\StrategyOne;

/**
 * Class BinanceBot
 */
class BinanceBot
{
    public const TRANSACTION_FEES = 0.001;
    public const PROFIT = 20;

    public const BTC_PRICE = 4740.11;

    public $api;
    
    protected $wallets = [];

    private $countCall = 0;

    private $data;

    private $myOrders;

    public function __construct(\App\BinanceBot\Binance\API $api)
    {
        $this->api = $api;

        $this->myOrders = [
            'datetime' => '',
            'operation_type' => 'BUY_BTC',  // BUY_BTC, SELL_BTC
            'price_USDT' => 4090.09,
            'amount_BTC' => 0.000123,
            'total_USDT' => 0.50308107
        ];

        $this->initWallets();
    }

    public function initWallets(): array
    {
        $this->wallets = [
            'USDT' => 1000,
            'BTC' => 1.00000000
        ];

        return $this->wallets;
    }

    public function trade()
    {
        while (1) {
            $data = $this->getData();

            echo "\n_______________________";

            if (!is_array($data)) {
                break;
            }
            $result = $this->buildAnalyticsMatrix($data);
        }

        return true;
    }

    /**
     * @param $symbol
     * @param $data
     *
     * @return array
     */
    public function buildAnalyticsMatrix($symbol, $data): array
    {
        $arr = array_map(function($elm) { return $elm['high']; }, $data);

//        $arr = [400, 410, 405];
        $iMax = count($arr);

        $maxSub = 0;
        $start = null;
        $close = null;

        /* @see https://www.omnicalculator.com/math/percentage-increase#percent-increase-formula */
        for ($i = 0; $i <= $iMax-1; $i++) {
            for ($j = $i + 1; $j <= $iMax-1; $j++) {
                $precentIncrease = (($arr[$j] - $arr[$i]) / $arr[$i]) * 100;
                $sub = number_format($arr[$j] - $arr[$i], 8);
//                echo "\n" . $arr[$i] . ' - ' . $arr[$j] . '  - ' . $precentIncrease . '%' . ' - ' . $sub .'$';
                if ($maxSub < $sub) {
                    $maxSub = $sub;
                    $start = $data[$i]['openTime'];
                    $close = $data[$j]['openTime'];
                    $bestIn = $arr[$i];
                    $bestOut = $arr[$j];
                }
            }
        }

        $ballanceUSD = 100;
        $ballanceBTC = $ballanceUSD / static::BTC_PRICE;
        $altBallance = $ballanceBTC / $bestIn;
//        $altCoin = $ballanceBTC / $bestIn;

        unset($arr);

        $result = [
            'symbol' => $symbol,
            'Max sub' => $maxSub,
            'Best Open time' => $start,
            'Best Close time' => $close,
            'Best in' => $bestIn,
            'Best out' => $bestOut,
            'Income from $' . $ballanceUSD => '$' . number_format ($altBallance * $maxSub * static::BTC_PRICE, 2)
        ];

        print_r($result);

        $this->log('Symbol: ' . $symbol);
        echo "\r\n___________________" . PHP_EOL;

        return $result;
    }

    /**
     * @return array|bool
     */
    public function getData()
    {
        echo "Call #" . $this->countCall;
        if ($this->countCall === 0) {
            $fileName = __DIR__ . '/tests/fixtures/for_test_1m.data';
            $this->data = unserialize(file_get_contents($fileName));
        }

        if ($this->countCall === 184) {
            return false;
        }
        $this->countCall++;

        return array_slice($this->data, 0, $this->countCall);
    }

    /**
     * Sell BTC
     *
     * @param array $order
     */
    public function sellBTC(array $order)
    {
        $this->wallets['BTC'] -= $order['amount_BTC'];
        $this->wallets['USDT'] += $order['total_USDT'];
    }

    /**
     * Buy BTC
     *
     * @param array $order
     */
    public function buyBTC(array $order)
    {
        $this->wallets['BTC'] += $order['amount_BTC'];
        $this->wallets['USDT'] -= $order['total_USDT'];
    }

    /**
     * Get wallets
     *
     * @return array
     */
    public function getWallets()
    {
        return $this->wallets;
    }

    public function getBalances()
    {
        $interval = '5m';
        $this->saveTradeAll($interval);
        $this->getInfo($interval);

//        $this->api->useServerTime();
//        $balances = $this->api->balances();
//
//        $result = $this->api->prices();
//
//        $result2 = $this->api->price('BTCUSDT');
//
//        $result3 = $this->api->bookPrices();
//
//        $result4 = $this->api->prevDay('BTCUSDT');
//
//        $trades = $this->api->aggTrades("BTCUSDT");
//
//        $result5 = $this->api->depth("BTCUSDT");

//        $result3 = $this->api->bookPrices();
//        $symbols = array_keys($result3);
//
//        $symbols = array_slice($symbols, 0, 5);
//
//        foreach ($symbols as $symbol) {
//            $result4 = $this->api->prevDay($symbol);
//            $arr[$symbol] = $result4['priceChangePercent'];
//        }
        
        $i = 0;
        while (0) {
            $msg = 'Start: ' . memory_get_usage(true);
            $this->log($msg);

            $trades = $this->api->aggTrades("BTCUSDT");

            $msg =  'Finish: ' . memory_get_usage(true);
            $this->log($msg);
//            sleep(1);
        }

    }

    /**
     * Get all symbols
     *
     * @return array
     * @throws Exception
     */
    public function getAllSymbols(): array
    {
        $result3 = $this->api->bookPrices();
        return array_keys($result3);
    }

    /**
     * @param string $interval
     *
     * @return bool
     */
    public function saveTradeAll($interval): bool
    {
        gc_enable();
        $count = count($this->getAllSymbols());
        $symbols = $this->getAllSymbols();

        foreach ($symbols as $i => $symbol) {
            if (!$this->isBTCPair($symbol)) {
                continue;
            }

            if ($i >= 15) {
                break;
            }

            $progress = number_format($i * 100 / $count, 1);
            $this->log("{$symbol} Progress: {$progress}% Memory usage: ". memory_get_usage(true));
            $this->saveTrade($symbol, $interval);
        }

        return true;
    }

    /**
     * @param $symbol
     *
     * @throws \Exception
     */
    public function getLatestPrice($symbol)
    {
        $price = $this->api->price($symbol);
    }
    /**
     * Save trade
     *
     * @param string $symbol
     * @param string $interval
     *
     * @throws \Exception
     */
    public function saveTrade($symbol, $interval)
    {
        $ticks = $this->api->candlesticks($symbol, $interval);
        $fileName = $this->getTradeFileName($symbol, $interval);
        file_put_contents($fileName, serialize($ticks));
    }

    /**
     * Get trade file name
     *
     * @param $symbol
     * @param $interval
     *
     * @return string
     */
    public function getTradeFileName($symbol, $interval): string
    {
        return sprintf(__DIR__. '/../../tests/fixtures/candlesticks_%s_%s.data', $symbol, $interval);
    }

    /**
     * Log
     *
     * @param $message
     */
    private function log($message): void
    {
//        $message = date('H:i:s') . " - $message".PHP_EOL;
        $message .= PHP_EOL;
        print($message);
        flush();
        ob_flush();
    }

    /**
     * @param $interval
     *
     * @return bool
     * @throws Exception
     */
    public function getInfo($interval)
    {
        $resultFilePath = __DIR__ . "/tests/analytics/stat_{$interval}.txt";
        file_put_contents($resultFilePath, '');

        $i = 1;

        foreach ($this->getAllSymbols() as $symbol) {
            if (!$this->isBTCPair($symbol)) {
                continue;
            }

            $data = unserialize(file_get_contents($this->getTradeFileName($symbol, $interval)));
            $result = $this->buildAnalyticsMatrix($symbol, $data);

            if ($i++ === 1) {
                file_put_contents($resultFilePath, $this->arrayToFileLine($result, 'array_keys'), FILE_APPEND);
            }

            file_put_contents($resultFilePath, $this->arrayToFileLine($result, 'array_values'), FILE_APPEND);
            unset($data);
        }

        return true;
    }

    /**
     * Is BTC Pair
     *
     * @param $symbol
     *
     * @return bool
     */
    public function isBTCPair($symbol): bool
    {
        return strpos($symbol, 'BTC') !== false;
    }

    /**
     * Array to file line
     *
     * @param array $data
     *
     * @return string
     */
    private function arrayToFileLine(array $data, $function)
    {
        return implode("\t", $function($data)) . "\n";
    }

    /**
     * Emulate trading
     */
    public function emulateTrading()
    {
        /* TODO: Эмуляция трейдинга одной пары
        Каждую минуту запрос биржу "дай последние данные по паре".
        В каждую итерацию создать ордер на покупку и продажу с расчетной выгодой 20%
            от мой (т.е за сколько я купил или продал) цены покупки или продажи
        */
        $i = 0;
        while ($i++ < 10) {
            print $i . "\n";
        }
    }

    /**
     * @param string $symbol
     * @param string $interval
     *
     * @param int    $profit_percent
     *
     * @return bool
     */
    public function reportSymbolTrading($symbol, $interval, $profit_percent)
    {
        $this->log("Emulate trading for {$symbol} on interval {$interval}, change {$profit_percent}%");
        $content = unserialize(file_get_contents($this->getTradeFileName($symbol, $interval)));
        $rows = [];
        $last_sell_price = 0;
        $last_buy_price = $content[0]['open'];
        $buy = true;
        $local_max = 0;
        $profit = 0;
        $transactionsFee = 0;
        $countTransactions = 0;

        foreach ($content as $i => $item) {
            $isChanged = false;
            $row = [];
//            $row[] = $item['openTime'];
            $row[] = $item['open'];
            $row[] = $item['high'];
            $row[] = $item['low'];
            $row[] = $item['close'];

            // sell if current price > than last buy price + % profit
            if (false === $buy) {
                if ($item['open'] > $last_buy_price * (1 + $profit_percent/100)) {
                    $last_sell_price = $item['open'];
                    $buy = !$buy;
                    $row[] = 0; // buy
                    $row[] = $last_sell_price; // sell
                    $isChanged = true;
                    $local_max = $last_sell_price;
                    $profit += abs($last_buy_price - $last_sell_price);
                }
            }

            // buy if current price < than last sell price + % profit
            if (true === $buy && !$isChanged) {
                if ($i === 0 || $item['open'] < $last_sell_price * (1 - $profit_percent/100) || ($local_max - $item['open']) * 100 / $local_max > $profit_percent) {
                    $last_buy_price = $item['open'];
                    $buy = !$buy;
                    $row[] = $last_buy_price; // buy
                    $row[] = 0; // sell
                    $isChanged = true;
                }
                if ($item['open'] > $local_max) {
                    $local_max = $item['open'];
                }
            }

            if (!$isChanged) {
                $row[] = 0; // buy
                $row[] = 0; // sell

            } else {
                $transactionsFee += static::TRANSACTION_FEES * $item['open'];
                $countTransactions++;
            }

            $rows[] = implode("\t", $row);
        }

        $result = implode("\n", $rows);
        $outputFile = __DIR__ . "/../../tests/analytics/trade_{$symbol}_{$interval}_profit_{$profit_percent}.data";
        file_put_contents($outputFile, $result);
        $this->log('Result in file: ' . $outputFile);


        $this->log("Count transactions: {$countTransactions}");
        $this->log("Profit: {$profit}");
        $this->log("Transaction fees: {$transactionsFee}");
        $income = $profit - $transactionsFee;
        $this->log("Income: {$income}");
        if ($buy === false) {
            $diff = $last_buy_price - end($content)['open'];
            $this->log('Potential risk ' . $diff);
        }
        $this->log('----------------------------');

        return true;
    }

    public function strategy()
    {
        $tradingStrategy = new StrategyOne();
        $tradingStrategy->execute();
    }
}
