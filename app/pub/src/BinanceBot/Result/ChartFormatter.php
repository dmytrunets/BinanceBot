<?php
/**
 * LoggerFormatter
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Result;

use App\BinanceBot\Binance\BotLogger;
use App\BinanceBot\Binance\Data\DataObject;

/**
 * Format logs to chart format
 */
class ChartFormatter extends BotLogger
{
    /**
     * @var BotLogger
     */
    private $logger;

    /**
     * @param BotLogger $logger
     */
    public function setLogger(BotLogger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param $key
     * @return array
     */
    public function getMyOrders($key): array
    {
        if (!isset($this->logger->ordersLog[$key])) {
            return [];
        }
        $content = [];
        $values = [];
        /** @var \App\BinanceBot\Binance\Order $order */
        foreach ($this->logger->ordersLog[$key] as $interval => $orders) {
            foreach ($orders as $order) {
                $order = new DataObject($order);
                $values[] = [$interval, $order->getPrice()];
                $content[] = 'Order: ' . $order->getSide() . ' Price:' . $order->getPrice() . ' Qty:' . $order->getQuantity() . ' - ' . $order->getType();
            }
        }

        return ['values' => $values, 'content' => $content];
    }
}
