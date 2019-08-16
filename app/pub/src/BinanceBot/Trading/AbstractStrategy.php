<?php
/**
 * AbstactStrategy
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Trading;

use App\BinanceBot\Binance\CandlestickCollection;
use App\BinanceBot\Binance\Order;

/**
 * Class AbstractStrategy
 */
abstract class AbstractStrategy
{
    /**
     * @var array Raw data from binance api
     */
    protected $candlesticks;

    /**
     * @var Order[]
     */
    protected $orders;

    protected $config;

    protected $ordersHistory = [];

    /**
     * @param CandlestickCollection $candlesticks
     */
    abstract public function execute(CandlestickCollection $candlesticks);

    /**
     * @param $config
     */
    public function setConfig($config)
    {
        $config = array_map(function($v) { return (float) $v; }, $config);

        $this->config = array_merge($this->config, $config);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getOrderHistory()
    {
        return $this->ordersHistory;
    }
}
