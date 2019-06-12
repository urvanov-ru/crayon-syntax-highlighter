<?php

require_once(URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'crayon_settings_wp.class.php');

class UrvanovSyntaxHighlighterTagEditorWP {

    public static $settings = null;

    public static function init() {
        // Hooks
        if (URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR) {
            Urvanov_Syntax_Highlighter_Settings_WP::load_settings(TRUE);
            if (is_admin()) {
                // XXX Only runs in wp-admin
                add_action('admin_print_scripts-post-new.php', 'UrvanovSyntaxHighlighterTagEditorWP::enqueue_resources');
                add_action('admin_print_scripts-post.php', 'UrvanovSyntaxHighlighterTagEditorWP::enqueue_resources');
                add_filter('tiny_mce_before_init', 'UrvanovSyntaxHighlighterTagEditorWP::init_tinymce');
                // Must come after
                add_action("admin_print_scripts-post-new.php", 'Urvanov_Syntax_Highlighter_Settings_WP::init_js_settings');
                add_action("admin_print_scripts-post.php", 'Urvanov_Syntax_Highlighter_Settings_WP::init_js_settings');
                self::addbuttons();
            } else if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_FRONT)) {
                // XXX This will always need to enqueue, but only runs on front end
                add_action('wp', 'UrvanovSyntaxHighlighterTagEditorWP::enqueue_resources');
                add_filter('tiny_mce_before_init', 'UrvanovSyntaxHighlighterTagEditorWP::init_tinymce');
                self::addbuttons();
            }
        }
    }

    public static function init_settings() {

        if (!self::$settings) {
            // Add settings
            self::$settings = array(
                'home_url' => home_url(),
                'css' => 'crayon-te',
                'css_selected' => 'crayon-selected',
                'code_css' => '#crayon-code',
                'url_css' => '#crayon-url',
                'url_info_css' => '#crayon-te-url-info',
                'lang_css' => '#crayon-lang',
                'title_css' => '#crayon-title',
                'mark_css' => '#crayon-mark',
                'range_css' => '#crayon-range',
                'inline_css' => 'crayon-inline',
                'inline_hide_css' => 'crayon-hide-inline',
                'inline_hide_only_css' => 'crayon-hide-inline-only',
                'hl_css' => '#crayon-highlight',
                'switch_html' => '#content-html',
                'switch_tmce' => '#content-tmce',
                'tinymce_button_generic' => '.mce-btn',
                'tinymce_button' => 'a.mce_crayon_tinymce,.mce-i-crayon_tinymce',
                'tinymce_button_unique' => 'mce_crayon_tinymce',
                'tinymce_highlight' => 'mce-active',
                'submit_css' => '#crayon-te-ok',
                'cancel_css' => '#crayon-te-cancel',
                'content_css' => '#crayon-te-content',
                'dialog_title_css' => '#crayon-te-title',
                'submit_wrapper_css' => '#crayon-te-submit-wrapper',
                'data_value' => 'data-value',
                'attr_sep' => Urvanov_Syntax_Highlighter_Global_Settings::val_str(Urvanov_Syntax_Highlighter_Settings::ATTR_SEP),
                'css_sep' => '_',
                'fallback_lang' => Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::FALLBACK_LANG),
                'add_text' => Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_ADD_BUTTON_TEXT),
                'edit_text' => Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_EDIT_BUTTON_TEXT),
                'quicktag_text' => Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_QUICKTAG_BUTTON_TEXT),
                'submit_add' => Urvanov_Syntax_Highlighter_Global::urvanov__('Add'),
                'submit_edit' => Urvanov_Syntax_Highlighter_Global::urvanov__('Save'),
                'bar' => '#crayon-te-bar',
                'bar_content' => '#crayon-te-bar-content',
                'extensions' => Urvanov_Syntax_Highlighter_Resources::langs()->extensions_inverted()
            );
        }
    }

    public static function enqueue_resources() {
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
        self::init_settings();

        if (URVANOV_SYNTAX_HIGHLIGHTER_MINIFY) {
            wp_deregister_script('crayon_js');
            wp_enqueue_script('crayon_js', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_JS_TE_MIN, dirname(dirname(__FILE__))), array('jquery', 'quicktags', 'wp-rich-text' , 'wp-element', 'wp-editor', 'wp-blocks', 'wp-components', 'wp-html-entities'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
            Urvanov_Syntax_Highlighter_Settings_WP::init_js_settings();
            wp_localize_script('crayon_js', 'CrayonTagEditorSettings', self::$settings);
        } else {
            wp_enqueue_script('crayon_colorbox_js', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_COLORBOX_JS, __FILE__), array('jquery'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
            wp_enqueue_style('crayon_colorbox_css', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_COLORBOX_CSS, __FILE__), array(), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
            wp_enqueue_script('crayon_te_js', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR_JS, __FILE__), array('crayon_util_js', 'crayon_colorbox_js', 'wpdialogs', 'wp-rich-text' , 'wp-element', 'wp-editor', 'wp-blocks', 'wp-components', 'wp-html-entities'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
            wp_enqueue_script('crayon_qt_js', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_QUICKTAGS_JS, __FILE__), array('quicktags', 'crayon_te_js'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION, TRUE);
            wp_localize_script('crayon_te_js', 'CrayonTagEditorSettings', self::$settings);
            Urvanov_Syntax_Highlighter_Settings_WP::other_scripts();
        }
    }

    public static function init_tinymce($init) {
        if (!array_key_exists('extended_valid_elements', $init)) {
            $init['extended_valid_elements'] = '';
        }
        $init['extended_valid_elements'] .= ',pre[*],code[*],iframe[*]';
        return $init;
    }

    public static function addbuttons() {
        // Add only in Rich Editor mode
        add_filter('mce_external_plugins', 'UrvanovSyntaxHighlighterTagEditorWP::add_plugin');
        add_filter('mce_buttons', 'UrvanovSyntaxHighlighterTagEditorWP::register_buttons');
        add_filter('bbp_before_get_the_content_parse_args', 'UrvanovSyntaxHighlighterTagEditorWP::bbp_get_the_content_args');
    }

    public static function bbp_get_the_content_args($args) {
        // Turn off "teeny" to allow the bbPress TinyMCE to display external plugins
        return array_merge($args, array('teeny' => false));
    }

    public static function register_buttons($buttons) {
        array_push($buttons, 'separator', 'crayon_tinymce');
        return $buttons;
    }

    public static function add_plugin($plugin_array) {
        $plugin_array['crayon_tinymce'] = plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_TINYMCE_JS, __FILE__);
        return $plugin_array;
    }

    // The remaining functions are for displayed output.

    public static function select_resource($id, $resources, $current, $set_class = TRUE) {
        $id = Urvanov_Syntax_Highlighter_Settings::PREFIX . $id;
        if (count($resources) > 0) {
            $class = $set_class ? 'class="' . Urvanov_Syntax_Highlighter_Settings::SETTING . ' ' . Urvanov_Syntax_Highlighter_Settings::SETTING_SPECIAL . '"' : '';
            echo '<select id="' . $id . '" name="' . $id . '" ' . $class . ' ' . Urvanov_Syntax_Highlighter_Settings::SETTING_ORIG_VALUE . '="' . $current . '">';
            foreach ($resources as $resource) {
                $asterisk = $current == $resource->id() ? ' *' : '';
                echo '<option value="' . $resource->id() . '" ' . selected($current, $resource->id()) . ' >' . $resource->name() . $asterisk . '</option>';
            }
            echo '</select>';
        } else {
            // None found, default to text box
            echo '<input type="text" id="' . $id . '" name="' . $id . '" class="' . Urvanov_Syntax_Highlighter_Settings::SETTING . ' ' . Urvanov_Syntax_Highlighter_Settings::SETTING_SPECIAL . '" />';
        }
    }

    public static function checkbox($id) {
        $id = Urvanov_Syntax_Highlighter_Settings::PREFIX . $id;
        echo '<input type="checkbox" id="' . $id . '" name="' . $id . '" class="' . Urvanov_Syntax_Highlighter_Settings::SETTING . ' ' . Urvanov_Syntax_Highlighter_Settings::SETTING_SPECIAL . '" />';
    }

    public static function textbox($id, $atts = array(), $set_class = TRUE) {
        $id = Urvanov_Syntax_Highlighter_Settings::PREFIX . $id;
        $atts_str = '';
        $class = $set_class ? 'class="' . Urvanov_Syntax_Highlighter_Settings::SETTING . ' ' . Urvanov_Syntax_Highlighter_Settings::SETTING_SPECIAL . '"' : '';
        foreach ($atts as $k => $v) {
            $atts_str = $k . '="' . $v . '" ';
        }
        echo '<input type="text" id="' . $id . '" name="' . $id . '" ' . $class . ' ' . $atts_str . ' />';
    }

    public static function submit() {
        ?>
        <input type="button"
               class="button-primary <?php echo UrvanovSyntaxHighlighterTagEditorWP::$settings['submit_css']; ?>"
               value="<?php echo UrvanovSyntaxHighlighterTagEditorWP::$settings['submit_add']; ?>"
               name="submit"/>
    <?php
    }

    public static function content() {
        Urvanov_Syntax_Highlighter_Settings_WP::load_settings();
        $langs = Urvanov_Syntax_Highlighter_Langs::sort_by_name(Urvanov_Syntax_Highlighter_Parser::parse_all());
        $curr_lang = Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::FALLBACK_LANG);
        $themes = Urvanov_Syntax_Highlighter_Resources::themes()->get();
        $curr_theme = Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::THEME);
        $fonts = Urvanov_Syntax_Highlighter_Resources::fonts()->get();
        $curr_font = Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::FONT);
        UrvanovSyntaxHighlighterTagEditorWP::init_settings();

        ?>

        <div id="crayon-te-content" class="crayon-te">
            <div id="crayon-te-bar">
                <div id="crayon-te-bar-content">
                    <div id="crayon-te-title">Title</div>
                    <div id="crayon-te-controls">
                        <a id="crayon-te-ok" href="#"><?php Urvanov_Syntax_Highlighter_Global::urvanov_e('OK'); ?></a> <span
                            class="crayon-te-seperator">|</span> <a id="crayon-te-cancel"
                                                                    href="#"><?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Cancel'); ?></a>
                    </div>
                </div>
            </div>

            <table id="crayon-te-table" class="describe">
                <tr class="crayon-tr-center">
                    <th><?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Title'); ?>
                    </th>
                    <td class="crayon-nowrap"><?php self::textbox('title', array('placeholder' => Urvanov_Syntax_Highlighter_Global::urvanov__('A short description'))); ?>
                        <span id="crayon-te-sub-section"> <?php self::checkbox('inline'); ?>
                            <span class="crayon-te-section"><?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Inline'); ?> </span>
			</span> <span id="crayon-te-sub-section"> <?php self::checkbox('highlight'); ?>
                            <span class="crayon-te-section"><?php Urvanov_Syntax_Highlighter_Global::urvanov_e("Don't Highlight"); ?>
				</span>
			</span></td>
                </tr>
                <tr class="crayon-tr-center">
                    <th><?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Language'); ?>
                    </th>
                    <td class="crayon-nowrap"><?php self::select_resource('lang', $langs, $curr_lang); ?>
                        <span class="crayon-te-section"><?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Line Range'); ?> </span>
                        <?php self::textbox('range', array('placeholder' => Urvanov_Syntax_Highlighter_Global::urvanov__('(e.g. 3-5 or 3)'))); ?>
                        <span class="crayon-te-section"><?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Marked Lines'); ?> </span>
                        <?php self::textbox('mark', array('placeholder' => Urvanov_Syntax_Highlighter_Global::urvanov__('(e.g. 1,2,3-5)'))); ?>
                    </td>
                </tr>
                <tr class="crayon-tr-center" style="text-align: center;">
                    <th>
                        <div>
                            <?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Code'); ?>
                        </div>
                        <input type="button" id="crayon-te-clear"
                               class="secondary-primary" value="<?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Clear'); ?>"
                               name="clear"/>
                    </th>
                    <td><textarea id="crayon-code" name="code"
                                  placeholder="<?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Paste your code here, or type it in manually.'); ?>"></textarea>
                    </td>
                </tr>
                <tr class="crayon-tr-center">
                    <th id="crayon-url-th"><?php Urvanov_Syntax_Highlighter_Global::urvanov_e('URL'); ?>
                    </th>
                    <td><?php self::textbox('url', array('placeholder' => Urvanov_Syntax_Highlighter_Global::urvanov__('Relative local path or absolute URL'))); ?>
                        <div id="crayon-te-url-info" class="crayon-te-info">
                            <?php
                            Urvanov_Syntax_Highlighter_Global::urvanov_e("If the URL fails to load, the code above will be shown instead. If no code exists, an error is shown.");
                            echo ' ';
                            printf(Urvanov_Syntax_Highlighter_Global::urvanov__('If a relative local path is given it will be appended to %s - which is defined in %sCrayon &gt; Settings &gt; Files%s.'), '<span class="crayon-te-quote">' . get_home_url() . '/' . Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::LOCAL_PATH) . '</span>', '<a href="options-general.php?page=crayon_settings" target="_blank">', '</a>');
                            ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td id="crayon-te-submit-wrapper" colspan="2"
                        style="text-align: center;"><?php self::submit(); ?></td>
                </tr>
                <!--		<tr>-->
                <!--			<td colspan="2"><div id="crayon-te-warning" class="updated crayon-te-info"></div></td>-->
                <!--		</tr>-->
                <tr>
                    <td colspan="2"><?php
                        $admin = isset($_GET['is_admin']) ? intval($_GET['is_admin']) : is_admin();
                        if (!$admin && !Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_SETTINGS)) {
                            exit();
                        }
                        ?>
                        <hr/>
                        <div>
                            <h2 class="crayon-te-heading">
                                <?php Urvanov_Syntax_Highlighter_Global::urvanov_e('Settings'); ?>
                            </h2>
                        </div>
                        <div id="crayon-te-settings-info" class="crayon-te-info">
                            <?php
                            Urvanov_Syntax_Highlighter_Global::urvanov_e('Change the following settings to override their global values.');
                            echo ' <span class="', Urvanov_Syntax_Highlighter_Settings::SETTING_CHANGED, '">';
                            Urvanov_Syntax_Highlighter_Global::urvanov_e('Only changes (shown yellow) are applied.');
                            echo '</span><br/>';
                            echo sprintf(Urvanov_Syntax_Highlighter_Global::urvanov__('Future changes to the global settings under %sCrayon &gt; Settings%s won\'t affect overridden settings.'), '<a href="options-general.php?page=crayon_settings" target="_blank">', '</a>');
                            ?>
                        </div>
                    </td>
                </tr>
                <?php
                $sections = array('Theme', 'Font', 'Metrics', 'Toolbar', 'Lines', 'Code');
                foreach ($sections as $section) {
                    echo '<tr><th>', Urvanov_Syntax_Highlighter_Global::urvanov__($section), '</th><td>';
                    call_user_func('Urvanov_Syntax_Highlighter_Settings_WP::' . strtolower($section), TRUE);
                    echo '</td></tr>';
                }
                ?>
            </table>
        </div>

        <?php
        exit();
    }

}

if (defined('ABSPATH')) {
    add_action('init', 'UrvanovSyntaxHighlighterTagEditorWP::init');
}

?>
