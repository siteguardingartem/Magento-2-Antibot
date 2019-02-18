<?php

namespace Siteguarding\Antibot\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class AutoPrepend extends AbstractHelper
{

    public $rootPath = '';
    public $filePath = '';


    const APACHE = 1;
    const NGINX = 2;
    const LITESPEED = 4;
    const IIS = 8;


    private $handler;
    private $software;
    private $softwareName;
    private $dirsep;


    public function __construct()
    {

        $this->rootPath = str_replace(DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'Siteguarding' . DIRECTORY_SEPARATOR . 'Antibot' . DIRECTORY_SEPARATOR . 'Helper' . DIRECTORY_SEPARATOR . 'AutoPrepend.php' , '', __FILE__);
        $this->filePath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Antibot.php';

        $sapi = php_sapi_name();
        if (stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false) {
            $this->setSoftware(self::APACHE);
            $this->setSoftwareName('apache');
        }
        if (stripos($_SERVER['SERVER_SOFTWARE'], 'litespeed') !== false || $sapi == 'litespeed') {
            $this->setSoftware(self::LITESPEED);
            $this->setSoftwareName('litespeed');
        }
        if (strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false) {
            $this->setSoftware(self::NGINX);
            $this->setSoftwareName('nginx');
        }
        if (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer') !== false) {
            $this->setSoftware(self::IIS);
            $this->setSoftwareName('iis');
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->dirsep = '\\';
        } else {
            $this->dirsep = '/';
        }

        $this->setHandler($sapi);


    }

    public function isApache()
    {
        return $this->getSoftware() === self::APACHE;
    }


    public function isNGINX()
    {
        return $this->getSoftware() === self::NGINX;
    }


    public function isLiteSpeed()
    {
        return $this->getSoftware() === self::LITESPEED;
    }


    public function isIIS()
    {
        return $this->getSoftware() === self::IIS;
    }


    public function isApacheModPHP()
    {
        return $this->isApache() && function_exists('apache_get_modules');
    }


    public function isApacheSuPHP()
    {
        return $this->isApache() && $this->isCGI() &&
        function_exists('posix_getuid') &&
        getmyuid() === posix_getuid();
    }


    public function isCGI()
    {
        return !$this->isFastCGI() && stripos($this->getHandler(), 'cgi') !== false;
    }


    public function isFastCGI()
    {
        return stripos($this->getHandler(), 'fastcgi') !== false || stripos($this->getHandler(), 'fpm-fcgi') !== false;
    }


    public function getHandler()
    {
        return $this->handler;
    }


    public function setHandler($handler)
    {
        $this->handler = $handler;
    }


    public function getSoftware()
    {
        return $this->software;
    }


    public function setSoftware($software)
    {
        $this->software = $software;
    }


    public function getSoftwareName()
    {
        return $this->softwareName;
    }


    public function setSoftwareName($softwareName)
    {
        $this->softwareName = $softwareName;
    }


    public function getServerConfig()
    {
        if ($this->isApacheModPHP()) return 'apache-mod_php';
        if ($this->isApacheSuPHP()) return 'apache-suphp';
        if ($this->isLiteSpeed()) return 'cgi';
        if ($this->isApache() && !$this->isApacheSuPHP() && ($this->isCGI() || $this->isFastCGI())) return 'litespeed';
        if ($this->isNGINX()) return 'nginx';
        if ($this->isIIS()) return 'iis';

    }

    public function getHtaccessPath()
    {
        return str_replace(array("/", "\\"), $this->dirsep, $this->rootPath . $this->dirsep . '.htaccess');
    }

    public function getUserIniPath()
    {
        $userIni = ini_get('user_ini.filename');
        if ($userIni) {
            return str_replace(array("/", "\\"), $this->dirsep, $this->rootPath . $this->dirsep . $userIni);
        }
        return false;
    }

    public function getAntiBotFilePath()
    {
        return str_replace(array("/", "\\"), $this->dirsep, $this->filePath);
    }


    function setAutoPrepends($state = true)
    {

        $bootstrapPath = $this->getAntiBotFilePath();

        $serverConfig = $this->getServerConfig();

        $htaccessPath = $this->getHtaccessPath();


        $homePath = dirname($htaccessPath);

        $userIniPath = $this->getUserIniPath();
        $userIni = ini_get('user_ini.filename');

        if (!$state) {
            if (is_file($htaccessPath)) {
                $htaccessContent = @file_get_contents($htaccessPath);
                $regex = '/# SiteGuarding AntiBot Block.*?# END SiteGuarding AntiBot Block/is';
                if (preg_match($regex, $htaccessContent, $matches)) {
                    $htaccessContent = preg_replace($regex, '', $htaccessContent);
                    if (!file_put_contents($htaccessPath, $htaccessContent)) {
                        return false;
                    }
                }
            }

            if (is_file($userIniPath)) {
                $userIniContent = @file_get_contents($userIniPath);
                $regex = '/; SiteGuarding AntiBot Block.*?; END SiteGuarding AntiBot Block/is';
                if (preg_match($regex, $userIniContent, $matches)) {
                    $userIniContent = preg_replace($regex, '', $userIniContent);
                    if (!file_put_contents($userIniPath, $userIniContent)) {
                        return false;
                    }
                }
            }
            return true;
        } else {

            $userIniHtaccessDirectives = '';

            if ($userIni) {
                $userIniHtaccessDirectives = sprintf('<Files "%s">
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Order deny,allow
	Deny from all
</IfModule>
</Files>
', addcslashes($userIni, '"'));
            }


            // .htaccess configuration

            switch ($serverConfig) {
                case 'apache-mod_php':
                    $autoPrependDirective = sprintf("# SiteGuarding AntiBot Block
<IfModule mod_php%d.c>
	php_value auto_prepend_file '%s'
</IfModule>
$userIniHtaccessDirectives
# END SiteGuarding AntiBot Block
", PHP_MAJOR_VERSION, addcslashes($bootstrapPath, "'"));
                    break;

                case 'litespeed':
                    $escapedBootstrapPath = addcslashes($bootstrapPath, "'");
                    $autoPrependDirective = sprintf("# SiteGuarding AntiBot Block
<IfModule LiteSpeed>
php_value auto_prepend_file '%s'
</IfModule>
<IfModule lsapi_module>
php_value auto_prepend_file '%s'
</IfModule>
$userIniHtaccessDirectives
# END SiteGuarding AntiBot Block
", $escapedBootstrapPath, $escapedBootstrapPath);
                    break;

                case 'apache-suphp':
                    $autoPrependDirective = sprintf("# SiteGuarding AntiBot Block
$userIniHtaccessDirectives
# END SiteGuarding AntiBot Block
", addcslashes($homePath, "'"));
                    break;

                case 'cgi':
                    if ($userIniHtaccessDirectives) {
                        $autoPrependDirective = sprintf("# SiteGuarding AntiBot Block
$userIniHtaccessDirectives
# END SiteGuarding AntiBot Block
", addcslashes($homePath, "'"));
                    }
                    break;

            }

            if (!empty($autoPrependDirective)) {
                // Modify .htaccess
                $htaccessContent = @file_get_contents($htaccessPath);

                if ($htaccessContent) {
                    $regex = '/# SiteGuarding AntiBot Block.*?# END SiteGuarding AntiBot Block/is';
                    if (preg_match($regex, $htaccessContent, $matches)) {
                        $htaccessContent = preg_replace($regex, $autoPrependDirective, $htaccessContent);
                    } else {
                        $htaccessContent .= "\n\n" . $autoPrependDirective;
                    }
                } else {
                    $htaccessContent = $autoPrependDirective;
                }

                file_put_contents($htaccessPath, $htaccessContent);

                if ($serverConfig == 'litespeed') {
                    // sleep(2);
                    touch($htaccessPath);
                }

            }
            if ($userIni) {
                // .user.ini configuration
                switch ($serverConfig) {
                    case 'cgi':
                    case 'nginx':
                    case 'apache-suphp':
                    case 'litespeed':
                    case 'iis':
                        $autoPrependIni = sprintf("; SiteGuarding AntiBot Block
auto_prepend_file = '%s'
; END SiteGuarding AntiBot Block
", addcslashes($bootstrapPath, "'"));

                        break;
                }

                if (!empty($autoPrependIni)) {

                    // Modify .user.ini
                    $userIniContent = @file_get_contents($userIniPath);
                    if (is_string($userIniContent)) {
                        $userIniContent = str_replace('auto_prepend_file', ';auto_prepend_file', $userIniContent);
                        $regex = '/; SiteGuarding AntiBot Block.*?; END SiteGuarding AntiBot Block/is';
                        if (preg_match($regex, $userIniContent, $matches)) {
                            $userIniContent = preg_replace($regex, $autoPrependIni, $userIniContent);
                        } else {
                            $userIniContent .= "\n\n" . $autoPrependIni;
                        }
                    } else {
                        $userIniContent = $autoPrependIni;
                    }

                    file_put_contents($userIniPath, $userIniContent);
                }
            }
            return true;
        }
    }

}