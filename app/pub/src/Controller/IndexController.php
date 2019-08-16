<?php

namespace App\Controller;

use App\BinanceBot\Binance\BalanceCollection;
use App\BinanceBot\Binance\BotLogger;
use App\BinanceBot\Binance\CandlestickCollection;
use App\BinanceBot\Binance\Order;
use App\BinanceBot\BinanceBot;
use App\BinanceBot\Ui\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class IndexController - Charts
 */
class IndexController extends AbstractController
{
    private $min;
    private $max;

    /**
     * @Route("/")
     */
    public function number(
        Request $request,
        \App\BinanceBot\Trading\StrategyDenis $strategy,
        \App\BinanceBot\Binance\API $api,
        \App\BinanceBot\Binance\CandlestickCollection $candlestickCollection,
        BotLogger $logger,
        \App\BinanceBot\Result\ChartFormatter $chartFormatter,
        \App\BinanceBot\Result\TableFormatter $tableFormatter
    ) {
        $form = new Form();
        $form->requestParameters = $request->query->all();
        $strategy->setConfig($request->query->all());
        $form->strategyConfig = $strategy->getConfig();

        $symbol = 'BTCUSDT';
        $interval = '1h';
        $limit = 50;

        $candlestickCollection->setSymbol($symbol);
        if ($request->query->get('use_test_data') == 1) {
            $candlestickCollection->setRawDataFromFile(__DIR__ . '/testdata.json');
        }
        $candlestickCollection->setInterval($interval);
        $candlestickCollection->setLimit($limit);
        $candlestickCollection->load();
        $rawData = $candlestickCollection->getRawData();

        $candles = $this->adaptValue($candlestickCollection);
        $strategy->execute($candlestickCollection);
        $orders = $strategy->getOrders();
        $orderHistory = $strategy->getOrderHistory();
        $logs = $strategy->getLogs();
        $chartFormatter->setLogger($logger);

        $data = [
            'candles'        => $candles,
            //            'range'          => $this->getStopLostsRangeData($rawData),
            'my_orders_limit_buy'  => $chartFormatter->getMyOrders(BotLogger::ORDER_LIMIT_BUY),
            'my_orders_limit_sell' => $chartFormatter->getMyOrders(BotLogger::ORDER_STOP_LIMIT_SELL),
            'my_orders_stop_limit_sell_actual' => $chartFormatter->getMyOrders(BotLogger::ORDER_STOP_LIMIT_SELL),
            'my_orders_stop_limit_buy_actual' => $chartFormatter->getMyOrders(BotLogger::ORDER_STOP_LIMIT_BUY),
            'my_orders_stop_limit_complete' => $chartFormatter->getMyOrders(BotLogger::ORDER_STOP_LIMIT_COMPLETE),
            //            'logs'           => $this->adoptLogs($logs),
            //            'order_history'  => $this->addOrderLog($orders),
            //            'order_stop_loss_executed' => $this->addOrderLog($orderHistory),
            'chart'          => [
                'symbol' => $symbol,
                'interval' => '1hour',
                'scale_y' => [
                    'values' => "{$this->getMin()}:{$this->getMax()}:2",
                ]
            ],
        ];

        $tableFormatter->setLogger($logger);
        $tableResult = $tableFormatter->getResult();

        return $this->render('lucky/number.html.twig', [
            'data' => json_encode($data),
            'logs' => implode('<br>', $this->adoptLogs($logs)['content']),
            'table_result' => $tableResult,
            'form' => $form->generate()
        ]);
    }

    /**
     * @param $logs
     * @return array
     */
    private function adoptLogs($logs)
    {
        $content = [];
        $values = [];
        foreach ($logs as $openTime => $data) {
            $values[] = [$openTime, $data[0]['value'] * 1.03];
            $messages = array_map(function($a) { return $a['messages'];}, $data);
            $content[] = implode('<br>', $messages);

        }

        return ['values' => $values, 'content' => $content];
    }

    /**
     * @return mixed
     */
    public function getMin()
    {
        return round($this->min);
    }

    /**
     * @return mixed
     */
    public function getMax()
    {
        return round($this->max) * 1.02;
    }

    /**
     * @param $candles
     *
     * @return array
     */
    private function adaptValue($candles)
    {
        $this->min = $candles->getFirstItem()->getHigh();
        $this->max = 0;
        $result = [];
        foreach ($candles as $openTime => $candle) {
            $value = [
                (real)$candle['open'],
                (real)$candle['high'],
                (real)$candle['low'],
                (real)$candle['close'],
            ];

            if ($this->min > (real)$candle['low']) {
                $this->min = (real)$candle['low'];
            }

            if ($this->max < (real)$candle['high']) {
                $this->max = (real)$candle['high'];
            }

            $result[] = [
                $openTime,
                $value
            ];
        }

        return $result;
    }

    private function getStopLostsRangeData($candles)
    {
        $result = [];
        foreach ($candles as $openTime => $candle) {
            $value = [
                (real)$candle['high'],
                (real)$candle['low'],
            ];

            $result[] = [
                $openTime,
                $value
            ];
        }

        return $result;
    }

    private function getMyOrders($candles, $side)
    {
        $result = [];
        foreach ($candles as $openTime => $candle) {
            if (rand(1, 10) % 2 === 0) {
                continue;
            }
            $x = 0.001;
            if ($side === 'BUY') {
                $value = [
                    (real)$candle['low'] * (1 + $x)
                ];
            } elseif ($side === 'SELL') {
                $value = [
                    (real)$candle['high'] * (1 - $x),
                ];
            }

            $result[] = [
                $openTime,
                $value
            ];
        }

        return $result;
    }
}
