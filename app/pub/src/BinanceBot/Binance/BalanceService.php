<?php
/**
 * BalanceService
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance;

use App\BinanceBot\Binance\Config\Provider;

/**
 * Class BalanceService
 */
class BalanceService
{
    /**
     * @var BalanceCollection
     */
    private $balanceCollection;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var Provider
     */
    private $config;

    public function __construct(BalanceCollection $balanceCollection, Provider $config)
    {
        $this->balanceCollection = $balanceCollection;

        $balanceBTC = [
            'asset'     => 'BTC',
            'available' => 0.00000000,
            'onOrder'   => 0.00000000,
            'btcValue'  => 0.00000000,
            'btcTotal'  => 0.00000000,
        ];

        $balanceUSDT = [
            'asset'     => 'USDT',
            'available' => 500.00000000,
            'onOrder'   => 0.00000000,
            'btcValue'  => 0.00000000,
            'btcTotal'  => 0.00000000,
        ];

        $this->balanceCollection->addItem(new Balance($balanceBTC));
        $this->balanceCollection->addItem(new Balance($balanceUSDT));
        $this->config = $config;
    }

    /**
     *
     * @todo: implement transaction fee
     * Update Balance
     *
     * @param $observer
     *
     * @return bool|void
     */
    public function updateBalance($observer)
    {
        /** @var Order $order */
        $order = $observer->getOrder();

        if ($order->getStatus() === Order::STATUS_NEW || $order->getStatus() === StopLimit::STATUS_STOP_LIMIT_ACTIVE) {
            return $this->lockAssets($order);
        }

        if ($order->getStatus() !== Order::STATUS_COMPLETE && $order->getStatus() !== StopLimit::STATUS_STOP_LIMIT_COMPLETED) {
            return;
        }

        if ($order->getSide() === 'BUY') {
            $balanceFrom = $this->balanceCollection->getByAsset('USDT');
            $balanceTo = $this->balanceCollection->getByAsset('BTC');
            $addAmount = $order->getQuantity() / $order->getPrice();
        } elseif ($order->getSide() === 'SELL') {
            $balanceFrom = $this->balanceCollection->getByAsset('BTC');
            $balanceTo = $this->balanceCollection->getByAsset('USDT');
            $addAmount = $order->getQuantity() * $order->getPrice();
        }

//        $transactionFeeAmount = $addAmount * $this->config->getTransactionFee() / 100;

        if (!$balanceFrom || !$balanceTo) {
            return;
        }

        $currentAmount = $balanceFrom->getOnOrder();
        $subAmount = $order->getQuantity();
        $newValue = $currentAmount - $subAmount;

        if ($newValue < 0) {
            throw new \InvalidArgumentException('Balance can\'t be < 0');
        }
        $balanceFrom->setOnOrder($newValue);

        $currentAmount = $balanceTo->getAvailable();
        $transactionFeeAmount = 0;
        $balanceTo->setAvailable($currentAmount + $addAmount - $transactionFeeAmount);
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function canCreateOrder(Order $order): bool
    {
        if ($order->getSide() === 'BUY') {
            $asset = 'USDT';
        } elseif ($order->getSide() === 'SELL') {
            $asset = 'BTC';
        }

        $balance = $this->balanceCollection->getByAsset($asset);
        if ($order->getQuantity() > $balance->getAvailable()) {
            return false;
        }

        return true;
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function lockAssets(Order $order): bool
    {
        if ($order->getSide() === 'BUY') {
            $balanceFrom = $this->balanceCollection->getByAsset('USDT');
        } elseif ($order->getSide() === 'SELL') {
            $balanceFrom = $this->balanceCollection->getByAsset('BTC');
        }

        $newAvailable = $balanceFrom->getAvailable() - $order->getQuantity();
        $balanceFrom->setAvailable($newAvailable);

        $newOnOrder = $balanceFrom->getOnOrder() + $order->getQuantity();
        $balanceFrom->setOnOrder($newOnOrder);

        return true;
    }
}
