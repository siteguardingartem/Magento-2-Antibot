<?php

namespace Siteguarding\Antibot\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class SettingsWriter extends AbstractHelper
{
    public function __construct()
    {
        $this->config_file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'rules.txt';
    }

    public function write_config($settings)
    {
        $content = '::ALLOWED_IP::' . PHP_EOL . trim($settings['allowed_ip']) . PHP_EOL . PHP_EOL . '::BLOCKED_IP::' . PHP_EOL .  trim($settings['blocked_ip']) . PHP_EOL . PHP_EOL . '::ALLOWED_BOTS::' . PHP_EOL . trim($settings['allowed_bots']) . PHP_EOL . PHP_EOL;

        file_put_contents($this->config_file, $content);
    }
}