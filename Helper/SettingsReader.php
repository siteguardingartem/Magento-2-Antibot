<?php

namespace Siteguarding\Antibot\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class SettingsReader extends AbstractHelper
{
    public function __construct()
    {
        $this->config_file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'rules.txt';
    }

    public function get_config()
    {
        $rules = array();

        $content = file_get_contents($this->config_file);

        $allowed_ip_length = strlen('::ALLOWED_IP::');
        $blocked_ip_length = strlen('::BLOCKED_IP::');
        $allowed_bots_length = strlen('::ALLOWED_BOTS::');

        $pos_blocked_ips = strpos($content,'::BLOCKED_IP::') ;
        $pos_allowed_bots_ips = strpos($content,'::ALLOWED_BOTS::') ;
        $pos_blocked_ips_end = $pos_blocked_ips + $blocked_ip_length ;
        $pos_allowed_bots_ips_end = $pos_allowed_bots_ips + $allowed_bots_length;

        $rules['allowed_ip'] =  trim(substr($content, $allowed_ip_length, $pos_blocked_ips - $allowed_ip_length)) . PHP_EOL;
        $rules['blocked_ip'] =  trim(substr($content, $pos_blocked_ips_end, $pos_allowed_bots_ips - $pos_blocked_ips_end)) . PHP_EOL;
        $rules['allowed_bots'] =  trim(substr($content, $pos_allowed_bots_ips_end)) . PHP_EOL;

        return $rules;
    }

}