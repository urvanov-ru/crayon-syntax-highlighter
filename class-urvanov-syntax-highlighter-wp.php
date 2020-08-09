<?php
require_once('class-urvanov-syntax-highlighter-global.php');
require_once(URVANOV_SYNTAX_HIGHLIGHTER_LANGS_PHP);
require_once(URVANOV_SYNTAX_HIGHLIGHTER_THEMES_PHP);
require_once(URVANOV_SYNTAX_HIGHLIGHTER_FONTS_PHP);
require_once(URVANOV_SYNTAX_HIGHLIGHTER_SETTINGS_PHP);

/*  Manages global settings within WP and integrates them with Urvanov_Syntax_Highlighter_Settings.
 CrayonHighlighter and any non-WP classes will only use Urvanov_Syntax_Highlighter_Settings to separate
the implementation of global settings and ensure any system can use them. */
// Old name: Urvanov_Syntax_Highlighter_SettingsWP
class Urvanov_Syntax_Highlighter_Settings_WP {
    // Properties and Constants ===============================================

    // A copy of the current options in db
    private static $options = NULL;
    // Posts containing crayons in db
    private static $urvanov_syntax_highlighter_posts = NULL;
    // Posts containing legacy tags in db
    private static $urvanov_syntax_highlighter_legacy_posts = NULL;
    // An array of cache names for use with Transients API
    private static $cache = NULL;
    // Array of settings to pass to js
    private static $js_settings = NULL;
    private static $js_strings = NULL;
    private static $admin_js_settings = NULL;
    private static $admin_js_strings = NULL;
    private static $admin_page = '';
    private static $is_fully_loaded = FALSE;

    const SETTINGS = 'urvanov_syntax_highlighter_fields';
    const FIELDS = 'urvanov_syntax_highlighter_settings';
    const OPTIONS = 'urvanov_syntax_highlighter_options';
    const POSTS = 'urvanov_syntax_highlighter_posts';
    const LEGACY_POSTS = 'urvanov_syntax_highlighter_legacy_posts';
    const CACHE = 'urvanov_syntax_highlighter_cache';
    const GENERAL = 'urvanov_syntax_highlighter_general';
    const DEBUG = 'urvanov_syntax_highlighter_debug';
    const ABOUT = 'urvanov_syntax_highlighter_about';

    // Used on submit
    const LOG_CLEAR = 'log_clear';
    const LOG_EMAIL_ADMIN = 'log_email_admin';
    const LOG_EMAIL_DEV = 'log_email_dev';
    const SAMPLE_CODE = 'sample-code';
    const CACHE_CLEAR = 'urvanov-syntax-highlighter-cache-clear';

    private function __construct() {
    }

    // Methods ================================================================

    public static function admin_load() {
        self::$admin_page = $admin_page = add_options_page('Crayon Syntax Highlighter ' . Urvanov_Syntax_Highlighter_Global::urvanov__('Settings'), 'Crayon', 'manage_options', 'urvanov_syntax_highlighter_settings', 'Urvanov_Syntax_Highlighter_Settings_WP::settings');
        add_action("admin_print_scripts-$admin_page", 'Urvanov_Syntax_Highlighter_Settings_WP::admin_scripts');
        add_action("admin_print_styles-$admin_page", 'Urvanov_Syntax_Highlighter_Settings_WP::admin_styles');
        add_action("admin_print_scripts-$admin_page", 'Urvanov_Syntax_Highlighter_Theme_Editor_WP::admin_resources');
        // Register settings, second argument is option name stored in db
        register_setting(self::FIELDS, self::OPTIONS, 'Urvanov_Syntax_Highlighter_Settings_WP::settings_validate');
        add_action("admin_head-$admin_page", 'Urvanov_Syntax_Highlighter_Settings_WP::admin_init');
        // Register settings for post page
        add_action("admin_print_styles-post-new.php", 'Urvanov_Syntax_Highlighter_Settings_WP::admin_scripts');
        add_action("admin_print_styles-post.php", 'Urvanov_Syntax_Highlighter_Settings_WP::admin_scripts');
        add_action("admin_print_styles-post-new.php", 'Urvanov_Syntax_Highlighter_Settings_WP::admin_styles');
        add_action("admin_print_styles-post.php", 'Urvanov_Syntax_Highlighter_Settings_WP::admin_styles');

        // TODO deprecated since WP 3.3, remove eventually
        global $wp_version;
        if ($wp_version >= '3.3') {
            add_action("load-$admin_page", 'Urvanov_Syntax_Highlighter_Settings_WP::help_screen');
        } else {
            add_filter('contextual_help', 'Urvanov_Syntax_Highlighter_Settings_WP::cont_help', 10, 3);
        }
    }

    public static function admin_styles() {
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
        if (URVANOV_SYNTAX_HIGHLIGHTER_MINIFY) {
            wp_enqueue_style('urvanov_syntax_highlighter', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_STYLE_MIN, __FILE__), array('editor-buttons'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
        } else {
            wp_enqueue_style('urvanov_syntax_highlighter', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_STYLE, __FILE__), array(), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
            wp_enqueue_style('urvanov_syntax_highlighter_global', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_STYLE_GLOBAL, __FILE__), array(), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
            wp_enqueue_style('urvanov_syntax_highlighter_admin', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_STYLE_ADMIN, __FILE__), array('editor-buttons'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
        }
    }

    public static function admin_scripts() {
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;

        if (URVANOV_SYNTAX_HIGHLIGHTER_MINIFY) {
            Urvanov_Syntax_Highlighter_Plugin::enqueue_resources();
        } else {
            wp_enqueue_script('urvanov_syntax_highlighter_util_js', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_JS_UTIL, __FILE__), array('jquery'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
            self::other_scripts();
        }

        self::init_js_settings();

        if (is_admin()) {
            wp_enqueue_script('urvanov_syntax_highlighter_admin_js', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_JS_ADMIN, __FILE__), array('jquery', 'urvanov_syntax_highlighter_js', 'wpdialogs'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
            self::init_admin_js_settings();
        }
    }

    public static function other_scripts() {
        UrvanovSyntaxHighlighterLog::debug('other_scripts');
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
        self::load_settings(TRUE);
        $deps = array('jquery', 'urvanov_syntax_highlighter_util_js');
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::POPUP) || is_admin()) {
            // TODO include anyway and minify
            wp_enqueue_script('urvanov_syntax_highlighter_jquery_popup', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_JQUERY_POPUP, __FILE__), array('jquery'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
            $deps[] = 'urvanov_syntax_highlighter_jquery_popup';
        }
        $result = wp_enqueue_script('urvanov_syntax_highlighter_js', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_JS, __FILE__), $deps, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
        UrvanovSyntaxHighlighterLog::debug($result, 'wp_enqueue_script='.$result);
    }

    public static function init_js_settings() {
        UrvanovSyntaxHighlighterLog::debug('Init js settings...');
        // This stores JS variables used in AJAX calls and in the JS files
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
        self::load_settings(TRUE);
        if (!self::$js_settings) {
            self::$js_settings = array(
                'version' => $URVANOV_SYNTAX_HIGHLIGHTER_VERSION,
                'is_admin' => intval(is_admin()),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'prefix' => Urvanov_Syntax_Highlighter_Settings::PREFIX,
                'setting' => Urvanov_Syntax_Highlighter_Settings::SETTING,
                'selected' => Urvanov_Syntax_Highlighter_Settings::SETTING_SELECTED,
                'changed' => Urvanov_Syntax_Highlighter_Settings::SETTING_CHANGED,
                'special' => Urvanov_Syntax_Highlighter_Settings::SETTING_SPECIAL,
                'orig_value' => Urvanov_Syntax_Highlighter_Settings::SETTING_ORIG_VALUE,
                'debug' => URVANOV_SYNTAX_HIGHLIGHTER_DEBUG
            );
        }
        if (!self::$js_strings) {
            self::$js_strings = array(
                'copy' => Urvanov_Syntax_Highlighter_Global::urvanov__('Press %s to Copy, %s to Paste'),
                'minimize' => Urvanov_Syntax_Highlighter_Global::urvanov__('Click To Expand Code')
            );
        }
        UrvanovSyntaxHighlighterLog::debug(self::$js_settings, 'UrvanovSyntaxHighlighterSyntaxSettings to js...');
        if (URVANOV_SYNTAX_HIGHLIGHTER_MINIFY) {
            
            $result = wp_localize_script('urvanov_syntax_highlighter_js', 'UrvanovSyntaxHighlighterSyntaxSettings', self::$js_settings);
            UrvanovSyntaxHighlighterLog::debug($result, 'wp_localize_script UrvanovSyntaxHighlighterSyntaxSettings result = '.$result);
            wp_localize_script('urvanov_syntax_highlighter_js', 'UrvanovSyntaxHighlighterSyntaxStrings', self::$js_strings);
            UrvanovSyntaxHighlighterLog::debug($result, 'wp_localize_script UrvanovSyntaxHighlighterSyntaxString result = '.$result);
        } else {
            wp_localize_script('urvanov_syntax_highlighter_util_js', 'UrvanovSyntaxHighlighterSyntaxSettings', self::$js_settings);
            wp_localize_script('urvanov_syntax_highlighter_util_js', 'UrvanovSyntaxHighlighterSyntaxStrings', self::$js_strings);
        }
    }

    public static function init_admin_js_settings() {
        if (!self::$admin_js_settings) {
            // We need to load themes at this stage
            Urvanov_Syntax_Highlighter_Settings_WP::load_settings();
            $themes_ = Urvanov_Syntax_Highlighter_Resources::themes()->get();
            $stockThemes = array();
            $userThemes = array();
            foreach ($themes_ as $theme) {
                $id = $theme->id();
                $name = $theme->name();
                if ($theme->user()) {
                    $userThemes[$id] = $name;
                } else {
                    $stockThemes[$id] = $name;
                }
            }
            self::$admin_js_settings = array(
                'themes' => array_merge($stockThemes, $userThemes),
                'stockThemes' => $stockThemes,
                'userThemes' => $userThemes,
                'defaultTheme' => Urvanov_Syntax_Highlighter_Themes::DEFAULT_THEME,
                'themesURL' => Urvanov_Syntax_Highlighter_Resources::themes()->dirurl(false),
                'userThemesURL' => Urvanov_Syntax_Highlighter_Resources::themes()->dirurl(true),
                'sampleCode' => self::SAMPLE_CODE,
                'dialogFunction' => 'wpdialog'
            );
            wp_localize_script('urvanov_syntax_highlighter_admin_js', 'UrvanovSyntaxHighlighterAdminSettings', self::$admin_js_settings);
        }

        if (!self::$admin_js_strings) {
            self::$admin_js_strings = array(
                'prompt' => Urvanov_Syntax_Highlighter_Global::urvanov__("Prompt"),
                'value' => Urvanov_Syntax_Highlighter_Global::urvanov__("Value"),
                'alert' => Urvanov_Syntax_Highlighter_Global::urvanov__("Alert"),
                'no' => Urvanov_Syntax_Highlighter_Global::urvanov__("No"),
                'yes' => Urvanov_Syntax_Highlighter_Global::urvanov__("Yes"),
                'confirm' => Urvanov_Syntax_Highlighter_Global::urvanov__("Confirm"),
                'changeCode' => Urvanov_Syntax_Highlighter_Global::urvanov__("Change Code")
            );
            wp_localize_script('urvanov_syntax_highlighter_admin_js', 'UrvanovSyntaxHighlighterAdminStrings', self::$admin_js_strings);
        }
    }

    public static function settings() {
        if (!current_user_can('manage_options')) {
            wp_die(Urvanov_Syntax_Highlighter_Global::urvanov__('You do not have sufficient permissions to access this page.'));
        }
        ?>

        <script type="text/javascript">
            jQuery(document).ready(function () {
            	UrvanovSyntaxHighlighterAdmin.init();
            });
        </script>

        <div id="urvanov-syntax-highlighter-main-wrap" class="wrap">

            <div id="icon-options-general" class="icon32">
                <br>
            </div>
            <h2>
                Crayon Syntax Highlighter
                <?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Settings'); ?>
            </h2>
            <?php self::help(); ?>
            <form id="urvanov-syntax-highlighter-settings-form" action="options.php" method="post">
                <?php
                settings_fields(self::FIELDS);
                ?>

                <?php
                do_settings_sections(self::SETTINGS);
                ?>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button-primary"
                           value="<?php
                           Urvanov_Syntax_Highlighter_Global::urvanov_e('Save Changes');
                           ?>"/><span style="width:10px; height: 5px; float:left;"></span>
                    <input type="submit"
                           name="<?php echo self::OPTIONS; ?>[reset]"
                           id="reset"
                           class="button-primary"
                           value="<?php
                           Urvanov_Syntax_Highlighter_Global::urvanov_e('Reset Settings');
                           ?>"/>
                </p>
            </form>
        </div>

        <div id="urvanov-syntax-highlighter-theme-editor-wrap" class="wrap"></div>

    <?php
    }

    // Load the global settings and update them from the db
    public static function load_settings($just_load_settings = FALSE) {
        if (self::$options === NULL) {
            // Load settings from db
            if (!(self::$options = get_option(self::OPTIONS))) {
                self::$options = Urvanov_Syntax_Highlighter_Settings::get_defaults_array();
                update_option(self::OPTIONS, self::$options);
            }
            // Initialise default global settings and update them from db
            Urvanov_Syntax_Highlighter_Global_Settings::set(self::$options);
        }

        if (!self::$is_fully_loaded && !$just_load_settings) {
            // Load everything else as well

            // For local file loading
            // This is used to decouple WP functions from internal Crayon classes
            Urvanov_Syntax_Highlighter_Global_Settings::site_url(home_url());
            Urvanov_Syntax_Highlighter_Global_Settings::site_path(ABSPATH);
            Urvanov_Syntax_Highlighter_Global_Settings::plugin_path(plugins_url('', __FILE__));
            $upload = wp_upload_dir();

            UrvanovSyntaxHighlighterLog::debug($upload, "WP UPLOAD FUNCTION");
            UrvanovSyntaxHighlighterLog::debug(URVANOV_SYNTAX_HIGHLIGHTER_DIR, "URVANOV_SYNTAX_HIGHLIGHTER_DIR=".URVANOV_SYNTAX_HIGHLIGHTER_DIR);
            Urvanov_Syntax_Highlighter_Global_Settings::upload_path(UrvanovSyntaxHighlighterUtil::path_slash($upload['basedir']) . URVANOV_SYNTAX_HIGHLIGHTER_DIR);
            Urvanov_Syntax_Highlighter_Global_Settings::upload_url($upload['baseurl'] . '/' . URVANOV_SYNTAX_HIGHLIGHTER_DIR);
            UrvanovSyntaxHighlighterLog::debug(Urvanov_Syntax_Highlighter_Global_Settings::upload_path(), "UPLOAD PATH");
            Urvanov_Syntax_Highlighter_Global_Settings::set_mkdir('wp_mkdir_p');

            // Load all available languages and themes
            Urvanov_Syntax_Highlighter_Resources::langs()->load();
            Urvanov_Syntax_Highlighter_Resources::themes()->load();

            // Ensure all missing settings in db are replaced by default values
            $changed = FALSE;
            foreach (Urvanov_Syntax_Highlighter_Settings::get_defaults_array() as $name => $value) {
                // Add missing settings
                if (!array_key_exists($name, self::$options)) {
                    self::$options[$name] = $value;
                    $changed = TRUE;
                }
            }
            // A setting was missing, update options
            if ($changed) {
                update_option(self::OPTIONS, self::$options);
            }

            self::$is_fully_loaded = TRUE;
        }
    }

    public static function get_settings() {
        return get_option(self::OPTIONS);
    }

    // Saves settings from Urvanov_Syntax_Highlighter_Global_Settings, or provided array, to the db
    public static function save_settings($settings = NULL) {
        if ($settings === NULL) {
            $settings = Urvanov_Syntax_Highlighter_Global_Settings::get_array();
        }
        update_option(self::OPTIONS, $settings);
    }

    // Crayon posts

    /**
     * This loads the posts marked as containing Crayons
     */
    public static function load_posts() {
        if (self::$urvanov_syntax_highlighter_posts === NULL) {
            // Load from db
            if (!(self::$urvanov_syntax_highlighter_posts = get_option(self::POSTS))) {
                // Posts don't exist! Scan for them. This will fill self::$urvanov_syntax_highlighter_posts
                self::$urvanov_syntax_highlighter_posts = Urvanov_Syntax_Highlighter_Plugin::scan_posts();
                update_option(self::POSTS, self::$urvanov_syntax_highlighter_posts);
            }
        }
        return self::$urvanov_syntax_highlighter_posts;
    }

    /**
     * This looks through all posts and marks those which contain Crayons
     */
// 	public static function scan_and_save_posts() {
// 		self::save_posts(Urvanov_Syntax_Highlighter_Plugin::scan_posts(TRUE, TRUE));
// 	}

    /**
     * Saves the marked posts to the db
     */
    public static function save_posts($posts = NULL) {
        if ($posts === NULL) {
            $posts = self::$urvanov_syntax_highlighter_posts;
        }
        update_option(self::POSTS, $posts);
        self::load_posts();
    }

    /**
     * Adds a post as containing a Crayon
     */
    public static function add_post($id, $save = TRUE) {
        self::load_posts();
        if (!in_array($id, self::$urvanov_syntax_highlighter_posts)) {
            self::$urvanov_syntax_highlighter_posts[] = $id;
        }
        if ($save) {
            self::save_posts();
        }
    }

    /**
     * Removes a post as not containing a Crayon
     */
    public static function remove_post($id, $save = TRUE) {
        self::load_posts();
        $key = array_search($id, self::$urvanov_syntax_highlighter_posts);
        if ($key === false) {
            return;
        }
        unset(self::$urvanov_syntax_highlighter_posts[$key]);
        if ($save) {
            self::save_posts();
        }
    }

    public static function remove_posts() {
        self::$urvanov_syntax_highlighter_posts = array();
        self::save_posts();
    }

    // Crayon legacy posts

    /**
     * This loads the posts marked as containing Crayons
     */
    public static function load_legacy_posts($force = FALSE) {
        if (self::$urvanov_syntax_highlighter_legacy_posts === NULL || $force) {
            // Load from db
            if (!(self::$urvanov_syntax_highlighter_legacy_posts = get_option(self::LEGACY_POSTS))) {
                // Posts don't exist! Scan for them. This will fill self::$urvanov_syntax_highlighter_legacy_posts
                self::$urvanov_syntax_highlighter_legacy_posts = Urvanov_Syntax_Highlighter_Plugin::scan_legacy_posts();
                update_option(self::LEGACY_POSTS, self::$urvanov_syntax_highlighter_legacy_posts);
            }
        }
        return self::$urvanov_syntax_highlighter_legacy_posts;
    }

    /**
     * This looks through all posts and marks those which contain Crayons
     */
// 	public static function scan_and_save_posts() {
// 		self::save_posts(Urvanov_Syntax_Highlighter_Plugin::scan_posts(TRUE, TRUE));
// 	}

    /**
     * Saves the marked posts to the db
     */
    public static function save_legacy_posts($posts = NULL) {
        if ($posts === NULL) {
            $posts = self::$urvanov_syntax_highlighter_legacy_posts;
        }
        update_option(self::LEGACY_POSTS, $posts);
        self::load_legacy_posts();
    }

    /**
     * Adds a post as containing a Crayon
     */
    public static function add_legacy_post($id, $save = TRUE) {
        self::load_legacy_posts();
        if (!in_array($id, self::$urvanov_syntax_highlighter_legacy_posts)) {
            self::$urvanov_syntax_highlighter_legacy_posts[] = $id;
        }
        if ($save) {
            self::save_legacy_posts();
        }
    }

    /**
     * Removes a post as not containing a Crayon
     */
    public static function remove_legacy_post($id, $save = TRUE) {
        self::load_legacy_posts();
        $key = array_search($id, self::$urvanov_syntax_highlighter_legacy_posts);
        if ($key === false) {
            return;
        }
        unset(self::$urvanov_syntax_highlighter_legacy_posts[$key]);
        if ($save) {
            self::save_legacy_posts();
        }
    }

    public static function remove_legacy_posts() {
        self::$urvanov_syntax_highlighter_legacy_posts = array();
        self::save_legacy_posts();
    }

    // Cache

    public static function add_cache($name) {
        self::load_cache();
        if (!in_array($name, self::$cache)) {
            self::$cache[] = $name;
        }
        self::save_cache();
    }

    public static function remove_cache($name) {
        self::load_cache();
        $key = array_search($name, self::$cache);
        if ($key === false) {
            return;
        }
        unset(self::$cache[$key]);
        self::save_cache();
    }

    public static function clear_cache() {
        self::load_cache();
        foreach (self::$cache as $name) {
            delete_transient($name);
        }
        self::$cache = array();
        self::save_cache();
    }

    public static function load_cache() {
        // Load cache from db
        if (!(self::$cache = get_option(self::CACHE))) {
            self::$cache = array();
            update_option(self::CACHE, self::$cache);
        }
    }

    public static function save_cache() {
        update_option(self::CACHE, self::$cache);
        self::load_cache();
    }

    // Paths

    public static function admin_init() {
        // Load default settings if they don't exist
        self::load_settings();

        // General
        // Some of these will the $editor arguments, if TRUE it will alter for use in the Tag Editor
        self::add_section(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('General'));
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Theme'), 'theme');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Font'), 'font');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Metrics'), 'metrics');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Toolbar'), 'toolbar');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Lines'), 'lines');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Code'), 'code');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Tags'), 'tags');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Languages'), 'langs');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Files'), 'files');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Posts'), 'posts');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Tag Editor'), 'tag_editor');
        self::add_field(self::GENERAL, Urvanov_Syntax_Highlighter_Global::urvanov__('Misc'), 'misc');

        // Debug
        self::add_section(self::DEBUG, Urvanov_Syntax_Highlighter_Global::urvanov__('Debug'));
        self::add_field(self::DEBUG, Urvanov_Syntax_Highlighter_Global::urvanov__('Errors'), 'errors');
        self::add_field(self::DEBUG, Urvanov_Syntax_Highlighter_Global::urvanov__('Log'), 'log');
        // ABOUT

        self::add_section(self::ABOUT, Urvanov_Syntax_Highlighter_Global::urvanov__('About'));
        $image = '<div id="urvanov-syntax-highlighter-logo">

				<img src="' . plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_LOGO, __FILE__) . '" /><br/></div>';
        self::add_field(self::ABOUT, $image, 'info');
    }

    // Wrapper functions

    private static function add_section($name, $title, $callback = NULL) {
        $callback = (empty($callback) ? 'blank' : $callback);
        add_settings_section($name, $title, 'Urvanov_Syntax_Highlighter_Settings_WP::' . $callback, self::SETTINGS);
    }

    private static function add_field($section, $title, $callback, $args = array()) {
        $unique = preg_replace('#\\s#', '_', strtolower($title));
        add_settings_field($unique, $title, 'Urvanov_Syntax_Highlighter_Settings_WP::' . $callback, self::SETTINGS, $section, $args);
    }

    // Validates all the settings passed from the form in $inputs

    public static function settings_validate($inputs) {

        // Load current settings from db
        self::load_settings(TRUE);

        global $URVANOV_SYNTAX_HIGHLIGHTER_EMAIL;
        // When reset button is pressed, remove settings so default loads next time
        if (array_key_exists('reset', $inputs)) {
            self::clear_cache();
            return array();
        }
        // Convert old tags
        if (array_key_exists('convert', $inputs)) {
            $encode = array_key_exists('convert_encode', $inputs);
            Urvanov_Syntax_Highlighter_Plugin::convert_tags($encode);
        }
        // Refresh internal tag management
        if (array_key_exists('refresh_tags', $inputs)) {
            Urvanov_Syntax_Highlighter_Plugin::refresh_posts();
        }
        // Clear the log if needed
        if (array_key_exists(self::LOG_CLEAR, $_POST)) {
            UrvanovSyntaxHighlighterLog::clear();
        }
        // Send to admin
        if (array_key_exists(self::LOG_EMAIL_ADMIN, $_POST)) {
            UrvanovSyntaxHighlighterLog::email(get_bloginfo('admin_email'));
        }
        // Send to developer
        if (array_key_exists(self::LOG_EMAIL_DEV, $_POST)) {
            UrvanovSyntaxHighlighterLog::email($URVANOV_SYNTAX_HIGHLIGHTER_EMAIL, get_bloginfo('admin_email'));
        }

        // Clear the cache
        if (array_key_exists(self::CACHE_CLEAR, $_POST)) {
            self::clear_cache();
        }

        // If settings don't exist in input, set them to default
        $global_settings = Urvanov_Syntax_Highlighter_Settings::get_defaults();

        $ignored = array(Urvanov_Syntax_Highlighter_Settings::HIDE_HELP);

        foreach ($global_settings as $setting) {
            // XXX Ignore some settings
            if (in_array($setting->name(), $ignored)) {
                $inputs[$setting->name()] = Urvanov_Syntax_Highlighter_Global_Settings::val($setting->name());
                continue;
            }

            // If boolean setting is not in input, then it is set to FALSE in the form
            if (!array_key_exists($setting->name(), $inputs)) {
                // For booleans, set to FALSE (unchecked boxes are not sent as POST)
                if (is_bool($setting->def())) {
                    $inputs[$setting->name()] = FALSE;
                } else {
                    /*  For array settings, set the input as the value, which by default is the
                     default index */
                    if (is_array($setting->def())) {
                        $inputs[$setting->name()] = $setting->value();
                    } else {
                        $inputs[$setting->name()] = $setting->def();
                    }
                }
            }
        }

        $refresh = array(
            // These should trigger a refresh of which posts contain crayons, since they affect capturing
            Urvanov_Syntax_Highlighter_Settings::INLINE_TAG => TRUE,
            Urvanov_Syntax_Highlighter_Settings::INLINE_TAG_CAPTURE => TRUE,
            Urvanov_Syntax_Highlighter_Settings::CODE_TAG_CAPTURE => TRUE,
            Urvanov_Syntax_Highlighter_Settings::BACKQUOTE => TRUE,
            Urvanov_Syntax_Highlighter_Settings::CAPTURE_PRE => TRUE,
            Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG => TRUE,
            Urvanov_Syntax_Highlighter_Settings::PLAIN_TAG => TRUE
        );

        // Validate inputs
        foreach ($inputs as $input => $value) {
            // Convert all array setting values to ints
            $inputs[$input] = $value = Urvanov_Syntax_Highlighter_Settings::validate($input, $value);
            // Clear cache when changed
            if (Urvanov_Syntax_Highlighter_Global_Settings::has_changed($input, Urvanov_Syntax_Highlighter_Settings::CACHE, $value)) {
                self::clear_cache();
            }
            if (isset($refresh[$input])) {
                if (Urvanov_Syntax_Highlighter_Global_Settings::has_changed($input, $input, $value)) {
                    // Needs to take place, in case it refresh depends on changed value
                    Urvanov_Syntax_Highlighter_Global_Settings::set($input, $value);
                    Urvanov_Syntax_Highlighter_Plugin::refresh_posts();
                }
            }
        }

        return $inputs;
    }

    // Section callback functions

    public static function blank() {
    } // Used for required callbacks with blank content

    // Input Drawing ==========================================================

    private static function input($args) {
        $id = '';
        $size = 40;
        $margin = FALSE;
        $preview = 1;
        $break = FALSE;
        $type = 'text';
        extract($args);

        echo '<input id="', Urvanov_Syntax_Highlighter_Settings::PREFIX, $id, '" name="', self::OPTIONS, '[', $id, ']" class="' . Urvanov_Syntax_Highlighter_Settings::SETTING . '" size="', $size, '" type="', $type, '" value="',
        self::$options[$id], '" style="margin-left: ', ($margin ? '20px' : '0px'), ';" urvanov-syntax-highlighter-preview="', ($preview ? 1 : 0), '" />', ($break ? URVANOV_SYNTAX_HIGHLIGHTER_BR : '');
    }

    private static function checkbox($args, $line_break = TRUE, $preview = TRUE) {
        if (empty($args) || !is_array($args) || count($args) != 2) {
            return;
        }
        $id = $args[0];
        $text = $args[1];
        $checked = (!array_key_exists($id, self::$options)) ? FALSE : self::$options[$id] == TRUE;
        $checked_str = $checked ? ' checked="checked"' : '';
        echo '<input id="', Urvanov_Syntax_Highlighter_Settings::PREFIX, $id, '" name="', self::OPTIONS, '[', $id, ']" type="checkbox" class="' . Urvanov_Syntax_Highlighter_Settings::SETTING . '" value="1"', $checked_str,
        ' urvanov-syntax-highlighter-preview="', ($preview ? 1 : 0), '" /> ', '<label for="', Urvanov_Syntax_Highlighter_Settings::PREFIX, $id, '">', $text, '</label>', ($line_break ? URVANOV_SYNTAX_HIGHLIGHTER_BR : '');
    }

    // Draws a dropdown by loading the default value (an array) from a setting
    private static function dropdown($id, $line_break = TRUE, $preview = TRUE, $echo = TRUE, $resources = NULL, $selected = NULL) {
        if (!array_key_exists($id, self::$options)) {
            return;
        }
        $resources = $resources != NULL ? $resources : Urvanov_Syntax_Highlighter_Global_Settings::get($id)->def();

        $return = '<select id="' . Urvanov_Syntax_Highlighter_Settings::PREFIX . $id . '" name="' . self::OPTIONS . '[' . $id . ']" class="' . Urvanov_Syntax_Highlighter_Settings::SETTING . '" urvanov-syntax-highlighter-preview="' . ($preview ? 1 : 0) . '">';
        foreach ($resources as $k => $v) {
            if (is_array($v) && count($v)) {
                $data = $v[0];
                $text = $v[1];
            } else {
                $text = $v;
            }
            $is_selected = $selected !== NULL && $selected == $k ? 'selected' : selected(self::$options[$id], $k, FALSE);
            $return .= '<option ' . (isset($data) ? 'data-value="' . $data . '"' : '') . ' value="' . $k . '" ' . $is_selected . '>' . $text . '</option>';
        }
        $return .= '</select>' . ($line_break ? URVANOV_SYNTAX_HIGHLIGHTER_BR : '');
        if ($echo) {
            echo $return;
        } else {
            return $return;
        }
    }

    private static function button($args = array()) {
        extract($args);
        UrvanovSyntaxHighlighterUtil::set_var($id, '');
        UrvanovSyntaxHighlighterUtil::set_var($class, '');
        UrvanovSyntaxHighlighterUtil::set_var($onclick, '');
        UrvanovSyntaxHighlighterUtil::set_var($title, '');
        return '<a id="' . $id . '" class="button-primary ' . $class . '" onclick="' . $onclick . '">' . $title . '</a>';
    }

    private static function info_span($name, $text) {
        echo '<span id="', $name, '-info">', $text, '</span>';
    }

    private static function span($text) {
        echo '<span>', $text, '</span>';
    }

    // General Fields =========================================================
    public static function help() {
        global $URVANOV_SYNTAX_HIGHLIGHTER_WEBSITE, $URVANOV_SYNTAX_HIGHLIGHTER_TWITTER, $URVANOV_SYNTAX_HIGHLIGHTER_GIT, $URVANOV_SYNTAX_HIGHLIGHTER_PLUGIN_WP, $URVANOV_SYNTAX_HIGHLIGHTER_DONATE;
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::HIDE_HELP)) {
            return;
        }
        echo '<div id="urvanov-syntax-highlighter-help" class="updated settings-error urvanov-syntax-highlighter-help">
				<p><strong>Howdy, coder!</strong> Thanks for using Crayon. <strong>Useful Links:</strong> <a href="' . $URVANOV_SYNTAX_HIGHLIGHTER_WEBSITE . '" target="_blank">Documentation</a>, <a href="' . $URVANOV_SYNTAX_HIGHLIGHTER_GIT . '" target="_blank">GitHub</a>, <a href="' . $URVANOV_SYNTAX_HIGHLIGHTER_PLUGIN_WP . '" target="_blank">Plugin Page</a>, <a href="' . $URVANOV_SYNTAX_HIGHLIGHTER_TWITTER . '" target="_blank">Twitter</a>. Crayon has always been free. If you value my work please consider a <a href="' . $URVANOV_SYNTAX_HIGHLIGHTER_DONATE . '">small donation</a> to show your appreciation. Thanks! <a class="urvanov-syntax-highlighter-help-close">X</a></p></div>
						';
    }

    public static function help_screen() {
        $screen = get_current_screen();

        if ($screen->id != self::$admin_page) {
            return;
        }
    }

    public static function metrics() {
        echo '<div id="urvanov-syntax-highlighter-section-metrics" class="urvanov-syntax-highlighter-hide-inline">';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::HEIGHT_SET, '<span class="urvanov-syntax-highlighter-span-50">' . Urvanov_Syntax_Highlighter_Global::urvanov__('Height') . ' </span>'), FALSE);
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::HEIGHT_MODE, FALSE);
        echo ' ';
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::HEIGHT, 'size' => 8));
        echo ' ';
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::HEIGHT_UNIT);
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::WIDTH_SET, '<span class="urvanov-syntax-highlighter-span-50">' . Urvanov_Syntax_Highlighter_Global::urvanov__('Width') . ' </span>'), FALSE);
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::WIDTH_MODE, FALSE);
        echo ' ';
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::WIDTH, 'size' => 8));
        echo ' ';
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::WIDTH_UNIT);
        $text = array(Urvanov_Syntax_Highlighter_Global::urvanov__('Top Margin') => array(Urvanov_Syntax_Highlighter_Settings::TOP_SET, Urvanov_Syntax_Highlighter_Settings::TOP_MARGIN),
            Urvanov_Syntax_Highlighter_Global::urvanov__('Bottom Margin') => array(Urvanov_Syntax_Highlighter_Settings::BOTTOM_SET, Urvanov_Syntax_Highlighter_Settings::BOTTOM_MARGIN),
            Urvanov_Syntax_Highlighter_Global::urvanov__('Left Margin') => array(Urvanov_Syntax_Highlighter_Settings::LEFT_SET, Urvanov_Syntax_Highlighter_Settings::LEFT_MARGIN),
            Urvanov_Syntax_Highlighter_Global::urvanov__('Right Margin') => array(Urvanov_Syntax_Highlighter_Settings::RIGHT_SET, Urvanov_Syntax_Highlighter_Settings::RIGHT_MARGIN));
        foreach ($text as $p => $s) {
            $set = $s[0];
            $margin = $s[1];
            $preview = ($p == Urvanov_Syntax_Highlighter_Global::urvanov__('Left Margin') || $p == Urvanov_Syntax_Highlighter_Global::urvanov__('Right Margin'));
            self::checkbox(array($set, '<span class="urvanov-syntax-highlighter-span-110">' . $p . '</span>'), FALSE, $preview);
            echo ' ';
            self::input(array('id' => $margin, 'size' => 8, 'preview' => FALSE));
            echo '<span class="urvanov-syntax-highlighter-span-margin">', Urvanov_Syntax_Highlighter_Global::urvanov__('Pixels'), '</span>', URVANOV_SYNTAX_HIGHLIGHTER_BR;
        }
        echo '<span class="urvanov-syntax-highlighter-span" style="min-width: 135px;">' . Urvanov_Syntax_Highlighter_Global::urvanov__('Horizontal Alignment') . ' </span>';
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::H_ALIGN);
        echo '<div id="urvanov-syntax-highlighter-subsection-float">';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::FLOAT_ENABLE, Urvanov_Syntax_Highlighter_Global::urvanov__('Allow floating elements to surround Crayon')), FALSE, FALSE);
        echo '</div>';
        echo '<span class="urvanov-syntax-highlighter-span-100">' . Urvanov_Syntax_Highlighter_Global::urvanov__('Inline Margin') . ' </span>';
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::INLINE_MARGIN, 'size' => 2));
        echo '<span class="urvanov-syntax-highlighter-span-margin">', Urvanov_Syntax_Highlighter_Global::urvanov__('Pixels'), '</span>';
        echo '</div>';
    }

    public static function toolbar() {
        echo '<div id="urvanov-syntax-highlighter-section-toolbar" class="urvanov-syntax-highlighter-hide-inline">';
        self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('Display the Toolbar') . ' ');
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::TOOLBAR);
        echo '<div id="urvanov-syntax-highlighter-subsection-toolbar">';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::TOOLBAR_OVERLAY, Urvanov_Syntax_Highlighter_Global::urvanov__('Overlay the toolbar on code rather than push it down when possible')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::TOOLBAR_HIDE, Urvanov_Syntax_Highlighter_Global::urvanov__('Toggle the toolbar on single click when it is overlayed')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::TOOLBAR_DELAY, Urvanov_Syntax_Highlighter_Global::urvanov__('Delay hiding the toolbar on MouseOut')));
        echo '</div>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::SHOW_TITLE, Urvanov_Syntax_Highlighter_Global::urvanov__('Display the title when provided')));
        self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('Display the language') . ' ');
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::SHOW_LANG);
        echo '</div>';
    }

    public static function lines() {
        echo '<div id="urvanov-syntax-highlighter-section-lines" class="urvanov-syntax-highlighter-hide-inline">';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::STRIPED, Urvanov_Syntax_Highlighter_Global::urvanov__('Display striped code lines')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::MARKING, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable line marking for important lines')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::RANGES, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable line ranges for showing only parts of code')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::NUMS, Urvanov_Syntax_Highlighter_Global::urvanov__('Display line numbers by default')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::NUMS_TOGGLE, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable line number toggling')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::WRAP, Urvanov_Syntax_Highlighter_Global::urvanov__('Wrap lines by default')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::WRAP_TOGGLE, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable line wrap toggling')));
        self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('Start line numbers from') . ' ');
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::START_LINE, 'size' => 2, 'break' => TRUE));
        echo '</div>';
    }

    public static function langs() {
        echo '<a name="langs"></a>';
        // Specialised dropdown for languages
        if (array_key_exists(Urvanov_Syntax_Highlighter_Settings::FALLBACK_LANG, self::$options)) {
            if (($langs = Urvanov_Syntax_Highlighter_Parser::parse_all()) != FALSE) {
                $langs = Urvanov_Syntax_Highlighter_Langs::sort_by_name($langs);
                self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('When no language is provided, use the fallback') . ': ');
                self::dropdown(Urvanov_Syntax_Highlighter_Settings::FALLBACK_LANG, FALSE, TRUE, TRUE, $langs);
                // Information about parsing
                $parsed = Urvanov_Syntax_Highlighter_Resources::langs()->is_parsed();
                $count = count($langs);
                echo '</select>', URVANOV_SYNTAX_HIGHLIGHTER_BR, ($parsed ? '' : '<span class="urvanov-syntax-highlighter-error">'),
                sprintf(Urvanov_Syntax_Highlighter_Global::urvanov_n('%d language has been detected.', '%d languages have been detected.', $count), $count), ' ',
                $parsed ? Urvanov_Syntax_Highlighter_Global::urvanov__('Parsing was successful') : Urvanov_Syntax_Highlighter_Global::urvanov__('Parsing was unsuccessful'),
                ($parsed ? '. ' : '</span>');
                // Check if fallback from db is loaded
                $db_fallback = self::$options[Urvanov_Syntax_Highlighter_Settings::FALLBACK_LANG]; // Fallback name from db

                if (!Urvanov_Syntax_Highlighter_Resources::langs()->is_loaded($db_fallback) || !Urvanov_Syntax_Highlighter_Resources::langs()->exists($db_fallback)) {
                    echo '<br/><span class="urvanov-syntax-highlighter-error">', sprintf(Urvanov_Syntax_Highlighter_Global::urvanov__('The selected language with id %s could not be loaded'), '<strong>' . $db_fallback . '</strong>'), '. </span>';
                }
                // Language parsing info
                echo URVANOV_SYNTAX_HIGHLIGHTER_BR, '<div id="urvanov-syntax-highlighter-subsection-langs-info"><div>' . self::button(array('id' => 'show-langs', 'title' => Urvanov_Syntax_Highlighter_Global::urvanov__('Show Languages'))) . '</div></div>';
            } else {
                echo Urvanov_Syntax_Highlighter_Global::urvanov__('No languages could be parsed.');
            }
        }
    }

    public static function show_langs() {
        Urvanov_Syntax_Highlighter_Settings_WP::load_settings();
        require_once(URVANOV_SYNTAX_HIGHLIGHTER_PARSER_PHP);
        if (($langs = Urvanov_Syntax_Highlighter_Parser::parse_all()) != FALSE) {
            $langs = Urvanov_Syntax_Highlighter_Langs::sort_by_name($langs);
            echo '<table class="urvanov-syntax-highlighter-table" cellspacing="0" cellpadding="0"><tr class="urvanov-syntax-highlighter-table-header">',
            '<td>', Urvanov_Syntax_Highlighter_Global::urvanov__('ID'), '</td><td>', Urvanov_Syntax_Highlighter_Global::urvanov__('Name'), '</td><td>', Urvanov_Syntax_Highlighter_Global::urvanov__('Version'), '</td><td>', Urvanov_Syntax_Highlighter_Global::urvanov__('File Extensions'), '</td><td>', Urvanov_Syntax_Highlighter_Global::urvanov__('Aliases'), '</td><td>', Urvanov_Syntax_Highlighter_Global::urvanov__('State'), '</td></tr>';
            $keys = array_values($langs);
            for ($i = 0; $i < count($langs); $i++) {
                $lang = $keys[$i];
                $tr = ($i == count($langs) - 1) ? 'urvanov-syntax-highlighter-table-last' : '';
                echo '<tr class="', $tr, '">',
                '<td>', $lang->id(), '</td>',
                '<td>', $lang->name(), '</td>',
                '<td>', $lang->version(), '</td>',
                '<td>', implode(', ', $lang->ext()), '</td>',
                '<td>', implode(', ', $lang->alias()), '</td>',
                '<td class="', strtolower(UrvanovSyntaxHighlighterUtil::space_to_hyphen($lang->state_info())), '">',
                $lang->state_info(), '</td>',
                '</tr>';
            }
            echo '</table><br/>' . Urvanov_Syntax_Highlighter_Global::urvanov__("Languages that have the same extension as their name don't need to explicitly map extensions.");
        } else {
            echo Urvanov_Syntax_Highlighter_Global::urvanov__('No languages could be found.');
        }
        exit();
    }

    public static function posts() {
        echo '<a name="posts"></a>';
        echo self::button(array('id' => 'show-posts', 'title' => Urvanov_Syntax_Highlighter_Global::urvanov__('Show Crayon Posts')));
        echo ' <input type="submit" name="', self::OPTIONS, '[refresh_tags]" id="refresh_tags" class="button-primary" value="', Urvanov_Syntax_Highlighter_Global::urvanov__('Refresh'), '" />';
        echo self::help_button('http://aramk.com/blog/2012/09/26/internal-post-management-crayon/');
        echo '<div id="urvanov-syntax-highlighter-subsection-posts-info"></div>';
    }

    public static function post_cmp($a, $b) {
        $a = $a->post_modified;
        $b = $b->post_modified;
        if ($a == $b) {
            return 0;
        } else {
            return $a < $b ? 1 : -1;
        }
    }

    public static function show_posts() {
        Urvanov_Syntax_Highlighter_Settings_WP::load_settings();
        $postIDs = self::load_posts();
        $legacy_posts = self::load_legacy_posts();
        // Avoids O(n^2) by using a hash map, tradeoff in using strval
        $legacy_map = array();
        foreach ($legacy_posts as $legacyID) {
            $legacy_map[strval($legacyID)] = TRUE;
        }

        echo '<table class="urvanov-syntax-highlighter-table" cellspacing="0" cellpadding="0"><tr class="urvanov-syntax-highlighter-table-header">',
        '<td>', Urvanov_Syntax_Highlighter_Global::urvanov__('ID'), '</td><td>', Urvanov_Syntax_Highlighter_Global::urvanov__('Title'), '</td><td>', Urvanov_Syntax_Highlighter_Global::urvanov__('Posted'), '</td><td>', Urvanov_Syntax_Highlighter_Global::urvanov__('Modifed'), '</td><td>', Urvanov_Syntax_Highlighter_Global::urvanov__('Contains Legacy Tags?'), '</td></tr>';

        $posts = array();
        for ($i = 0; $i < count($postIDs); $i++) {
            $posts[$i] = get_post($postIDs[$i]);
        }

        usort($posts, 'Urvanov_Syntax_Highlighter_Settings_WP::post_cmp');

        for ($i = 0; $i < count($posts); $i++) {
            $post = $posts[$i];
            $postID = $post->ID;
            $title = $post->post_title;
            $title = !empty($title) ? $title : 'N/A';
            $tr = ($i == count($posts) - 1) ? 'urvanov-syntax-highlighter-table-last' : '';
            echo '<tr class="', $tr, '">',
            '<td>', $postID, '</td>',
            '<td><a href="', $post->guid, '" target="_blank">', $title, '</a></td>',
            '<td>', $post->post_date, '</td>',
            '<td>', $post->post_modified, '</td>',
            '<td>', isset($legacy_map[strval($postID)]) ? '<span style="color: red;">' . Urvanov_Syntax_Highlighter_Global::urvanov__('Yes') . '</a>' : Urvanov_Syntax_Highlighter_Global::urvanov__('No'), '</td>',
            '</tr>';
        }

        echo '</table>';
        exit();
    }

    public static function show_preview() {
        echo '<div id="content">';

        self::load_settings(); // Run first to ensure global settings loaded

        $urvanov_syntax_highlighter = Urvanov_Syntax_Highlighter_Plugin::instance();

        // Settings to prevent from validating
        $preview_settings = array(self::SAMPLE_CODE);

        // Load settings from GET and validate
        foreach ($_POST as $key => $value) {
            //	echo $key, ' ', $value , '<br/>';
        	$value = stripslashes(sanitize_text_field($value));
            if (!in_array($key, $preview_settings)) {
                $_POST[$key] = Urvanov_Syntax_Highlighter_Settings::validate($key, $value);
            } else {
                $_POST[$key] = $value;
            }
        }
        $urvanov_syntax_highlighter->settings($_POST);
        if (!isset($urvanov_syntax_highlighter_preview_dont_override_get) || !$urvanov_syntax_highlighter_preview_dont_override_get) {
            $settings = array(Urvanov_Syntax_Highlighter_Settings::TOP_SET => TRUE, Urvanov_Syntax_Highlighter_Settings::TOP_MARGIN => 10,
                Urvanov_Syntax_Highlighter_Settings::BOTTOM_SET => FALSE, Urvanov_Syntax_Highlighter_Settings::BOTTOM_MARGIN => 0);
            $urvanov_syntax_highlighter->settings($settings);
        }

        // Print the theme CSS
        $theme_id = $urvanov_syntax_highlighter->setting_val(Urvanov_Syntax_Highlighter_Settings::THEME);
        if ($theme_id != NULL) {
            echo Urvanov_Syntax_Highlighter_Resources::themes()->get_css($theme_id, date('U'));
        }

        $font_id = $urvanov_syntax_highlighter->setting_val(Urvanov_Syntax_Highlighter_Settings::FONT);
        if ($font_id != NULL /*&& $font_id != CrayonFonts::DEFAULT_FONT*/) {
            echo Urvanov_Syntax_Highlighter_Resources::fonts()->get_css($font_id);
        }

        // Load custom code based on language
        $lang = $urvanov_syntax_highlighter->setting_val(Urvanov_Syntax_Highlighter_Settings::FALLBACK_LANG);
        $path = Urvanov_Syntax_Highlighter_Global_Settings::plugin_path() . URVANOV_SYNTAX_HIGHLIGHTER_UTIL_DIR . '/sample/' . $lang . '.txt';

        if (isset($_POST[self::SAMPLE_CODE])) {
        	$urvanov_syntax_highlighter->code(sanitize_text_field($_POST[self::SAMPLE_CODE]));
        } else if ($lang && @file_exists($path)) {
            $urvanov_syntax_highlighter->url($path);
        } else {
            $code = "
// A sample class
class Human {
	private int age = 0;
	public void birthday() {
		age++;
		print('Happy Birthday!');
	}
}
";
            $urvanov_syntax_highlighter->code($code);
        }
        $urvanov_syntax_highlighter->title('Sample Code');
        $urvanov_syntax_highlighter->marked('5-7');
        $urvanov_syntax_highlighter->output($highlight = true, $nums = true, $print = true);
        echo '</div>';
        Urvanov_Syntax_Highlighter_Global::load_plugin_textdomain();
        exit();
    }

    public static function theme($editor = FALSE) {
        $db_theme = self::$options[Urvanov_Syntax_Highlighter_Settings::THEME]; // Theme name from db
        if (!array_key_exists(Urvanov_Syntax_Highlighter_Settings::THEME, self::$options)) {
            $db_theme = '';
        }
        $themes_array = Urvanov_Syntax_Highlighter_Resources::themes()->get_array();
        // Mark user themes
        foreach ($themes_array as $id => $name) {
            $mark = Urvanov_Syntax_Highlighter_Resources::themes()->get($id)->user() ? ' *' : '';
            $themes_array[$id] = array($name, $name . $mark);
        }
        $missing_theme = !Urvanov_Syntax_Highlighter_Resources::themes()->is_loaded($db_theme) || !Urvanov_Syntax_Highlighter_Resources::themes()->exists($db_theme);
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::THEME, FALSE, FALSE, TRUE, $themes_array, $missing_theme ? Urvanov_Syntax_Highlighter_Themes::DEFAULT_THEME : NULL);
        if ($editor) {
            return;
        }
        // Theme editor
        if (URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR) {
            // 			echo '<a id="urvanov-syntax-highlighter-theme-editor-button" class="button-primary urvanov-syntax-highlighter-admin-button" loading="'. Urvanov_Syntax_Highlighter_Global::urvanov__('Loading...') .'" loaded="'. Urvanov_Syntax_Highlighter_Global::urvanov__('Theme Editor') .'" >'. Urvanov_Syntax_Highlighter_Global::urvanov__('Theme Editor') .'</a></br>';
            echo '<div id="urvanov-syntax-highlighter-theme-editor-admin-buttons">';
            $buttons = array('edit' => Urvanov_Syntax_Highlighter_Global::urvanov__('Edit'), 'duplicate' => Urvanov_Syntax_Highlighter_Global::urvanov__('Duplicate'), 'submit' => Urvanov_Syntax_Highlighter_Global::urvanov__('Submit'),
                'delete' => Urvanov_Syntax_Highlighter_Global::urvanov__('Delete'));
            foreach ($buttons as $k => $v) {
                echo '<a id="urvanov-syntax-highlighter-theme-editor-', $k, '-button" class="button-secondary urvanov-syntax-highlighter-admin-button" loading="', Urvanov_Syntax_Highlighter_Global::urvanov__('Loading...'), '" loaded="', $v, '" >', $v, '</a>';
            }
            echo '<span class="urvanov-syntax-highlighter-span-5"></span>', self::help_button('http://aramk.com/blog/2012/12/27/urvanov-syntax-highlighter-theme-editor/'), '<span class="urvanov-syntax-highlighter-span-5"></span>', Urvanov_Syntax_Highlighter_Global::urvanov__("Duplicate a Stock Theme into a User Theme to allow editing.");
            echo '</br></div>';
        }
        // Preview Box
        ?>
        <div id="urvanov-syntax-highlighter-theme-panel">
            <div id="urvanov-syntax-highlighter-theme-info"></div>
            <div id="urvanov-syntax-highlighter-live-preview-wrapper">
                <div id="urvanov-syntax-highlighter-live-preview-inner">
                    <div id="urvanov-syntax-highlighter-live-preview"></div>
                    <div id="urvanov-syntax-highlighter-preview-info">
                        <?php printf(Urvanov_Syntax_Highlighter_Global::urvanov__('Change the %1$sfallback language%2$s to change the sample code or %3$schange it manually%4$s. Lines 5-7 are marked.'), '<a href="#langs">', '</a>', '<a id="urvanov-syntax-highlighter-change-code" href="#">', '</a>'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        // Preview checkbox
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::PREVIEW, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable Live Preview')), FALSE, FALSE);
        echo '</select><span class="urvanov-syntax-highlighter-span-10"></span>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::ENQUEUE_THEMES, Urvanov_Syntax_Highlighter_Global::urvanov__('Enqueue themes in the header (more efficient).') . self::help_button('http://aramk.com/blog/2012/01/07/enqueuing-themes-and-fonts-in-crayon/')));
        // Check if theme from db is loaded
        if ($missing_theme) {
            echo '<span class="urvanov-syntax-highlighter-error">', sprintf(Urvanov_Syntax_Highlighter_Global::urvanov__('The selected theme with id %s could not be loaded'), '<strong>' . $db_theme . '</strong>'), '. </span>';
        }
    }

    public static function font($editor = FALSE) {
        $db_font = self::$options[Urvanov_Syntax_Highlighter_Settings::FONT]; // Theme name from db
        if (!array_key_exists(Urvanov_Syntax_Highlighter_Settings::FONT, self::$options)) {
            $db_font = '';
        }
        $fonts_array = Urvanov_Syntax_Highlighter_Resources::fonts()->get_array();
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::FONT, FALSE, TRUE, TRUE, $fonts_array);
        echo '<span class="urvanov-syntax-highlighter-span-5"></span>';
        // TODO(aramk) Add this blog article back.
        // echo '<a href="http://bit.ly/Yr2Xv6" target="_blank">', Urvanov_Syntax_Highlighter_Global::urvanov__('Add More'), '</a>';
        echo '<span class="urvanov-syntax-highlighter-span-10"></span>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::FONT_SIZE_ENABLE, Urvanov_Syntax_Highlighter_Global::urvanov__('Custom Font Size') . ' '), FALSE);
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::FONT_SIZE, 'size' => 2));
        echo '<span class="urvanov-syntax-highlighter-span-margin">', Urvanov_Syntax_Highlighter_Global::urvanov__('Pixels'), ',&nbsp;&nbsp;', Urvanov_Syntax_Highlighter_Global::urvanov__('Line Height'), ' </span>';
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::LINE_HEIGHT, 'size' => 2));
        echo '<span class="urvanov-syntax-highlighter-span-margin">', Urvanov_Syntax_Highlighter_Global::urvanov__('Pixels'), '</span></br>';
        if ((!Urvanov_Syntax_Highlighter_Resources::fonts()->is_loaded($db_font) || !Urvanov_Syntax_Highlighter_Resources::fonts()->exists($db_font))) {
            // Default font doesn't actually exist as a file, it means do not override default theme font
            echo '<span class="urvanov-syntax-highlighter-error">', sprintf(Urvanov_Syntax_Highlighter_Global::urvanov__('The selected font with id %s could not be loaded'), '<strong>' . $db_font . '</strong>'), '. </span><br/>';
        }
        if ($editor) {
            return;
        }
        echo '<div style="height:10px;"></div>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::ENQUEUE_FONTS, Urvanov_Syntax_Highlighter_Global::urvanov__('Enqueue fonts in the header (more efficient).') . self::help_button('http://aramk.com/blog/2012/01/07/enqueuing-themes-and-fonts-in-crayon/')));
    }

    public static function code($editor = FALSE) {
        echo '<div id="urvanov-syntax-highlighter-section-code-interaction" class="urvanov-syntax-highlighter-hide-inline-only">';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::PLAIN, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable plain code view and display') . ' '), FALSE);
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::SHOW_PLAIN);
        echo '<span id="urvanov-syntax-highlighter-subsection-copy-check">';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::PLAIN_TOGGLE, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable plain code toggling')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::SHOW_PLAIN_DEFAULT, Urvanov_Syntax_Highlighter_Global::urvanov__('Show the plain code by default')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::COPY, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable code copy/paste')));
        echo '</span>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::POPUP, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable opening code in a window')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::SCROLL, Urvanov_Syntax_Highlighter_Global::urvanov__('Always display scrollbars')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::MINIMIZE, Urvanov_Syntax_Highlighter_Global::urvanov__('Minimize code') . self::help_button('http://aramk.com/blog/2013/01/15/minimizing-code-in-crayon/')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::EXPAND, Urvanov_Syntax_Highlighter_Global::urvanov__('Expand code beyond page borders on mouseover')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::EXPAND_TOGGLE, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable code expanding toggling when possible')));
        echo '</div>';
        if (!$editor) {
            self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::DECODE, Urvanov_Syntax_Highlighter_Global::urvanov__('Decode HTML entities in code')));
        }
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::DECODE_ATTRIBUTES, Urvanov_Syntax_Highlighter_Global::urvanov__('Decode HTML entities in attributes')));
        echo '<div class="urvanov-syntax-highlighter-hide-inline-only">';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::TRIM_WHITESPACE, Urvanov_Syntax_Highlighter_Global::urvanov__('Remove whitespace surrounding the shortcode content')));
        echo '</div>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::TRIM_CODE_TAG, Urvanov_Syntax_Highlighter_Global::urvanov__('Remove &lt;code&gt; tags surrounding the shortcode content')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::ALTERNATE, Urvanov_Syntax_Highlighter_Global::urvanov__('Allow Mixed Language Highlighting with delimiters and tags.') . self::help_button('http://aramk.com/blog/2011/12/25/mixed-language-highlighting-in-crayon/')));
        echo '<div class="urvanov-syntax-highlighter-hide-inline-only">';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::SHOW_ALTERNATE, Urvanov_Syntax_Highlighter_Global::urvanov__('Show Mixed Language Icon (+)')));
        echo '</div>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::TAB_CONVERT, Urvanov_Syntax_Highlighter_Global::urvanov__('Convert tabs to spaces')));
        self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('Tab size in spaces') . ': ');
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::TAB_SIZE, 'size' => 2, 'break' => TRUE));
        self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('Blank lines before code:') . ' ');
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::WHITESPACE_BEFORE, 'size' => 2, 'break' => TRUE));
        self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('Blank lines after code:') . ' ');
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::WHITESPACE_AFTER, 'size' => 2, 'break' => TRUE));
    }

    public static function tags() {
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::INLINE_TAG, Urvanov_Syntax_Highlighter_Global::urvanov__('Capture Inline Tags') . self::help_button('http://aramk.com/blog/2012/03/07/inline-crayons/')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::INLINE_WRAP, Urvanov_Syntax_Highlighter_Global::urvanov__('Wrap Inline Tags') . self::help_button('http://aramk.com/blog/2012/03/07/inline-crayons/')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::CODE_TAG_CAPTURE, Urvanov_Syntax_Highlighter_Global::urvanov__('Capture &lt;code&gt; as')), FALSE);
        echo ' ';
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::CODE_TAG_CAPTURE_TYPE, FALSE);
        echo self::help_button('http://aramk.com/blog/2012/03/07/inline-crayons/') . '<br/>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::BACKQUOTE, Urvanov_Syntax_Highlighter_Global::urvanov__('Capture `backquotes` as &lt;code&gt;') . self::help_button('http://aramk.com/blog/2012/03/07/inline-crayons/')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::CAPTURE_PRE, Urvanov_Syntax_Highlighter_Global::urvanov__('Capture &lt;pre&gt; tags as Crayons') . self::help_button('http://aramk.com/blog/2011/12/27/mini-tags-in-crayon/')));

        echo '<div class="note" style="width: 350px;">', sprintf(Urvanov_Syntax_Highlighter_Global::urvanov__("Using this markup for Mini Tags and Inline tags is now %sdeprecated%s! Use the %sTag Editor%s instead and convert legacy tags."), '<a href="http://aramk.com/blog/2011/12/27/mini-tags-in-crayon/" target="_blank">', '</a>', '<a href="http://aramk.com/blog/2012/03/25/urvanov-syntax-highlighter-tag-editor/" target="_blank">', '</a>'), '</div>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG, Urvanov_Syntax_Highlighter_Global::urvanov__('Capture Mini Tags like [php][/php] as Crayons.') . self::help_button('http://aramk.com/blog/2011/12/27/mini-tags-in-crayon/')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::INLINE_TAG_CAPTURE, Urvanov_Syntax_Highlighter_Global::urvanov__('Capture Inline Tags like {php}{/php} inside sentences.') . self::help_button('http://aramk.com/blog/2012/03/07/inline-crayons/')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::PLAIN_TAG, Urvanov_Syntax_Highlighter_Global::urvanov__('Enable [plain][/plain] tag.') . self::help_button('http://aramk.com/blog/2011/12/27/mini-tags-in-crayon/')));
    }

    public static function files() {
        echo '<a name="files"></a>';
        echo Urvanov_Syntax_Highlighter_Global::urvanov__('When loading local files and a relative path is given for the URL, use the absolute path'), ': ',
        '<div style="margin-left: 20px">', home_url(), '/';
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::LOCAL_PATH));
        echo '</div>', Urvanov_Syntax_Highlighter_Global::urvanov__('Followed by your relative URL.');
    }

    public static function tag_editor() {
        $can_convert = self::load_legacy_posts();
        if ($can_convert) {
            $disabled = '';
            $convert_text = Urvanov_Syntax_Highlighter_Global::urvanov__('Convert Legacy Tags');
        } else {
            $disabled = 'disabled="disabled"';
            $convert_text = Urvanov_Syntax_Highlighter_Global::urvanov__('No Legacy Tags Found');
        }

        echo '<input type="submit" name="', self::OPTIONS, '[convert]" id="convert" class="button-primary" value="', $convert_text, '"', $disabled, ' />&nbsp; ';
        self::checkbox(array('convert_encode', Urvanov_Syntax_Highlighter_Global::urvanov__("Encode")), FALSE);
        echo self::help_button('http://aramk.com/blog/2012/09/26/converting-legacy-tags-to-pre/'), URVANOV_SYNTAX_HIGHLIGHTER_BR, URVANOV_SYNTAX_HIGHLIGHTER_BR;
        $sep = sprintf(Urvanov_Syntax_Highlighter_Global::urvanov__('Use %s to separate setting names from values in the &lt;pre&gt; class attribute'),
            self::dropdown(Urvanov_Syntax_Highlighter_Settings::ATTR_SEP, FALSE, FALSE, FALSE));
        echo '<span>', $sep, self::help_button('http://aramk.com/blog/2012/03/25/urvanov-syntax-highlighter-tag-editor/'), '</span><br/>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_FRONT, Urvanov_Syntax_Highlighter_Global::urvanov__("Display the Tag Editor in any TinyMCE instances on the frontend (e.g. bbPress)") . self::help_button('http://aramk.com/blog/2012/09/08/urvanov-syntax-highlighter-with-bbpress/')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_SETTINGS, Urvanov_Syntax_Highlighter_Global::urvanov__("Display Tag Editor settings on the frontend")));
        self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('Add Code button text') . ' ');
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_ADD_BUTTON_TEXT, 'break' => TRUE));
        self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('Edit Code button text') . ' ');
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_EDIT_BUTTON_TEXT, 'break' => TRUE));
        self::span(Urvanov_Syntax_Highlighter_Global::urvanov__('Quicktag button text') . ' ');
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_QUICKTAG_BUTTON_TEXT, 'break' => TRUE));
    }

    public static function misc() {
        echo Urvanov_Syntax_Highlighter_Global::urvanov__('Clear the cache used to store remote code requests'), ': ';
        self::dropdown(Urvanov_Syntax_Highlighter_Settings::CACHE, false);
        echo '<input type="submit" id="', self::CACHE_CLEAR, '" name="', self::CACHE_CLEAR, '" class="button-secondary" value="', Urvanov_Syntax_Highlighter_Global::urvanov__('Clear Now'), '" /><br/>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::EFFICIENT_ENQUEUE, Urvanov_Syntax_Highlighter_Global::urvanov__('Attempt to load Crayon\'s CSS and JavaScript only when needed') . self::help_button('http://aramk.com/blog/2012/01/23/failing-to-load-crayons-on-pages/')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::SAFE_ENQUEUE, Urvanov_Syntax_Highlighter_Global::urvanov__('Disable enqueuing for page templates that may contain The Loop.') . self::help_button('http://aramk.com/blog/2012/01/23/failing-to-load-crayons-on-pages/')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::COMMENTS, Urvanov_Syntax_Highlighter_Global::urvanov__('Allow Crayons inside comments')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::EXCERPT_STRIP, Urvanov_Syntax_Highlighter_Global::urvanov__('Remove Crayons from excerpts')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::MAIN_QUERY, Urvanov_Syntax_Highlighter_Global::urvanov__('Load Crayons only from the main Wordpress query')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::TOUCHSCREEN, Urvanov_Syntax_Highlighter_Global::urvanov__('Disable mouse gestures for touchscreen devices (eg. MouseOver)')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::DISABLE_ANIM, Urvanov_Syntax_Highlighter_Global::urvanov__('Disable animations')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::DISABLE_RUNTIME, Urvanov_Syntax_Highlighter_Global::urvanov__('Disable runtime stats')));
        echo '<span class="urvanov-syntax-highlighter-span-100">' . Urvanov_Syntax_Highlighter_Global::urvanov__('Disable for posts before') . ':</span> ';
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::DISABLE_DATE, 'type' => 'date', 'size' => 8, 'break' => FALSE));
        echo '<br/>';
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::DELAY_LOAD_JS, Urvanov_Syntax_Highlighter_Global::urvanov__('Load scripts in the page footer using wp_footer() to improve loading performance.')));
    }

    // Debug Fields ===========================================================

    public static function errors() {
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::ERROR_LOG, Urvanov_Syntax_Highlighter_Global::urvanov__('Log errors for individual Crayons')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::ERROR_LOG_SYS, Urvanov_Syntax_Highlighter_Global::urvanov__('Log system-wide errors')));
        self::checkbox(array(Urvanov_Syntax_Highlighter_Settings::ERROR_MSG_SHOW, Urvanov_Syntax_Highlighter_Global::urvanov__('Display custom message for errors')));
        self::input(array('id' => Urvanov_Syntax_Highlighter_Settings::ERROR_MSG, 'size' => 60, 'margin' => TRUE));
    }

    public static function log() {
        $log = UrvanovSyntaxHighlighterLog::log();
        touch(URVANOV_SYNTAX_HIGHLIGHTER_LOG_FILE);
        $exists = file_exists(URVANOV_SYNTAX_HIGHLIGHTER_LOG_FILE);
        $writable = is_writable(URVANOV_SYNTAX_HIGHLIGHTER_LOG_FILE);
        if (!empty($log)) {
            echo '<div id="urvanov-syntax-highlighter-log-wrapper">', '<div id="urvanov-syntax-highlighter-log"><div id="urvanov-syntax-highlighter-log-text">', $log,
            '</div></div>', '<div id="urvanov-syntax-highlighter-log-controls">',
            '<input type="button" id="urvanov-syntax-highlighter-log-toggle" show_txt="', Urvanov_Syntax_Highlighter_Global::urvanov__('Show Log'), '" hide_txt="', Urvanov_Syntax_Highlighter_Global::urvanov__('Hide Log'), '" class="button-secondary" value="', Urvanov_Syntax_Highlighter_Global::urvanov__('Show Log'), '"> ',
            '<input type="submit" id="urvanov-syntax-highlighter-log-clear" name="', self::LOG_CLEAR,
            '" class="button-secondary" value="', Urvanov_Syntax_Highlighter_Global::urvanov__('Clear Log'), '"> ', '<input type="submit" id="urvanov-syntax-highlighter-log-email" name="',
                self::LOG_EMAIL_ADMIN . '" class="button-secondary" value="', Urvanov_Syntax_Highlighter_Global::urvanov__('Email Admin'), '"> ',
            '<input type="submit" id="urvanov-syntax-highlighter-log-email" name="', self::LOG_EMAIL_DEV,
            '" class="button-secondary" value="', Urvanov_Syntax_Highlighter_Global::urvanov__('Email Developer'), '"> ', '</div>', '</div>';
        }
        echo '<span', (!empty($log)) ? ' class="urvanov-syntax-highlighter-span"' : '', '>', (empty($log)) ? Urvanov_Syntax_Highlighter_Global::urvanov__('The log is currently empty.') . ' ' : '';
        if ($exists) {
            $writable ? Urvanov_Syntax_Highlighter_Global::urvanov_e('The log file exists and is writable.') : Urvanov_Syntax_Highlighter_Global::urvanov_e('The log file exists and is not writable.');
        } else {
            Urvanov_Syntax_Highlighter_Global::urvanov_e('The log file does not exist and is not writable.');
        }
        echo '</span>';
    }

    // About Fields ===========================================================

    public static function info() {
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION, $URVANOV_SYNTAX_HIGHLIGHTER_DATE, $URVANOV_SYNTAX_HIGHLIGHTER_AUTHOR, $URVANOV_SYNTAX_HIGHLIGHTER_WEBSITE, $URVANOV_SYNTAX_HIGHLIGHTER_TWITTER, $URVANOV_SYNTAX_HIGHLIGHTER_GIT, $URVANOV_SYNTAX_HIGHLIGHTER_PLUGIN_WP, $URVANOV_SYNTAX_HIGHLIGHTER_AUTHOR_SITE, $URVANOV_SYNTAX_HIGHLIGHTER_EMAIL, $URVANOV_SYNTAX_HIGHLIGHTER_DONATE;
        echo '<a name="info"></a>';
        $version = '<strong>' . Urvanov_Syntax_Highlighter_Global::urvanov__('Version') . ':</strong> ' . $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
        $date = $URVANOV_SYNTAX_HIGHLIGHTER_DATE;
        $developer = '<strong>' . Urvanov_Syntax_Highlighter_Global::urvanov__('Developer') . ':</strong> ' . '<a href="' . $URVANOV_SYNTAX_HIGHLIGHTER_AUTHOR_SITE . '" target="_blank">' . $URVANOV_SYNTAX_HIGHLIGHTER_AUTHOR . '</a>';
        $translators = '<strong>' . Urvanov_Syntax_Highlighter_Global::urvanov__('Translators') . ':</strong> ' .
            '
            Arabic (Djennad Hamza),
            Chinese Simplified (<a href="http://smerpup.com/" target="_blank">Dezhi Liu</a>, Jash Yin),
            Chinese Traditional (<a href="http://www.arefly.com/" target="_blank">Arefly</a>),
            Dutch (<a href="https://twitter.com/RobinRoelofsen" target="_blank">Robin Roelofsen</a>, <a href="https://twitter.com/#!/chilionsnoek" target="_blank">Chilion Snoek</a>),
            French (<a href="https://vhf.github.io" target="_blank">Victor Felder</a>),
            Finnish (<a href="https://github.com/vahalan" target="_blank">vahalan</a>),
            German (<a href="http://www.technologyblog.de/" target="_blank">Stephan Knau&#223;</a>),
            Italian (<a href="http://www.federicobellucci.net/" target="_blank">Federico Bellucci</a>),
            Japanese (<a href="https://twitter.com/#!/west_323" target="_blank">@west_323</a>),
            Korean (<a href="https://github.com/dokenzy" target="_blank">dokenzy</a>),
            Lithuanian (Vincent G),
            Norwegian (<a href="http://www.jackalworks.com/blogg" target="_blank">Jackalworks</a>),
            Persian (MahdiY),
            Polish (<a href="https://github.com/toszcze" target="_blank">Bartosz Romanowski</a>, <a href="http://rob006.net/" target="_blank">Robert Korulczyk</a>),
            Portuguese (<a href="http://www.adonai.eti.br" target="_blank">Adonai S. Canez</a>),
            Russian (<a href="http://simplelib.com/" target="_blank">Minimus</a>, Di_Skyer),
            Slovak (<a href="https://twitter.com/#!/webhostgeeks" target="_blank">webhostgeeks</a>),
            Slovenian (<a href="http://jodlajodla.si/" target="_blank">Jan Su&#353;nik</a>),
            Spanish (<a href="http://www.hbravo.com/" target="_blank">Hermann Bravo</a>),
            Tamil (KKS21199),
            Turkish (<a href="http://hakanertr.wordpress.com" target="_blank">Hakan</a>),
            Ukrainian (<a href="http://getvoip.com/blog" target="_blank">Michael Yunat</a>)';

        $links = '
	 			<a id="docs-icon" class="small-icon" title="Documentation" href="' . $URVANOV_SYNTAX_HIGHLIGHTER_WEBSITE . '" target="_blank"></a>
				<a id="git-icon" class="small-icon" title="GitHub" href="' . $URVANOV_SYNTAX_HIGHLIGHTER_GIT . '" target="_blank"></a>
				<a id="wp-icon" class="small-icon" title="Plugin Page" href="' . $URVANOV_SYNTAX_HIGHLIGHTER_PLUGIN_WP . '" target="_blank"></a>
	 			<a id="twitter-icon" class="small-icon" title="Twitter" href="' . $URVANOV_SYNTAX_HIGHLIGHTER_TWITTER . '" target="_blank"></a>
				<a id="gmail-icon" class="small-icon" title="Email" href="mailto:' . $URVANOV_SYNTAX_HIGHLIGHTER_EMAIL . '" target="_blank"></a>
				<div id="urvanov-syntax-highlighter-donate"><a href="' . $URVANOV_SYNTAX_HIGHLIGHTER_DONATE . '" title="Donate" target="_blank">
					<img src="' . plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_DONATE_BUTTON, __FILE__) . '"></a>
				</div>';

        echo '
				<table id="urvanov-syntax-highlighter-info" border="0">
		  <tr>
				<td>' . $version . ' - ' . $date . '</td>
					</tr>
					<tr>
					<td>' . $developer . '</td>
		  </tr>
		  <tr>
				<td>' . $translators . '</td>
		  </tr>
		  <tr>
				<td colspan="2">' . $links . '</td>
		  </tr>
				</table>';

    }

    public static function help_button($link) {
        return ' <a href="' . $link . '" target="_blank" class="urvanov-syntax-highlighter-question">' . Urvanov_Syntax_Highlighter_Global::urvanov__('?') . '</a>';
    }

    public static function plugin_row_meta($meta, $file) {
        global $URVANOV_SYNTAX_HIGHLIGHTER_DONATE;
        if ($file == Urvanov_Syntax_Highlighter_Plugin::basename()) {
            $meta[] = '<a href="options-general.php?page=urvanov_syntax_highlighter_settings">' . Urvanov_Syntax_Highlighter_Global::urvanov__('Settings') . '</a>';
            $meta[] = '<a href="options-general.php?page=urvanov_syntax_highlighter_settings&theme-editor=1">' . Urvanov_Syntax_Highlighter_Global::urvanov__('Theme Editor') . '</a>';
            $meta[] = '<a href="' . $URVANOV_SYNTAX_HIGHLIGHTER_DONATE . '" target="_blank">' . Urvanov_Syntax_Highlighter_Global::urvanov__('Donate') . '</a>';
        }
        return $meta;
    }
}

// Add the settings menus

if (defined('ABSPATH') && is_admin()) {
    // For the admin section
    add_action('admin_menu', 'Urvanov_Syntax_Highlighter_Settings_WP::admin_load');
    add_filter('plugin_row_meta', 'Urvanov_Syntax_Highlighter_Settings_WP::plugin_row_meta', 10, 2);
}

?>
