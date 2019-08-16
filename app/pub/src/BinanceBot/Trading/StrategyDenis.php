<?php
/**
 * StrategyOne
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Trading;

use App\BinanceBot\Binance\BalanceCollection;
use App\BinanceBot\Binance\BalanceService;
use App\BinanceBot\Binance\BotLogger;
use App\BinanceBot\Binance\Candlestick;
use App\BinanceBot\Binance\CandlestickCollection;
use App\BinanceBot\Binance\Data\DataObject;
use App\BinanceBot\Binance\Order;
use App\BinanceBot\Binance\OrderService;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * Class StrategyDenis
 * @todo: log for showing on chart
 */
class StrategyDenis extends AbstractStrategy
{
    private const DEFAULT_QTY = 100;

    private $currentOpen;
    private $currentCandlestick;
    private $previous;
    private $lastBuyQtyBTC;

    private $currentInterval;
    /**
     * @var OrderService
     */
    public $orderService;
    /**
     * @var BotLogger
     */
    private $logger;
    /**
     * @var BalanceCollection
     */
    private $balanceCollection;
    private $listeners;

    /**
     * @param OrderService                              $orderService
     * @param BotLogger                                 $logger
     * @param BalanceCollection $balanceCollection
     */
    public function __construct(
        OrderService $orderService,
        BotLogger $logger,
        BalanceCollection $balanceCollection
    ) {
        $this->config = [
            /**
             * Limit If change between interval exceed x% need to buy next part
             */
            'STEP_PERCENT'           => 2,

            /**
             * Limit if change between interval -x% . STOP LIMIT from current price in %
             */
            'SUB_PERCENT_FOR_STOP_LOSS' => 1,

            'THRESHOLD_FOR_INCREASE_STOP_LOSS_PRICE' => 1,

            /**
             * Example: your deposit 500 USDT. MAX_COUNT_PARTS = 5. 500 / 5 = 100 USDT in one part
             */
            'BUY_QTY_PART'              => 100
        ];

        $this->orderService = $orderService;
        $this->logger = $logger;
        $this->balanceCollection = $balanceCollection;

        $this->listeners['iteration_after'] = [
            ['class' => $this->orderService, 'method' => 'completeNewOrders'],
            ['class' => $this, 'method' => 'logState']
        ];
    }

    /**
     * @inheritdoc
     */
    public function execute(CandlestickCollection $candlesticksCollection)
    {
        $this->dispatch('strategy_before', ['object' => $this]);
        $this->candlesticks = array_values($candlesticksCollection->getRawData());

        for ($i = 0; $i < $candlesticksCollection->getSize(); $i++) {
            $this->dispatch('iteration_before', ['object' => $this]);
            $this->initializeState($this->candlesticks, $i);

            if ($i === 0) {
                $this->orderService->createOrder('BUY', $this->config['BUY_QTY_PART'], current($this->candlesticks)['open'], current($this->candlesticks)['openTime']);
                $this->lastBuyQtyBTC = $this->config['BUY_QTY_PART'] / $this->currentOpen;
                $this->dispatch('iteration_after', ['object' => $this]);
                continue;
            }

            $changeInPercent = $this->getChangeInPercent();
            $this->log('Change in %: ' . $changeInPercent);

            // @todo: fix execute stop loss logic
            $this->orderService->executeStopLossOrders(new Candlestick($this->currentCandlestick), $changeInPercent);

            if ($this->isExceedUpThreshold($changeInPercent) && $this->canBuy()) {
                $sellPrice = $this->subPercent($this->currentOpen, $this->config['SUB_PERCENT_FOR_STOP_LOSS']);
                $this->orderService->createStopLimit('SELL', $this->lastBuyQtyBTC, $sellPrice, $this->currentInterval, $this->currentOpen);
                $this->orderService->createOrder('BUY', $this->config['BUY_QTY_PART'], $this->currentOpen, $this->currentInterval);

                $this->lastBuyQtyBTC = $this->config['BUY_QTY_PART'] / $this->currentOpen;
            }

            if ($this->isUpdateStopLimitPriceNeeded($changeInPercent) && !$this->canBuy()) {
                $this->updateStopLossOrders('increasePrice');
            }

            if ($this->isExceedDownThreshold($changeInPercent)) {
                // todo: incorrect condition
                if ($this->countStopLimitOrderBUY() < 4) {
                    $buyPrice = $this->addPercent($this->currentOpen, $this->config['SUB_PERCENT_FOR_STOP_LOSS']);

                    $this->orderService->createStopLimit('BUY', $this->config['BUY_QTY_PART'], $buyPrice, $this->currentInterval, $this->currentOpen);
                } else {
                    $this->updateStopLossOrders('decreasePrice');
                }
            }

            $this->dispatch('iteration_after', ['object' => $this]);
        }

        $this->dispatch('strategy_after', ['object' => $this]);

        return true;
    }

    /**
     * @param DataObject $observer
     * @todo: move to logger class
     */
    public function logState(DataObject $observer)
    {
        /** @var StrategyDenis $strategy */
        $strategy = $observer->getObject();
        $orders = $strategy->orderService->getActiveStopLimits();
        foreach ($orders as $order) {
            $strategy->logger->logOrder(new DataObject(['order' => $order]));
        }

        $this->balanceCollection->setEstimationPrice(['BTCUSDT' => $this->currentOpen]);

        $strategy->logger->logBalance(new DataObject(['balance_collection' => $this->balanceCollection]));
        $this->logger->logChange(number_format($this->getChangeInPercent(), 2) . sprintf('(%s)', $this->orderService->getLastOrderPrice()));
        $strategy->logger->logCurrentPrice($this->currentOpen);
        $this->log((string) $this->balanceCollection);
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
     * @param $message
     */
    private function log($message): void
    {
        $this->logger->log($this->currentOpen, $message);
    }

    /**
     * @param $candlesticks
     * @param $currentIndex
     */
    private function initializeState($candlesticks, $currentIndex)
    {
        $this->currentCandlestick = $candlesticks[$currentIndex];
        $this->currentOpen = $candlesticks[$currentIndex]['open'];
        if ($currentIndex > 0) {
            $this->previous = $candlesticks[$currentIndex - 1]['open'];
        }
        $this->currentInterval = $candlesticks[$currentIndex]['openTime'];
        $this->logger->setInterval($this->currentInterval);

        $this->log('--------- Interval: ' . $currentIndex . '--------------');
        $this->log('Current price: ' . $this->currentOpen);
    }

    /**
     * Get change in percent
     *
     * @return float
     */
    private function getChangeInPercent(): float
    {
        $changeAbsolute = $this->currentOpen - $this->orderService->getLastOrderPrice();
        $this->log('c: ' . $this->currentOpen . ' - p: ' . $this->orderService->getLastOrderPrice() . ' = ' . $changeAbsolute);


        $this->changeInPercent = $changeAbsolute * 100 / $this->orderService->getLastOrderPrice();
        $this->logger->logChange(number_format($this->changeInPercent, 2) . sprintf('(%s)', $this->orderService->getLastOrderPrice()));

        return $this->changeInPercent;
    }

    /**
     * @param $changeInPercent
     *
     * @return bool
     */
    private function isExceedUpThreshold($changeInPercent): bool
    {
        return $changeInPercent >= $this->config['STEP_PERCENT'];
    }

    private function canBuy(): bool
    {
        $balance = $this->balanceCollection->getByAsset('USDT');
        if (isset($balance)) {
            $result = $balance->getAvailable() >= $this->config['BUY_QTY_PART'];
        }

        if (!$result) {
            $this->log('canBuy=false. Reason not enough USDT on balance');
        }

        return $result;
    }

    /**
     * Get value
     *
     * @param $value
     * @param $percent
     *
     * @return float
     */
    protected function subPercent($value, $percent): float
    {
        $result = $value * $percent / 100;

        return $value - $result;
    }

    protected function addPercent($value, $percent): float
    {
        $result = $value * $percent / 100;

        return $value + $result;
    }


    /**
     * @param $changeInPercent
     *
     * @return bool
     */
    protected function isUpdateStopLimitPriceNeeded($changeInPercent): bool
    {
        return $changeInPercent >= $this->config['THRESHOLD_FOR_INCREASE_STOP_LOSS_PRICE'];
    }

    /**
     * @param $action 'increase', 'decrease'
     * @see \App\BinanceBot\Binance\Order::increasePriceAction
     * @see \App\BinanceBot\Binance\Order::decreasePriceAction
     * @todo: fix update
     *
     * @throws \Exception
     */
    private function updateStopLossOrders($action)
    {
        if (!in_array($action, ['increasePrice', 'decreasePrice'])) {
            throw new \Exception('Error: use increase or decrease type instead ' . $action);
        }
        $this->log('--Update all STOP LOSS orders--');

        foreach ($this->orderService->getActiveStopLimits() as $i => $stopLimit) {
            $percent = ($i + 1) * $this->config['SUB_PERCENT_FOR_STOP_LOSS'];
            $price = $this->subPercent($this->currentOpen, $percent);
            /** @var Order $stopLimit */
            if ($stopLimit->getType() === Order::TYPE_STOP_LOSS_LIMIT && $stopLimit->getCreatedDate() !== $this->currentInterval) {

                $stopLimit->setPrice($price);
//                $method = $action . 'Action';
//                $stopLimit->$method($this->config['SUB_PERCENT_FOR_STOP_LOSS']);
            }
        }
    }

    private function isExceedDownThreshold($changeInPercent)
    {
        if ($changeInPercent > 0) {
            return false;
        }

        return abs($changeInPercent) >= $this->config['STEP_PERCENT'];
    }

    private function countStopLimitOrderBUY():int
    {
        $count = 0;
        foreach ($this->orderService->getOrders() as $order) {
            /**
             * @var Order $order
             */
            if ($order->getType() === Order::TYPE_STOP_LOSS_LIMIT && $order->getSide() === 'BUY') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return mixed
     */
    public function getLogs()
    {
        return $this->logger->getLogs();
    }

    /**
     * @return Order[]
     */
    public function getOrders()
    {
        return $this->orderService->getOrders();
    }
}
