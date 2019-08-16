<?php
/**
 * Logger
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance;

use App\BinanceBot\Binance\Data\DataObject;

/**
 * Class Logger
 */
class BotLogger
{
    const ORDER_LIMIT_SELL = 'order_limit_sell';
    const ORDER_LIMIT_BUY = 'order_limit_buy';
    const ORDER_STOP_LIMIT_BUY = 'order_stop_limit_buy_active';
    const ORDER_STOP_LIMIT_SELL = 'order_stop_limit_sell_active';
    const ORDER_STOP_LIMIT_COMPLETE = 'order_stop_limit_complete';

    const BALANCE = 'balance';
    const CHANGE = 'change';
    const CURRENT_PRICE = 'current_price';

    private $logs;

    private $currentDate;

    public  $ordersLog;
    public $logsByInterval;

    public function logOrder(DataObject $observer)
    {
        /** @var Order $order */
        $order = $observer->getOrder();
        $key = $this->getLogKey($order);
        $this->ordersLog[$key][$this->getInterval()][] = $order->toArray();
        $this->logsByInterval[$this->getInterval()][$key][] = (string) $order;
    }

    /**
     * @param Order $order
     * @return int|string
     */
    private function getLogKey(Order $order)
    {
        if (get_class($order) === Order::class) {
            if ($order->getSide() === 'BUY') {
                $key = self::ORDER_LIMIT_BUY;
            } elseif ($order->getSide() === 'SELL') {
                $key  = self::ORDER_LIMIT_SELL;
            }
        } elseif (get_class($order) === StopLimit::class) {
            if ($order->getStatus() === StopLimit::STATUS_STOP_LIMIT_COMPLETED) {
                $key = self::ORDER_STOP_LIMIT_COMPLETE;
            } elseif ($order->getSide() === 'BUY') {
                $key = self::ORDER_STOP_LIMIT_BUY;
            } elseif ($order->getSide() === 'SELL') {
                $key  = self::ORDER_STOP_LIMIT_SELL;
            }
        } else {
            $key = '';
        }

        return $key;

    }

    /**
     * @param $interval
     * @param $price
     * @param $message
     */
    public function log($price, $message): void
    {
//        if (isset($this->logs[$this->currentInterval])) {
        $this->logs[$this->getInterval()][] = ['value' => (float)$price, 'messages' => $message];
//        }

        $isCli = php_sapi_name() === 'cli';
        if ($isCli) {
            $message .= PHP_EOL;
            print($message);
            flush();
            ob_flush();
        }
    }

    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param mixed $currentDate
     */
    public function setInterval($currentDate)
    {
        $this->currentDate = $currentDate;
    }

    public function getInterval(): string
    {
        return (string) $this->currentDate;
    }

    public function logBalance(DataObject $observer)
    {
        $balanceCollection = $observer->getBalanceCollection();

        $key = static::BALANCE;
        $this->logsByInterval[$this->getInterval()][$key][] = (string) $balanceCollection;
    }

    public function logChange($value)
    {
        $key = static::CHANGE;
        $this->logsByInterval[$this->getInterval()][$key][] = $value;
    }
    public function logCurrentPrice($value)
    {
        $key = static::CURRENT_PRICE;
        $this->logsByInterval[$this->getInterval()][$key] = (float) $value;
    }
}
