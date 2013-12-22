<?php
/**
 *  global.php
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2013-10-14 09:48
 */

// Prevent repeated loading
if (defined('APP_PATH')) return 0;
// admin path
define('APP_PATH', dirname(__FILE__));
// include LWP
include APP_PATH . '/LWP/LWP.php';
