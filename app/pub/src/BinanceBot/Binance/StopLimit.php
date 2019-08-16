<?php
/**
 * StopLimit
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance;

/**
 * Class StopLimit
 */
class StopLimit extends Order
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->setStatus(static::STATUS_STOP_LIMIT_ACTIVE);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Stop loss %s %s price: %s qty: %s (%s)', $this->getType(), $this->getSide(), $this->getPrice(), $this->getQuantity(), $this->getData('executeType'));
    }
}
