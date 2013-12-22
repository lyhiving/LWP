<?php
/**
 *  Common.php
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2013-10-14 11:10
 */
class Common extends BaseLib {
    /**
     * IP to Address
     *
     * @param $ipaddr
     * @return string
     */
    static function ip2addr($ipaddr) {
        return trim(str_replace('CZ88.NET', '', QQWry::instance(APP_PATH . '/lib/qqwry.dat')->ip2addr($ipaddr)));
    }
}