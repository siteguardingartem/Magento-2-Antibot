<?php

namespace Siteguarding\Antibot\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class LogReader extends AbstractHelper
{

    public function remove_old_logs()
    {
        $actual_files = [
            date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 6, date("Y"))) . '.log',
            date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 5, date("Y"))) . '.log',
            date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 4, date("Y"))) . '.log',
            date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 3, date("Y"))) . '.log',
            date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 2, date("Y"))) . '.log',
            date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"))) . '.log',
            date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '.log'
        ];

        $log_files = scandir(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'logs');

        $system_folders = ['.', '..'];

        $result = array_diff($log_files, $actual_files, $system_folders);

        foreach ($result as $filename) {
            unlink(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $filename);
        }
    }

    public function get_logs($type)
    {
        $result = [];
        $result_tmp = [];

        for ($i = 6; $i >= 0; $i--) {
            $result[date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $i, date("Y")))] = array();
        }

        switch ($type) {
            case 'table':
                foreach ($result as $key => $value) {
                    $file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $key . '.log';
                    if (file_exists($file)) {
                        $result_tmp[] = file($file);
                    } else {
                        $result_tmp[] = array();
                    }
                }
                $result_tmp = call_user_func_array('array_merge', $result_tmp);
                $result = array(
                    'bots' => array(),
                    'pages' => array()
                );
                foreach ($result_tmp as $value) {
                    $value_tmp = explode(' | ', $value);

                    //bots
                    if (array_key_exists($value_tmp[0], $result['bots'])) {
                        $result['bots'][$value_tmp[0]]['total']++;
                        if (trim($value_tmp[2]) == 'block') {
                            $result['bots'][$value_tmp[0]]['block']++;
                        } else {
                            $result['bots'][$value_tmp[0]]['active']++;
                        }
                    } else {
                        $result['bots'][$value_tmp[0]] = array(
                                'total' => 1,
                                'active' => 0,
                                'block' => 0,
                        );
                        if (trim($value_tmp[2]) == 'block') {
                            $result['bots'][$value_tmp[0]]['block'] = 1;
                        } else {
                            $result['bots'][$value_tmp[0]]['active'] = 1;
                        }
                    }

                    //pages
                    if (array_key_exists($value_tmp[1], $result['pages'])) {
                        $result['pages'][$value_tmp[1]]['total']++;
                        if (trim($value_tmp[2]) == 'block') {
                            $result['pages'][$value_tmp[1]]['block']++;
                        } else {
                            $result['pages'][$value_tmp[1]]['active']++;
                        }
                    } else {
                        $result['pages'][$value_tmp[1]] = array(
                            'total' => 1,
                            'active' => 0,
                            'block' => 0,
                        );
                        if (trim($value_tmp[2]) == 'block') {
                            $result['pages'][$value_tmp[1]]['block'] = 1;
                        } else {
                            $result['pages'][$value_tmp[1]]['active'] = 1;
                        }
                    }
                }
                break;
            case 'graph':
                foreach ($result as $key => $value) {
                    $file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $key . '.log';
                    if (file_exists($file)) {
                        $content = file_get_contents($file);
                        $block = substr_count($content, ' | block');
                        $allow = substr_count($content, ' | allow');
                        $result[$key] = array('block' => $block, 'allow' => $allow);
                    } else {
                        $result[$key] = array('block' => 0, 'allow' => 0);
                    }
                }
                break;
        }
        return $result;
    }

}