<?php
/**
 * Binance
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance\Data\Collection;

use App\BinanceBot\Binance\API;
use App\BinanceBot\Binance\Data\AbstractCollection;
use App\BinanceBot\Binance\Data\DataObject;

/**
 * Class Binance
 */
abstract class BinanceCollection extends AbstractCollection
{
    /**
     * Binance api connection
     *
     * @var API
     */
    protected $_conn;

    public function __construct(API $connection)
    {
        $this->_conn = $connection;
    }

    /**
     * @return \Binance\API
     */
    public function getConnection()
    {
        return $this->_conn;
    }
}
