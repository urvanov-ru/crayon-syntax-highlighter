<?php
require_once('class-urvanov-syntax-highlighter-global.php');
require_once(URVANOV_SYNTAX_HIGHLIGHTER_PARSER_PHP);
require_once(URVANOV_SYNTAX_HIGHLIGHTER_THEMES_PHP);

/**
 * Stores Urvanov_Syntax_Highlighter_Setting objects.
 * Each Crayon instance stores an instance of this class containing its specific settings.
 */
// Old name: CrayonSettings
class Urvanov_Syntax_Highlighter_Settings {
    // Properties and Constants ===============================================
    const INVALID = -1; // Used for invalid dropdown index
    // Plugin data
    const VERSION = 'version';

    // Added when used in HTML to avoid id conflicts
    const PREFIX = 'urvanov-syntax-highlighter-';
    const SETTING = 'urvanov-syntax-highlighter-setting';
    const SETTING_SELECTED = 'urvanov-syntax-highlighter-setting-selected';
    const SETTING_CHANGED = 'urvanov-syntax-highlighter-setting-changed';
    const SETTING_SPECIAL = 'urvanov-syntax-highlighter-setting-special';
    const SETTING_ORIG_VALUE = 'data-orig-value';

    // Global names for settings
    const THEME = 'theme';
    const FONT = 'font';
    const FONT_SIZE_ENABLE = 'font-size-enable';
    const FONT_SIZE = 'font-size';
    const LINE_HEIGHT = 'line-height';
    const PREVIEW = 'preview';
    const HEIGHT_SET = 'height-set';
    const HEIGHT_MODE = 'height-mode';
    const HEIGHT = 'height';
    const HEIGHT_UNIT = 'height-unit';
    const WIDTH_SET = 'width-set';
    const WIDTH_MODE = 'width-mode';
    const WIDTH = 'width';
    const WIDTH_UNIT = 'width-unit';
    const TOP_SET = 'top-set';
    const TOP_MARGIN = 'top-margin';
    const LEFT_SET = 'left-set';
    const LEFT_MARGIN = 'left-margin';
    const BOTTOM_SET = 'bottom-set';
    const BOTTOM_MARGIN = 'bottom-margin';
    const RIGHT_SET = 'right-set';
    const RIGHT_MARGIN = 'right-margin';
    const H_ALIGN = 'h-align';
    const FLOAT_ENABLE = 'float-enable';
    const TOOLBAR = 'toolbar';
    const TOOLBAR_OVERLAY = 'toolbar-overlay';
    const TOOLBAR_HIDE = 'toolbar-hide';
    const TOOLBAR_DELAY = 'toolbar-delay';
    const COPY = 'copy';
    const POPUP = 'popup';
    const SHOW_LANG = 'show-lang';
    const SHOW_TITLE = 'show-title';
    const STRIPED = 'striped';
    const MARKING = 'marking';
    const START_LINE = 'start-line';
    const NUMS = 'nums';
    const NUMS_TOGGLE = 'nums-toggle';
    const TRIM_WHITESPACE = 'trim-whitespace';
    const WHITESPACE_BEFORE = 'whitespace-before';
    const WHITESPACE_AFTER = 'whitespace-after';
    const TRIM_CODE_TAG = 'trim-code-tag';
    const TAB_SIZE = 'tab-size';
    const TAB_CONVERT = 'tab-convert';
    const FALLBACK_LANG = 'fallback-lang';
    const LOCAL_PATH = 'local-path';
    const SCROLL = 'scroll';
    const PLAIN = 'plain';
    const PLAIN_TOGGLE = 'plain-toggle';
    const SHOW_PLAIN = 'show-plain';
    const DISABLE_RUNTIME = 'runtime';
    const DISABLE_DATE = 'disable-date';
    const TOUCHSCREEN = 'touchscreen';
    const DISABLE_ANIM = 'disable-anim';
    const ERROR_LOG = 'error-log';
    const ERROR_LOG_SYS = 'error-log-sys';
    const ERROR_MSG_SHOW = 'error-msg-show';
    const ERROR_MSG = 'error-msg';
    const HIDE_HELP = 'hide-help';
    const CACHE = 'cache';
    const EFFICIENT_ENQUEUE = 'efficient-enqueue';
    const CAPTURE_PRE = 'capture-pre';
    const CAPTURE_MINI_TAG = 'capture-mini-tag';
    const ALTERNATE = 'alternate';
    const SHOW_ALTERNATE = 'show_alternate';
    const PLAIN_TAG = 'plain_tag';
    const SHOW_PLAIN_DEFAULT = 'show-plain-default';
    const ENQUEUE_THEMES = 'enqueque-themes';
    const ENQUEUE_FONTS = 'enqueque-fonts';
    const MAIN_QUERY = 'main-query';
    const SAFE_ENQUEUE = 'safe-enqueue';
    const INLINE_TAG = 'inline-tag';
    const INLINE_TAG_CAPTURE = 'inline-tag-capture';
    const CODE_TAG_CAPTURE = 'code-tag-capture';
    const CODE_TAG_CAPTURE_TYPE = 'code-tag-capture-type';
    const INLINE_MARGIN = 'inline-margin';
    const INLINE_WRAP = 'inline-wrap';
    const BACKQUOTE = 'backquote';
    const COMMENTS = 'comments';
    const DECODE = 'decode';
    const DECODE_ATTRIBUTES = 'decode-attributes';
// 	const TINYMCE_USED = 'tinymce-used';
    const ATTR_SEP = 'attr-sep';
    const EXCERPT_STRIP = 'excerpt-strip';
    const RANGES = 'ranges';
    const TAG_EDITOR_FRONT = 'tag-editor-front';
    const TAG_EDITOR_SETTINGS = 'tag-editor-front-hide';
    const TAG_EDITOR_ADD_BUTTON_TEXT = 'tag-editor-button-add-text';
    const TAG_EDITOR_EDIT_BUTTON_TEXT = 'tag-editor-button-edit-text';
    const TAG_EDITOR_QUICKTAG_BUTTON_TEXT = 'tag-editor-quicktag-button-text';
    const WRAP_TOGGLE = 'wrap-toggle';
    const WRAP = 'wrap';
    const EXPAND = 'expand';
    const EXPAND_TOGGLE = 'expand-toggle';
    const MINIMIZE = 'minimize';
    const IGNORE = 'ignore';
    const DELAY_LOAD_JS = 'delay-load-js';

    private static $cache_array;

    public static function get_cache_sec($cache) {
        $values = array_values(self::$cache_array);
        if (array_key_exists($cache, $values)) {
            return $values[$cache];
        } else {
            return $values[0];
        }
    }

    // The current settings, should be loaded with default if none exists
    private $settings = array();

    // The settings with default values
    private static $default = NULL;

    function __construct() {
        $this->init();
    }

    function copy() {
        $settings = new Urvanov_Syntax_Highlighter_Settings();
        foreach ($this->settings as $setting) {
            $settings->set($setting); // Overuse of set?
        }
        return $settings;
    }

    // Methods ================================================================

    private function init() {
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;

        Urvanov_Syntax_Highlighter_Global::load_plugin_textdomain();

        self::$cache_array = array(Urvanov_Syntax_Highlighter_Global::urvanov__('Hourly') => 3600, Urvanov_Syntax_Highlighter_Global::urvanov__('Daily') => 86400,
            Urvanov_Syntax_Highlighter_Global::urvanov__('Weekly') => 604800, Urvanov_Syntax_Highlighter_Global::urvanov__('Monthly') => 18144000,
            Urvanov_Syntax_Highlighter_Global::urvanov__('Immediately') => 1);

        $settings = array(
            new Urvanov_Syntax_Highlighter_Setting(self::VERSION, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION, NULL, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::THEME, Urvanov_Syntax_Highlighter_Themes::DEFAULT_THEME),
            new Urvanov_Syntax_Highlighter_Setting(self::FONT, Urvanov_Syntax_Highlighter_Fonts::DEFAULT_FONT),
            new Urvanov_Syntax_Highlighter_Setting(self::FONT_SIZE_ENABLE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::FONT_SIZE, 12),
            new Urvanov_Syntax_Highlighter_Setting(self::LINE_HEIGHT, 15),
            new Urvanov_Syntax_Highlighter_Setting(self::PREVIEW, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::HEIGHT_SET, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::HEIGHT_MODE, array(Urvanov_Syntax_Highlighter_Global::urvanov__('Max'), Urvanov_Syntax_Highlighter_Global::urvanov__('Min'), Urvanov_Syntax_Highlighter_Global::urvanov__('Static'))),
            new Urvanov_Syntax_Highlighter_Setting(self::HEIGHT, '500'),
            new Urvanov_Syntax_Highlighter_Setting(self::HEIGHT_UNIT, array(Urvanov_Syntax_Highlighter_Global::urvanov__('Pixels'), Urvanov_Syntax_Highlighter_Global::urvanov__('Percent'))),
            new Urvanov_Syntax_Highlighter_Setting(self::WIDTH_SET, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::WIDTH_MODE, array(Urvanov_Syntax_Highlighter_Global::urvanov__('Max'), Urvanov_Syntax_Highlighter_Global::urvanov__('Min'), Urvanov_Syntax_Highlighter_Global::urvanov__('Static'))),
            new Urvanov_Syntax_Highlighter_Setting(self::WIDTH, '500'),
            new Urvanov_Syntax_Highlighter_Setting(self::WIDTH_UNIT, array(Urvanov_Syntax_Highlighter_Global::urvanov__('Pixels'), Urvanov_Syntax_Highlighter_Global::urvanov__('Percent'))),
            new Urvanov_Syntax_Highlighter_Setting(self::TOP_SET, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::TOP_MARGIN, 12),
            new Urvanov_Syntax_Highlighter_Setting(self::BOTTOM_SET, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::BOTTOM_MARGIN, 12),
            new Urvanov_Syntax_Highlighter_Setting(self::LEFT_SET, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::LEFT_MARGIN, 12),
            new Urvanov_Syntax_Highlighter_Setting(self::RIGHT_SET, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::RIGHT_MARGIN, 12),
            new Urvanov_Syntax_Highlighter_Setting(self::H_ALIGN, array(Urvanov_Syntax_Highlighter_Global::urvanov__('None'), Urvanov_Syntax_Highlighter_Global::urvanov__('Left'), Urvanov_Syntax_Highlighter_Global::urvanov__('Center'), Urvanov_Syntax_Highlighter_Global::urvanov__('Right'))),
            new Urvanov_Syntax_Highlighter_Setting(self::FLOAT_ENABLE, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::TOOLBAR, array(Urvanov_Syntax_Highlighter_Global::urvanov__('On MouseOver'), Urvanov_Syntax_Highlighter_Global::urvanov__('Always'), Urvanov_Syntax_Highlighter_Global::urvanov__('Never'))),
            new Urvanov_Syntax_Highlighter_Setting(self::TOOLBAR_OVERLAY, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::TOOLBAR_HIDE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::TOOLBAR_DELAY, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::COPY, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::POPUP, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::SHOW_LANG, array(Urvanov_Syntax_Highlighter_Global::urvanov__('When Found'), Urvanov_Syntax_Highlighter_Global::urvanov__('Always'), Urvanov_Syntax_Highlighter_Global::urvanov__('Never'))),
            new Urvanov_Syntax_Highlighter_Setting(self::SHOW_TITLE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::STRIPED, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::MARKING, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::START_LINE, 1),
            new Urvanov_Syntax_Highlighter_Setting(self::NUMS, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::NUMS_TOGGLE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::TRIM_WHITESPACE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::WHITESPACE_BEFORE, 0),
            new Urvanov_Syntax_Highlighter_Setting(self::WHITESPACE_AFTER, 0),
            new Urvanov_Syntax_Highlighter_Setting(self::TRIM_CODE_TAG, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::TAB_CONVERT, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::TAB_SIZE, 4),
            new Urvanov_Syntax_Highlighter_Setting(self::FALLBACK_LANG, Urvanov_Syntax_Highlighter_Langs::DEFAULT_LANG),
            new Urvanov_Syntax_Highlighter_Setting(self::LOCAL_PATH, ''),
            new Urvanov_Syntax_Highlighter_Setting(self::SCROLL, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::PLAIN, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::PLAIN_TOGGLE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::SHOW_PLAIN_DEFAULT, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::SHOW_PLAIN,
                array(Urvanov_Syntax_Highlighter_Global::urvanov__('On Double Click'), Urvanov_Syntax_Highlighter_Global::urvanov__('On Single Click'), Urvanov_Syntax_Highlighter_Global::urvanov__('On MouseOver'), Urvanov_Syntax_Highlighter_Global::urvanov__('Disable Mouse Events'))),
            new Urvanov_Syntax_Highlighter_Setting(self::DISABLE_ANIM, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::TOUCHSCREEN, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::DISABLE_RUNTIME, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::DISABLE_DATE, ''),
            new Urvanov_Syntax_Highlighter_Setting(self::ERROR_LOG, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::ERROR_LOG_SYS, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::ERROR_MSG_SHOW, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::ERROR_MSG, Urvanov_Syntax_Highlighter_Global::urvanov__('An error has occurred. Please try again later.')),
            new Urvanov_Syntax_Highlighter_Setting(self::HIDE_HELP, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::CACHE, array_keys(self::$cache_array), 1),
            new Urvanov_Syntax_Highlighter_Setting(self::EFFICIENT_ENQUEUE, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::CAPTURE_PRE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::CAPTURE_MINI_TAG, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::ALTERNATE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::SHOW_ALTERNATE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::PLAIN_TAG, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::ENQUEUE_THEMES, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::ENQUEUE_FONTS, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::MAIN_QUERY, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::SAFE_ENQUEUE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::INLINE_TAG, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::INLINE_TAG_CAPTURE, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::CODE_TAG_CAPTURE, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::CODE_TAG_CAPTURE_TYPE, array(Urvanov_Syntax_Highlighter_Global::urvanov__('Inline Tag'), Urvanov_Syntax_Highlighter_Global::urvanov__('Block Tag'))),
            new Urvanov_Syntax_Highlighter_Setting(self::INLINE_MARGIN, 5),
            new Urvanov_Syntax_Highlighter_Setting(self::INLINE_WRAP, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::BACKQUOTE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::COMMENTS, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::DECODE, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::DECODE_ATTRIBUTES, TRUE),
// 			new Urvanov_Syntax_Highlighter_Setting(self::TINYMCE_USED, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::ATTR_SEP, array(':', '_')),
            new Urvanov_Syntax_Highlighter_Setting(self::EXCERPT_STRIP, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::RANGES, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::TAG_EDITOR_FRONT, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::TAG_EDITOR_SETTINGS, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::TAG_EDITOR_ADD_BUTTON_TEXT, Urvanov_Syntax_Highlighter_Global::urvanov__('Add Code')),
            new Urvanov_Syntax_Highlighter_Setting(self::TAG_EDITOR_EDIT_BUTTON_TEXT, Urvanov_Syntax_Highlighter_Global::urvanov__('Edit Code')),
            new Urvanov_Syntax_Highlighter_Setting(self::TAG_EDITOR_QUICKTAG_BUTTON_TEXT, 'crayon'),
            new Urvanov_Syntax_Highlighter_Setting(self::WRAP_TOGGLE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::WRAP, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::EXPAND, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::EXPAND_TOGGLE, TRUE),
            new Urvanov_Syntax_Highlighter_Setting(self::MINIMIZE, FALSE),
            new Urvanov_Syntax_Highlighter_Setting(self::DELAY_LOAD_JS, FALSE)
        );

        $this->set($settings);

        $nonNegs = array(self::FONT_SIZE, self::LINE_HEIGHT, self::HEIGHT, self::WIDTH, self::START_LINE, self::WHITESPACE_BEFORE, self::WHITESPACE_AFTER, self::TAB_SIZE, self::INLINE_MARGIN);
        $intNonNegValid = new Urvanov_Syntax_Highlighter_Non_Neg_Int_Validator();
        foreach ($nonNegs as $name) {
            $this->get($name)->validator($intNonNegValid);
        }
    }

    // Getter and Setter ======================================================

    // TODO this needs simplification
    function set($name, $value = NULL, $replace = FALSE) {
        // Set associative array of settings
        if (is_array($name)) {
            $keys = array_keys($name);
            foreach ($keys as $key) {
                if (is_string($key)) {
                    // Associative value
                    $this->set($key, $name[$key], $replace);
                } else if (is_int($key)) {
                    $setting = $name[$key];
                    $this->set($setting, NULL, $replace);
                }
            }
        } else if (is_string($name) && !empty($name) && $value !== NULL) {
            $value = Urvanov_Syntax_Highlighter_Settings::validate($name, $value);
            if ($replace || !$this->is_setting($name)) {
                // Replace/Create
                $this->settings[$name] = new Urvanov_Syntax_Highlighter_Setting($name, $value);
            } else {
                // Update
                $this->settings[$name]->value($value);
            }
        } else if (is_object($name) && get_class($name) == URVANOV_SYNTAX_HIGHLIGHTER_SETTING_CLASS) {
            $setting = $name; // Semantics
            if ($replace || !$this->is_setting($setting->name())) {
                // Replace/Create
                $this->settings[$setting->name()] = $setting->copy();
            } else {
                // Update
                if ($setting->is_array()) {
                    $this->settings[$setting->name()]->index($setting->index());
                } else {
                    $this->settings[$setting->name()]->value($setting->value());
                }
            }
        }
    }

    function get($name = NULL) {
        if ($name === NULL) {
            $copy = array();
            foreach ($this->settings as $name => $setting) {
                $copy[$name] = $setting->copy(); // Deep copy
            }
            return $copy;
        } else if (is_string($name)) {
            if ($this->is_setting($name)) {
                return $this->settings[$name];
            }
        }
        return FALSE;
    }

    function val($name = NULL) {
        if (($setting = self::get($name)) != FALSE) {
            return $setting->value();
        } else {
            return NULL;
        }
    }

    function val_str($name) {
        if (($setting = self::get($name)) != FALSE) {
            $def = $setting->def();
            $index = $setting->value();
            if (array_key_exists($index, $def)) {
                return $def[$index];
            } else {
                return NULL;
            }
        }
    }

    function get_array() {
        $array = array();
        foreach ($this->settings as $setting) {
            $array[$setting->name()] = $setting->value();
        }
        return $array;
    }

    function is_setting($name) {
        return (is_string($name) && array_key_exists($name, $this->settings));
    }

    /* Gets default settings, either as associative array of name=>value or Urvanov_Syntax_Highlighter_Setting
     objects */
    public static function get_defaults($name = NULL, $objects = TRUE) {
        if (self::$default === NULL) {
            self::$default = new Urvanov_Syntax_Highlighter_Settings();
        }
        if ($name === NULL) {
            // Get all settings
            if ($objects) {
                // Return array of objects
                return self::$default->get();
            } else {
                // Return associative array of name=>value
                $settings = self::$default->get();
                $defaults = array();
                foreach ($settings as $setting) {
                    $defaults[$setting->name()] = $setting->value();
                }
                return $defaults;
            }
        } else {
            // Return specific setting
            if ($objects) {
                return self::$default->get($name);
            } else {
                return self::$default->get($name)->value();
            }
        }
    }

    public static function get_defaults_array() {
        return self::get_defaults(NULL, FALSE);
    }

    // Validation =============================================================

    /**
     * Validates settings coming from an HTML form and also for internal use.
     * This is used when saving form an HTML form to the db, and also when reading from the db
     * back into the global settings.
     * @param string $name
     * @param alternate $value
     */
    public static function validate($name, $value) {
        if (!is_string($name)) {
            return '';
        }

        // Type-cast to correct value for known settings
        if (($setting = Urvanov_Syntax_Highlighter_Global_Settings::get($name)) != FALSE) {
            // Booleans settings that are sent as string are allowed to have "false" == false
            if (is_bool($setting->def())) {
                if (is_string($value)) {
                    $value = UrvanovSyntaxHighlighterUtil::str_to_bool($value);
                }
            } else {
                // Ensure we don't cast integer settings to 0 because $value doesn't have any numbers in it
                $value = strval($value);
                // Only occurs when saving from the form ($_POST values are strings)
                if ($value == '' || ($cleaned = $setting->sanitize($value, FALSE)) == '') {
                    // The value sent has no integers, change to default
                    $value = $setting->def();
                } else {
                    // Cleaned value is int
                    $value = $cleaned;
                }
                // Cast all other settings as usual
                if (!settype($value, $setting->type())) {
                    // If we can't cast, then use default value
                    if ($setting->is_array()) {
                        $value = 0; // default index
                    } else {
                        $value = $setting->def();
                    }
                }
            }
        } else {
            // If setting not found, remove value
            return '';
        }

        switch ($name) {
            case Urvanov_Syntax_Highlighter_Settings::LOCAL_PATH:
                $path = parse_url($value, PHP_URL_PATH);
                // Remove all spaces, prefixed and trailing forward slashes
                $path = preg_replace('#^/*|/*$|\s*#', '', $path);
                // Replace backslashes
                $path = preg_replace('#\\\\#', '/', $path);
                // Append trailing forward slash
                if (!empty($path)) {
                    $path .= '/';
                }
                return $path;
            case Urvanov_Syntax_Highlighter_Settings::FONT_SIZE:
                if ($value < 1) {
                    $value = 1;
                }
                break;
            case Urvanov_Syntax_Highlighter_Settings::LINE_HEIGHT:
                $font_size = Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::FONT_SIZE);
                $value = $value >= $font_size ? $value : $font_size;
                break;
            case Urvanov_Syntax_Highlighter_Settings::THEME:
                $value = strtolower($value);
            // XXX validate settings here
        }

        // If no validation occurs, return value
        return $value;
    }

    // Takes an associative array of "smart settings" and regular settings. Smart settings can be used
    // to configure regular settings quickly.
    // E.g. 'max_height="20px"' will set 'height="20"', 'height_mode="0", height_unit="0"'
    public static function smart_settings($settings) {
        if (!is_array($settings)) {
            return FALSE;
        }

        // If a setting is given, it is automatically enabled
        foreach ($settings as $name => $value) {
            if (($setting = Urvanov_Syntax_Highlighter_Global_Settings::get($name)) !== FALSE && is_bool($setting->def())) {
                $value = UrvanovSyntaxHighlighterUtil::str_to_bool($value);
            }

            // XXX removed height and width, since it wasn't using the global settings for mode if only height was provided
            if ($name == 'min-height' || $name == 'max-height' /* || $name == 'height'*/) {
                self::smart_hw($name, Urvanov_Syntax_Highlighter_Settings::HEIGHT_SET, Urvanov_Syntax_Highlighter_Settings::HEIGHT_MODE, Urvanov_Syntax_Highlighter_Settings::HEIGHT_UNIT, $settings);
            } else if ($name == 'min-width' || $name == 'max-width' /* || $name == 'width'*/) {
                self::smart_hw($name, Urvanov_Syntax_Highlighter_Settings::WIDTH_SET, Urvanov_Syntax_Highlighter_Settings::WIDTH_MODE, Urvanov_Syntax_Highlighter_Settings::WIDTH_UNIT, $settings);
            } else if ($name == Urvanov_Syntax_Highlighter_Settings::FONT_SIZE) {
                $settings[Urvanov_Syntax_Highlighter_Settings::FONT_SIZE_ENABLE] = TRUE;
            } else if ($name == Urvanov_Syntax_Highlighter_Settings::TOP_MARGIN) {
                $settings[Urvanov_Syntax_Highlighter_Settings::TOP_SET] = TRUE;
            } else if ($name == Urvanov_Syntax_Highlighter_Settings::LEFT_MARGIN) {
                $settings[Urvanov_Syntax_Highlighter_Settings::LEFT_SET] = TRUE;
            } else if ($name == Urvanov_Syntax_Highlighter_Settings::BOTTOM_MARGIN) {
                $settings[Urvanov_Syntax_Highlighter_Settings::BOTTOM_SET] = TRUE;
            } else if ($name == Urvanov_Syntax_Highlighter_Settings::RIGHT_MARGIN) {
                $settings[Urvanov_Syntax_Highlighter_Settings::RIGHT_SET] = TRUE;
            } else if ($name == Urvanov_Syntax_Highlighter_Settings::ERROR_MSG) {
                $settings[Urvanov_Syntax_Highlighter_Settings::ERROR_MSG_SHOW] = TRUE;
            } else if ($name == Urvanov_Syntax_Highlighter_Settings::H_ALIGN) {
                $settings[Urvanov_Syntax_Highlighter_Settings::FLOAT_ENABLE] = TRUE;
                $value = UrvanovSyntaxHighlighterUtil::tlower($value);
                $values = array('none' => 0, 'left' => 1, 'center' => 2, 'right' => 3);
                if (array_key_exists($value, $values)) {
                    $settings[Urvanov_Syntax_Highlighter_Settings::H_ALIGN] = $values[$value];
                }
            } else if ($name == Urvanov_Syntax_Highlighter_Settings::SHOW_LANG) {
                $value = UrvanovSyntaxHighlighterUtil::tlower($value);
                $values = array('found' => 0, 'always' => 1, 'true' => 1, 'never' => 2, 'false' => 2);
                if (array_key_exists($value, $values)) {
                    $settings[Urvanov_Syntax_Highlighter_Settings::SHOW_LANG] = $values[$value];
                }
            } else if ($name == Urvanov_Syntax_Highlighter_Settings::TOOLBAR) {
                if (UrvanovSyntaxHighlighterUtil::tlower($value) == 'always') {
                    $settings[Urvanov_Syntax_Highlighter_Settings::TOOLBAR] = 1;
                } else if (UrvanovSyntaxHighlighterUtil::str_to_bool($value) === FALSE) {
                    $settings[Urvanov_Syntax_Highlighter_Settings::TOOLBAR] = 2;
                }
            }
        }

        return $settings;
    }

    // Used for height and width smart settings, I couldn't bear to copy paste code twice...
    private static function smart_hw($name, $set, $mode, $unit, &$settings) {
        if (!is_string($name) || !is_string($set) || !is_string($mode) || !is_string($unit) || !is_array($settings)) {
            return;
        }
        $settings[$set] = TRUE;
        if (strpos($name, 'max-') !== FALSE) {
            $settings[$mode] = 0;
        } else if (strpos($name, 'min-') !== FALSE) {
            $settings[$mode] = 1;
        } else {
            $settings[$mode] = 2;
        }
        preg_match('#(\d+)\s*([^\s]*)#', $settings[$name], $match);
        if (count($match) == 3) {
            $name = str_replace(array('max-', 'min-'), '', $name);
            $settings[$name] = $match[1];
            switch (strtolower($match[2])) {
                case 'px':
                    $settings[$unit] = 0;
                    break;
                case '%':
                    $settings[$unit] = 1;
                    break;
            }
        }
    }
}

/**
 * Stores global/static copy of Urvanov_Syntax_Highlighter_Settings loaded from db.
 * These settings can be overriden by individual Crayons.
 * Also manages global site settings and paths.
 */
// Old name: CrayonGlobalSettings
class Urvanov_Syntax_Highlighter_Global_Settings {
    // The global settings stored as a Urvanov_Syntax_Highlighter_Settings object.
    private static $global = NULL;
    /* These are used to load local files reliably and prevent scripts like PHP from executing
     when attempting to load their code. */
    // The URL of the site (eg. http://localhost/example/)
    private static $site_http = '';
    // The absolute root directory of the site (eg. /User/example/)
    private static $site_path = '';
    // The absolute root directory of the plugins (eg. /User/example/plugins)
    private static $plugin_path = '';
    private static $upload_path = '';
    private static $upload_url = '';
    private static $mkdir = NULL;

    private function __construct() {
    }

    private static function init() {
        if (self::$global === NULL) {
            self::$global = new Urvanov_Syntax_Highlighter_Settings();
        }
    }

    public static function get($name = NULL) {
        self::init();
        return self::$global->get($name);
    }

    public static function get_array() {
        self::init();
        return self::$global->get_array();
    }

    public static function get_obj() {
        self::init();
        return self::$global->copy();
    }

    public static function val($name = NULL) {
        self::init();
        return self::$global->val($name);
    }

    public static function val_str($name = NULL) {
        self::init();
        return self::$global->val_str($name);
    }

    public static function has_changed($input, $setting, $value) {
        return $input == $setting && $value != Urvanov_Syntax_Highlighter_Global_Settings::val($setting);
    }

    public static function set($name, $value = NULL, $replace = FALSE) {
        self::init();
        self::$global->set($name, $value, $replace);
    }

    public static function site_url($site_http = NULL) {
        if ($site_http === NULL) {
            return self::$site_http;
        } else {
            self::$site_http = UrvanovSyntaxHighlighterUtil::url_slash($site_http);
        }
    }

    public static function site_path($site_path = NULL) {
        if ($site_path === NULL) {
            return self::$site_path;
        } else {
            self::$site_path = UrvanovSyntaxHighlighterUtil::path_slash($site_path);
        }
    }

    public static function plugin_path($plugin_path = NULL) {
        if ($plugin_path === NULL) {
            return self::$plugin_path;
        } else {
            self::$plugin_path = UrvanovSyntaxHighlighterUtil::path_slash($plugin_path);
        }
    }

    public static function upload_path($upload_path = NULL) {
        if ($upload_path === NULL) {
            return self::$upload_path;
        } else {
            self::$upload_path = UrvanovSyntaxHighlighterUtil::path_slash($upload_path);
        }
    }

    public static function upload_url($upload_url = NULL) {
        if ($upload_url === NULL) {
            return self::$upload_url;
        } else {
            self::$upload_url = UrvanovSyntaxHighlighterUtil::url_slash($upload_url);
        }
    }

    public static function set_mkdir($mkdir = NULL) {
        if ($mkdir === NULL) {
            return self::$mkdir;
        } else {
            self::$mkdir = $mkdir;
        }
    }

    public static function mkdir($dir = NULL) {
        if (self::$mkdir) {
            call_user_func(self::$mkdir, $dir);
        } else {
            @mkdir($dir, 0777, TRUE);
        }
    }
}


$INT = new Urvanov_Syntax_Highlighter_Validator('#\d+#');

/**
 * Validation class.
 */
// Old name: CrayonValidator
class Urvanov_Syntax_Highlighter_Validator {
    private $pattern = '#*#msi';

    public function __construct($pattern) {
        $this->pattern($pattern);
    }

    public function pattern($pattern) {
        if ($pattern === NULL) {
            return $pattern;
        } else {
            $this->pattern = $pattern;
        }
    }

    public function validate($str) {
        return preg_match($this->pattern, $str) !== FALSE;
    }

    public function sanitize($str) {
        preg_match_all($this->pattern, $str, $matches);
        $result = '';
        foreach ($matches as $match) {
            $result .= $match[0];
        }
        return $result;
    }
}

// Old name: CrayonNonNegIntValidator
class Urvanov_Syntax_Highlighter_Non_Neg_Int_Validator extends Urvanov_Syntax_Highlighter_Validator {
    public function __construct() {
        parent::__construct('#\d+#');
    }
}

// Old name: CrayonIntValidator
class Urvanov_Syntax_Highligher_Int_Validator extends Urvanov_Syntax_Highlighter_Validator {
    public function __construct() {
        parent::__construct('#-?\d+#');
    }
}

/**
 * Individual setting.
 * Can store boolean, string, dropdown (with array of strings), etc.
 */
// Old name: CrayonSetting
class Urvanov_Syntax_Highlighter_Setting {
    private $name = '';
    /* The type of variables that can be set as the value.
     * For dropdown settings, value is int, even though value() will return a string. */
    private $type = NULL;
    private $default = NULL; // stores string array for dropdown settings

    private $value = NULL; // stores index int for dropdown settings

    private $is_array = FALSE; // only TRUE for dropdown settings
    private $locked = FALSE;

    private $validator = NULL;


    public function __construct($name, $default = '', $value = NULL, $locked = NULL) {
        $this->name($name);
        if ($default !== NULL) {
            $this->def($default); // Perform first to set type
        }
        if ($value !== NULL) {
            $this->value($value);
        }
        if ($locked !== NULL) {
            $this->locked($locked);
        }
    }

    function __tostring() {
        return $this->name;
    }

    function copy() {
        return new Urvanov_Syntax_Highlighter_Setting($this->name, $this->default, $this->value, $this->locked);
    }

    function name($name = NULL) {
        if (!UrvanovSyntaxHighlighterUtil::str($this->name, $name)) {
            return $this->name;
        }
    }

    function type() {
        return $this->type;
    }

    function is_array() {
        return $this->is_array;
    }

    function locked($locked = NULL) {
        if ($locked === NULL) {
            return $this->locked;
        } else {
            $this->locked = ($locked == TRUE);
        }
    }

    /**
     * Sets/gets value;
     * Value is index (int) in default value (array) for dropdown settings.
     * value($value) is alias for index($index) if dropdown setting.
     * value() returns string value at current index for dropdown settings.
     * @param $value
     */
    function value($value = NULL) {
        if ($value === NULL) {
            /*if ($this->is_array) {
                return $this->default[$this->value]; // value at index
            } else */
            if ($this->value !== NULL) {
                return $this->value;
            } else {
                if ($this->is_array) {
                    return 0;
                } else {
                    return $this->default;
                }
            }
        } else if ($this->locked === FALSE) {
            if ($this->is_array) {
                $this->index($value); // $value is index
            } else {
                settype($value, $this->type); // Type cast
                $this->value = $value;
            }
        }
    }

    function array_value() {
        if ($this->is_array) {
            return NULL;
        }
        return $this->default[$this->value];
    }

    /**
     * Sets/gets default value.
     * For dropdown settings, default value is array of all possible value strings.
     * @param $default
     */
    function def($default = NULL) {
        // Only allow default to be set once

        if ($this->type === NULL && $default !== NULL) {
            // For dropdown settings

            if (is_array($default)) { // The only time we don't use $this->is_array

                // If empty, set to blank array

                if (empty($default)) {
                    $default = array('');
                } else {
                    // Ensure all values are unique strings

                    $default = UrvanovSyntaxHighlighterUtil::array_unique_str($default);
                }
                $this->value = 0; // initial index

                $this->is_array = TRUE;
                $this->type = gettype(0); // Type is int (index)

            } else {
                $this->is_array = FALSE;
                $this->type = gettype($default);
                if (is_int($default)) {
                    $this->validator(new Urvanov_Syntax_Highligher_Int_Validator());
                }
            }
            $this->default = $default;
        } else {
            return $this->default;
        }
    }

    /**
     * Sets/gets index.
     * @param int|string $index
     * @return FALSE if not dropdown setting
     */
    function index($index = NULL) {
        if (!$this->is_array) {
            return FALSE;
        } else if ($index === NULL) {
            return $this->value; // return current index
        } else {
            if (!is_int($index)) {
                // Ensure $value is int for index
                $index = intval($index);
            }
            // Validate index
            if ($index < 0 || $index > count($this->default) - 1) {
                $index = 0;
            }
            $this->value = $index;
        }
    }

    /**
     * Finds the index of a string in an array setting
     */
    function find_index($str) {
        if (!$this->is_array || is_string($str)) {
            return FALSE;
        }
        for ($i = 0; $i < count($this->default); $i++) {
            if ($this->default[$i] == $str) {
                return $i;
            }
        }
        return FALSE;
    }

    function validator($validator) {
        if ($validator === NULL) {
            return $this->validator;
        } else {
            $this->validator = $validator;
        }
    }

    function sanitize($str) {
        if ($this->validator != NULL) {
            return $this->validator->sanitize($str);
        } else {
            return $str;
        }
    }

}

?>
