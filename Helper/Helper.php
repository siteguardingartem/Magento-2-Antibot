<?php

namespace Siteguarding\Antibot\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Helper extends AbstractHelper
{
    public function get_auto_prepend_file()
    {

        $auto_prepend_file = ini_get('auto_prepend_file');

        if ($auto_prepend_file == '') {
            return 'enable';
        } elseif (stripos($auto_prepend_file, 'Antibot.php') === false) {
            return 'other';
        }
    }
}