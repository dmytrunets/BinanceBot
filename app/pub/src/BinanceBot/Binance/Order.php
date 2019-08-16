<?php
/**
 * Copyright © InComm, Inc. All rights reserved.
 */

namespace App\BinanceBot\Binance;

use App\BinanceBot\Binance\Data\DataObject;

/**
 * Class Order
 *
 * @method float getQuantity()
 * @method float getPrice()
 * @method Order setPrice($value)
 * @method float getSide()
 * @method float getStatus()
 * @method Order setStatus($value)
 * @method float getSymbol()
 * @method float getCreatedDate()
 * @method Order setCreatedDate($value)
 */
class Order extends DataObject
{
    /**
     * @link https://www.binance.vision/tutorials/what-is-a-limit-order
     * @link https://www.binance.vision/tutorials/what-is-a-market-order
     * @link https://www.binance.vision/tutorials/what-is-a-limit-order
     * @link https://www.binance.vision/tutorials/what-is-a-stop-limit-order
     */
    public const TYPE_LIMIT              = 'LIMIT';
    public const TYPE_MARKET             = 'MARKET';
    public const TYPE_STOP_LOSS          = 'STOP_LOSS';
    public const TYPE_STOP_LOSS_LIMIT    = 'STOP_LOSS_LIMIT';
    public const TYPE_TAKE_PROFIT        = 'TAKE_PROFIT';
    public const TYPE_TAKE_PROFIT_LIMIT  = 'TAKE_PROFIT_LIMIT';
    public const TYPE_LIMIT_MAKER        = 'LIMIT_MAKER';

    private $stopLossTypes = [
        self::TYPE_STOP_LOSS_LIMIT,
        self::TYPE_TAKE_PROFIT_LIMIT
    ];
    private $logger;

    public const STATUS_NEW                  = 'new';
    public const STATUS_STOP_LIMIT_ACTIVE    = 'stop_limit_new';
    public const STATUS_STOP_LIMIT_COMPLETED = 'stop_limit_completed';
    public const STATUS_COMPLETE             = 'complete';

    /**
     * Order constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->setStatus(static::STATUS_NEW);
    }

    /**
     * Increase price for STOP LOSS order
     * @param $percent
     */
    public function increasePriceAction($percent)
    {
        $origPrice = $this->getPrice();
        $delta = $this->getPrice() * $percent / 100;
        $this->setPrice($this->getPrice() + $delta);
        $this->log('Update ' . $this ." Details: price was $origPrice become ". $this->getPrice() . " (+{$percent}%)");
    }

    /**
     * Increase price for STOP LOSS order
     * @param $percent
     */
    public function decreasePriceAction($percent)
    {
        $origPrice = $this->getPrice();
        $delta = $this->getPrice() * $percent / 100;
        $this->setPrice($this->getPrice() - $delta);

        $this->log('Update ' . $this ." Details: price was $origPrice become ". $this->getPrice() . " (-{$percent}%)");
    }

    /**
     * @param $message
     */
    private function log($message): void
    {
        // @todo: add dependency on BotLogger
        $isCli = php_sapi_name() === 'cli';
        if ($isCli) {
            $message .= PHP_EOL;
            print($message);
            flush();
            ob_flush();
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Order %s %s price: %s qty: %s', $this->getType(), $this->getSide(), $this->getPrice(), $this->getQuantity());
    }
}
