<?php





if ( !class_exists( 'Urvanov_Syntax_Highlighter_Global' ) ) {
	class Urvanov_Syntax_Highlighter_Global {
		

		
		// Turn on the error & exception handlers
		//crayon_handler_on();
		
		// GLOBAL FUNCTIONS
		
		// Check for forwardslash/backslash in folder path to structure paths
		// Old name crayon_s
		public static function fix_s($url = '') {
		    $url = strval($url);
		    if (!empty($url) && !preg_match('#(\\\\|/)$#', $url)) {
		        return $url . '/';
		    } else if (empty($url)) {
		        return '/';
		    } else {
		        return $url;
		    }
		}
		
		// Returns path using forward slashes, slash added at the end
		// Old name crayon_pf
		public static function path_forward_slashes($url, $slash = TRUE) {
		    $url = trim(strval($url));
		    if ($slash) {
		        $url = Urvanov_Syntax_Highlighter_Global::fix_s($url);
		    }
		    return str_replace('\\', '/', $url);
		}
		
		// Returns path using back slashes
		// Old name: crayon_pb
		public static function path_back_slashes($url) {
		    return str_replace('/', '\\', Urvanov_Syntax_Highlighter_Global::fix_s(trim(strval($url))));
		}
		
		// Get/Set plugin information
		// Old name: crayon_set_info
		public static function set_info($info_array) {
		    global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION, $URVANOV_SYNTAX_HIGHLIGHTER_DATE, $URVANOV_SYNTAX_HIGHLIGHTER_AUTHOR, $URVANOV_SYNTAX_HIGHLIGHTER_WEBSITE;
		    if (!is_array($info_array)) {
		        return;
		    }
		    Urvanov_Syntax_Highlighter_Global::set_info_key('Version', $info_array, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
		    Urvanov_Syntax_Highlighter_Global::set_info_key('Date', $info_array, $URVANOV_SYNTAX_HIGHLIGHTER_DATE);
		    Urvanov_Syntax_Highlighter_Global::set_info_key('AuthorName', $info_array, $URVANOV_SYNTAX_HIGHLIGHTER_AUTHOR);
		    Urvanov_Syntax_Highlighter_Global::set_info_key('PluginURI', $info_array, $URVANOV_SYNTAX_HIGHLIGHTER_WEBSITE);
		}
		
		// Old name: crayon_set_info_key
		public static function set_info_key($key, $array, &$info) {
		    if (array_key_exists($key, $array)) {
		        $info = $array[$key];
		    } else {
		        return FALSE;
		    }
		}
		
		// Old name: crayon_vargs
		public static function vargs(&$var, $default) {
		    $var = isset($var) ? $var : $default;
		}
		
		// Checks if the input is a valid PHP file and matches the $valid filename
		// Old name: crayon_is_php_file
		public static function is_php_file($filepath, $valid) {
		    $path = pathinfo(Urvanov_Syntax_Highlighter_Global::path_forward_slashes($filepath));
		    return is_file($filepath) && $path['extension'] === 'php' && $path['filename'] === $valid;
		}
		
		// Stops the script if Urvanov_Syntax_Highlighter_Global::is_php_file() returns false or a remote path is given
		// Old name: crayon_die_if_not_php
		public static function die_if_not_php($filepath, $valid) {
		    if (!Urvanov_Syntax_Highlighter_Global::is_php_file($filepath, $valid) || Urvanov_Syntax_Highlighter_Global::is_path_url($filepath)) {
		        die("[ERROR] '$filepath' is not a valid PHP file for '$valid'");
		    }
		}
		
		// Old name: crayon_is_path_url
		public static function is_path_url($path) {
		    $parts = parse_url($path);
		    return isset($parts['scheme']) && strlen($parts['scheme']) > 1;
		}
		
		// LANGUAGE TRANSLATION FUNCTIONS
		
		// Old name: crayon_load_plugin_textdomain
		public static function load_plugin_textdomain() {
		    if (function_exists('load_plugin_textdomain')) {
		    	load_plugin_textdomain('urvanov-syntax-highlighter', false, URVANOV_SYNTAX_HIGHLIGHTER_DIR . URVANOV_SYNTAX_HIGHLIGHTER_TRANS_DIR);
		    }
		}
		
		// Old name: crayon__
		public static function urvanov__($text) {
		    if (function_exists('__')) {
		    	return __($text, 'urvanov-syntax-highlighter');
		    } else {
		        return $text;
		    }
		}
		
		// Old name: crayon_e
		public static function urvanov_e($text) {
		    if (function_exists('_e')) {
		    	_e($text, 'urvanov-syntax-highlighter');
		    } else {
		        echo $text;
		    }
		}
		
		// Old name: crayon_n
		public static function urvanov_n($singular, $plural, $count) {
		    if (function_exists('_n')) {
		    	return _n($singular, $plural, $count, 'urvanov-syntax-highlighter');
		    } else {
		        return $count > 1 ? $plural : $singular;
		    }
		}
		
		// Old name: crayon_x
		public static function urvanov_x($text, $context) {
		    if (function_exists('_x')) {
		    	return _x($text, $context, 'urvanov-syntax-highlighter');
		    } else {
		        return $text;
		    }
		}
	}
}



// Switches
define('URVANOV_SYNTAX_HIGHLIGHTER_DEBUG', FALSE);

define('URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR', TRUE);
define('URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR', TRUE);

define('URVANOV_SYNTAX_HIGHLIGHTER_MINIFY', TRUE);

// Constants


// These are overridden by functions since v1.1.1
$URVANOV_SYNTAX_HIGHLIGHTER_VERSION = '1.1.1';
$URVANOV_SYNTAX_HIGHLIGHTER_DATE = '22th August, 2020';
$URVANOV_SYNTAX_HIGHLIGHTER_AUTHOR = 'Fedor Urvanov';
$URVANOV_SYNTAX_HIGHLIGHTER_AUTHOR_SITE = 'https://urvanov.ru';
$URVANOV_SYNTAX_HIGHLIGHTER_DONATE = 'https://money.yandex.ru/to/41001288941320';
$URVANOV_SYNTAX_HIGHLIGHTER_WEBSITE = 'https://github.com/urvanov-ru/crayon-syntax-highlighter';
$URVANOV_SYNTAX_HIGHLIGHTER_EMAIL = 'fedor@urvanov.ru';
$URVANOV_SYNTAX_HIGHLIGHTER_TWITTER = 'http://twitter.com/crayonsyntax';
$URVANOV_SYNTAX_HIGHLIGHTER_GIT = 'https://github.com/urvanov-ru/crayon-syntax-highlighter';
$URVANOV_SYNTAX_HIGHLIGHTER_PLUGIN_WP = 'https://wordpress.org/plugins/urvanov-syntax-highlighter/';


// XXX Used to name the class

define('URVANOV_SYNTAX_HIGHLIGHTER_HIGHLIGHTER', 'Urvanov_Syntax_Highlighter');
define('URVANOV_SYNTAX_HIGHLIGHTER_ELEMENT_CLASS', 'Urvanov_Syntax_Highlighter_Element');
define('URVANOV_SYNTAX_HIGHLIGHTER_SETTING_CLASS', 'Urvanov_Syntax_Highlighter_Setting');

// Directories

define('URVANOV_SYNTAX_HIGHLIGHTER_DIR', Urvanov_Syntax_Highlighter_Global::path_forward_slashes(basename(dirname(__FILE__))));
define('URVANOV_SYNTAX_HIGHLIGHTER_LANG_DIR', Urvanov_Syntax_Highlighter_Global::fix_s('langs'));
define('URVANOV_SYNTAX_HIGHLIGHTER_THEME_DIR', Urvanov_Syntax_Highlighter_Global::fix_s('themes'));
define('URVANOV_SYNTAX_HIGHLIGHTER_FONT_DIR', Urvanov_Syntax_Highlighter_Global::fix_s('fonts'));
define('URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR', Urvanov_Syntax_Highlighter_Global::fix_s('util'));
define('URVANOV_SYNTAX_HIGHLIGHTER_CSS_DIR', Urvanov_Syntax_Highlighter_Global::fix_s('css'));
define('URVANOV_SYNTAX_HIGHLIGHTER_CSS_SRC_DIR', URVANOV_SYNTAX_HIGHLIGHTER_CSS_DIR . Urvanov_Syntax_Highlighter_Global::fix_s('src'));
define('URVANOV_SYNTAX_HIGHLIGHTER_CSS_MIN_DIR', URVANOV_SYNTAX_HIGHLIGHTER_CSS_DIR . Urvanov_Syntax_Highlighter_Global::fix_s('min'));
define('URVANOV_SYNTAX_HIGHLIGHTER_JS_DIR', Urvanov_Syntax_Highlighter_Global::fix_s('js'));
define('URVANOV_SYNTAX_HIGHLIGHTER_JS_SRC_DIR', URVANOV_SYNTAX_HIGHLIGHTER_JS_DIR . Urvanov_Syntax_Highlighter_Global::fix_s('src'));
define('URVANOV_SYNTAX_HIGHLIGHTER_JS_MIN_DIR', URVANOV_SYNTAX_HIGHLIGHTER_JS_DIR . Urvanov_Syntax_Highlighter_Global::fix_s('min'));
define('URVANOV_SYNTAX_HIGHLIGHTER_TRANS_DIR', Urvanov_Syntax_Highlighter_Global::fix_s('trans'));
define('URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_DIR', Urvanov_Syntax_Highlighter_Global::fix_s('theme-editor'));
define('URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR_DIR', Urvanov_Syntax_Highlighter_Global::fix_s('tag-editor'));

// Paths

define('URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH', Urvanov_Syntax_Highlighter_Global::path_forward_slashes(dirname(__FILE__)));
define('URVANOV_SYNTAX_HIGHLIGHTER_LANG_PATH', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . URVANOV_SYNTAX_HIGHLIGHTER_LANG_DIR);
define('URVANOV_SYNTAX_HIGHLIGHTER_THEME_PATH', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . URVANOV_SYNTAX_HIGHLIGHTER_THEME_DIR);
define('URVANOV_SYNTAX_HIGHLIGHTER_FONT_PATH', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . URVANOV_SYNTAX_HIGHLIGHTER_FONT_DIR);
define('URVANOV_SYNTAX_HIGHLIGHTER_UTIL_PATH', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR);
define('URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR_PATH', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR . URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR_DIR);
define('URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_PATH', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR . URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_DIR);

// Files

define('URVANOV_SYNTAX_HIGHLIGHTER_LOG_FILE', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'log.txt');
define('URVANOV_SYNTAX_HIGHLIGHTER_TOUCH_FILE', URVANOV_SYNTAX_HIGHLIGHTER_UTIL_PATH . 'touch.txt');
define('URVANOV_SYNTAX_HIGHLIGHTER_LOG_MAX_SIZE', 50000); // Bytes

define('URVANOV_SYNTAX_HIGHLIGHTER_README_FILE', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'readme.txt');
define('URVANOV_SYNTAX_HIGHLIGHTER_LANG_EXT', URVANOV_SYNTAX_HIGHLIGHTER_LANG_PATH . 'extensions.txt');
define('URVANOV_SYNTAX_HIGHLIGHTER_LANG_ALIAS', URVANOV_SYNTAX_HIGHLIGHTER_LANG_PATH . 'aliases.txt');
define('URVANOV_SYNTAX_HIGHLIGHTER_LANG_DELIM', URVANOV_SYNTAX_HIGHLIGHTER_LANG_PATH . 'delimiters.txt');
define('URVANOV_SYNTAX_HIGHLIGHTER_HELP_FILE', URVANOV_SYNTAX_HIGHLIGHTER_UTIL_PATH . 'help.htm');

// Minified
define('URVANOV_SYNTAX_HIGHLIGHTER_JS_MIN', URVANOV_SYNTAX_HIGHLIGHTER_JS_MIN_DIR . 'urvanov_syntax_highlighter.min.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_JS_TE_MIN', URVANOV_SYNTAX_HIGHLIGHTER_JS_MIN_DIR . 'urvanov_syntax_highlighter.te.min.js');

// Source
define('URVANOV_SYNTAX_HIGHLIGHTER_JQUERY_POPUP', URVANOV_SYNTAX_HIGHLIGHTER_JS_SRC_DIR . 'jquery.popup.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_JS', URVANOV_SYNTAX_HIGHLIGHTER_JS_SRC_DIR . 'urvanov_syntax_highlighter.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_JS_ADMIN', URVANOV_SYNTAX_HIGHLIGHTER_JS_SRC_DIR . 'urvanov_syntax_highlighter_admin.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_JS_UTIL', URVANOV_SYNTAX_HIGHLIGHTER_JS_SRC_DIR . 'util.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_CSSJSON_JS', URVANOV_SYNTAX_HIGHLIGHTER_JS_SRC_DIR . 'cssjson.js');

define('URVANOV_SYNTAX_HIGHLIGHTER_CSS_JQUERY_COLORPICKER', URVANOV_SYNTAX_HIGHLIGHTER_JS_DIR . 'jquery-colorpicker/jquery.colorpicker.css');
define('URVANOV_SYNTAX_HIGHLIGHTER_JS_JQUERY_COLORPICKER', URVANOV_SYNTAX_HIGHLIGHTER_JS_DIR . 'jquery-colorpicker/jquery.colorpicker.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_JS_TINYCOLOR', URVANOV_SYNTAX_HIGHLIGHTER_JS_DIR . 'tinycolor-min.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR_JS', 'urvanov_syntax_highlighter_tag_editor.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_EDITOR_CSS', 'util/tag-editor/urvanov_syntax_highlighter_editor.css');
define('URVANOV_SYNTAX_HIGHLIGHTER_COLORBOX_JS', 'colorbox/jquery.colorbox-min.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_COLORBOX_CSS', 'colorbox/colorbox.css');
define('URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR_PHP', URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR_PATH . 'class-urvanov-syntax-highlighter-tag-editor-wp.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_TINYMCE_JS', 'urvanov_syntax_highlighter_tinymce.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_QUICKTAGS_JS', 'urvanov_syntax_highlighter_qt.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_STYLE', URVANOV_SYNTAX_HIGHLIGHTER_CSS_SRC_DIR . 'urvanov_syntax_highlighter_style.css');
define('URVANOV_SYNTAX_HIGHLIGHTER_STYLE_ADMIN', URVANOV_SYNTAX_HIGHLIGHTER_CSS_SRC_DIR . 'admin_style.css');
define('URVANOV_SYNTAX_HIGHLIGHTER_STYLE_GLOBAL', URVANOV_SYNTAX_HIGHLIGHTER_CSS_SRC_DIR . 'global_style.css');
define('URVANOV_SYNTAX_HIGHLIGHTER_STYLE_MIN', URVANOV_SYNTAX_HIGHLIGHTER_CSS_MIN_DIR . 'urvanov_syntax_highlighter.min.css');
define('URVANOV_SYNTAX_HIGHLIGHTER_LOGO', URVANOV_SYNTAX_HIGHLIGHTER_CSS_DIR . 'images/crayon_logo.png');
define('URVANOV_SYNTAX_HIGHLIGHTER_DONATE_BUTTON', URVANOV_SYNTAX_HIGHLIGHTER_CSS_DIR . 'images/donate.png');
define('URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_PHP', URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_PATH . 'class-urvanov-syntax-highlighter-theme-editor.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_JS', URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR . URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_DIR . 'theme_editor.js');
define('URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_STYLE', URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR . URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_DIR . 'theme_editor.css');
define('URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_BUTTON', URVANOV_SYNTAX_HIGHLIGHTER_CSS_DIR . 'images/theme_editor.png');

// PHP Files
define('URVANOV_SYNTAX_HIGHLIGHTER_FORMATTER_PHP', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'class-urvanov-syntax-highlighter-formatter.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_HIGHLIGHTER_PHP', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'class-urvanov-syntax-highlighter.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_LANGS_PHP', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'class-urvanov-syntax-highlighter-langs.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_PARSER_PHP', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'class-urvanov-syntax-highlighter-parser.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_SETTINGS_PHP', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'class-urvanov-syntax-highlighter-settings.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_THEMES_PHP', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'class-urvanov-syntax-highlighter-themes.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_FONTS_PHP', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'class-urvanov-syntax-highlighter-fonts.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_RESOURCE_PHP', URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'class-urvanov-syntax-highlighter-resource.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_UTIL_PHP', URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR . 'class-urvanov-syntax-highlighter-util.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_TIMER_PHP', URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR . 'class-urvanov-syntax-highlighter-timer.php');
define('URVANOV_SYNTAX_HIGHLIGHTER_LOG_PHP', URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR . 'class-urvanov-syntax-highlighter-log.php');

// Script time

define('URVANOV_SYNTAX_HIGHLIGHTER_LOAD_TIME', 'Load Time');
//define('URVANOV_SYNTAX_HIGHLIGHTER_PARSE_TIME', 'Parse Time');
define('URVANOV_SYNTAX_HIGHLIGHTER_FORMAT_TIME', 'Format Time');

// Printing

define('URVANOV_SYNTAX_HIGHLIGHTER_BR', "<br />");
define('URVANOV_SYNTAX_HIGHLIGHTER_NL', "\r\n");
define('URVANOV_SYNTAX_HIGHLIGHTER_BL', URVANOV_SYNTAX_HIGHLIGHTER_BR . URVANOV_SYNTAX_HIGHLIGHTER_NL);
define('URVANOV_SYNTAX_HIGHLIGHTER_DASH', "==============================================================================");
define('URVANOV_SYNTAX_HIGHLIGHTER_LINE', "------------------------------------------------------------------------------");

// Load utilities

require_once (URVANOV_SYNTAX_HIGHLIGHTER_UTIL_PHP);
require_once (URVANOV_SYNTAX_HIGHLIGHTER_TIMER_PHP);
require_once (URVANOV_SYNTAX_HIGHLIGHTER_LOG_PHP);
?>