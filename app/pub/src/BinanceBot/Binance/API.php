<?php
/**
 * API
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance;

/**
 * Class API
 */
class API
{
    public const API_KEY = 'y6iKyuRBNlHhpzvGRs0g9mwjUlKIPFa1HsF5lPk8hWbjyEDBv95sS2y971Ta88ML';
    public const SECRET_KEY = '0zcli7DLNioR4d69Mp7fsSXu8cBqpIMhnE18WQJLpaei2E8O6aiS64lokIidmWox';
    private $api;

    public function __construct()
    {
        $this->api = new \Binance\API(static::API_KEY, static::SECRET_KEY);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->api, $name], $arguments);
    }
}
