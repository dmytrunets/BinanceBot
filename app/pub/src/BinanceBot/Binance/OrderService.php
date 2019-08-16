<?php
/**
 * OrderService
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance;

use App\BinanceBot\Binance\Data\DataObject;

/**
 * Class OrderService
 */
class OrderService
{
    /**
     * @var BotLogger
     */
    private $logger;

    /**
     * @var Order[]
     */
    private $orders;

    private $listeners;
    /**
     * @var BalanceService
     */
    private $balanceService;

    /**
     * @var StopLimit[]
     */
    private $stopLimitItems = [];

    /**
     * @var float
     */
    private $lastActionPrice;

    /**
     * OrderService constructor.
     *
     * @param BotLogger      $logger
     * @param BalanceService $balanceService
     */
    public function __construct(BotLogger $logger, BalanceService $balanceService)
    {
        $this->logger = $logger;
        $this->balanceService = $balanceService;

        $this->listeners['order_complete'] = [
            ['class' => $balanceService, 'method' => 'updateBalance'],
            ['class' => $this, 'method' => 'updateLastActionPrice'],
        ];

        $this->listeners['stop_limit_order_complete'] = [
            ['class' => $balanceService, 'method' => 'updateBalance'],
            ['class' => $this, 'method' => 'updateLastActionPrice'],
            ['class' => $logger, 'method' => 'logOrder']
        ];

        $this->listeners['new_order_created'] = [
            ['class' => $balanceService, 'method' => 'updateBalance'],
            ['class' => $logger, 'method' => 'logOrder']
        ];

        $this->listeners['new_order_stop_limit_created'] = [
            ['class' => $balanceService, 'method' => 'updateBalance'],
//            ['class' => $logger, 'method' => 'logOrder']
        ];

    }

    public function createOrder($side, $quantity, $price, $createdInInterval)
    {
        $type = Order::TYPE_MARKET;

        if (!is_numeric($quantity) || $quantity <= 0) {
            throw new \InvalidArgumentException('Error: order qty should be > 0');
        }

        $data = [
            'side' => $side,
            'quantity' => (float) $quantity,
            'price' => (float) $price,
            'type' => $type,
            'createdDate' => $createdInInterval
        ];
        $order = new Order($data);
        if (!$this->balanceService->canCreateOrder($order)) {
            return;
            throw new \InvalidArgumentException('Can\'t create order validation is fails');
        }

        $this->orders[] = $order;
        $this->dispatch('new_order_created', ['order' => $order]);

        $order->setStatus(Order::STATUS_COMPLETE);
        $this->dispatch('order_complete', ['order' => $order]);
    }

    public function createStopLimit($side, $quantity, $price, $createdInInterval, $currentOpen): void
    {
        $type = Order::TYPE_STOP_LOSS_LIMIT;

        $data = [
            'side' => $side,
            'quantity' => (float) $quantity,
            'price' => (float) $price,
            'type' => $type,
            'createdDate' => $createdInInterval,
            'executeType' => $price > $currentOpen ? 'above': 'bellow'
        ];


        $order = new StopLimit($data);
        if (!$this->balanceService->canCreateOrder($order)) {
            return;
            throw new \InvalidArgumentException('Can\'t create order validation is fails');
        }

        $this->stopLimitItems[] = $order;
        $this->dispatch('new_order_stop_limit_created', ['order' => $order]);
    }

    /**
     * @return Order[]
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @return StopLimit[]
     */
    public function getActiveStopLimits(): array
    {
        $result = [];
        foreach ($this->stopLimitItems as $stopLimit) {
            if ($stopLimit->getStatus() === Order::STATUS_STOP_LIMIT_ACTIVE) {
                $result[] = $stopLimit;
            }
        }
        return $result;
    }

    public function completeNewOrders()
    {
        foreach ($this->getOrders() as $order) {
            if ($order->getStatus() === Order::STATUS_NEW) {
                $order->setStatus(Order::STATUS_COMPLETE);
                $this->dispatch('order_complete', ['order' => $order]);
            }
        }

        foreach ($this->getOrders() as $order) {
            if ($order->getStatus() === Order::STATUS_STOP_LIMIT_COMPLETED) {
                $order->setStatus(Order::STATUS_COMPLETE);
                $this->dispatch('order_complete', ['order' => $order]);
            }
        }
    }

    /**
     * @param Candlestick $currentCandlestick
     * @todo: extract class BinanceExecutor
     */
    public function executeStopLossOrders(Candlestick $currentCandlestick, $changeInPercent = 0)
    {
        /** @var StopLimit $stopLimit */
        foreach ($this->getActiveStopLimits() as $stopLimit) {
            // @todo: incorrect condition
            if ($this->isExecuted($stopLimit, $currentCandlestick->getOpen(), $stopLimit->getPrice(), $changeInPercent)) {
                $stopLimit->setStatus(Order::STATUS_STOP_LIMIT_COMPLETED);
                $this->dispatch('stop_limit_order_complete', ['order' => $stopLimit]);
                $this->logger->log(1, 'Stop limit executed: ' . $stopLimit);

            }
        }
    }

    public function isExecuted(StopLimit $stopLimit, $currentPrice, $orderPrice, $changeInPercent): bool
    {
        if ($stopLimit->getData('executeType') === 'above' && $currentPrice >= $orderPrice) {
            return true;
        } elseif ($stopLimit->getData('executeType') === 'bellow' && $currentPrice <= $orderPrice) {
            return true;
        }

        return false;
    }

    /**
     * @param string $eventName
     * @param array  $data
     */
    private function dispatch($eventName, array $data = [])
    {
        $eventName = mb_strtolower($eventName);

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $event = new DataObject($data);
            $object = $listener['class'];
            $method = $listener['method'];

            $object->$method($event);
        }
    }

    /**
     * @return float
     */
    public function getLastOrderPrice(): float
    {
        $orderPrice = end($this->orders)->getPrice();
        return $this->lastActionPrice ?? $orderPrice;
    }

    /**
     * @param $order
     */
    public function updateLastActionPrice($observer)
    {
        /** @var Order $order */
        $order = $observer->getOrder();

        if ($order->getSide() === 'BUY' || true) {
            $this->lastActionPrice = $order->getPrice();
        } else {
            $this->lastActionPrice = null;
        }
    }
}
