<?php
/**
 * 日志类
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2011-09-18 01:20
 */

class Logger {

    private $format	= 'Y/m/d H:i:s';
    private $queue  = array();

    private $switch = false;
    private $allowIPs, $isAllowed;
        
    // Logger instance
    private static $instance;
    /**
     * Returns Logger instance.
     *
     * @static
     * @return Logger
     */
    public static function &instance() {
        if (!self::$instance) {
            self::$instance = new Logger(get_config('logger_allowIPs'));
        }
        return self::$instance;
    }

    public function __construct($allowIPs = null) {
        $this->switch = get_config('logger_switch');
        if (IS_CLI) {
            $this->isAllowed = true;
        } else {
            $this->allowIPs = array(
                '127.0.0.0/127.255.255.255',
                '192.168.0.0/192.168.255.255',
                '10.0.0.0.0/10.255.255.255',
                '172.16.0.0/172.31.255.255',
            );
            if (is_array($allowIPs)) {
                $this->allowIPs = array_merge($this->allowIPs, $allowIPs);
            } elseif (is_string($allowIPs)) {
                array_unshift($this->allowIPs, $allowIPs);
            }


            if (isset($_REQUEST['debug'])) {
                $level = $_REQUEST['debug'];
                Cookie::set('debug', $level);
            } else {
                $level = Cookie::get('debug');
            }
            // 获取到参数，才执行debug
            if ($level) {
                $this->isAllowed = $this->isAllowed();
                App::instance()->register_shutdown(array(&$this, 'trace'));
            }
        }
    }
    /**
     * 判断IP是否在可以debug的范围内
     *
     * @return bool
     */
    private function isAllowed() {
        $strIP = get_client_ip();
        if ($strIP == 'Unknown') return false;
        $intIP = sprintf('%u', ip2long($strIP));
        foreach($this->allowIPs as $IPs) {
            if (strpos($IPs, '/') !== false) {
                $IPs = explode('/', $IPs);
            } else {
                $IPs = array($IPs, $IPs);
            }
            $IPs[0] = sprintf('%u', ip2long($IPs[0]));
            $IPs[1] = sprintf('%u', ip2long($IPs[1]));
            if ($IPs[0] <= $intIP && $intIP <= $IPs[1]) {
                return true;
            }
        }
        return false;
    }
        
    public function trace() {
        if (is_xhr_request()) {
            $logs = explode("\n", implode('', $this->queue));
            foreach($logs as $i=>$log) {
                $log = rtrim($log);
                if (mb_strlen($log,'UTF-8') > 255) {
                    $log = mb_substr($log, 0, 255).'...';
                }
                header('X-LWP-TRACE-'.$i.': '.$log);
            }
        } else {
            foreach($this->queue as $log) {
                e(nl2br($log));
            }
        }
    }

    public function debug($line) {
        $this->log($line, LOGGER_DEBUG);
    }

    public function info($line) {
        $this->log($line, LOGGER_INFO);
    }

    public function warn($line) {
        $this->log($line, LOGGER_WARN);
    }

    public function error($line) {
        $this->log($line, LOGGER_ERROR);
    }

    public function fatal($line) {
        $this->log($line, LOGGER_FATAL);
    }

    public function log($line, $priority) {
        if ($this->isAllowed && $this->switch) {
            $time = date($this->format);

            switch ($priority) {
                case LOGGER_DEBUG:
                    $status = '['.$time.'] '.LOGGER_DEBUG.' -> '; break;
                case LOGGER_INFO:
                    $status = '['.$time.'] '.LOGGER_INFO.' -> '; break;
                case LOGGER_WARN:
                    $status = '['.$time.'] '.LOGGER_WARN.' -> '; break;
                case LOGGER_ERROR:
                    $status = '['.$time.'] '.LOGGER_ERROR.' -> '; break;
                case LOGGER_FATAL:
                    $status = '['.$time.'] '.LOGGER_FATAL.' -> '; break;
            }

            // 不是标量
            if (!is_scalar($line)) {
                $line = print_r($line, true);
            }

            // TODO 记录 WARN/ERROR/FATAL 类型日志到文件

            // 命令行模式直接输出
            if (IS_CLI) {
                e($status.$line."\n");
            } else {
                $this->queue[] = $status.$line."\n";
            }
            return true;
        }
        return false;
    }
}
