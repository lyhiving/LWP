<?php
/**
 *  Base.php
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2013-10-14 11:09
 */
abstract class BaseLib extends LWP_Lib_Base {

    public function __get($key) {
        return isset($this->$key) ? $this->$key : null;
    }

    public function __set($key, $value) {
        $this->$key = $value;
    }

    /**
     * 业务报错
     *
     * 此报错信息会中断页面，谨慎使用
     *
     * @param string $error
     * @param int $errno
     */
    protected function error($error, $errno = 200) {
        lwp_error($error, $errno);
    }
}
