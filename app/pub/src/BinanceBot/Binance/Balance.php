<?php
/**
 * Balance
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance;

use App\BinanceBot\Binance\Data\DataObject;

/**
 * Class Balance
 *
 * @method float getAvailable()
 * @method Balance setAvailable(float $value)
 * @method float getOnOrder()
 * @method Balance setOnOrder(float $value)
 * @method float getBtcValue()
 * @method float getBtcTotal()
 * @method string getAsset()
 * @method string setAsset(string $value)
 */
class Balance extends DataObject
{
    public function getTotalBalance()
    {
        return $this->getAvailable() + $this->getOnOrder();
    }
}
