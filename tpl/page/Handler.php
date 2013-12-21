<?php
/**
 *  Handler.php
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2013-10-14 09:49
 */
abstract class Handler extends LWP_Page_Handler {
    // user info
    protected $USER;
    // 页面标题
    protected $title = '';
    // 页面菜单
    protected $menu = '';
    // 装饰器
    protected $wrap = true;

    public function get_page() {
        return APP_PATH . '/page/Handler.phtml';
    }

    public function get_scripts() {
        return array_merge(parent::get_scripts(),
            array(
                APP_RES . 'assets/js/jquery-1.10.2.js',
				APP_RES . 'assets/js/common.js',
            )
        );
    }

    public function get_styles() {
        return array_merge(parent::get_styles(),
            array(
                APP_RES . 'assets/css/normalize.css',
            )
        );
    }

    public function get_sections() {
        return array_merge(parent::get_sections(),
            array(
                '<!--[if lt IE 9]>',
                '<meta http-equiv="content-type" content="text/html; charset=utf-8" />',
                '<script src="'.APP_RES.'assets/js/html5.js" type="text/javascript"></script>',
                '<![endif]-->',
            )
        );
    }

    /**
     * 字符串截取
     *
     * @param string $content
     * @param int $length
     * @param string $ellipsis
     * @return string
     */
    public function substr($content, $length, $ellipsis = '...') {
        if (intval(mb_strlen($content, 'UTF-8')) > intval($length)) {
            $content = mb_substr($content, 0, $length, 'UTF-8') . $ellipsis;
        }
        return $content;
    }

    /**
     * 解析markdown
     *
     * @param $md_file
     * @param $format_anchor
     * @return array
     */
    public function parse_markdown($md_file,$format_anchor=false) {
        $md_cont = file_get_contents($md_file);
        $md = new MarkdownExtra();
        $md_cont = $md->transform($md_cont);
        if ($format_anchor) {
            $md_cont = $this->format_anchors($md_cont);
        }
        return $md_cont;
    }

    /**
     * 格式化锚点
     *
     * @param $md_cont
     * @return mixed
     */
    public function format_anchors($md_cont) {
        if (preg_match_all('@<h(\d)>(.+)</\s*h\1>@i', $md_cont, $matches, PREG_SET_ORDER)) {
            $anchors = array();
            foreach ($matches as $match) {
                $text = strip_tags(trim($match[2]));
                if (!isset($anchors[$text])) $anchors[$text] = 0;
                if ($anchors[$text] > 0) {
                    $anchor = $text . '-' . $anchors[$text];
                } else {
                    $anchor = $text;
                }
                $anchors[$text]++;
                $md_cont = preg_replace('@'.preg_quote($match[0], '@').'@', '<h' . $match[1] . '><a class="anchor" href="#' . $anchor . '" name="' . $anchor . '">&nbsp;</a>' . $match[2] . '</h' . $match[1] . '>', $md_cont, 1);
            }
        }
        return $md_cont;
    }
}