<?php
/**
 * job run file
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2011-10-08 23:49
 */
// not cli mode
(PHP_SAPI == 'cli') or die('<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL '.$_SERVER['REQUEST_URI'].' was not found on this server.</p></body></html>');
// include global
include dirname(__FILE__) . '/../global.php';
// PRIMARY KEY PATH
define('PK_PATH', APP_PATH.'/job/keys');
/**
 * Class Job
 */
class Job {
    /**
     * 获取pkid
     *
     * @param $classname
     * @return int|string
     */
    function get_pkid($classname) {
        $pk_file = PK_PATH.'/'.$classname.'.pk';
        if (is_ifile($pk_file)) {
            return file_get_contents($pk_file);
        } else {
            return 0;
        }
    }

    /**
     * set pkid
     *
     * @param $classname
     * @param $pkid
     * @return int
     */
    function set_pkid($classname, $pkid) {
        $pk_file = PK_PATH.'/'.$classname.'.pk';
        return file_put_contents($pk_file, $pkid);
    }
}

$handler = empty($argv[1]) ? null : $argv[1];

if ($handler) {
    include APP_PATH . '/job/bin/'.$handler.'.php';
} else {
    class run {
        function main() {
            var_dump(__CLASS__);
        }
    }
}

// app run
App::instance()->run($handler);