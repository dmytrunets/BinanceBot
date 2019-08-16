<?php
/**
 * BalanceCollection
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance;

use App\BinanceBot\Binance\Data\Collection\BinanceCollection;
use App\BinanceBot\Binance\Data\DataObject;

/***
 * Class BalanceCollection
 */
class BalanceCollection extends BinanceCollection
{
    /**
     * Retrieve collection empty item
     *
     */
    public function getNewEmptyItem(): DataObject
    {
        return new Balance();
    }

    protected function getData()
    {
        return $this->getConnection()->balances();
    }

    protected function _getItemId(DataObject $item)
    {
        return $item->getData('asset');
    }

    public function getByAsset($asset): Balance
    {
        return $this->items[$asset] ?? null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $result = [];

        /** @var \App\BinanceBot\Binance\Balance $balance */
        foreach ($this->getItems() as $balance) {
            $result[] = 'Balance ' . $balance->getAsset() . ' total: ' . $balance->getTotalBalance() . ' available: ' . $balance->getAvailable() . ' onOrder: ' . (real)$balance->getOnOrder() . $this->estimateTotal($balance);
        }

        $total = $this->items['USDT']->getTotalBalance() + $this->items['BTC']->getTotalBalance() * $this->getPriceForEstimation('BTCUSDT');
        $result[] = 'Estimated total: ' . number_format($total, 2) . '$';

        return implode("\n", $result);
    }

    public function estimateTotal($balance)
    {
        $result =  '';
        if ($balance->getAsset() == 'BTC') {
            $lastPrice = $this->getPriceForEstimation('BTCUSDT');
            $result = ' Estimate total in USDT: ' . number_format($balance->getTotalBalance() * $lastPrice, 2);
        }

        return $result;
    }

    public function setEstimationPrice($data)
    {
        $this->estimationPrice = $data;
    }

    private function getPriceForEstimation($symbol)
    {
        return $this->estimationPrice[$symbol];
    }
}
