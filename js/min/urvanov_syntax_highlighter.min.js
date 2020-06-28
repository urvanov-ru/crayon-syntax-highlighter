// To avoid duplicates conflicting
var jQueryUrvanovSyntaxHighlighter = jQuery;

(function ($) {

    UrvanovSyntaxHighlighterUtil = new function () {

        var base = this;
        var settings = null;

        base.init = function () {
            settings = UrvanovSyntaxHighlighterSyntaxSettings;
            base.initGET();
        };

        base.addPrefixToID = function (id) {
            return id.replace(/^([#.])?(.*)$/, '$1' + settings.prefix + '$2');
        };

        base.removePrefixFromID = function (id) {
            var re = new RegExp('^[#.]?' + settings.prefix, 'i');
            return id.replace(re, '');
        };

        base.cssElem = function (id) {
            return $(base.addPrefixToID(id));
        };

        base.setDefault = function (v, d) {
            return (typeof v == 'undefined') ? d : v;
        };

        base.setMax = function (v, max) {
            return v <= max ? v : max;
        };

        base.setMin = function (v, min) {
            return v >= min ? v : min;
        };

        base.setRange = function (v, min, max) {
            return base.setMax(base.setMin(v, min), max);
        };

        base.getExt = function (str) {
            if (str.indexOf('.') == -1) {
                return undefined;
            }
            var ext = str.split('.');
            if (ext.length) {
                ext = ext[ext.length - 1];
            } else {
                ext = '';
            }
            return ext;
        };

        base.initGET = function () {
            // URLs
            window.currentURL = window.location.protocol + '//' + window.location.host + window.location.pathname;
            window.currentDir = window.currentURL.substring(0, window.currentURL.lastIndexOf('/'));

            // http://stackoverflow.com/questions/439463
            function getQueryParams(qs) {
                qs = qs.split("+").join(" ");
                var params = {}, tokens, re = /[?&]?([^=]+)=([^&]*)/g;
                while (tokens = re.exec(qs)) {
                    params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
                }
                return params;
            }

            window.GET = getQueryParams(document.location.search);
        };

        base.getAJAX = function (args, callback) {
            args.version = settings.version;
            $.get(settings.ajaxurl, args, callback);
        };
        
        /**
         * @param {String} HTML representing any number of sibling elements
         * @return {NodeList} 
         */
        base.htmlToElements = function (html) {
            return $.parseHTML(html, document, true);
        }


        base.postAJAX = function (args, callback) {
            args.version = settings.version;
            $.post(settings.ajaxurl, args, callback);
        };

        base.reload = function () {
            var get = '?';
            for (var i in window.GET) {
                get += i + '=' + window.GET[i] + '&';
            }
            window.location = window.currentURL + get;
        };

        base.escape = function (string) {
            if (typeof encodeURIComponent == 'function') {
                return encodeURIComponent(string);
            } else if (typeof escape != 'function') {
                return escape(string);
            } else {
                return string;
            }
        };

        base.log = function (string) {
            if (typeof console != 'undefined' && settings.debug) {
                console.log(string);
            }
        };

        base.decode_html = function (str) {
            return String(str).replace(/&lt;/g, '<').replace(
                /&gt;/g, '>').replace(/&amp;/g, '&');
        };

        base.encode_html = function (str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(
                />/g, '&gt;');
        };

        /**
         * Returns either black or white to ensure this color is distinguishable with the given RGB hex.
         * This function can be used to create a readable foreground color given a background color, or vice versa.
         * It forms a radius around white where black is returned. Outside this radius, white is returned.
         *
         * @param hex An RGB hex (e.g. "#FFFFFF")
         * @requires jQuery and TinyColor
         * @param args The argument object. Properties:
         *      amount: a value in the range [0,1]. If the distance of the given hex from white exceeds this value,
         *          white is returned. Otherwise, black is returned.
         *      xMulti: a multiplier to the distance in the x-axis.
         *      yMulti: a multiplier to the distance in the y-axis.
         *      normalizeHue: either falsey or an [x,y] array range. If hex is a colour with hue in this range,
         *          then normalizeHueXMulti and normalizeHueYMulti are applied.
         *      normalizeHueXMulti: a multiplier to the distance in the x-axis if hue is normalized.
         *      normalizeHueYMulti: a multiplier to the distance in the y-axis if hue is normalized.
         * @return the RGB hex string of black or white.
         */
        base.getReadableColor = function (hex, args) {
            args = $.extend({
                amount: 0.5,
                xMulti: 1,
                // We want to achieve white a bit sooner in the y axis
                yMulti: 1.5,
                normalizeHue: [20, 180],
                // For colors that appear lighter (yellow, green, light blue) we reduce the distance in the x direction,
                // stretching the radius in the x axis allowing more black than before.
                normalizeHueXMulti: 1 / 2.5,
                normalizeHueYMulti: 1
            }, args);
            var color = tinycolor(hex);
            var hsv = color.toHsv();
            // Origin is white
            var coord = {x: hsv.s, y: 1 - hsv.v};
            // Multipliers
            coord.x *= args.xMulti;
            coord.y *= args.yMulti;
            if (args.normalizeHue && hsv.h > args.normalizeHue[0] && hsv.h < args.normalizeHue[1]) {
                coord.x *= args.normalizeHueXMulti;
                coord.y *= args.normalizeHueYMulti;
            }
            var dist = Math.sqrt(Math.pow(coord.x, 2) + Math.pow(coord.y, 2));
            if (dist < args.amount) {
                hsv.v = 0; // black
            } else {
                hsv.v = 1; // white
            }
            hsv.s = 0;
            return tinycolor(hsv).toHexString();
        };

        base.removeChars = function (chars, str) {
            var re = new RegExp('[' + chars + ']', 'gmi');
            return str.replace(re, '');
        }

    };

    $(document).ready(function () {
        UrvanovSyntaxHighlighterUtil.init();
    });

    // http://stackoverflow.com/questions/2360655/jquery-event-handlers-always-execute-in-order-they-were-bound-any-way-around-t

    // [name] is the name of the event "click", "mouseover", ..
    // same as you'd pass it to bind()
    // [fn] is the handler function
    $.fn.bindFirst = function (name, fn) {
        // bind as you normally would
        // don't want to miss out on any jQuery magic
        this.bind(name, fn);
        // Thanks to a comment by @Martin, adding support for
        // namespaced events too.
        var handlers = this.data('events')[name.split('.')[0]];
        // take out the handler we just inserted from the end
        var handler = handlers.pop();
        // move it at the beginning
        handlers.splice(0, 0, handler);
    };

    // http://stackoverflow.com/questions/4079274/how-to-get-an-objects-properties-in-javascript-jquery
    $.keys = function (obj) {
        var keys = [];
        for (var key in obj) {
            keys.push(key);
        }
        return keys;
    }

    // Prototype modifications

    RegExp.prototype.execAll = function (string) {
        var matches = [];
        var match = null;
        while ((match = this.exec(string)) != null) {
            var matchArray = [];
            for (var i in match) {
                if (parseInt(i) == i) {
                    matchArray.push(match[i]);
                }
            }
            matches.push(matchArray);
        }
        return matches;
    };

    // Escape regex chars with \
    RegExp.prototype.escape = function (text) {
        return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
    };

    String.prototype.sliceReplace = function (start, end, repl) {
        return this.substring(0, start) + repl + this.substring(end);
    };

    String.prototype.escape = function () {
        var tagsToReplace = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;'
        };
        return this.replace(/[&<>]/g, function (tag) {
            return tagsToReplace[tag] || tag;
        });
    };

    String.prototype.linkify = function (target) {
        target = typeof target != 'undefined' ? target : '';
        return this.replace(/(http(s)?:\/\/(\S)+)/gmi, '<a href="$1" target="' + target + '">$1</a>');
    };

    String.prototype.toTitleCase = function () {
        var parts = this.split(/\s+/);
        var title = '';
        $.each(parts, function (i, part) {
            if (part != '') {
                title += part.slice(0, 1).toUpperCase() + part.slice(1, part.length);
                if (i != parts.length - 1 && parts[i + 1] != '') {
                    title += ' ';
                }
            }
        });
        return title;
    };

})(jQueryUrvanovSyntaxHighlighter);
// Default Settings
jqueryPopup = Object();
jqueryPopup.defaultSettings = {
		centerBrowser:0, // center window over browser window? {1 (YES) or 0 (NO)}. overrides top and left
		centerScreen:0, // center window over entire screen? {1 (YES) or 0 (NO)}. overrides top and left
		height:500, // sets the height in pixels of the window.
		left:0, // left position when the window appears.
		location:0, // determines whether the address bar is displayed {1 (YES) or 0 (NO)}.
		menubar:0, // determines whether the menu bar is displayed {1 (YES) or 0 (NO)}.
		resizable:0, // whether the window can be resized {1 (YES) or 0 (NO)}. Can also be overloaded using resizable.
		scrollbars:0, // determines whether scrollbars appear on the window {1 (YES) or 0 (NO)}.
		status:0, // whether a status line appears at the bottom of the window {1 (YES) or 0 (NO)}.
		width:500, // sets the width in pixels of the window.
		windowName:null, // name of window set from the name attribute of the element that invokes the click
		windowURL:null, // url used for the popup
		top:0, // top position when the window appears.
		toolbar:0, // determines whether a toolbar (includes the forward and back buttons) is displayed {1 (YES) or 0 (NO)}.
		data:null,
		event:'click'
	};

(function ($) {

	popupWindow = function (object, instanceSettings, beforeCallback, afterCallback) {
		beforeCallback = typeof beforeCallback !== 'undefined' ? beforeCallback : null;
		afterCallback = typeof afterCallback !== 'undefined' ? afterCallback : null;
	
		if (typeof object == 'string') {
			object = jQuery(object);
		}
		if (!(object instanceof jQuery)) {
			return false;
		}
		var settings = jQuery.extend({}, jqueryPopup.defaultSettings, instanceSettings || {});
		object.handler = jQuery(object).bind(settings.event, function() {
			
			if (beforeCallback) {
				beforeCallback();
			}
			
			var windowFeatures =    'height=' + settings.height +
									',width=' + settings.width +
									',toolbar=' + settings.toolbar +
									',scrollbars=' + settings.scrollbars +
									',status=' + settings.status + 
									',resizable=' + settings.resizable +
									',location=' + settings.location +
									',menuBar=' + settings.menubar;
		
			settings.windowName = settings.windowName || jQuery(this).attr('name');
			var href = jQuery(this).attr('href');
			if (!settings.windowURL && !(href == '#') && !(href == '')) {
				settings.windowURL = jQuery(this).attr('href');
			}
			
			var centeredY,centeredX;
			
			var win = null;
			if (settings.centerBrowser) {
				if (typeof window.screenY == 'undefined') {// not defined for old IE versions
					centeredY = (window.screenTop - 120) + ((((document.documentElement.clientHeight + 120)/2) - (settings.height/2)));
					centeredX = window.screenLeft + ((((document.body.offsetWidth + 20)/2) - (settings.width/2)));
				} else {
					centeredY = window.screenY + (((window.outerHeight/2) - (settings.height/2)));
					centeredX = window.screenX + (((window.outerWidth/2) - (settings.width/2)));
				}
				win = window.open(settings.windowURL, settings.windowName, windowFeatures+',left=' + centeredX +',top=' + centeredY);
			} else if (settings.centerScreen) {
				centeredY = (screen.height - settings.height)/2;
				centeredX = (screen.width - settings.width)/2;
				win = window.open(settings.windowURL, settings.windowName, windowFeatures+',left=' + centeredX +',top=' + centeredY);
			} else {
				win = window.open(settings.windowURL, settings.windowName, windowFeatures+',left=' + settings.left +',top=' + settings.top);
			}
			if (win != null) {
				win.focus();
				if (settings.data) {
					win.document.write(settings.data);
				}
			}
			
			if (afterCallback) {
				afterCallback();
			}
		});
		return settings;
	};

	popdownWindow = function(object, event) {
		if (typeof event == 'undefined') {
			event = 'click';
		}
		object = jQuery(object);
		if (!(object instanceof jQuery)) {
			return false;
		}
		object.unbind(event, object.handler);
	};

})(jQueryUrvanovSyntaxHighlighter);

// Urvanov Syntax Highlighter JavaScript

(function ($) {


	
    // BEGIN AUXILIARY FUNCTIONS

    $.fn.exists = function () {
        return this.length !== 0;
    };

    $.fn.style = function (styleName, value, priority) {
        // DOM node
        var node = this.get(0);
        // Ensure we have a DOM node
        if (typeof node == 'undefined') {
            return;
        }
        // CSSStyleDeclaration
        var style = node.style;
        // Getter/Setter
        if (typeof styleName != 'undefined') {
            if (typeof value != 'undefined') {
                // Set style property
                priority = typeof priority != 'undefined' ? priority : '';
                if (typeof style.setProperty != 'undefined') {
                    style.setProperty(styleName, value, priority);
                } else {
                    // XXX Using priority breaks on IE 7 & 8
//                    if (priority) {
//                        value = value + ' !' + priority;
//                    }
                    style[styleName] = value;
                }
            } else {
                // Get style property
                return style[styleName];
            }
        } else {
            // Get CSSStyleDeclaration
            return style;
        }
    };

    // END AUXILIARY FUNCTIONS

    var PRESSED = 'crayon-pressed';
    var UNPRESSED = '';

    var URVANOV_SYNTAX_HIGHLIGHTER_SYNTAX = 'div.urvanov-syntax-highlighter-syntax';
    var URVANOV_SYNTAX_HIGHLIGHTER_TOOLBAR = '.crayon-toolbar';
    var URVANOV_SYNTAX_HIGHLIGHTER_INFO = '.crayon-info';
    var URVANOV_SYNTAX_HIGHLIGHTER_PLAIN = '.urvanov-syntax-highlighter-plain';
    var URVANOV_SYNTAX_HIGHLIGHTER_MAIN = '.urvanov-syntax-highlighter-main';
    var URVANOV_SYNTAX_HIGHLIGHTER_TABLE = '.crayon-table';
    var URVANOV_SYNTAX_HIGHLIGHTER_LOADING = '.urvanov-syntax-highlighter-loading';
    var URVANOV_SYNTAX_HIGHLIGHTER_CODE = '.urvanov-syntax-highlighter-code';
    var URVANOV_SYNTAX_HIGHLIGHTER_TITLE = '.crayon-title';
    var URVANOV_SYNTAX_HIGHLIGHTER_TOOLS = '.crayon-tools';
    var URVANOV_SYNTAX_HIGHLIGHTER_NUMS = '.crayon-nums';
    var URVANOV_SYNTAX_HIGHLIGHTER_NUM = '.crayon-num';
    var URVANOV_SYNTAX_HIGHLIGHTER_LINE = '.crayon-line';
    var URVANOV_SYNTAX_HIGHLIGHTER_WRAPPED = 'urvanov-syntax-highlighter-wrapped';
    var URVANOV_SYNTAX_HIGHLIGHTER_NUMS_CONTENT = '.urvanov-syntax-highlighter-nums-content';
    var URVANOV_SYNTAX_HIGHLIGHTER_NUMS_BUTTON = '.urvanov-syntax-highlighter-nums-button';
    var URVANOV_SYNTAX_HIGHLIGHTER_WRAP_BUTTON = '.urvanov-syntax-highlighter-wrap-button';
    var URVANOV_SYNTAX_HIGHLIGHTER_EXPAND_BUTTON = '.urvanov-syntax-highlighter-expand-button';
    var URVANOV_SYNTAX_HIGHLIGHTER_EXPANDED = 'urvanov-syntax-highlighter-expanded urvanov-syntax-highlighter-toolbar-visible';
    var URVANOV_SYNTAX_HIGHLIGHTER_PLACEHOLDER = 'urvanov-syntax-highlighter-placeholder';
    var URVANOV_SYNTAX_HIGHLIGHTER_POPUP_BUTTON = '.urvanov-syntax-highlighter-popup-button';
    var URVANOV_SYNTAX_HIGHLIGHTER_COPY_BUTTON = '.urvanov-syntax-highlighter-copy-button';
    var URVANOV_SYNTAX_HIGHLIGHTER_PLAIN_BUTTON = '.urvanov-syntax-highlighter-plain-button';

    UrvanovSyntaxHighlighterSyntax = new function () {
        var base = this;
        var urvanov_syntax_highlighters = new Object();
        var settings;
        var strings;
        var currUID = 0;
        var touchscreen;

        base.init = function () {
            if (typeof urvanov_syntax_highlighters == 'undefined') {
                urvanov_syntax_highlighters = new Object();
            }
            settings = UrvanovSyntaxHighlighterSyntaxSettings;
            strings = UrvanovSyntaxHighlighterSyntaxStrings;
            $(URVANOV_SYNTAX_HIGHLIGHTER_SYNTAX).each(function () {
                base.process(this);
            });
        };

        base.process = function (c, replace) {
            c = $(c);
            var uid = c.attr('id');
            if (uid == 'urvanov-syntax-highlighter-') {
                // No ID, generate one
                uid += getUID();
            }
            c.attr('id', uid);
            UrvanovSyntaxHighlighterUtil.log(uid);

            if (typeof replace == 'undefined') {
                replace = false;
            }

            if (!replace && !makeUID(uid)) {
                // Already a UrvanovSyntaxHighlighter
                return;
            }

            var toolbar = c.find(URVANOV_SYNTAX_HIGHLIGHTER_TOOLBAR);
            var info = c.find(URVANOV_SYNTAX_HIGHLIGHTER_INFO);
            var plain = c.find(URVANOV_SYNTAX_HIGHLIGHTER_PLAIN);
            var main = c.find(URVANOV_SYNTAX_HIGHLIGHTER_MAIN);
            var table = c.find(URVANOV_SYNTAX_HIGHLIGHTER_TABLE);
            var code = c.find(URVANOV_SYNTAX_HIGHLIGHTER_CODE);
            var title = c.find(URVANOV_SYNTAX_HIGHLIGHTER_TITLE);
            var tools = c.find(URVANOV_SYNTAX_HIGHLIGHTER_TOOLS);
            var nums = c.find(URVANOV_SYNTAX_HIGHLIGHTER_NUMS);
            var numsContent = c.find(URVANOV_SYNTAX_HIGHLIGHTER_NUMS_CONTENT);
            var numsButton = c.find(URVANOV_SYNTAX_HIGHLIGHTER_NUMS_BUTTON);
            var wrapButton = c.find(URVANOV_SYNTAX_HIGHLIGHTER_WRAP_BUTTON);
            var expandButton = c.find(URVANOV_SYNTAX_HIGHLIGHTER_EXPAND_BUTTON);
            var popupButton = c.find(URVANOV_SYNTAX_HIGHLIGHTER_POPUP_BUTTON);
            var copyButton = c.find(URVANOV_SYNTAX_HIGHLIGHTER_COPY_BUTTON);
            var plainButton = c.find(URVANOV_SYNTAX_HIGHLIGHTER_PLAIN_BUTTON);

            urvanov_syntax_highlighters[uid] = c;
            urvanov_syntax_highlighters[uid].toolbar = toolbar;
            urvanov_syntax_highlighters[uid].plain = plain;
            urvanov_syntax_highlighters[uid].info = info;
            urvanov_syntax_highlighters[uid].main = main;
            urvanov_syntax_highlighters[uid].table = table;
            urvanov_syntax_highlighters[uid].code = code;
            urvanov_syntax_highlighters[uid].title = title;
            urvanov_syntax_highlighters[uid].tools = tools;
            urvanov_syntax_highlighters[uid].nums = nums;
            urvanov_syntax_highlighters[uid].nums_content = numsContent;
            urvanov_syntax_highlighters[uid].numsButton = numsButton;
            urvanov_syntax_highlighters[uid].wrapButton = wrapButton;
            urvanov_syntax_highlighters[uid].expandButton = expandButton;
            urvanov_syntax_highlighters[uid].popup_button = popupButton;
            urvanov_syntax_highlighters[uid].copy_button = copyButton;
            urvanov_syntax_highlighters[uid].plainButton = plainButton;
            urvanov_syntax_highlighters[uid].numsVisible = true;
            urvanov_syntax_highlighters[uid].wrapped = false;
            urvanov_syntax_highlighters[uid].plainVisible = false;

            urvanov_syntax_highlighters[uid].toolbar_delay = 0;
            urvanov_syntax_highlighters[uid].time = 1;

            // Set plain
            $(URVANOV_SYNTAX_HIGHLIGHTER_PLAIN).css('z-index', 0);

            // XXX Remember CSS dimensions
            var mainStyle = main.style();
            urvanov_syntax_highlighters[uid].mainStyle = {
                'height': mainStyle && mainStyle.height || '',
                'max-height': mainStyle && mainStyle.maxHeight || '',
                'min-height': mainStyle && mainStyle.minHeight || '',
                'width': mainStyle && mainStyle.width || '',
                'max-width': mainStyle && mainStyle.maxWidth || '',
                'min-width': mainStyle && mainStyle.minWidth || ''
            };
            urvanov_syntax_highlighters[uid].mainHeightAuto = urvanov_syntax_highlighters[uid].mainStyle.height == '' && urvanov_syntax_highlighters[uid].mainStyle['max-height'] == '';

            var load_timer;
            var i = 0;
            urvanov_syntax_highlighters[uid].loading = true;
            urvanov_syntax_highlighters[uid].scrollBlockFix = false;

            // Register click events
            numsButton.click(function () {
                UrvanovSyntaxHighlighterSyntax.toggleNums(uid);
            });
            wrapButton.click(function () {
                UrvanovSyntaxHighlighterSyntax.toggleWrap(uid);
            });
            expandButton.click(function () {
                UrvanovSyntaxHighlighterSyntax.toggleExpand(uid);
            });
            plainButton.click(function () {
                UrvanovSyntaxHighlighterSyntax.togglePlain(uid);
            });
            copyButton.click(function () {
                UrvanovSyntaxHighlighterSyntax.copyPlain(uid);
            });

            // Enable retina if supported
            retina(uid);

            var load_func = function () {
                // If nums hidden by default
                if (nums.filter('[data-settings~="hide"]').length != 0) {
                    numsContent.ready(function () {
                        UrvanovSyntaxHighlighterUtil.log('function' + uid);
                        UrvanovSyntaxHighlighterSyntax.toggleNums(uid, true, true);
                    });
                } else {
                    updateNumsButton(uid);
                }

                if (typeof urvanov_syntax_highlighters[uid].expanded == 'undefined') {
                    // Determine if we should enable code expanding toggling
                    if (Math.abs(urvanov_syntax_highlighters[uid].main.outerWidth() - urvanov_syntax_highlighters[uid].table.outerWidth()) < 10) {
                        urvanov_syntax_highlighters[uid].expandButton.hide();
                    } else {
                        urvanov_syntax_highlighters[uid].expandButton.show();
                    }
                }

                // TODO If width has changed or timeout, stop timer
                if (/*last_num_width != nums.outerWidth() ||*/ i == 5) {
                    clearInterval(load_timer);
                    //urvanov_syntax_highlighters[uid].removeClass(URVANOV_SYNTAX_HIGHLIGHTER_LOADING);
                    urvanov_syntax_highlighters[uid].loading = false;
                }
                i++;
            };
            load_timer = setInterval(load_func, 300);
            fixScrollBlank(uid);

            // Add ref to num for each line
            $(URVANOV_SYNTAX_HIGHLIGHTER_NUM, urvanov_syntax_highlighters[uid]).each(function () {
                var lineID = $(this).attr('data-line');
                var line = $('#' + lineID);
                var height = line.style('height');
                if (height) {
                    line.attr('data-height', height);
                }
            });

            // Used for toggling
            main.css('position', 'relative');
            main.css('z-index', 1);

            // Disable certain features for touchscreen devices
            touchscreen = (c.filter('[data-settings~="touchscreen"]').length != 0);

            // Used to hide info
            if (!touchscreen) {
                main.click(function () {
                    urvanovSyntaxHighlighterInfo(uid, '', false);
                });
                plain.click(function () {
                    urvanovSyntaxHighlighterInfo(uid, '', false);
                });
                info.click(function () {
                    urvanovSyntaxHighlighterInfo(uid, '', false);
                });
            }

            // Used for code popup
            if (c.filter('[data-settings~="no-popup"]').length == 0) {
                urvanov_syntax_highlighters[uid].popup_settings = popupWindow(popupButton, {
                    height: screen.height - 200,
                    width: screen.width - 100,
                    top: 75,
                    left: 50,
                    scrollbars: 1,
                    windowURL: '',
                    data: '' // Data overrides URL
                }, function () {
                    codePopup(uid);
                }, function () {
                    //UrvanovSyntaxHighlighterUtil.log('after');
                });
            }

            plain.css('opacity', 0);

            urvanov_syntax_highlighters[uid].toolbarVisible = true;
            urvanov_syntax_highlighters[uid].hasOneLine = table.outerHeight() < toolbar.outerHeight() * 2;
            urvanov_syntax_highlighters[uid].toolbarMouseover = false;
            // If a toolbar with mouseover was found
            if (toolbar.filter('[data-settings~="mouseover"]').length != 0 && !touchscreen) {
                urvanov_syntax_highlighters[uid].toolbarMouseover = true;
                urvanov_syntax_highlighters[uid].toolbarVisible = false;

                toolbar.css('margin-top', '-' + toolbar.outerHeight() + 'px');
                toolbar.hide();
                // Overlay the toolbar if needed, only if doing so will not hide the
                // whole code!
                if (toolbar.filter('[data-settings~="overlay"]').length != 0
                    && !urvanov_syntax_highlighters[uid].hasOneLine) {
                    toolbar.css('position', 'absolute');
                    toolbar.css('z-index', 2);
                    // Hide on single click when overlayed
                    if (toolbar.filter('[data-settings~="hide"]').length != 0) {
                        main.click(function () {
                            toggleToolbar(uid, undefined, undefined, 0);
                        });
                        plain.click(function () {
                            toggleToolbar(uid, false, undefined, 0);
                        });
                    }
                } else {
                    toolbar.css('z-index', 4);
                }
                // Enable delay on mouseout
                if (toolbar.filter('[data-settings~="delay"]').length != 0) {
                    urvanov_syntax_highlighters[uid].toolbar_delay = 500;
                }
                // Use .hover() for chrome, but in firefox mouseover/mouseout worked best
                c.mouseenter(function () {
                    toggleToolbar(uid, true);
                })
                    .mouseleave(function () {
                        toggleToolbar(uid, false);
                    });
            } else if (touchscreen) {
                toolbar.show();
            }

            // Minimize
            if (c.filter('[data-settings~="minimize"]').length == 0) {
                base.minimize(uid);
            }

            // Plain show events
            if (plain.length != 0 && !touchscreen) {
                if (plain.filter('[data-settings~="dblclick"]').length != 0) {
                    main.dblclick(function () {
                        UrvanovSyntaxHighlighterSyntax.togglePlain(uid);
                    });
                } else if (plain.filter('[data-settings~="click"]').length != 0) {
                    main.click(function () {
                        UrvanovSyntaxHighlighterSyntax.togglePlain(uid);
                    });
                } else if (plain.filter('[data-settings~="mouseover"]').length != 0) {
                    c.mouseenter(function () {
                        UrvanovSyntaxHighlighterSyntax.togglePlain(uid, true);
                    })
                        .mouseleave(function () {
                            UrvanovSyntaxHighlighterSyntax.togglePlain(uid, false);
                        });
                    numsButton.hide();
                }
                if (plain.filter('[data-settings~="show-plain-default"]').length != 0) {
                    // XXX
                    UrvanovSyntaxHighlighterSyntax.togglePlain(uid, true);
                }
            }

            // Scrollbar show events
            var expand = c.filter('[data-settings~="expand"]').length != 0;
//            urvanov_syntax_highlighters[uid].mouse_expand = expand;
            if (!touchscreen && c.filter('[data-settings~="scroll-mouseover"]').length != 0) {
                // Disable on touchscreen devices and when set to mouseover
                main.css('overflow', 'hidden');
                plain.css('overflow', 'hidden');
                c.mouseenter(function () {
                    toggle_scroll(uid, true, expand);
                })
                .mouseleave(function () {
                    toggle_scroll(uid, false, expand);
                });
            }

            if (expand) {
                c.mouseenter(function () {
                    toggleExpand(uid, true);
                })
                    .mouseleave(function () {
                        toggleExpand(uid, false);
                    });
            }

            // Disable animations
            if (c.filter('[data-settings~="disable-anim"]').length != 0) {
                urvanov_syntax_highlighters[uid].time = 0;
            }

            // Wrap
            if (c.filter('[data-settings~="wrap"]').length != 0) {
                urvanov_syntax_highlighters[uid].wrapped = true;
            }

            // Determine if Mac
            urvanov_syntax_highlighters[uid].mac = c.hasClass('urvanov-syntax-highlighter-os-mac');

            // Update clickable buttons
            updateNumsButton(uid);
            updatePlainButton(uid);
            updateWrap(uid);
        };

        var makeUID = function (uid) {
            UrvanovSyntaxHighlighterUtil.log(urvanov_syntax_highlighters);
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                urvanov_syntax_highlighters[uid] = $('#' + uid);
                UrvanovSyntaxHighlighterUtil.log('make ' + uid);
                return true;
            }

            UrvanovSyntaxHighlighterUtil.log('no make ' + uid);
            return false;
        };

        var getUID = function () {
            return currUID++;
        };

        var codePopup = function (uid) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                return makeUID(uid);
            }
            var settings = urvanov_syntax_highlighters[uid].popup_settings;
            if (settings && settings.data) {
                // Already done
                return;
            }

            var clone = urvanov_syntax_highlighters[uid].clone(true);
            clone.removeClass('urvanov-syntax-highlighter-wrapped');

            // Unwrap
            if (urvanov_syntax_highlighters[uid].wrapped) {
                $(URVANOV_SYNTAX_HIGHLIGHTER_NUM, clone).each(function () {
                    var line_id = $(this).attr('data-line');
                    var line = $('#' + line_id);
                    var height = line.attr('data-height');
                    height = height ? height : '';
                    if (typeof height != 'undefined') {
                        line.css('height', height);
                        $(this).css('height', height);
                    }
                });
            }
            clone.find(URVANOV_SYNTAX_HIGHLIGHTER_MAIN).css('height', '');

            var code = '';
            if (urvanov_syntax_highlighters[uid].plainVisible) {
                code = clone.find(URVANOV_SYNTAX_HIGHLIGHTER_PLAIN);
            } else {
                code = clone.find(URVANOV_SYNTAX_HIGHLIGHTER_MAIN);
            }

            settings.data = base.getAllCSS() + '<body class="urvanov-syntax-highlighter-popup-window" style="padding:0; margin:0;"><div class="' + clone.attr('class') +
                ' urvanov-syntax-highlighter-popup">' + base.removeCssInline(base.getHtmlString(code)) + '</div></body>';
        };

        base.minimize = function (uid) {
            var button = $('<div class="urvanov-syntax-highlighter-minimize urvanov-syntax-highlighter-button"><div>');
            urvanov_syntax_highlighters[uid].tools.append(button);
            // TODO translate
            urvanov_syntax_highlighters[uid].origTitle = urvanov_syntax_highlighters[uid].title.html();
            if (!urvanov_syntax_highlighters[uid].origTitle) {
                urvanov_syntax_highlighters[uid].title.html(strings.minimize);
            };
            var cls = 'urvanov-syntax-highlighter-minimized';
            var show = function () {
                urvanov_syntax_highlighters[uid].toolbarPreventHide = false;
                button.remove();
                urvanov_syntax_highlighters[uid].removeClass(cls);
                urvanov_syntax_highlighters[uid].title.html(urvanov_syntax_highlighters[uid].origTitle);
                var toolbar = urvanov_syntax_highlighters[uid].toolbar;
                if (toolbar.filter('[data-settings~="never-show"]').length != 0) {
                    toolbar.remove();
                }
            };
            urvanov_syntax_highlighters[uid].toolbar.click(show);
            button.click(show);
            urvanov_syntax_highlighters[uid].addClass(cls);
            urvanov_syntax_highlighters[uid].toolbarPreventHide = true;
            toggleToolbar(uid, undefined, undefined, 0);
        }

        base.getHtmlString = function (object) {
            return $('<div>').append(object.clone()).remove().html();
        };

        base.removeCssInline = function (string) {
            var reStyle = /style\s*=\s*"([^"]+)"/gmi;
            var match = null;
            while ((match = reStyle.exec(string)) != null) {
                var repl = match[1];
                repl = repl.replace(/\b(?:width|height)\s*:[^;]+;/gmi, '');
                string = string.sliceReplace(match.index, match.index + match[0].length, 'style="' + repl + '"');
            }
            return string;
        };

        // Get all CSS on the page as a string
        base.getAllCSS = function () {
            var css_str = '';
            var css = $('link[rel="stylesheet"]');
            var filtered = [];
            if (css.length == 1) {
                // For minified CSS, only allow a single file
                filtered = css;
            } else {
                // Filter all others for UrvanovSyntaxHighlighter CSS
                filtered = css.filter('[href*="urvanov-syntax-highlighter"], [href*="min/"]');
            }
            filtered.each(function () {
                var string = base.getHtmlString($(this));
                css_str += string;
            });
            return css_str;
        };

        base.copyPlain = function (uid, hover) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                return makeUID(uid);
            }

            var plain = urvanov_syntax_highlighters[uid].plain;

            base.togglePlain(uid, true, true);
            toggleToolbar(uid, true);

            var key = urvanov_syntax_highlighters[uid].mac ? '\u2318' : 'CTRL';
            var text = strings.copy;
            text = text.replace(/%s/, key + '+C');
            text = text.replace(/%s/, key + '+V');
            urvanovSyntaxHighlighterInfo(uid, text);
            return false;
        };

        var urvanovSyntaxHighlighterInfo = function (uid, text, show) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                return makeUID(uid);
            }

            var info = urvanov_syntax_highlighters[uid].info;

            if (typeof text == 'undefined') {
                text = '';
            }
            if (typeof show == 'undefined') {
                show = true;
            }

            if (isSlideHidden(info) && show) {
                info.html('<div>' + text + '</div>');
                info.css('margin-top', -info.outerHeight());
                info.show();
                urvanovSyntaxHighlighterSlide(uid, info, true);
                setTimeout(function () {
                    urvanovSyntaxHighlighterSlide(uid, info, false);
                }, 5000);
            }

            if (!show) {
                urvanovSyntaxHighlighterSlide(uid, info, false);
            }

        };

        var retina = function (uid) {
            if (window.devicePixelRatio > 1) {
                var buttons = $('.urvanov-syntax-highlighter-button-icon', urvanov_syntax_highlighters[uid].toolbar);
                buttons.each(function () {
                    var lowres = $(this).css('background-image');
                    var highres = lowres.replace(/\.(?=[^\.]+$)/g, '@2x.');
                    $(this).css('background-size', '48px 128px');
                    $(this).css('background-image', highres);
                });
            }
        };

        var isSlideHidden = function (object) {
            var object_neg_height = '-' + object.outerHeight() + 'px';
            if (object.css('margin-top') == object_neg_height || object.css('display') == 'none') {
                return true;
            } else {
                return false;
            }
        };

        var urvanovSyntaxHighlighterSlide = function (uid, object, show, animTime, hideDelay, callback) {
            var complete = function () {
                if (callback) {
                    callback(uid, object);
                }
            }
            var objectNegHeight = '-' + object.outerHeight() + 'px';

            if (typeof show == 'undefined') {
                if (isSlideHidden(object)) {
                    show = true;
                } else {
                    show = false;
                }
            }
            // Instant means no time delay for showing/hiding
            if (typeof animTime == 'undefined') {
                animTime = 100;
            }
            if (animTime == false) {
                animTime = false;
            }
            if (typeof hideDelay == 'undefined') {
                hideDelay = 0;
            }
            object.stop(true);
            if (show == true) {
                object.show();
                object.animate({
                    marginTop: 0
                }, animt(animTime, uid), complete);
            } else if (show == false) {
                // Delay if fully visible
                if (/*instant == false && */object.css('margin-top') == '0px' && hideDelay) {
                    object.delay(hideDelay);
                }
                object.animate({
                    marginTop: objectNegHeight
                }, animt(animTime, uid), function () {
                    object.hide();
                    complete();
                });
            }
        };

        base.togglePlain = function (uid, hover, select) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                return makeUID(uid);
            }

            var main = urvanov_syntax_highlighters[uid].main;
            var plain = urvanov_syntax_highlighters[uid].plain;

            if ((main.is(':animated') || plain.is(':animated')) && typeof hover == 'undefined') {
                return;
            }

            reconsileDimensions(uid);

            var visible, hidden;
            if (typeof hover != 'undefined') {
                if (hover) {
                    visible = main;
                    hidden = plain;
                } else {
                    visible = plain;
                    hidden = main;
                }
            } else {
                if (main.css('z-index') == 1) {
                    visible = main;
                    hidden = plain;
                } else {
                    visible = plain;
                    hidden = main;
                }
            }

            urvanov_syntax_highlighters[uid].plainVisible = (hidden == plain);

            // Remember scroll positions of visible
            urvanov_syntax_highlighters[uid].top = visible.scrollTop();
            urvanov_syntax_highlighters[uid].left = visible.scrollLeft();

            /* Used to detect a change in overflow when the mouse moves out
             * of the UrvanovSyntaxHighlighter. If it does, then overflow has already been changed,
             * no need to revert it after toggling plain. */
            urvanov_syntax_highlighters[uid].scrollChanged = false;

            // Hide scrollbars during toggle to avoid Chrome weird draw error
            // visible.css('overflow', 'hidden');
            // hidden.css('overflow', 'hidden');

            fixScrollBlank(uid);

            // Show hidden, hide visible
            visible.stop(true);
            visible.fadeTo(animt(500, uid), 0,
                function () {
                    visible.css('z-index', 0);
                });
            hidden.stop(true);
            hidden.fadeTo(animt(500, uid), 1,
                function () {
                    hidden.css('z-index', 1);
                    // Give focus to plain code
                    if (hidden == plain) {
                        if (select) {
                            plain.select();
                        } else {
                            // XXX not needed
                            // plain.focus();
                        }
                    }

                    // Refresh scrollbar draw
                    hidden.scrollTop(urvanov_syntax_highlighters[uid].top + 1);
                    hidden.scrollTop(urvanov_syntax_highlighters[uid].top);
                    hidden.scrollLeft(urvanov_syntax_highlighters[uid].left + 1);
                    hidden.scrollLeft(urvanov_syntax_highlighters[uid].left);
                });

            // Restore scroll positions to hidden
            hidden.scrollTop(urvanov_syntax_highlighters[uid].top);
            hidden.scrollLeft(urvanov_syntax_highlighters[uid].left);

            updatePlainButton(uid);

            // Hide toolbar if possible
            toggleToolbar(uid, false);
            return false;
        };

        base.toggleNums = function (uid, hide, instant) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                makeUID(uid);
                return false;
            }

            if (urvanov_syntax_highlighters[uid].table.is(':animated')) {
                return false;
            }
            var numsWidth = Math.round(urvanov_syntax_highlighters[uid].nums_content.outerWidth() + 1);
            var negWidth = '-' + numsWidth + 'px';

            // Force hiding
            var numHidden;
            if (typeof hide != 'undefined') {
                numHidden = false;
            } else {
                // Check hiding
                numHidden = (urvanov_syntax_highlighters[uid].table.css('margin-left') == negWidth);
            }

            var numMargin;
            if (numHidden) {
                // Show
                numMargin = '0px';
                urvanov_syntax_highlighters[uid].numsVisible = true;
            } else {
                // Hide
                urvanov_syntax_highlighters[uid].table.css('margin-left', '0px');
                urvanov_syntax_highlighters[uid].numsVisible = false;
                numMargin = negWidth;
            }

            if (typeof instant != 'undefined') {
                urvanov_syntax_highlighters[uid].table.css('margin-left', numMargin);
                updateNumsButton(uid);
                return false;
            }

            // Stop jerking animation from scrollbar appearing for a split second due to
            // change in width. Prevents scrollbar disappearing if already visible.
            var h_scroll_visible = (urvanov_syntax_highlighters[uid].table.outerWidth() + pxToInt(urvanov_syntax_highlighters[uid].table.css('margin-left')) > urvanov_syntax_highlighters[uid].main.outerWidth());
            var v_scroll_visible = (urvanov_syntax_highlighters[uid].table.outerHeight() > urvanov_syntax_highlighters[uid].main.outerHeight());
            if (!h_scroll_visible && !v_scroll_visible) {
                urvanov_syntax_highlighters[uid].main.css('overflow', 'hidden');
            }
            urvanov_syntax_highlighters[uid].table.animate({
                marginLeft: numMargin
            }, animt(200, uid), function () {
                if (typeof urvanov_syntax_highlighters[uid] != 'undefined') {
                    updateNumsButton(uid);
                    if (!h_scroll_visible && !v_scroll_visible) {
                        urvanov_syntax_highlighters[uid].main.css('overflow', 'auto');
                    }
                }
            });
            return false;
        };

        base.toggleWrap = function (uid) {
            urvanov_syntax_highlighters[uid].wrapped = !urvanov_syntax_highlighters[uid].wrapped;
            updateWrap(uid);
        };

        base.toggleExpand = function (uid) {
            var expand = !UrvanovSyntaxHighlighterUtil.setDefault(urvanov_syntax_highlighters[uid].expanded, false);
            toggleExpand(uid, expand);
        };

        var updateWrap = function (uid, restore) {
            restore = UrvanovSyntaxHighlighterUtil.setDefault(restore, true);
            if (urvanov_syntax_highlighters[uid].wrapped) {
                urvanov_syntax_highlighters[uid].addClass(URVANOV_SYNTAX_HIGHLIGHTER_WRAPPED);
            } else {
                urvanov_syntax_highlighters[uid].removeClass(URVANOV_SYNTAX_HIGHLIGHTER_WRAPPED);
            }
            updateWrapButton(uid);
            if (!urvanov_syntax_highlighters[uid].expanded && restore) {
                restoreDimensions(uid);
            }
            urvanov_syntax_highlighters[uid].wrapTimes = 0;
            clearInterval(urvanov_syntax_highlighters[uid].wrapTimer);
            urvanov_syntax_highlighters[uid].wrapTimer = setInterval(function () {
                if (urvanov_syntax_highlighters[uid].is(':visible')) {
                    // XXX if hidden the height can't be determined
                    reconsileLines(uid);
                    urvanov_syntax_highlighters[uid].wrapTimes++;
                    if (urvanov_syntax_highlighters[uid].wrapTimes == 5) {
                        clearInterval(urvanov_syntax_highlighters[uid].wrapTimer);
                    }
                }
            }, 200);
        };

        var fixTableWidth = function (uid) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                makeUID(uid);
                return false;
            }
        };

        // Convert '-10px' to -10
        var pxToInt = function (pixels) {
            if (typeof pixels != 'string') {
                return 0;
            }
            var result = pixels.replace(/[^-0-9]/g, '');
            if (result.length == 0) {
                return 0;
            } else {
                return parseInt(result);
            }
        };

        var updateNumsButton = function (uid) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined' || typeof urvanov_syntax_highlighters[uid].numsVisible == 'undefined') {
                return;
            }
            if (urvanov_syntax_highlighters[uid].numsVisible) {
                urvanov_syntax_highlighters[uid].numsButton.removeClass(UNPRESSED);
                urvanov_syntax_highlighters[uid].numsButton.addClass(PRESSED);
            } else {
                // TODO doesn't work on iPhone
                urvanov_syntax_highlighters[uid].numsButton.removeClass(PRESSED);
                urvanov_syntax_highlighters[uid].numsButton.addClass(UNPRESSED);
            }
        };

        var updateWrapButton = function (uid) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined' || typeof urvanov_syntax_highlighters[uid].wrapped == 'undefined') {
                return;
            }
            if (urvanov_syntax_highlighters[uid].wrapped) {
                urvanov_syntax_highlighters[uid].wrapButton.removeClass(UNPRESSED);
                urvanov_syntax_highlighters[uid].wrapButton.addClass(PRESSED);
            } else {
                // TODO doesn't work on iPhone
                urvanov_syntax_highlighters[uid].wrapButton.removeClass(PRESSED);
                urvanov_syntax_highlighters[uid].wrapButton.addClass(UNPRESSED);
            }
        };

        var updateExpandButton = function (uid) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined' || typeof urvanov_syntax_highlighters[uid].expanded == 'undefined') {
                return;
            }

            if (urvanov_syntax_highlighters[uid].expanded) {
                urvanov_syntax_highlighters[uid].expandButton.removeClass(UNPRESSED);
                urvanov_syntax_highlighters[uid].expandButton.addClass(PRESSED);
            } else {
                // TODO doesn't work on iPhone
                urvanov_syntax_highlighters[uid].expandButton.removeClass(PRESSED);
                urvanov_syntax_highlighters[uid].expandButton.addClass(UNPRESSED);
            }
        };

        var updatePlainButton = function (uid) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined' || typeof urvanov_syntax_highlighters[uid].plainVisible == 'undefined') {
                return;
            }

            if (urvanov_syntax_highlighters[uid].plainVisible) {
                urvanov_syntax_highlighters[uid].plainButton.removeClass(UNPRESSED);
                urvanov_syntax_highlighters[uid].plainButton.addClass(PRESSED);
            } else {
                // TODO doesn't work on iPhone
                urvanov_syntax_highlighters[uid].plainButton.removeClass(PRESSED);
                urvanov_syntax_highlighters[uid].plainButton.addClass(UNPRESSED);
            }
        };

        var toggleToolbar = function (uid, show, animTime, hideDelay) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                return makeUID(uid);
            } else if (!urvanov_syntax_highlighters[uid].toolbarMouseover) {
                return;
            } else if (show == false && urvanov_syntax_highlighters[uid].toolbarPreventHide) {
                return;
            } else if (touchscreen) {
                return;
            }
            var toolbar = urvanov_syntax_highlighters[uid].toolbar;

            if (typeof hideDelay == 'undefined') {
                hideDelay = urvanov_syntax_highlighters[uid].toolbar_delay;
            }

            urvanovSyntaxHighlighterSlide(uid, toolbar, show, animTime, hideDelay, function () {
                urvanov_syntax_highlighters[uid].toolbarVisible = show;
            });
        };

        var addSize = function (orig, add) {
            var copy = $.extend({}, orig);
            copy.width += add.width;
            copy.height += add.height;
            return copy;
        };

        var minusSize = function (orig, minus) {
            var copy = $.extend({}, orig);
            copy.width -= minus.width;
            copy.height -= minus.height;
            return copy;
        };

        var initSize = function (uid) {
            if (typeof urvanov_syntax_highlighters[uid].initialSize == 'undefined') {
                // Shared for scrollbars and expanding
                urvanov_syntax_highlighters[uid].toolbarHeight = urvanov_syntax_highlighters[uid].toolbar.outerHeight();
                urvanov_syntax_highlighters[uid].innerSize = {width: urvanov_syntax_highlighters[uid].width(), height: urvanov_syntax_highlighters[uid].height()};
                urvanov_syntax_highlighters[uid].outerSize = {width: urvanov_syntax_highlighters[uid].outerWidth(), height: urvanov_syntax_highlighters[uid].outerHeight()};
                urvanov_syntax_highlighters[uid].borderSize = minusSize(urvanov_syntax_highlighters[uid].outerSize, urvanov_syntax_highlighters[uid].innerSize);
                urvanov_syntax_highlighters[uid].initialSize = {width: urvanov_syntax_highlighters[uid].main.outerWidth(), height: urvanov_syntax_highlighters[uid].main.outerHeight()};
                urvanov_syntax_highlighters[uid].initialSize.height += urvanov_syntax_highlighters[uid].toolbarHeight;
                urvanov_syntax_highlighters[uid].initialOuterSize = addSize(urvanov_syntax_highlighters[uid].initialSize, urvanov_syntax_highlighters[uid].borderSize);
                urvanov_syntax_highlighters[uid].finalSize = {width: urvanov_syntax_highlighters[uid].table.outerWidth(), height: urvanov_syntax_highlighters[uid].table.outerHeight()};
                urvanov_syntax_highlighters[uid].finalSize.height += urvanov_syntax_highlighters[uid].toolbarHeight;
                // Ensure we don't shrink
                urvanov_syntax_highlighters[uid].finalSize.width = UrvanovSyntaxHighlighterUtil.setMin(urvanov_syntax_highlighters[uid].finalSize.width, urvanov_syntax_highlighters[uid].initialSize.width);
                urvanov_syntax_highlighters[uid].finalSize.height = UrvanovSyntaxHighlighterUtil.setMin(urvanov_syntax_highlighters[uid].finalSize.height, urvanov_syntax_highlighters[uid].initialSize.height);
                urvanov_syntax_highlighters[uid].diffSize = minusSize(urvanov_syntax_highlighters[uid].finalSize, urvanov_syntax_highlighters[uid].initialSize);
                urvanov_syntax_highlighters[uid].finalOuterSize = addSize(urvanov_syntax_highlighters[uid].finalSize, urvanov_syntax_highlighters[uid].borderSize);
                urvanov_syntax_highlighters[uid].initialSize.height += urvanov_syntax_highlighters[uid].toolbar.outerHeight();
            }
        };

        var toggleExpand = function (uid, expand) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                return makeUID(uid);
            }
            if (typeof expand == 'undefined') {
                return;
            }

            var main = urvanov_syntax_highlighters[uid].main;
            var plain = urvanov_syntax_highlighters[uid].plain;

            if (expand) {
                if (typeof urvanov_syntax_highlighters[uid].expanded == 'undefined') {
                    initSize(uid);
                    urvanov_syntax_highlighters[uid].expandTime = UrvanovSyntaxHighlighterUtil.setRange(urvanov_syntax_highlighters[uid].diffSize.width / 3, 300, 800);
                    urvanov_syntax_highlighters[uid].expanded = false;
                    var placeHolderSize = urvanov_syntax_highlighters[uid].finalOuterSize;
                    urvanov_syntax_highlighters[uid].placeholder = $('<div></div>');
                    urvanov_syntax_highlighters[uid].placeholder.addClass(URVANOV_SYNTAX_HIGHLIGHTER_PLACEHOLDER);
                    urvanov_syntax_highlighters[uid].placeholder.css(placeHolderSize);
                    urvanov_syntax_highlighters[uid].before(urvanov_syntax_highlighters[uid].placeholder);
                    urvanov_syntax_highlighters[uid].placeholder.css('margin', urvanov_syntax_highlighters[uid].css('margin'));
                    $(window).bind('resize', placeholderResize);
                }

                var expandHeight = {
                    'height': 'auto',
                    'min-height': 'none',
                    'max-height': 'none'
                };
                var expandWidth = {
                    'width': 'auto',
                    'min-width': 'none',
                    'max-width': 'none'
                };

                urvanov_syntax_highlighters[uid].outerWidth(urvanov_syntax_highlighters[uid].outerWidth());
                urvanov_syntax_highlighters[uid].css({
                    'min-width': 'none',
                    'max-width': 'none'
                });
                var newSize = {
                    width: urvanov_syntax_highlighters[uid].finalOuterSize.width
                };
                if (!urvanov_syntax_highlighters[uid].mainHeightAuto && !urvanov_syntax_highlighters[uid].hasOneLine) {
                    newSize.height = urvanov_syntax_highlighters[uid].finalOuterSize.height;
                    urvanov_syntax_highlighters[uid].outerHeight(urvanov_syntax_highlighters[uid].outerHeight());
                }

                main.css(expandHeight);
                main.css(expandWidth);
                urvanov_syntax_highlighters[uid].stop(true);

                urvanov_syntax_highlighters[uid].animate(newSize, animt(urvanov_syntax_highlighters[uid].expandTime, uid), function () {
                    urvanov_syntax_highlighters[uid].expanded = true;
                    updateExpandButton(uid);
                });

                urvanov_syntax_highlighters[uid].placeholder.show();
                $('body').prepend(urvanov_syntax_highlighters[uid]);
                urvanov_syntax_highlighters[uid].addClass(URVANOV_SYNTAX_HIGHLIGHTER_EXPANDED);
                placeholderResize();
            } else {
                var initialSize = urvanov_syntax_highlighters[uid].initialOuterSize;
                var delay = urvanov_syntax_highlighters[uid].toolbar_delay;
                if (initialSize) {
                    urvanov_syntax_highlighters[uid].stop(true);
                    if (!urvanov_syntax_highlighters[uid].expanded) {
                        urvanov_syntax_highlighters[uid].delay(delay);
                    }
                    var newSize = {
                        width: initialSize.width
                    };
                    if (!urvanov_syntax_highlighters[uid].mainHeightAuto && !urvanov_syntax_highlighters[uid].hasOneLine) {
                        newSize.height = initialSize.height;
                    }
                    urvanov_syntax_highlighters[uid].animate(newSize, animt(urvanov_syntax_highlighters[uid].expandTime, uid), function () {
                        expandFinish(uid);
                    });
                } else {
                    setTimeout(function () {
                       expandFinish(uid);
                    }, delay);
                }
                urvanov_syntax_highlighters[uid].placeholder.hide();
                urvanov_syntax_highlighters[uid].placeholder.before(urvanov_syntax_highlighters[uid]);
                urvanov_syntax_highlighters[uid].css({left: 'auto', top: 'auto'});
                urvanov_syntax_highlighters[uid].removeClass(URVANOV_SYNTAX_HIGHLIGHTER_EXPANDED);
            }

            reconsileDimensions(uid);
            if (expand) {
                updateWrap(uid, false);
            }
        };

        var placeholderResize = function () {
            for (uid in urvanov_syntax_highlighters) {
                if (urvanov_syntax_highlighters[uid].hasClass(URVANOV_SYNTAX_HIGHLIGHTER_EXPANDED)) {
                    urvanov_syntax_highlighters[uid].css(urvanov_syntax_highlighters[uid].placeholder.offset());
                }
            }
        };

        var expandFinish = function(uid) {
            urvanov_syntax_highlighters[uid].expanded = false;
            restoreDimensions(uid);
            updateExpandButton(uid);
            if (urvanov_syntax_highlighters[uid].wrapped) {
                updateWrap(uid);
            }
        };

        var toggle_scroll = function (uid, show, expand) {
            if (typeof urvanov_syntax_highlighters[uid] == 'undefined') {
                return makeUID(uid);
            }
            if (typeof show == 'undefined' || expand || urvanov_syntax_highlighters[uid].expanded) {
                return;
            }

            var main = urvanov_syntax_highlighters[uid].main;
            var plain = urvanov_syntax_highlighters[uid].plain;

            if (show) {
                // Show scrollbars
                main.css('overflow', 'auto');
                plain.css('overflow', 'auto');
                if (typeof urvanov_syntax_highlighters[uid].top != 'undefined') {
                    visible = (main.css('z-index') == 1 ? main : plain);
                    // Browser will not render until scrollbar moves, move it manually
                    visible.scrollTop(urvanov_syntax_highlighters[uid].top - 1);
                    visible.scrollTop(urvanov_syntax_highlighters[uid].top);
                    visible.scrollLeft(urvanov_syntax_highlighters[uid].left - 1);
                    visible.scrollLeft(urvanov_syntax_highlighters[uid].left);
                }
            } else {
                // Hide scrollbars
                visible = (main.css('z-index') == 1 ? main : plain);
                urvanov_syntax_highlighters[uid].top = visible.scrollTop();
                urvanov_syntax_highlighters[uid].left = visible.scrollLeft();
                main.css('overflow', 'hidden');
                plain.css('overflow', 'hidden');
            }
            // Register that overflow has changed
            urvanov_syntax_highlighters[uid].scrollChanged = true;
            fixScrollBlank(uid);
        };

        /* Fix weird draw error, causes blank area to appear where scrollbar once was. */
        var fixScrollBlank = function (uid) {
            // Scrollbar draw error in Chrome
            urvanov_syntax_highlighters[uid].table.style('width', '100%', 'important');
            var redraw = setTimeout(function () {
                urvanov_syntax_highlighters[uid].table.style('width', '');
                clearInterval(redraw);
            }, 10);
        };

        var restoreDimensions = function (uid) {
            // Restore dimensions
            var main = urvanov_syntax_highlighters[uid].main;
            var mainStyle = urvanov_syntax_highlighters[uid].mainStyle;
            main.css(mainStyle);
            // Width styles also apply to urvanovSyntaxHighlighter
            urvanov_syntax_highlighters[uid].css('height', 'auto');
            urvanov_syntax_highlighters[uid].css('width', mainStyle['width']);
            urvanov_syntax_highlighters[uid].css('max-width', mainStyle['max-width']);
            urvanov_syntax_highlighters[uid].css('min-width', mainStyle['min-width']);
        };

        var reconsileDimensions = function (uid) {
            // Reconsile dimensions
            urvanov_syntax_highlighters[uid].plain.outerHeight(urvanov_syntax_highlighters[uid].main.outerHeight());
        };

        var reconsileLines = function (uid) {
            $(URVANOV_SYNTAX_HIGHLIGHTER_NUM, urvanov_syntax_highlighters[uid]).each(function () {
                var lineID = $(this).attr('data-line');
                var line = $('#' + lineID);
                var height = null;
                if (urvanov_syntax_highlighters[uid].wrapped) {
                    line.css('height', '');
                    height = line.outerHeight();
                    height = height ? height : '';
                    // TODO toolbar should overlay title if needed
                } else {
                    height = line.attr('data-height');
                    height = height ? height : '';
                    line.css('height', height);
                    //line.css('height', line.css('line-height'));
                }
                $(this).css('height', height);
            });
        };

        var animt = function (x, uid) {
            if (x == 'fast') {
                x = 200;
            } else if (x == 'slow') {
                x = 600;
            } else if (!isNumber(x)) {
                x = parseInt(x);
                if (isNaN(x)) {
                    return 0;
                }
            }
            return x * urvanov_syntax_highlighters[uid].time;
        };

        var isNumber = function (x) {
            return typeof x == 'number';
        };

    };

    $(document).ready(function () {
        UrvanovSyntaxHighlighterSyntax.init();
    });
})(jQueryUrvanovSyntaxHighlighter);
