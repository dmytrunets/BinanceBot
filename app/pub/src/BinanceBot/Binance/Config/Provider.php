<?php
/**
 * Provider
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance\Config;

/**
 * Class Config Provider
 */
class Provider
{
    private const TRANSACTION_FEE = 0.075;

    /**
     * @link https://settle.finance/blog/binance-fees-info/
     */
    public function getTransactionFee(): float
    {
        return static::TRANSACTION_FEE;
    }
}
