/*!
 * 系统公共文件
 *
 * @author Lukin <my@lukin.cn>
 * @version $Id$
 * @datetime 2011-10-24 23:32
 */
var scripts = document.getElementsByTagName('script'), script = scripts[ scripts.length - 1 ]; eval(script.innerHTML);
// 取得 static的路径
script.src.replace(/(.+?)\/assets\/js\/common(?:\.src)?\.js\??(.*)/i, function(all, path, query) {
    window.ROOT = '/' + path.split('/').slice(3).join('/'); window.VERSION = query ? '?' + query : ''; return all;
});
// URI info
var URI  = window.URI = {};
URI.Host = (('https:' == self.location.protocol) ? 'https://' + self.location.hostname : 'http://' + self.location.hostname);
URI.Path = self.location.href.replace(/\?(.*)/, '').replace(URI.Host, '');
URI.File = URI.Path.split('/').pop();
URI.Path = URI.Path.substr(0, URI.Path.lastIndexOf('/') + 1);
URI.URL  = URI.Host + URI.Path + URI.File;

/**
 * 翻译
 *
 * @param msgid
 * @param context
 */
function __(msgid, context) {
    if (context) {
        context = '__' + context + '__';
        return window.Language && window.Language[context] && window.Language[context][msgid] || msgid;
    } else {
        return window.Language && window.Language[msgid] || msgid;
    }
}
/**
 * js 跳转
 *
 * @param location
 */
function redirect(location, args, anchor) {
    if (location) {
        // 处理url
        location = url(location, args);
        // 锚点
        if (anchor) {
            location += '#' + anchor;
        }
        // 跳转
        (top || window).location.href = location.replace('&amp;', '&');
    }
}
/**
 * url
 *
 * @param url
 * @param args
 */
function url(url, args) {
    // 参数
    if (args && !$.isEmptyObject(args)) {
        if (url.indexOf('?') != -1) {
            url += '&' + $.param(args);
        } else {
            url += '?' + $.param(args);
        }
    }
    return url;
}
/**
 *
 * 判断允许的域名
 *
 * @param domain
 */
function allowDomain(domain) {
    document.crossdomain = document.crossdomain || '*.' + document.location.host;
    var crossdomain = '('+document.crossdomain.replace(/\./g, '\\.').replace(/\*/g, '.*')+')$';
    return new RegExp(crossdomain, 'i').test(domain);
}

// preLoadImages
jQuery && (function ($) {
    var cache = [];
    // Arguments are image paths relative to the current page.
    $.preLoadImages = function () {
        var args_len = arguments.length;
        for (var i = args_len; i--;) {
            var cacheImage = document.createElement('img');
            cacheImage.src = arguments[i];
            cache.push(cacheImage);
        }
    };

    // 取得最大的zIndex
    $.fn.maxIndex = function(){
        var max = 0, index;
        this.each(function(){
            index = $(this).css('z-index');
            index = isNaN(parseInt(index)) ? 0 : index;
            max = Math.max(max, index);
        });
        return max;
    };
})(jQuery);

/*
 * 位置任意对齐
 *
 * @author  Lukin <my@lukin.cn>
 */
jQuery && (function ($) {
    /**
     * 对齐常量
     */
    $.align = {
        TL: 'tl',   // 左上
        TC: 'tc',   // 中上
        TR: 'tr',   // 右上
        CL: 'cl',   // 左中
        CC: 'cc',   // 中对齐
        CR: 'cr',   // 右中
        BL: 'bl',   // 左下
        BC: 'bc',   // 中下
        BR: 'br'    // 右下
    };
    /**
     * 位置对齐
     *
     * @example
     *      $(selector).align(points, offset, parent);
     *      $(selector).center(offset, parent);
     * @param points 对齐方式
     *      第一个字符取值 t,b,c ，第二个字符取值 l,r,c，可以表示 9 种取值范围
     *      分别表示 top,bottom,center 与 left,right,center 的两两组合
     * @param offset
     */
    $.fn.align = function(points, offset, parent) {
        parent = parent || window;
        var self = this, wrap, inner, diff, xy = this.offset();
        if (!$.isArray(points)) {
            points = [points, points];
        }
        offset = offset || [0,0];

        var getOffset = function(node, align) {
            var V = align.charAt(0),
                H = align.charAt(1),
                offset, w, h, x, y;

            if (node) {
                offset = node.offset();
                w = node.outerWidth();
                h = node.outerHeight();
            } else {
                offset = {left:$(parent).scrollLeft(), top:$(parent).scrollTop()};
                w = $(parent).width();
                h = $(parent).height();
            }
            x = offset.left;
            y = offset.top;
            if (V === 'c') {
                y += h / 2;
            } else if (V === 'b') {
                y += h;
            }

            if (H === 'c') {
                x += w / 2;
            } else if (H === 'r') {
                x += w;
            }
            return { left: x, top: y };
        }

        wrap  = getOffset(null, points[0]);
        inner = getOffset(self, points[1]);
        diff  = [inner.left - wrap.left, inner.top - wrap.top];

        xy = {
            left: Math.max(xy.left - diff[0] + (+offset[0]), 0),
            top: Math.max(xy.top - diff[1] + (+offset[1]), 0),
            position: $.inArray(this.css('position'), ['absolute','relative','fixed']) == -1 ? 'absolute' : this.css('position')
        };
        return this.css(xy);
    }
    /**
     * 居中
     */
    $.fn.center = function(offset, parent) {
        return this.align('cc', offset, parent);
    }

})(jQuery);

/*
 * 浮动提示
 *
 * @example
 *      $.tooltip();
 *      $(selector).tooltip(wrapper);
 *
 * @parma wrapper
 * @author  Lukin <my@lukin.cn>
 */
jQuery && (function ($) {
    var cache, title = '',
        wrapper = $('<div id="tooltip" class="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-mark"></div><div class="tooltip-inner"></div></div>');
    $.fn.tooltip = function(settings) {
        var defaults = {
            className:null,
            gravity:'b',
            html:true,
            live:true,
            opacity:0.8,
            title:'title',
            fallback:'',
            trigger:'hover'
        };
        settings = $.extend(defaults, settings || {});

        var wrapid   = wrapper.attr('id');
        var deltaX   = 9, deltaY = 12;
        var binder   = settings.live ? 'on' : 'bind',
            eventIn  = settings.trigger == 'hover' ? 'mouseenter' : (settings.trigger == 'move' ? 'mousemove' : 'focus'),
            eventOut = $.inArray(settings.trigger, ['hover', 'move']) != -1 ? 'mouseleave' : 'blur';
        // 容器不存在，添加到指定位置
        if (!$('#' + wrapid).is('[id=' + wrapid + ']')) $('body').append(wrapper);
        if (wrapper.css('position') != 'absolute') wrapper.css('position', 'absolute');
        if (settings.className) wrapper.addClass(settings.className);

        // fixed title
        $('[' + (settings.title) + ']', this).each(function(){
            var self = $(this), title = self.attr(settings.title);
            self.removeAttr(settings.title); self.attr('tooltip', title);
        });

        // events
        this[binder](eventIn, function(e) {
            var self = $(this), update = false, position = { X: e.pageX + deltaX, Y: e.pageY + deltaY };
            // title 存在，替换为 tooltip
            if (self.is('[' + (settings.title) + ']')) {
                update = true;
                title = self.attr(settings.title); self.removeAttr(settings.title); self.attr('tooltip', title);
            } else {
                update = false;
                title = self.attr('tooltip');
            }
            title = (title && title.length > 0) ? title : settings.fallback;
            if (title == '') return ;

            // set
            if (cache != e.target || update) {
                cache = e.target;
                var inner = $('.tooltip-inner', wrapper);
                var maxIndex = $('*').maxIndex(); maxIndex = maxIndex==0 ? 1 : maxIndex;
                var wrapIndex = wrapper.css('z-index');
                wrapIndex = isNaN(parseInt(wrapIndex)) ? 0 : wrapIndex;
                if (maxIndex > wrapIndex) wrapper.css('z-index', maxIndex + 1);
                $('.tooltip-arrow, .tooltip-mark', wrapper).css({opacity: settings.opacity});
                inner.css({width: 'auto'})[settings.html ? 'html' : 'text'](title).show();
                setTimeout(function(){ $('.tooltip-mark', wrapper).width(wrapper.width()).height(wrapper.height()); }, 0);
            }
            // mouse move
            if (settings.trigger == 'move') {
                // 垂直位置
                if (e.clientY + wrapper.height() + deltaY + 2 > $(window).height()) {
                    position.Y = e.pageY - (wrapper.height() + deltaY + 2);
                }
                // 水平位置
                if (e.clientX + wrapper.width() + deltaX + 10 > $(window).width()) {
                    position.X = e.pageX - (wrapper.width() + deltaX + 2);
                }
                wrapper.css({display:'block', left:Math.max(position.X, deltaX), top:Math.max(position.Y, deltaY)});
            }
            // hover and focus
            else {
                var gravity = settings.gravity, css,
                    tooltip = { width: wrapper.outerWidth(), height: wrapper.outerHeight()},
                    current = $.extend(self.offset(), { width: self.outerWidth(), height: self.outerHeight()});

                switch (gravity.charAt(0)) {
                    case 'b':
                        css = {top: current.top + current.height, left: current.left + current.width / 2 - tooltip.width / 2};
                        break;
                    case 't':
                        css = {top: current.top - tooltip.height, left: current.left + current.width / 2 - tooltip.width / 2};
                        break;
                    case 'l':
                        css = {top: current.top + current.height / 2 - tooltip.height / 2, left: current.left - tooltip.width};
                        break;
                    case 'r':
                        css = {top: current.top + current.height / 2 - tooltip.height / 2, left: current.left + current.width};
                        break;
                }
                if (gravity.length == 2) {
                    if (gravity.charAt(1) == 'r') {
                        css.left = current.left + current.width / 2 - 15;
                    } else {
                        css.left = current.left + current.width / 2 - tooltip.width + 15;
                    }
                }
                wrapper.css($.extend(css, {display:'block'})).removeClass().addClass('tooltip').addClass('tooltip-' + gravity);
                $('.tooltip-arrow', wrapper).removeClass().addClass('tooltip-arrow').addClass('tooltip-arrow-' + gravity.charAt(0));
            }
        })[binder](eventOut, function(e) {
            wrapper.hide();
        });
        return this;
    };
})(jQuery);

/*
 * JSON  - JSON for jQuery
 *
 * @example
 *      $.toJSON(Object);
 * @author  Lukin <my@lukin.cn>
 */
jQuery && (function ($) {
    $.toJSON = function(o){
        var i, v, s = $.toJSON, t;
        if (o == null) return 'null';
        t = typeof o;
        if (t == 'string') {
            v = '\bb\tt\nn\ff\rr\""\'\'\\\\';
            return '"' + o.replace(/([\u0080-\uFFFF\x00-\x1f\"])/g, function(a, b) {
                i = v.indexOf(b);
                if (i + 1) return '\\' + v.charAt(i + 1);
                a = b.charCodeAt().toString(16);
                return '\\u' + '0000'.substring(a.length) + a;
            }) + '"';
        }
        if (t == 'object') {
            if (o instanceof Array) {
                for (i=0, v = '['; i<o.length; i++) v += (i > 0 ? ',' : '') + s(o[i]);
                return v + ']';
            }
            v = '{';
            for (i in o) v += typeof o[i] != 'function' ? (v.length > 1 ? ',"' : '"') + i + '":' + s(o[i]) : '';
            return v + '}';
        }
        return '' + o;
    };
})(jQuery);

/*
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery && (function ($) {
    /**
     * Create a cookie with the given name and value and other optional parameters.
     *
     * @example $.cookie('the_cookie', 'the_value');
     * @desc Set the value of a cookie.
     * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
     * @desc Create a cookie with all available options.
     * @example $.cookie('the_cookie', 'the_value');
     * @desc Create a session cookie.
     * @example $.cookie('the_cookie', null);
     * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
     *       used when the cookie was set.
     *
     * @param String name The name of the cookie.
     * @param String value The value of the cookie.
     * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
     * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
     *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
     *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
     *                             when the the browser exits.
     * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
     * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
     * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
     *                        require a secure protocol (like HTTPS).
     * @type undefined
     *
     * @name $.cookie
     * @cat Plugins/Cookie
     * @author Klaus Hartl/klaus.hartl@stilbuero.de
     */
    $.cookie = function(name, value, options) {
        if (typeof value != 'undefined') { // name and value given, set cookie
            options = options || {};
            if (value === null) {
                value = '';
                options.expires = -1;
            }
            var expires = '';
            if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
                var date;
                if (typeof options.expires == 'number') {
                    date = new Date();
                    date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
                } else {
                    date = options.expires;
                }
                expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
            }
            // CAUTION: Needed to parenthesize options.path and options.domain
            // in the following expressions, otherwise they evaluate to undefined
            // in the packed version for some reason...
            var path = options.path ? '; path=' + (options.path) : '';
            var domain = options.domain ? '; domain=' + (options.domain) : '';
            var secure = options.secure ? '; secure' : '';
            document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
        } else { // only name given, get cookie
            var cookieValue = null;
            if (document.cookie && document.cookie != '') {
                var cookies = document.cookie.split(';');
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = jQuery.trim(cookies[i]);
                    // Does this cookie string begin with the name we want?
                    if (cookie.substring(0, name.length + 1) == (name + '=')) {
                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                        break;
                    }
                }
            }
            return cookieValue;
        }
    };
})(jQuery);