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
 */
class Strategy2 extends AbstractStrategy
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
        $stick = $this->apiGetCurrentStick();
        $myLastBuyOrder = $this->apiGetMyLastOrder();

        if ($this->isTriggeredRise($stick->getCurrentPrice(), $myLastBuyOrder->getPrice(), '2%')) {
            $this->apiConditionalSell($myLastBuyOrder->getQty(), $myLastBuyOrder->getPrice(), '+1%');
            $this->apiBuy(100);
        } elseif ($this->isTriggeredFall($stick->getCurrentPrice(), $myLastBuyOrder->getPrice(), '-2%')) {
            $this->apiConditionalBuy(100, $stick->getCurrentPrice(), '1%');
        }

        return true;
    }
}
