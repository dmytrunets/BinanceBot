<?php
/**
 * TableFormatter
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Result;

use App\BinanceBot\Binance\BotLogger;

/**
 * Format result in table view
 */
class TableFormatter
{
    private $logger;

    /**
     * @param mixed $logger
     */
    public function setLogger($logger): void
    {
        $this->logger = $logger;
    }

    public function getResult()
    {
        $content[] = '<table border="1" width="100%">';
        $content[] = '<tr>
<td>Iteration</td>
<td>Date</td>
<td>Change, %</td>
<td>Current price</td>
<td>Order limit SELL</td>
<td>Order limit BUY</td>
<td>Order STOP LIMIT actual</td>
<td>Order STOP LIMIT completed</td>
<td>Balance</td>
</tr>';

        foreach ($this->logger->logsByInterval as $interval => $data) {
            $content[] = '<tr>
<td>'.$interval.'</td>
<td>'.date('Y-m-d H:i:s', $interval/1000).'</td>
<td>'.$this->toStringChange($data, BotLogger::CHANGE) .'</td>
<td>'.number_format($data[BotLogger::CURRENT_PRICE], 2, null, '').'</td>
<td>'.$this->toStringOrder($data, BotLogger::ORDER_LIMIT_SELL).'</td>
<td>'.$this->toStringOrder($data, BotLogger::ORDER_LIMIT_BUY).'</td>
<td>'.$this->toStringOrder($data, [BotLogger::ORDER_STOP_LIMIT_SELL, BotLogger::ORDER_STOP_LIMIT_BUY]).'</td>
<td>'.$this->toStringOrder($data, BotLogger::ORDER_STOP_LIMIT_COMPLETE).'</td>
<td>'.$this->toStringBalance($data, BotLogger::BALANCE).'</td>
</tr>';
        }

        $content[] = '</table>';

        return implode('', $content);
    }

    private function toStringChange($logData, $key)
    {
        if (!isset($logData[$key][0])) {
            return;
        }

        $content = [];
        foreach (array_unique($logData[$key])as $change) {
            $content[] = str_replace("\n", '<br><hr>', $change);
        }


        return implode('<br><hr>', $content);
    }

    private function toStringOrder($logData, $keys)
    {
        if (is_scalar($keys)) {
            $keys = [$keys];
        }

        $content = [];
        foreach ($keys as $key) {
            if (!isset($logData[$key])) {
                continue;
            }

            foreach ($logData[$key] as $i => $order) {
                $content[] = (string) $order;
            }
        }


        return implode('<br><hr>', $content);
    }

    public function toStringBalance($logData, $key)
    {
        if (!isset($logData[$key][0])) {
            return;
        }

        $content = [];
        foreach ($logData[$key] as $balance) {
            $content[] = str_replace("\n", '<br><hr>', (string) $balance);
        }


        return implode('<br><hr>', $content);
    }
}
