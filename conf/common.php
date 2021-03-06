<?php
/**
 * common config
 * 
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2011-10-09 14:09
 */
// app rewrite
$config['app_rewrite'] = true;
// logger switch
$config['logger_switch'] = true;
// app autoload
$config['app_autoload'] = array(
    '^(Lib)$' => LWP_PATH . '/lib/$1.php',
    '^(.+?)Handler$' => APP_PATH . '/$1.php',
);
// app route
$config['app_routes'] = array(
    'Default' => array(
        '^/$',
        '^/(index)\.(htm|html|php).*',
    ),
);