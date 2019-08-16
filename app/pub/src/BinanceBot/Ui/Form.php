<?php
/**
 * Form
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

namespace App\BinanceBot\Ui;

class Form
{
    public $strategyConfig;

    public $requestParameters;

    public function generate()
    {
        $form = [];
        foreach ($this->strategyConfig as $name => $value) {
            $form[] = sprintf('<input type="text" value="%s" name="%s">%s', $value, $name, $name);
        }

        $form[] = sprintf('Date from-to: <input value="Y-m-d H:i" placeholder="Y-m-d H:i" disabled>-<input value="Y-m-d H:i" placeholder="Y-m-d H:i" disabled>');
        $form[] = sprintf('Use Test data: <input value="0" name="use_test_data">');

        return implode('<br>', $form);
    }
}
