<?php
/**
 *  Default.php
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2013-10-14 11:14
 */

class DefaultHandler extends Handler{
    function __before($url, $menu='index') {
        $this->menu  = $menu;
        $this->title = 'Hello World';
    }
    function get() {
        ?>
        <h1>Hello world</h1>
        <?php
    }
}