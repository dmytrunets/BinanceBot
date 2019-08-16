<?php
/**
 * StrategyOne
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Trading;

/**
 * Class StrategyOne
 */
class StrategyOne
{
    private $balance;

    private $strategy = '20% 20% 10% 20% 15% 15%';

    private $orders = [];

    public function __construct()
    {
        $this->balance['USDT'] = 100;
        $this->balance['BTC'] = 0;
    }

    public function execute()
    {
        $orders = [
            ['price' => 5100, 'amount' => 20, 'action' => 'buy']
        ];

        $entryPoint = 5100;
        $changesInPoints = '-10 -10 -10 -10 +10 +10 +10 +10 +30 +30 +10';
        $changesInPoints = explode(' ', $changesInPoints);
        $changesInPoints = array_map(function($value) { return (double) $value; }, $changesInPoints);

        $prices = [];
        $count = count($changesInPoints);
        $changesInPercent = [];

        for ($i = 0; $i < $count; $i++) {
            $previousPrice = $i === 0 ? $entryPoint : $prices[$i-1];
            $previousChangesInPercent = $i === 0 ? 0 : $changesInPercent[$i-1];
            $prices[$i] = $previousPrice + $changesInPoints[$i];
            $changesInPercent[$i] = $changesInPoints[$i] * 100 / $entryPoint;
            $changesInPercent[$i] += (double) $previousChangesInPercent;
        }

        var_dump($prices);

        foreach ($prices as $i => $price) {
            if ($changesInPercent[$i] > 0.8) {
                echo 'Sell +' . $changesInPercent[$i] . "\n";
            }

            if ($changesInPercent[$i] < 0 && abs($changesInPercent[$i]) > 0.8) {
                echo 'Buy ' . $changesInPercent[$i] . "\n";
            }
        }
    }

    private function partBalance()
    {
        $parts = str_replace('%', '', $this->strategy);
        $parts = explode(' ', $parts);

        $amounts = [];
        foreach ($parts as $persent) {
            $amounts[] = $this->balance['USDT'] * $persent / 100;
        }
    }

    public function openTrade()
    {
        $data = [
            'amount_buy' => 100,
            'price_buy' => 4700,
            'amount_sell' => 0,
            'price_sell' => 0,
            'diff' => 0,
            'status' => 'open'
        ];
    }
}
