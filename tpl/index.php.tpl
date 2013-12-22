<?php
/**
 *  index.php
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2013-10-14 09:48
 */
// include global
include dirname(__FILE__) . '/global.php';
// include handler
include APP_PATH . '/page/Handler.php';
// app run
App::instance()->run();
