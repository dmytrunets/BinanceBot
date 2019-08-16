<?php
/**
 * CandlestickCollection
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance;

use App\BinanceBot\Binance\Data\Collection\BinanceCollection;
use App\BinanceBot\Binance\Data\DataObject;

/**
 * Class CandlestickCollection
 */
class CandlestickCollection extends BinanceCollection
{
    /**
     * @var Candlestick[]
     */
    protected $items;
    private   $symbol;
    private   $interval;
    private   $limit;
    private   $startTime;
    private   $endTime;
    private   $useStub;

    /**
     * @return mixed
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @param mixed $symbol
     */
    public function setSymbol($symbol): void
    {
        $this->symbol = $symbol;
    }

    /**
     * @return mixed
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param mixed $interval
     */
    public function setInterval($interval): void
    {
        $this->interval = $interval;
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param mixed $limit
     */
    public function setLimit($limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param mixed $startTime
     */
    public function setStartTime($startTime): void
    {
        $this->startTime = $startTime;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param mixed $endTime
     */
    public function setEndTime($endTime): void
    {
        $this->endTime = $endTime;
    }

    /**
     * Retrieve collection empty item
     *
     */
    public function getNewEmptyItem(): DataObject
    {
        return new Candlestick();
    }

    protected function _getItemId(DataObject $item)
    {
        return $item->getData('openTime');
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getData(): array
    {
        if ($this->useStub) {
            return $this->data;
        }

        $rows = $this->getConnection()->candlesticks(
            $this->getSymbol(),
            $this->getInterval(),
            $this->getLimit(),
            $this->getStartTime(),
            $this->getEndTime()
        );

        $this->data = $rows;

        return $rows;
    }

    public function setRawDataFromFile($filePath)
    {
        $this->useStub = true;
        $content = json_decode(file_get_contents($filePath), true);
        $this->setRawData($content);
    }

}
