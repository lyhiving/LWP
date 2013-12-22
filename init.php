<?php
/**
 *  init.php
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2013-12-21 18:24
 */
// app path
define('APP_PATH', dirname(__FILE__));
// include LWP
include APP_PATH . '/LWP.php';
// not cli mode
if (!IS_CLI) {
    header('HTTP/1.1 404 Not Found', true, 404);
    die('<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found on this server.</p></body></html>');
}
/**
 * Class LWP_init
 */
class LWP_init {
    private $app_config = '';

    function main() {
        $this->app_config = '    // Handler' . "\n";
        $this->app_config .= '    \'^(.+?)Handler$\' => APP_PATH . \'/page/$1.php\',' . "\n";
        $this->app_config .= '    // Lib' . "\n";
        $this->app_config .= '    \'^(.+?)Lib$\' => APP_PATH . \'/lib/$1.php\',' . "\n";
        $this->app_config .= '    \'^(.+?)$\' => APP_PATH . \'/lib/$1.php\',';

        Logger::instance()->info('app init ...');

        mkdirs(APP_PATH . '/../conf/');
        Logger::instance()->info('create floder: ' . realpath(APP_PATH . '/../conf/'));
        // copy database.php
        $database = file_get_contents(APP_PATH . '/conf/database.php');
        $database = preg_replace('/(\*\s*@datetime\s*)[\d]{2,4}\-[\d]{1,2}\-[\d]{1,2}\s*[\d]{1,2}\:[\d]{1,2}/e', '"$1".date("Y-m-d H:i")', $database);
        file_put_contents(APP_PATH . '/../conf/database.php', $database);
        Logger::instance()->info('create file: ' . realpath(APP_PATH . '/../conf/database.php'));
        // copy common.php

        $common = file_get_contents(APP_PATH . '/conf/common.php');
        $common = preg_replace('/(\*\s*@datetime\s*)[\d]{2,4}\-[\d]{1,2}\-[\d]{1,2}\s*[\d]{1,2}\:[\d]{1,2}/e', '"$1".date("Y-m-d H:i")', $common);
        $common = preg_replace('/(\$config\[\'app_autoload\']\s*=\s*array\().+?(\)\;)/se', '\'$1\'."\n".$this->app_config."\n".\'$2\'', $common);
        file_put_contents(APP_PATH . '/../conf/common.php', $common);
        Logger::instance()->info('create file: ' . realpath(APP_PATH . '/../conf/common.php'));

        $this->copy_dir(APP_PATH . '/tpl/', APP_PATH . '/..');

        Logger::instance()->info('app init ok!');

    }

    /**
     * 复制文件夹
     *
     * @param $source
     * @param $dest
     */
    function copy_dir($source, $dest) {
        if ($dh = opendir($source)) {
            while (false !== ($file = readdir($dh))) {
                if (substr($file, 0, 1) != '.') {
                    $file_path = $source . '/' . $file;
                    if (substr($file, -4) == '.tpl') {
                        $dest_path = $dest . '/' . substr($file, 0, -4);
                    } else {
                        $dest_path = $dest . '/' . $file;
                    }
                    if (is_dir($file_path)) {
                        mkdirs($dest_path);
                        Logger::instance()->info('create floder: ' . realpath($dest_path));
                        $this->copy_dir($file_path, $dest_path);
                    } else {
                        // 清除文件缓存
                        clearstatcache();
                        // copy file
                        if (filesize($file_path) > 0) {
                            $content = file_get_contents($file_path);
                            $content = preg_replace('/(\*\s*@datetime\s*)[\d]{2,4}\-[\d]{1,2}\-[\d]{1,2}\s*[\d]{1,2}\:[\d]{1,2}/e', '"$1".date("Y-m-d H:i")', $content);
                            file_put_contents($dest_path, $content);
                            Logger::instance()->info('create file: ' . realpath($dest_path));
                        }
                    }
                }
            }
            closedir($dh);
        }
    }
}

// app run
App::instance()->run('LWP_init');