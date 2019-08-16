<?php
/**
 * Collection
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Binance\Data;

/**
 * Class Abstract Collection
 */
abstract class AbstractCollection  implements \IteratorAggregate, \Countable
{
    protected $items = [];

    protected $data;

    private $_totalRecords;

    /**
     * @return array
     */
    public function getItems(): array
    {
        $this->load();
        return $this->items;
    }

    /**
     * Retrieve collection empty item
     *
     */
    abstract public function getNewEmptyItem(): DataObject;

    /**
     * Retrieve item id
     *
     * @param DataObject $item
     * @return mixed
     */
    protected function _getItemId(DataObject $item)
    {
        return $item->getId();
    }

    /**
     * Adding item to item array
     *
     * @param   DataObject $item
     * @return $this
     * @throws \Exception
     */
    public function addItem(DataObject $item)
    {
        $itemId = $this->_getItemId($item);
        $this->items[$itemId] = $item;

        return $this;
    }

    public function load()
    {
        if ($this->data === null) {
            $this->data = $this->getData();
        }

        if (is_array($this->data)) {
            foreach ($this->data as $value) {
                $object = $this->getNewEmptyItem()->setData($value);
                $this->addItem($object);
            }
        }

        return $this;
    }

    abstract protected function getData();

    /**
     * @return array
     */
    public function getRawData()
    {
        return $this->data;
    }

    /**
     * @param $data
     */
    public function setRawData($data)
    {
        $this->data = $data;
    }

    public function getFirstItem()
    {
        $this->load();

        if (count($this->items)) {
            reset($this->items);
            return current($this->items);
        }
    }

    /**
     * Implementation of \IteratorAggregate::getIterator()
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->load();
        return new \ArrayIterator($this->items);
    }

    /**
     * Retrieve count of collection loaded items
     *
     * @return int
     */
    public function count()
    {
        $this->load();
        return count($this->items);
    }

    /**
     * Retrieve collection all items count
     *
     * @return int
     */
    public function getSize()
    {
        $this->load();
        if ($this->_totalRecords === null) {
            $this->_totalRecords = count($this->getItems());
        }
        return (int) $this->_totalRecords;
    }

}
