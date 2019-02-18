<?php

$rules_path = __DIR__ . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'rules.txt';

if (!file_exists($rules_path)) {
    return;
} else {

    $rules = array();
    $config = array();

    $rows = file($rules_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $section = '';

    foreach ($rows as $row) {

        $row = trim($row);

        if ($row == '::ALLOWED_IP::') {
            $section = 'ALLOW_ALL_IP';
            continue;
        }
        if ($row == '::BLOCKED_IP::') {
            $section = 'BLOCK_ALL_IP';
            continue;
        }
        if ($row == '::ALLOWED_BOTS::') {
            $section = 'ALLOWED_BOTS';
            continue;
        }

        if (strlen($row) == 0) continue;
        if ($section == '') continue;

        switch ($section) {
            case 'ALLOW_ALL_IP':
            case 'BLOCK_ALL_IP':
                $rules[$section][] = str_replace(array(".*.*", ".*"), ".", trim($row));
                break;
            case 'ALLOWED_BOTS':
                $tmp = explode("|", $row);
                $rules[$section][trim($tmp[0])] = explode(",", str_replace(array(".*.*", ".*", " "), array(".", ".", ""), $tmp[1]));
                break;
            default:
                continue;
                break;
        }
    }

    $config['host'] = isset($_SERVER['HTTP_HOST']) ? preg_replace("/[^0-9a-z-.:]/", "", $_SERVER['HTTP_HOST']) : '';
    $config['useragent'] = isset($_SERVER['HTTP_USER_AGENT']) ? trim(strip_tags($_SERVER['HTTP_USER_AGENT'])) : '';
    $config['uri'] = trim(strip_tags($_SERVER['REQUEST_URI']));
    $config['referer'] = isset($_SERVER['HTTP_REFERER']) ? trim(strip_tags($_SERVER['HTTP_REFERER'])) : '/';

    if (isset($_SERVER['HTTP_FORWARDED'])) {
        $config['ip'] = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $config['ip'] = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $config['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $config['ip'] = $_SERVER['HTTP_X_FORWARDED'];
    } else {
        $config['ip'] = $_SERVER['REMOTE_ADDR'];
    }

    $config['ip'] = strip_tags($config['ip']);

    if (mb_stripos($config['ip'], ',', 0, 'utf-8') !== false) {
        $config['ip'] = explode(',', $config['ip']);
        $config['ip'] = trim($config['ip'][0]);
    }
    if (mb_stripos($config['ip'], ':', 0, 'utf-8') !== false) {
        $config['ip'] = explode(':', $config['ip']);
        $config['ip'] = trim($config['ip'][0]);
    }
    $config['ip'] = preg_replace("/[^0-9.]/", "", $config['ip']);

    if ($config['useragent'] == '') {
        save_logs('block');
        return;
    }

    if (filter_var($config['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false){ return; } 

    foreach ($rules['BLOCK_ALL_IP'] as $block_ip) {
        if (stripos($config['ip'], $block_ip) !== false) {
            save_logs('block');
            return;
        }
    }

    $config['whitebot'] = 0;

    foreach ($rules['ALLOW_ALL_IP'] as $allow_ip) {
        if (stripos($config['ip'], $allow_ip) !== false) {
            save_logs('allow');
            $config['whitebot'] = 1;
        }
    }

    if ($config['whitebot'] == 0) {
        foreach ($rules['ALLOWED_BOTS'] as $signature => $records) {
            if (mb_stripos($config['useragent'], $signature, 0, 'utf-8') !== false) {
                $config['whitebot'] = 1;
                break;
            }
        }

        if ($config['whitebot'] == 1) {
            $config['whitebot'] = 0;
            $config['ptr'] = gethostbyaddr($config['ip']);
            foreach ($records as $record) {
                if (mb_stripos($config['ptr'], $record, 0, 'utf-8') !== false) {
                    if ($record != '.') {
                        $fp_rules_tmp = file($rules_path);
                        foreach ($fp_rules_tmp as &$row) {
                            $row = trim($row);
                            if ($row == "::ALLOWED_IP::") {
                                $row = trim($row);
                                $row .= PHP_EOL . $config['ip'];
                            }
                        }
                        $fp_rules  = fopen($rules_path,'w');
                        fwrite($fp_rules, implode("\n", $fp_rules_tmp));
                        fclose($fp_rules);
                    }
                    $config['whitebot'] = 1;
                    save_logs('allow');
                    break;
                }
            }
        }
    }

    $config['antibot_ok'] = md5($config['host'] . $config['useragent'] . $config['ip']);

    $config['antibot'] = isset($_COOKIE['antibot']) ? trim($_COOKIE['antibot']) : '';

    if ((filter_input(INPUT_POST,'submit') !== NULL) && (filter_input(INPUT_POST,'antibot') !== NULL)) {
        $config['antibot'] = filter_input(INPUT_POST,'antibot');
        setcookie('antibot', $config['antibot'], time() + 86400, '/', $config['host']);
        if (!isset($config['ptr'])) {
            $config['ptr'] = gethostbyaddr($config['ip']);
        }
    }

    if ($config['whitebot'] == 0 AND $config['antibot_ok'] != $config['antibot']) {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Robots-Tag: noindex');
        header('X-Frame-Options: DENY');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'antibot_tpl.txt');
        save_logs('block');
        return;
    }
}

function save_logs($status)
{
    global $config;

    $separator = ' | ';

    $line = $config['useragent'] . $separator . $config['uri'] . $separator . $status . PHP_EOL;

    $fp = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . date("Y-m-d") . '.log', 'a');
    fwrite($fp, $line);
    fclose($fp);

}