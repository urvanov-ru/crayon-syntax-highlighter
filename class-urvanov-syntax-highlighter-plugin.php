<?php
/*
Plugin Name: Urvanov Syntax Highlighter
Plugin URI: https://github.com/urvanov-ru/crayon-syntax-highlighter
Description: Supports multiple languages, themes, highlighting from a URL, local file or post text.
Version: %urvanov-syntax-highlighter-version%
Author: Fedor Urvanov, Aram Kocharyan
Author URI: https://urvanov.ru
Text Domain: urvanov-syntax-highlighter
Domain Path: /trans/
License: GPL2
Copyright 2013	Aram Kocharyan	(email : akarmenia@gmail.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once('class-urvanov-syntax-highlighter-global.php');
require_once(URVANOV_SYNTAX_HIGHLIGHTER_HIGHLIGHTER_PHP);
if (URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR) {
    require_once(URVANOV_SYNTAX_HIGHLIGHTER_TAG_EDITOR_PHP);
}
if (URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR) {
    require_once(URVANOV_SYNTAX_HIGHLIGHTER_THEME_EDITOR_PHP);
}
require_once('class-urvanov-syntax-highlighter-wp.php');

Urvanov_Syntax_Highlighter_Global::set_info(array(
	'Version' => '%urvanov-syntax-highlighter-version%',
	'Date' => '%urvanov-syntax-highlighter-date%',
	'AuthorName' => 'Fedor Urvanov & Aram Kocharyan',
	'PluginURI' => 'https://github.com/urvanov-ru/crayon-syntax-highlighter',
));

/* The plugin class that manages all other classes and integrates Crayon with WP */
// Old name: CrayonWP
class Urvanov_Syntax_Highlighter_Plugin {
    // Properties and Constants ===============================================

    //	Associative array, keys are post IDs as strings and values are number of crayons parsed as ints
    private static $post_queue = array();
    // Ditto for comments
    private static $comment_queue = array();
    private static $post_captures = array();
    private static $comment_captures = array();
    // Whether we are displaying an excerpt
    private static $is_excerpt = FALSE;
    // Whether we have added styles and scripts
    private static $enqueued = FALSE;
    // Whether we have already printed the wp head
    private static $wp_head = FALSE;
    // Used to keep Crayon IDs
    private static $next_id = 0;
    // String to store the regex for capturing tags
    private static $alias_regex = '';
    private static $tags_regex = '';
    private static $tags_regex_legacy = '';
    private static $tag_regexes = array();
    // Defined constants used in bitwise flags
    private static $tag_types = array(
        Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG,
        Urvanov_Syntax_Highlighter_Settings::CAPTURE_PRE,
        Urvanov_Syntax_Highlighter_Settings::INLINE_TAG,
        Urvanov_Syntax_Highlighter_Settings::PLAIN_TAG,
        Urvanov_Syntax_Highlighter_Settings::BACKQUOTE);
    private static $tag_bits = array();
    // Used to find legacy tags
    private static $legacy_flags = NULL;

    // Used to detect the shortcode
    private static $allowed_atts = array('url' => NULL, 'lang' => NULL, 'title' => NULL, 'mark' => NULL, 'range' => NULL, 'inline' => NULL);
    const REGEX_CLOSED = '(?:\[\s*crayon(?:-(\w+))?\b([^\]]*)/\s*\])'; // [crayon atts="" /]
    const REGEX_TAG = '(?:\[\s*crayon(?:-(\w+))?\b([^\]]*)\](.*?)\[\s*/\s*crayon\s*\])'; // [crayon atts=""] ... [/crayon]
    const REGEX_INLINE_CLASS = '\bcrayon-inline\b';

    const REGEX_CLOSED_NO_CAPTURE = '(?:\[\s*crayon\b[^\]]*/\])';
    const REGEX_TAG_NO_CAPTURE = '(?:\[\s*crayon\b[^\]]*\].*?\[/crayon\])';

    const REGEX_QUICK_CAPTURE = '(?:\[\s*crayon[^\]]*\].*?\[\s*/\s*crayon\s*\])|(?:\[\s*crayon[^\]]*/\s*\])';

    const REGEX_BETWEEN_PARAGRAPH = '<p[^<]*>(?:[^<]*<(?!/?p(\s+[^>]*)?>)[^>]+(\s+[^>]*)?>)*[^<]*((?:\[\s*crayon[^\]]*\].*?\[\s*/\s*crayon\s*\])|(?:\[\s*crayon[^\]]*/\s*\]))(?:[^<]*<(?!/?p(\s+[^>]*)?>)[^>]+(\s+[^>]*)?>)*[^<]*</p[^<]*>';
    const REGEX_BETWEEN_PARAGRAPH_SIMPLE = '(<p(?:\s+[^>]*)?>)(.*?)(</p(?:\s+[^>]*)?>)';

    // For [crayon-id/]
    const REGEX_BR_BEFORE = '#<\s*br\s*/?\s*>\s*(\[\s*crayon-\w+\])#msi';
    const REGEX_BR_AFTER = '#(\[\s*crayon-\w+\])\s*<\s*br\s*/?\s*>#msi';

    const REGEX_ID = '#(?<!\$)\[\s*crayon#mi';
    //const REGEX_WITH_ID = '#(\[\s*crayon-\w+)\b([^\]]*["\'])(\s*/?\s*\])#mi';
    const REGEX_WITH_ID = '#\[\s*(crayon-\w+)\b[^\]]*\]#mi';

    const MODE_NORMAL = 0, MODE_JUST_CODE = 1, MODE_PLAIN_CODE = 2;

    // Public Methods =========================================================

    public static function post_captures() {
        return self::$post_queue;
    }

    // Methods ================================================================

    private function __construct() {
    }

    public static function regex() {
        return '#(?<!\$)(?:' . self::REGEX_CLOSED . '|' . self::REGEX_TAG . ')(?!\$)#msi';
    }

    public static function regex_with_id($id) {
        return '#\[\s*(crayon-' . $id . ')\b[^\]]*\]#mi';
    }

    public static function regex_no_capture() {
        return '#(?<!\$)(?:' . self::REGEX_CLOSED_NO_CAPTURE . '|' . self::REGEX_TAG_NO_CAPTURE . ')(?!\$)#msi';
    }

    /**
     * Adds the actual Crayon instance.
     * $mode can be: 0 = return crayon content, 1 = return only code, 2 = return only plain code
     */
    public static function shortcode($atts, $content = NULL, $id = NULL) {
        UrvanovSyntaxHighlighterLog::debug('shortcode');

        // Load attributes from shortcode
        $filtered_atts = shortcode_atts(self::$allowed_atts, $atts);

        // Clean attributes
        $keys = array_keys($filtered_atts);
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];
            $value = $filtered_atts[$key];
            if ($value !== NULL) {
                $filtered_atts[$key] = trim(strip_tags($value));
            }
        }

        // Contains all other attributes not found in allowed, used to override global settings
        $extra_attr = array();
        if (!empty($atts)) {
            $extra_attr = array_diff_key($atts, self::$allowed_atts);
            $extra_attr = Urvanov_Syntax_Highlighter_Settings::smart_settings($extra_attr);
        }
        $url = $lang = $title = $mark = $range = $inline = '';
        extract($filtered_atts);

        $highlighter_instance = self::instance($extra_attr, $id);

        // Set URL
        $highlighter_instance->url($url);
        $highlighter_instance->code($content);
        // Set attributes, should be set after URL to allow language auto detection
        $highlighter_instance->language($lang);
        $highlighter_instance->title($title);
        $highlighter_instance->marked($mark);
        $highlighter_instance->range($range);

        $highlighter_instance->is_inline($inline);

        // Determine if we should highlight
        $highlight = array_key_exists('highlight', $atts)
                ? UrvanovSyntaxHighlighterUtil::str_to_bool($atts['highlight'], FALSE)
                : Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::HIGHLIGHT);
        $highlighter_instance->is_highlighted($highlight);
        return $highlighter_instance;
    }

    /* Returns Crayon instance */
    public static function instance($extra_attr = array(), $id = NULL) {
        UrvanovSyntaxHighlighterLog::debug('instance');

        // Create Crayon
        $highlighter_instance = new Urvanov_Syntax_Highlighter();

        /* Load settings and merge shortcode attributes which will override any existing.
         * Stores the other shortcode attributes as settings in the crayon. */
        if (!empty($extra_attr)) {
            $highlighter_instance->settings($extra_attr);
        }
        if (!empty($id)) {
            $highlighter_instance->id($id);
        }

        return $highlighter_instance;
    }

    /* For manually highlighting code, useful for other PHP contexts */
    public static function highlight($code, $add_tags = FALSE) {
        $captures = Urvanov_Syntax_Highlighter_Plugin::capture_crayons(0, $code);
        $the_captures = $captures['capture'];
        if (count($the_captures) == 0 && $add_tags) {
            // Nothing captured, so wrap in a pre and try again
            $code = '<pre>' . $code . '</pre>';
            $captures = Urvanov_Syntax_Highlighter_Plugin::capture_crayons(0, $code);
            $the_captures = $captures['capture'];
        }
        $the_content = $captures['content'];
        $the_content = UrvanovSyntaxHighlighterUtil::strip_tags_blacklist($the_content, array('script'));
        $the_content = UrvanovSyntaxHighlighterUtil::strip_event_attributes($the_content);
        foreach ($the_captures as $id => $capture) {
            $atts = $capture['atts'];
            $no_enqueue = array(
                Urvanov_Syntax_Highlighter_Settings::ENQUEUE_THEMES => FALSE,
                Urvanov_Syntax_Highlighter_Settings::ENQUEUE_FONTS => FALSE);
            $atts = array_merge($atts, $no_enqueue);
            $code = $capture['code'];
            $crayon = Urvanov_Syntax_Highlighter_Plugin::shortcode($atts, $code, $id);
            $crayon_formatted = $crayon->output(TRUE, FALSE);
            $the_content = UrvanovSyntaxHighlighterUtil::preg_replace_escape_back(self::regex_with_id($id), $crayon_formatted, $the_content, 1, $count);
        }
        return $the_content;
    }

    public static function ajax_highlight() {
        header('Content-Type: text/plain');
        $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : null;
        if (!$code) {
        	$code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : null;
        }
        if ($code) {
            echo self::highlight($code, FALSE);
        } else {
            echo "No code specified.";
        }
        exit();
    }

    /* Uses the main query */
    public static function wp() {
        UrvanovSyntaxHighlighterLog::debug('wp (global)');
        global $wp_the_query;
        if (isset($wp_the_query->posts)) {
            $posts = $wp_the_query->posts;
            self::the_posts($posts);
        }
    }

    // TODO put args into an array
    public static function capture_crayons($wp_id, $wp_content, $extra_settings = array(), $args = array()) {
        extract($args);
        UrvanovSyntaxHighlighterUtil::set_var($callback, NULL);
        UrvanovSyntaxHighlighterUtil::set_var($callback_extra_args, NULL);
        UrvanovSyntaxHighlighterUtil::set_var($ignore, TRUE);
        UrvanovSyntaxHighlighterUtil::set_var($preserve_atts, FALSE);
        UrvanovSyntaxHighlighterUtil::set_var($flags, NULL);
        UrvanovSyntaxHighlighterUtil::set_var($skip_setting_check, FALSE);
        UrvanovSyntaxHighlighterUtil::set_var($just_check, FALSE);

        // Will contain captured crayons and altered $wp_content
        $capture = array('capture' => array(), 'content' => $wp_content, 'has_captured' => FALSE);

        // Do not apply Crayon for posts older than a certain date.
        $disable_date = trim(Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::DISABLE_DATE));
        if ($disable_date && get_post_time('U', true, $wp_id) <= strtotime($disable_date)) {
            return $capture;
        }

        // Flags for which Crayons to convert
        $in_flag = self::in_flag($flags);

        UrvanovSyntaxHighlighterLog::debug('capture for id ' . $wp_id . ' len ' . strlen($wp_content));

//         $blocks = parse_blocks($wp_content);
        
        //UrvanovSyntaxHighlighterLog::debug($blocks, 'Gutenberg blocks');
        
//         foreach ( $blocks as $block ) {
            // Urvanov Syntax Highlighter block
            // UrvanovSyntaxHighlighterLog::debug($block, 'Parsed post block');
//             $capture_pre = false;
//             if ( 'urvanov-syntax-highlighter/code-block' === $block['blockName'] ) {
//             	$capture_pre = true;
//             } else if (('paragraph' === $block['blockName'])
//                     && ((Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::CAPTURE_PRE)
//                             || $skip_setting_check) && $in_flag[Urvanov_Syntax_Highlighter_Settings::CAPTURE_PRE])) {
//                 $capture_pre = true;
//             }
//             UrvanovSyntaxHighlighterLog::debug($capture_pre, 'capture_pre');
//             UrvanovSyntaxHighlighterLog::debug($block['blockName'], 'Block name');
//             UrvanovSyntaxHighlighterLog::debug($block['innerHTML'], 'Inner HTML');
//             if ($capture_pre) {
//             	$block['innerHTML'] = 'TEST REPLACE';
//             	$block['innerContent'] = 'TEST REPLACE';
//                 //$block['innerHTML'] = preg_replace_callback('#(?<!\$)<\s*pre(?=(?:([^>]*)\bclass\s*=\s*(["\'])(.*?)\2([^>]*))?)([^>]*)>(.*?)<\s*/\s*pre\s*>#msi', 'Urvanov_Syntax_Highlighter_Plugin::pre_tag', $block['innerHTML']);
//             }
//         }
//         $wp_content = serialize_blocks($blocks);
        // Convert <pre> tags to crayon tags, if needed
        if ((Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::CAPTURE_PRE) || $skip_setting_check) && $in_flag[Urvanov_Syntax_Highlighter_Settings::CAPTURE_PRE]) {
            // XXX This will fail if <pre></pre> is used inside another <pre></pre>
            $wp_content = preg_replace_callback('#(?<!\$)<\s*pre(?=(?:([^>]*)\bclass\s*=\s*(["\'])(.*?)\2([^>]*))?)([^>]*)>(.*?)<\s*/\s*pre\s*>#msi', 'Urvanov_Syntax_Highlighter_Plugin::pre_tag', $wp_content);
        }

        // Convert mini [php][/php] tags to crayon tags, if needed
        if ((Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG) || $skip_setting_check) && $in_flag[Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG]) {
            $wp_content = preg_replace('#(?<!\$)\[\s*(' . self::$alias_regex . ')\b([^\]]*)\](.*?)\[\s*/\s*(?:\1)\s*\](?!\$)#msi', '[crayon lang="\1" \2]\3[/crayon]', $wp_content);
            $wp_content = preg_replace('#(?<!\$)\[\s*(' . self::$alias_regex . ')\b([^\]]*)/\s*\](?!\$)#msi', '[crayon lang="\1" \2 /]', $wp_content);
        }

        // Convert <code> to inline tags
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::CODE_TAG_CAPTURE)) {
            $inline = Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::CODE_TAG_CAPTURE_TYPE) === 0;
            $inline_setting = $inline ? 'inline="true"' : '';
            $wp_content = preg_replace('#<(\s*code\b)([^>]*)>(.*?)</\1[^>]*>#msi', '[crayon ' . $inline_setting . ' \2]\3[/crayon]', $wp_content);
        }

        if ((Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::INLINE_TAG) || $skip_setting_check) && $in_flag[Urvanov_Syntax_Highlighter_Settings::INLINE_TAG]) {
            if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::INLINE_TAG_CAPTURE)) {
                // Convert inline {php}{/php} tags to crayon tags, if needed
                $wp_content = preg_replace('#(?<!\$)\{\s*(' . self::$alias_regex . ')\b([^\}]*)\}(.*?)\{/(?:\1)\}(?!\$)#msi', '[crayon lang="\1" inline="true" \2]\3[/crayon]', $wp_content);
            }
            // Convert <span class="crayon-inline"> tags to inline crayon tags
            $wp_content = preg_replace_callback('#(?<!\$)<\s*span([^>]*)\bclass\s*=\s*(["\'])(.*?)\2([^>]*)>(.*?)<\s*/\s*span\s*>#msi', 'Urvanov_Syntax_Highlighter_Plugin::span_tag', $wp_content);
        }

        // Convert [plain] tags into <pre><code></code></pre>, if needed
        if ((Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::PLAIN_TAG) || $skip_setting_check) && $in_flag[Urvanov_Syntax_Highlighter_Settings::PLAIN_TAG]) {
            $wp_content = preg_replace_callback('#(?<!\$)\[\s*plain\s*\](.*?)\[\s*/\s*plain\s*\]#msi', 'Urvanov_Syntax_Highlighter_Formatter::plain_code', $wp_content);
        }

        // Add IDs to the Crayons
        UrvanovSyntaxHighlighterLog::debug('capture adding id ' . $wp_id . ' , now has len ' . strlen($wp_content));
        $wp_content = preg_replace_callback(self::REGEX_ID, 'Urvanov_Syntax_Highlighter_Plugin::add_crayon_id', $wp_content);

        UrvanovSyntaxHighlighterLog::debug('capture added id ' . $wp_id . ' : ' . strlen($wp_content));

        // Only include if a post exists with Crayon tag
        preg_match_all(self::regex(), $wp_content, $matches);
        $capture['has_captured'] = count($matches[0]) != 0;
        if ($just_check) {
            // Backticks are matched after other tags, so they need to be captured here.
            $result = self::replace_backquotes($wp_content);
            $wp_content = $result['content'];
            $capture['has_captured'] = $capture['has_captured'] || $result['changed'];
            $capture['content'] = $wp_content;
            return $capture;
        }

        UrvanovSyntaxHighlighterLog::debug('capture ignore for id ' . $wp_id . ' : ' . strlen($capture['content']) . ' vs ' . strlen($wp_content));

        if ($capture['has_captured']) {

            // Crayons found! Load settings first to ensure global settings loaded
            Urvanov_Syntax_Highlighter_Settings_WP::load_settings();

            UrvanovSyntaxHighlighterLog::debug('CAPTURED FOR ID ' . $wp_id);

            $full_matches = $matches[0];
            $closed_ids = $matches[1];
            $closed_atts = $matches[2];
            $open_ids = $matches[3];
            $open_atts = $matches[4];
            $contents = $matches[5];

            // Make sure we enqueue the styles/scripts
            $enqueue = TRUE;

            for ($i = 0; $i < count($full_matches); $i++) {
                // Get attributes
                if (!empty($closed_atts[$i])) {
                    $atts = $closed_atts[$i];
                } else if (!empty($open_atts[$i])) {
                    $atts = $open_atts[$i];
                } else {
                    $atts = '';
                }

                // Capture attributes
                preg_match_all('#([^="\'\s]+)[\t ]*=[\t ]*("|\')(.*?)\2#', $atts, $att_matches);
                // Add extra attributes
                $atts_array = $extra_settings;
                if (count($att_matches[0]) != 0) {
                    for ($j = 0; $j < count($att_matches[1]); $j++) {
                        $atts_array[trim(strtolower($att_matches[1][$j]))] = trim($att_matches[3][$j]);
                    }
                }

                if (isset($atts_array[Urvanov_Syntax_Highlighter_Settings::IGNORE]) && $atts_array[Urvanov_Syntax_Highlighter_Settings::IGNORE]) {
                    // TODO(aramk) Revert to the original content.
                    continue;
                }

                // Capture theme
                $theme_id = array_key_exists(Urvanov_Syntax_Highlighter_Settings::THEME, $atts_array) ? $atts_array[Urvanov_Syntax_Highlighter_Settings::THEME] : '';
                $theme = Urvanov_Syntax_Highlighter_Resources::themes()->get($theme_id);
                // If theme not found, use fallbacks
                if (!$theme) {
                    // Given theme is invalid, try global setting
                    $theme_id = Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::THEME);
                    $theme = Urvanov_Syntax_Highlighter_Resources::themes()->get($theme_id);
                    if (!$theme) {
                        // Global setting is invalid, fall back to default
                        $theme = Urvanov_Syntax_Highlighter_Resources::themes()->get_default();
                        $theme_id = Urvanov_Syntax_Highlighter_Themes::DEFAULT_THEME;
                    }
                }
                // If theme is now valid, change the array
                if ($theme) {
                    if (!$preserve_atts || isset($atts_array[Urvanov_Syntax_Highlighter_Settings::THEME])) {
                        $atts_array[Urvanov_Syntax_Highlighter_Settings::THEME] = $theme_id;
                    }
                    $theme->used(TRUE);
                }

                // Capture font
                $font_id = array_key_exists(Urvanov_Syntax_Highlighter_Settings::FONT, $atts_array) ? $atts_array[Urvanov_Syntax_Highlighter_Settings::FONT] : '';
                $font = Urvanov_Syntax_Highlighter_Resources::fonts()->get($font_id);
                // If font not found, use fallbacks
                if (!$font) {
                    // Given font is invalid, try global setting
                    $font_id = Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::FONT);
                    $font = Urvanov_Syntax_Highlighter_Resources::fonts()->get($font_id);
                    if (!$font) {
                        // Global setting is invalid, fall back to default
                        $font = Urvanov_Syntax_Highlighter_Resources::fonts()->get_default();
                        $font_id = Urvanov_Syntax_Highlighter_Fonts::DEFAULT_FONT;
                    }
                }

                // If font is now valid, change the array
                if ($font /* != NULL && $font_id != CrayonFonts::DEFAULT_FONT*/) {
                    if (!$preserve_atts || isset($atts_array[Urvanov_Syntax_Highlighter_Settings::FONT])) {
                        $atts_array[Urvanov_Syntax_Highlighter_Settings::FONT] = $font_id;
                    }
                    $font->used(TRUE);
                }

                // Add array of atts and content to post queue with key as post ID
                // XXX If at this point no ID is added we have failed!
                $id = !empty($open_ids[$i]) ? $open_ids[$i] : $closed_ids[$i];
                //if ($ignore) {
                $code = self::crayon_remove_ignore($contents[$i]);
                //}
                $c = array('post_id' => $wp_id, 'atts' => $atts_array, 'code' => $code);
                $capture['capture'][$id] = $c;
                UrvanovSyntaxHighlighterLog::debug('capture finished for post id ' . $wp_id . ' crayon-id ' . $id . ' atts: ' . count($atts_array) . ' code: ' . strlen($code));
                $is_inline = isset($atts_array['inline']) && UrvanovSyntaxHighlighterUtil::str_to_bool($atts_array['inline'], FALSE) ? '-i' : '';
                if ($callback === NULL) {
                    $wp_content = str_replace($full_matches[$i], '[crayon-' . $id . $is_inline . '/]', $wp_content);
                } else {
                    $wp_content = call_user_func($callback, $c, $full_matches[$i], $id, $is_inline, $wp_content, $callback_extra_args);
                }
            }

        }

        if ($ignore) {
            // We need to escape ignored Crayons, since they won't be captured
            // XXX Do this after replacing the Crayon with the shorter ID tag, otherwise $full_matches will be different from $wp_content
            $wp_content = self::crayon_remove_ignore($wp_content);
        }

        $result = self::replace_backquotes($wp_content);
        $wp_content = $result['content'];

        $capture['content'] = $wp_content;
        return $capture;
    }
    
    
    // *****************************************************************************

    public static function replace_backquotes($wp_content) {
        // Convert `` backquote tags into <code></code>, if needed
        // XXX Some code may contain `` so must do it after all Crayons are captured
        $result = array();
        $prev_count = strlen($wp_content);
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::BACKQUOTE)) {
            $wp_content = preg_replace('#(?<!\\\\)`([^`]*)`#msi', '<code>$1</code>', $wp_content);
        }
        $result['changed'] = $prev_count !== strlen($wp_content);
        $result['content'] = $wp_content;
        return $result;
    }

    /* Search for Crayons in posts and queue them for creation */
    public static function the_posts($posts) {
        UrvanovSyntaxHighlighterLog::debug('the_posts');

        // Whether to enqueue syles/scripts
        Urvanov_Syntax_Highlighter_Settings_WP::load_settings(TRUE); // We will eventually need more than the settings

        self::init_tags_regex();
        $crayon_posts = Urvanov_Syntax_Highlighter_Settings_WP::load_posts(); // Loads posts containing crayons

        // Search for shortcode in posts
        foreach ($posts as $post) {
            $wp_id = $post->ID;
            $is_page = $post->post_type == 'page';
            if (!in_array($wp_id, $crayon_posts)) {
                // If we get query for a page, then that page might have a template and load more posts containing Crayons
                // By this state, we would be unable to enqueue anything (header already written).
                if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::SAFE_ENQUEUE) && $is_page) {
                    Urvanov_Syntax_Highlighter_Global_Settings::set(Urvanov_Syntax_Highlighter_Settings::ENQUEUE_THEMES, false);
                    Urvanov_Syntax_Highlighter_Global_Settings::set(Urvanov_Syntax_Highlighter_Settings::ENQUEUE_FONTS, false);
                }
                // Only include crayon posts
                continue;
            }

            $id_str = strval($wp_id);

            if (wp_is_post_revision($wp_id)) {
                // Ignore post revisions, use the parent, which has the updated post content
                continue;
            }

            if (isset(self::$post_captures[$id_str])) {
                // Don't capture twice
                // XXX post->post_content is reset each loop, replace content
                // Doing this might cause content changed by other plugins between the last loop
                // to fail, so be cautious
                $post->post_content = self::$post_captures[$id_str];
                continue;
            }
            // Capture post Crayons
            $captures = self::capture_crayons(intval($post->ID), $post->post_content);

            // XXX Careful not to undo changes by other plugins
            // XXX Must replace to remove $ for ignored Crayons
            $post->post_content = $captures['content'];
            self::$post_captures[$id_str] = $captures['content'];
            if ($captures['has_captured'] === TRUE) {
                self::$post_queue[$id_str] = array();
                foreach ($captures['capture'] as $capture_id => $capture_content) {
                    self::$post_queue[$id_str][$capture_id] = $capture_content;
                }
            }

            // Search for shortcode in comments
            if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::COMMENTS)) {
                $comments = get_comments(array('post_id' => $post->ID));
                foreach ($comments as $comment) {
                    $id_str = strval($comment->comment_ID);
                    if (isset(self::$comment_queue[$id_str])) {
                        // Don't capture twice
                        continue;
                    }
                    // Capture comment Crayons, decode their contents if decode not specified
                    $content = apply_filters('get_comment_text', $comment->comment_content, $comment);
                    $captures = self::capture_crayons($comment->comment_ID, $content, array(Urvanov_Syntax_Highlighter_Settings::DECODE => TRUE));
                    self::$comment_captures[$id_str] = $captures['content'];
                    if ($captures['has_captured'] === TRUE) {
                        self::$comment_queue[$id_str] = array();
                        foreach ($captures['capture'] as $capture_id => $capture_content) {
                            self::$comment_queue[$id_str][$capture_id] = $capture_content;
                        }
                    }
                }
            }
        }

        return $posts;
    }

    private static function add_crayon_id($content) {
        $uid = $content[0] . '-' . str_replace('.', '', uniqid('', true));
        UrvanovSyntaxHighlighterLog::debug('add_crayon_id ' . $uid);
        return $uid;
    }

    private static function get_crayon_id() {
        return self::$next_id++;
    }

    public static function enqueue_resources() {
        if (!self::$enqueued) {

            UrvanovSyntaxHighlighterLog::debug('enqueue');
            global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
            UrvanovSyntaxHighlighterLog::debug('Loading settings...');
            Urvanov_Syntax_Highlighter_Settings_WP::load_settings(TRUE);
            if (URVANOV_SYNTAX_HIGHLIGHTER_MINIFY) {
                wp_enqueue_style('urvanov_syntax_highlighter', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_STYLE_MIN, __FILE__), array(), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
                wp_enqueue_script('urvanov_syntax_highlighter_js', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_JS_MIN, __FILE__), array('jquery'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION, Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::DELAY_LOAD_JS));
            } else {
                wp_enqueue_style('urvanov_syntax_highlighter_style', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_STYLE, __FILE__), array(), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
                wp_enqueue_style('urvanov_syntax_highlighter_global_style', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_STYLE_GLOBAL, __FILE__), array(), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
                wp_enqueue_script('urvanov_syntax_highlighter_util_js', plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_JS_UTIL, __FILE__), array('jquery'), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
                Urvanov_Syntax_Highlighter_Settings_WP::other_scripts();
            }
            UrvanovSyntaxHighlighterLog::debug('Initializing js settings...');
            Urvanov_Syntax_Highlighter_Settings_WP::init_js_settings();
            self::$enqueued = TRUE;
        }
    }

    private static function init_tags_regex($force = FALSE, $flags = NULL, &$tags_regex = NULL) {
        Urvanov_Syntax_Highlighter_Settings_WP::load_settings();
        self::init_tag_bits();

        // Default output
        if ($tags_regex === NULL) {
            $tags_regex = & self::$tags_regex;
        }

        if ($force || $tags_regex === "") {
            // Check which tags are in $flags. If it's NULL, then all flags are true.
            $in_flag = self::in_flag($flags);

            if (($in_flag[Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG] && (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG)) || $force) ||
                ($in_flag[Urvanov_Syntax_Highlighter_Settings::INLINE_TAG] && (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::INLINE_TAG) && Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::INLINE_TAG_CAPTURE)) || $force)
            ) {
                $aliases = Urvanov_Syntax_Highlighter_Resources::langs()->ids_and_aliases();
                self::$alias_regex = '';
                for ($i = 0; $i < count($aliases); $i++) {
                    $alias = $aliases[$i];
                    $alias_regex = UrvanovSyntaxHighlighterUtil::esc_hash(UrvanovSyntaxHighlighterUtil::esc_regex($alias));
                    if ($i != count($aliases) - 1) {
                        $alias_regex .= '|';
                    }
                    self::$alias_regex .= $alias_regex;
                }
            }

            // Add other tags
            $tags_regex = '#(?<!\$)(?:(\s*\[\s*crayon\b)';

            // TODO this is duplicated in capture_crayons()
            $tag_regexes = array(
                Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG => '(\[\s*(' . self::$alias_regex . ')\b)',
                Urvanov_Syntax_Highlighter_Settings::CAPTURE_PRE => '(<\s*pre\b)',
                Urvanov_Syntax_Highlighter_Settings::INLINE_TAG => '(' . self::REGEX_INLINE_CLASS . ')' . '|(\{\s*(' . self::$alias_regex . ')\b([^\}]*)\})',
                Urvanov_Syntax_Highlighter_Settings::PLAIN_TAG => '(\s*\[\s*plain\b)',
                Urvanov_Syntax_Highlighter_Settings::BACKQUOTE => '(`[^`]*`)'
            );

            foreach ($tag_regexes as $tag => $regex) {
                if ($in_flag[$tag] && (Urvanov_Syntax_Highlighter_Global_Settings::val($tag) || $force)) {
                    $tags_regex .= '|' . $regex;
                }
            }
            $tags_regex .= ')#msi';
        }

    }

    private static function init_tag_bits() {
        if (count(self::$tag_bits) == 0) {
            $values = array();
            for ($i = 0; $i < count(self::$tag_types); $i++) {
                $j = pow(2, $i);
                self::$tag_bits[self::$tag_types[$i]] = $j;
            }
        }
    }

    public static function tag_bit($tag) {
        self::init_tag_bits();
        if (isset(self::$tag_bits[$tag])) {
            return self::$tag_bits[$tag];
        } else {
            return null;
        }
    }

    public static function in_flag($flags) {
        $in_flag = array();
        foreach (self::$tag_types as $tag) {
            $in_flag[$tag] = $flags === NULL || ($flags & self::tag_bit($tag)) > 0;
        }
        return $in_flag;
    }

    private static function init_legacy_tag_bits() {
        if (self::$legacy_flags === NULL) {
            self::$legacy_flags = self::tag_bit(Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG) |
                self::tag_bit(Urvanov_Syntax_Highlighter_Settings::INLINE_TAG) |
                self::tag_bit(Urvanov_Syntax_Highlighter_Settings::PLAIN_TAG);
        }
        if (self::$tags_regex_legacy === "") {
            self::init_tags_regex(TRUE, self::$legacy_flags, self::$tags_regex_legacy);
        }
    }

    // Add Crayon into the_content
    public static function the_content($the_content) {
        UrvanovSyntaxHighlighterLog::debug('the_content');

        // Some themes make redundant queries and don't need extra work...
        if (strlen($the_content) == 0) {
            UrvanovSyntaxHighlighterLog::debug('the_content blank');
            return $the_content;
        }

        if (self::$is_excerpt) {
            UrvanovSyntaxHighlighterLog::debug('excerpt');
            if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::EXCERPT_STRIP)) {
                UrvanovSyntaxHighlighterLog::debug('excerpt strip');
                // Remove Crayon from content if we are displaying an excerpt
                $the_content = preg_replace(self::REGEX_WITH_ID, '', $the_content);
            }
            // Otherwise Crayon remains with ID and replaced later
            return $the_content;
        }

        global $post;
        
        // Go through queued posts and find crayons
        $post_id = strval($post->ID);

        // Find if this post has Crayons
        if (array_key_exists($post_id, self::$post_queue)) {
            self::enqueue_resources();

            // XXX We want the plain post content, no formatting
            $the_content_original = $the_content;

            // Replacing may cause <p> tags to become disjoint with a <div> inside them, close and reopen them if needed
            $the_content = preg_replace_callback('#' . self::REGEX_BETWEEN_PARAGRAPH_SIMPLE . '#msi', 'Urvanov_Syntax_Highlighter_Plugin::add_paragraphs', $the_content);
            // Loop through Crayons
            $post_in_queue = self::$post_queue[$post_id];

            foreach ($post_in_queue as $id => $v) {
                $atts = $v['atts'];
                $content = $v['code']; // The code we replace post content with
                $crayon = self::shortcode($atts, $content, $id);
                if (is_feed() && !$crayon->is_inline()) {
                    // Convert the plain code to entities and put in a <pre></pre> tag
                    $crayon_formatted = Urvanov_Syntax_Highlighter_Formatter::plain_code($crayon->code(), $crayon->setting_val(Urvanov_Syntax_Highlighter_Settings::DECODE));
                } else {
                    // Apply shortcode to the content
                    $crayon_formatted = $crayon->output(TRUE, FALSE);
                }
                // Replace the code with the Crayon
                UrvanovSyntaxHighlighterLog::debug('the_content: id ' . $post_id . ' has UID ' . $id . ' : ' . intval(stripos($the_content, $id) !== FALSE));
                $the_content = UrvanovSyntaxHighlighterUtil::preg_replace_escape_back(self::regex_with_id($id), $crayon_formatted, $the_content, 1, $count);
                UrvanovSyntaxHighlighterLog::debug('the_content: REPLACED for id ' . $post_id . ' from len ' . strlen($the_content_original) . ' to ' . strlen($the_content));
            }
        }

        return $the_content;
    }

    public static function pre_comment_text($text, $comment, $args ) {
        // according to
        // https://developer.wordpress.org/reference/hooks/comment_text/
        // this filter calls in different places.
        //
        // https://github.com/WordPress/WordPress/blob/01876b090612c0d2825a896a7ba0e7fd6dd6decc/wp-includes/comment.php#L48
        // calls it with $comment === null
        //
        // https://github.com/WordPress/WordPress/blob/bb26471543b104e047f9f4b264810adc372cd6ce/wp-includes/comment-template.php#L1094
        // calls it with the object $comment = get_comment( $comment_id ); as $comment
        if ($comment === null) {
            return $text;
        }
        // We process only call with filled $comment
        $comment_id = strval($comment->comment_ID);
        if (array_key_exists($comment_id, self::$comment_captures)) {
            // Replace with IDs now that we need to
            $text = self::$comment_captures[$comment_id];
        }
        return $text;
    }

    public static function comment_text($text, $comment, $args ) {
        // according to
        // https://developer.wordpress.org/reference/hooks/comment_text/
        // this filter calls in different places.
        //
        // https://github.com/WordPress/WordPress/blob/01876b090612c0d2825a896a7ba0e7fd6dd6decc/wp-includes/comment.php#L48
        // calls it with $comment === null
        //
        // https://github.com/WordPress/WordPress/blob/bb26471543b104e047f9f4b264810adc372cd6ce/wp-includes/comment-template.php#L1094
        // calls it with the object $comment = get_comment( $comment_id ); as $comment
        if ($comment === null) {
            return $text;
        }
        // We process only call with filled $comment
        $comment_id = strval($comment->comment_ID);
        // Find if this post has Crayons
        if (array_key_exists($comment_id, self::$comment_queue)) {
            // XXX We want the plain post content, no formatting
            $the_content_original = $text;
            // Loop through Crayons
            $post_in_queue = self::$comment_queue[$comment_id];

            foreach ($post_in_queue as $id => $v) {
                $atts = $v['atts'];
                $content = $v['code']; // The code we replace post content with
                $crayon = self::shortcode($atts, $content, $id);
                $crayon_formatted = $crayon->output(TRUE, FALSE);
                // Replacing may cause <p> tags to become disjoint with a <div> inside them, close and reopen them if needed
                if (!$crayon->is_inline()) {
                    $text = preg_replace_callback('#' . self::REGEX_BETWEEN_PARAGRAPH_SIMPLE . '#msi', 'Urvanov_Syntax_Highlighter_Plugin::add_paragraphs', $text);
                }
                // Replace the code with the Crayon
                $text = UrvanovSyntaxHighlighterUtil::preg_replace_escape_back(self::regex_with_id($id), $crayon_formatted, $text, 1, $text);
            }
        }
        return $text;
    }

    public static function add_paragraphs($capture) {
        if (count($capture) != 4) {
            UrvanovSyntaxHighlighterLog::debug('add_paragraphs: 0');
            return $capture[0];
        }
        $capture[2] = preg_replace('#(?:<\s*br\s*/\s*>\s*)?(\[\s*crayon-\w+/\])(?:<\s*br\s*/\s*>\s*)?#msi', '</p>$1<p>', $capture[2]);
        // If [crayon appears right after <p> then we will generate <p></p>, remove all these
        $paras = $capture[1] . $capture[2] . $capture[3];
        return $paras;
    }

    // Remove Crayons from the_excerpt
    public static function the_excerpt($the_excerpt) {
        UrvanovSyntaxHighlighterLog::debug('excerpt');
        global $post;
        if (!empty($post->post_excerpt)) {
            // Use custom excerpt if defined
            $the_excerpt = wpautop($post->post_excerpt);
        } else {
            // Pass wp_trim_excerpt('') to gen from content (and remove [crayons])
            $the_excerpt = wpautop(wp_trim_excerpt(''));
        }
        // XXX Returning "" may cause it to default to full contents...
        return $the_excerpt . ' ';
    }

    // Used to capture pre and span tags which have settings in class attribute
    public static function class_tag($matches) {
        // If class exists, atts is not captured
        $pre_class = $matches[1];
        $quotes = $matches[2];
        $class = $matches[3];
        $post_class = $matches[4];
        $atts = $matches[5];
        $content = $matches[6];
        UrvanovSyntaxHighlighterLog::debug($pre_class, 'class_tag_pre_class');
        UrvanovSyntaxHighlighterLog::debug($quotes, 'class_tag_quotes');
        UrvanovSyntaxHighlighterLog::debug($class, 'class_tag_class');
        UrvanovSyntaxHighlighterLog::debug($post_class, 'class_tag_post_class');
        UrvanovSyntaxHighlighterLog::debug($atts, 'class_tag_atts');
        UrvanovSyntaxHighlighterLog::debug($content, 'class_tag_content=');
        
        // If we find a crayon=false in the attributes, or a crayon[:_]false in the class, then we should not capture
        $ignore_regex_atts = '#crayon\s*=\s*(["\'])\s*(false|no|0)\s*\1#msi';
        $ignore_regex_class = '#crayon\s*[:_]\s*(false|no|0)#msi';
        if (preg_match($ignore_regex_atts, $atts) !== 0 ||
            preg_match($ignore_regex_class, $class) !== 0
        ) {
            return $matches[0];
        }

        if (!empty($class)) {
            if (preg_match('#\bignore\s*:\s*true#', $class)) {
                // Prevent any changes if ignoring the tag.
                return $matches[0];
            }
            // crayon-inline is turned into inline="1"
            $class = preg_replace('#' . self::REGEX_INLINE_CLASS . '#mi', 'inline="1"', $class);
            // "setting[:_]value" style settings in the class attribute
            $class = preg_replace('#\b([A-Za-z-]+)[_:](\S+)#msi', '$1=' . $quotes . '$2' . $quotes, $class);
        }

        // data-url is turned into url=""
        if (!empty($post_class)) {
            $post_class = preg_replace('#\bdata-url\s*=#mi', 'url=', $post_class);
        }
        if (!empty($pre_class)) {
            $pre_class = preg_replace('#\bdata-url\s*=#mi', 'url=', $pre_class);
        }

        if (!empty($class)) {
            return "[crayon $pre_class $class $post_class]{$content}[/crayon]";
        } else {
            return "[crayon $atts]{$content}[/crayon]";
        }
    }

    // Capture span tag and extract settings from the class attribute, if present.
    public static function span_tag($matches) {
        // Only use <span> tags with crayon-inline class
        if (preg_match('#' . self::REGEX_INLINE_CLASS . '#mi', $matches[3])) {
            // no $atts
            $matches[6] = $matches[5];
            $matches[5] = '';
            return self::class_tag($matches);
        } else {
            // Don't turn regular <span>s into Crayons
            return $matches[0];
        }
    }

    // Capture pre tag and extract settings from the class attribute, if present.
    public static function pre_tag($matches) {
        return self::class_tag($matches);
    }

    /**
     * Check if the $ notation has been used to ignore [crayon] tags within posts and remove all matches
     * Can also remove if used without $ as a regular crayon
     *
     * @deprecated
     */
    public static function crayon_remove_ignore($the_content, $ignore_flag = '$') {
        if ($ignore_flag == FALSE) {
            $ignore_flag = '';
        }
        $ignore_flag_regex = preg_quote($ignore_flag);

        $the_content = preg_replace('#' . $ignore_flag_regex . '(\s*\[\s*crayon)#msi', '$1', $the_content);
        $the_content = preg_replace('#(crayon\s*\])\s*\$#msi', '$1', $the_content);

        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::CAPTURE_PRE)) {
            $the_content = str_ireplace(array($ignore_flag . '<pre', 'pre>' . $ignore_flag), array('<pre', 'pre>'), $the_content);
            // Remove any <code> tags wrapping around the whole code, since we won't needed them
            // XXX This causes <code> tags to be stripped in the post content! Disabled now.
            // $the_content = preg_replace('#(^\s*<\s*code[^>]*>)|(<\s*/\s*code[^>]*>\s*$)#msi', '', $the_content);
        }
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::PLAIN_TAG)) {
            $the_content = str_ireplace(array($ignore_flag . '[plain', 'plain]' . $ignore_flag), array('[plain', 'plain]'), $the_content);
        }
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::CAPTURE_MINI_TAG) ||
            (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::INLINE_TAG && Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::INLINE_TAG_CAPTURE)))
        ) {
            self::init_tags_regex();
            // 			$the_content = preg_replace('#'.$ignore_flag_regex.'\s*([\[\{])\s*('. self::$alias_regex .')#', '$1$2', $the_content);
            // 			$the_content = preg_replace('#('. self::$alias_regex .')\s*([\]\}])\s*'.$ignore_flag_regex.'#', '$1$2', $the_content);
            $the_content = preg_replace('#' . $ignore_flag_regex . '(\s*[\[\{]\s*(' . self::$alias_regex . ')[^\]]*[\]\}])#', '$1', $the_content);
        }
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::BACKQUOTE)) {
            $the_content = str_ireplace('\\`', '`', $the_content);
        }
        return $the_content;
    }

    public static function wp_head() {
        UrvanovSyntaxHighlighterLog::debug('head');

        self::$wp_head = TRUE;
        if (!self::$enqueued) {
            UrvanovSyntaxHighlighterLog::debug('head: missed enqueue');
            // We have missed our chance to check before enqueuing. Use setting to either load always or only in the_post
            Urvanov_Syntax_Highlighter_Settings_WP::load_settings(TRUE); // Ensure settings are loaded
            // If we need the tag editor loaded at all times, we must enqueue at all times
            if (!Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::EFFICIENT_ENQUEUE) || Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_FRONT)) {
                UrvanovSyntaxHighlighterLog::debug('head: force enqueue');
                // Efficient enqueuing disabled, always load despite enqueuing or not in the_post
                self::enqueue_resources();
            }
        }
        // Enqueue Theme CSS
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::ENQUEUE_THEMES)) {
            self::crayon_theme_css();
        }
        // Enqueue Font CSS
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::ENQUEUE_FONTS)) {
            self::crayon_font_css();
        }
    }

    public static function save_post($update_id, $post) {
        self::refresh_post($post);
    }

    public static function rest_after_insert( $post, $request, $creating ){
    	Urvanov_Syntax_Highlighter_Plugin::save_post(null, $post);
    }

    public static function filter_post_data($data, $postarr) {
        // Remove the selected CSS that may be present from the tag editor.
        UrvanovSyntaxHighlighterTagEditorWP::init_settings();
        $css_selected = UrvanovSyntaxHighlighterTagEditorWP::$settings['css_selected'];
        $data['post_content'] = preg_replace("#(class\s*=\s*(\\\\[\"'])[^\"']*)$css_selected([^\"']*\\2)#msi", '$1$3', $data['post_content']);
        return $data;
    }

    public static function refresh_post($post, $refresh_legacy = TRUE, $save = TRUE) {
        $postID = $post->ID;
        if (wp_is_post_revision($postID)) {
            // Ignore revisions
            return;
        }
        if (Urvanov_Syntax_Highlighter_Plugin::scan_post($post)) {
            Urvanov_Syntax_Highlighter_Settings_WP::add_post($postID, $save);
            if ($refresh_legacy) {
                if (self::scan_legacy_post($post)) {
                    Urvanov_Syntax_Highlighter_Settings_WP::add_legacy_post($postID, $save);
                } else {
                    Urvanov_Syntax_Highlighter_Settings_WP::remove_legacy_post($postID, $save);
                }
            }
        } else {
            Urvanov_Syntax_Highlighter_Settings_WP::remove_post($postID, $save);
            Urvanov_Syntax_Highlighter_Settings_WP::remove_legacy_post($postID, $save);
        }
    }

    public static function refresh_posts() {
        Urvanov_Syntax_Highlighter_Settings_WP::remove_posts();
        Urvanov_Syntax_Highlighter_Settings_WP::remove_legacy_posts();
        foreach (Urvanov_Syntax_Highlighter_Plugin::get_posts() as $post) {
            self::refresh_post($post, TRUE, FALSE);
        }
        Urvanov_Syntax_Highlighter_Settings_WP::save_posts();
        Urvanov_Syntax_Highlighter_Settings_WP::save_legacy_posts();

    }

    public static function save_comment($id, $is_spam = NULL, $comment = NULL) {
        self::init_tags_regex();
        if ($comment === NULL) {
            $comment = get_comment($id);
        }
        $content = $comment->comment_content;
        $post_id = $comment->comment_post_ID;
        $found = preg_match(self::$tags_regex, $content);
        if ($found) {
            Urvanov_Syntax_Highlighter_Settings_WP::add_post($post_id);
        }
        return $found;
    }

    public static function crayon_theme_css() {
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
        Urvanov_Syntax_Highlighter_Settings_WP::load_settings();
        $css = Urvanov_Syntax_Highlighter_Resources::themes()->get_used_css();
        foreach ($css as $theme => $url) {
            wp_enqueue_style('crayon-theme-' . $theme, $url, array(), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
        }
    }

    public static function crayon_font_css() {
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
        Urvanov_Syntax_Highlighter_Settings_WP::load_settings();
        $css = Urvanov_Syntax_Highlighter_Resources::fonts()->get_used_css();
        foreach ($css as $font_id => $url) {
            wp_enqueue_style('crayon-font-' . $font_id, $url, array(), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
        }
    }

    public static function init_ajax() {
        add_action('wp_ajax_urvanov-syntax-highlighter-tag-editor', 'UrvanovSyntaxHighlighterTagEditorWP::content');
        add_action('wp_ajax_nopriv_urvanov-syntax-highlighter-tag-editor', 'UrvanovSyntaxHighlighterTagEditorWP::content');
        add_action('wp_ajax_urvanov-syntax-highlighter-highlight', 'Urvanov_Syntax_Highlighter_Plugin::ajax_highlight');
        add_action('wp_ajax_nopriv_urvanov-syntax-highlighter-highlight', 'Urvanov_Syntax_Highlighter_Plugin::ajax_highlight');
        if (current_user_can('manage_options')) {
            add_action('wp_ajax_urvanov-syntax-highlighter-ajax', 'Urvanov_Syntax_Highlighter_Plugin::ajax');
            add_action('wp_ajax_urvanov-syntax-highlighter-theme-editor', 'Urvanov_Syntax_Highlighter_Theme_Editor_WP::content');
            add_action('wp_ajax_urvanov-syntax-highlighter-theme-editor-save', 'Urvanov_Syntax_Highlighter_Theme_Editor_WP::save');
            add_action('wp_ajax_urvanov-syntax-highlighter-theme-editor-delete', 'Urvanov_Syntax_Highlighter_Theme_Editor_WP::delete');
            add_action('wp_ajax_urvanov-syntax-highlighter-theme-editor-duplicate', 'Urvanov_Syntax_Highlighter_Theme_Editor_WP::duplicate');
            add_action('wp_ajax_urvanov-syntax-highlighter-theme-editor-submit', 'Urvanov_Syntax_Highlighter_Theme_Editor_WP::submit');
            add_action('wp_ajax_urvanov-syntax-highlighter-show-posts', 'Urvanov_Syntax_Highlighter_Settings_WP::show_posts');
            add_action('wp_ajax_urvanov-syntax-highlighter-show-langs', 'Urvanov_Syntax_Highlighter_Settings_WP::show_langs');
            add_action('wp_ajax_urvanov-syntax-highlighter-show-preview', 'Urvanov_Syntax_Highlighter_Settings_WP::show_preview');
        }
    }

    public static function ajax() {
        check_ajax_referer( 'urvanov-syntax-highlighter-hide-help');
        $allowed = array(Urvanov_Syntax_Highlighter_Settings::HIDE_HELP);
        foreach ($allowed as $allow) {
            if (array_key_exists($allow, $_GET)) {
                Urvanov_Syntax_Highlighter_Global_Settings::set($allow, $_GET[$allow]);
                Urvanov_Syntax_Highlighter_Settings_WP::save_settings();
            }
        }
    }

    public static function get_posts() {
        $query = new WP_Query(array('post_type' => 'any', 'suppress_filters' => TRUE, 'posts_per_page' => '-1'));
        if (isset($query->posts)) {
            return $query->posts;
        } else {
            return array();
        }
    }

    /**
     * Return an array of post IDs where crayons occur.
     * Comments are ignored by default.
     */
    public static function scan_posts($check_comments = FALSE) {
        $crayon_posts = array();
        foreach (self::get_posts() as $post) {
            if (self::scan_post($post)) {
                $crayon_posts[] = $post->ID;
            }
        }
        return $crayon_posts;
    }

    public static function scan_legacy_posts($init_regex = TRUE, $check_comments = FALSE) {
        if ($init_regex) {
            // We can skip this if needed
            self::init_tags_regex();
        }
        $crayon_posts = array();
        foreach (self::get_posts() as $post) {
            if (self::scan_legacy_post($post)) { // TODO this part is different
                $crayon_posts[] = $post->ID;
            }
        }
        return $crayon_posts;
    }

    /**
     * Returns TRUE if a given post contains a Crayon tag
     */
    public static function scan_post($post, $scan_comments = TRUE, $flags = NULL) {
        if ($flags === NULL) {
            self::init_tags_regex(TRUE);
        }

        $id = $post->ID;

        $args = array(
            'ignore' => FALSE,
            'flags' => $flags,
            'skip_setting_check' => TRUE,
            'just_check' => TRUE
        );
        $captures = self::capture_crayons($id, $post->post_content, array(), $args);

        if ($captures['has_captured']) {
            return TRUE;
        } else if ($scan_comments) {
            Urvanov_Syntax_Highlighter_Settings_WP::load_settings(TRUE);
            if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::COMMENTS)) {
                $comments = get_comments(array('post_id' => $id));
                foreach ($comments as $comment) {
                    if (self::scan_comment($comment, $flags)) {
                        return TRUE;
                    }
                }
            }
        }
        return FALSE;
    }

    public static function scan_legacy_post($post, $scan_comments = TRUE) {
        self::init_legacy_tag_bits();
        return self::scan_post($post, $scan_comments, self::$legacy_flags);
    }

    /**
     * Returns TRUE if the comment contains a Crayon tag
     */
    public static function scan_comment($comment, $flags = NULL) {
        if ($flags === NULL) {
            self::init_tags_regex();
        }
        $args = array(
            'ignore' => FALSE,
            'flags' => $flags,
            'skip_setting_check' => TRUE,
            'just_check' => TRUE
        );
        $content = apply_filters('get_comment_text', $comment->comment_content, $comment);
        $captures = self::capture_crayons($comment->comment_ID, $content, array(), $args);
        return $captures['has_captured'];
    }

    public static function install() {
        self::refresh_posts();
        self::update();
    }

    public static function uninstall() {

    }

    public static function update() {
        global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
        Urvanov_Syntax_Highlighter_Settings_WP::load_settings(TRUE);
        $settings = Urvanov_Syntax_Highlighter_Settings_WP::get_settings();
        if ($settings === NULL || !isset($settings[Urvanov_Syntax_Highlighter_Settings::VERSION])) {
            return;
        }

        $version = $settings[Urvanov_Syntax_Highlighter_Settings::VERSION];

        // Only upgrade if the version differs
        if ($version != $URVANOV_SYNTAX_HIGHLIGHTER_VERSION) {
            $defaults = Urvanov_Syntax_Highlighter_Settings::get_defaults_array();
            $touched = FALSE;

            // Upgrade database and settings
            
            UrvanovSyntaxHighlighterLog::log($version, 'Upgrading database and settings');
            if (version_compare($version, '1.7.21') < 0) {
                $settings[Urvanov_Syntax_Highlighter_Settings::SCROLL] = $defaults[Urvanov_Syntax_Highlighter_Settings::SCROLL];
                $touched = TRUE;
            }

            if (version_compare($version, '1.7.23') < 0 && $settings[Urvanov_Syntax_Highlighter_Settings::FONT] == 'theme-font') {
                $settings[Urvanov_Syntax_Highlighter_Settings::FONT] = $defaults[Urvanov_Syntax_Highlighter_Settings::FONT];
                $touched = TRUE;
            }

            if (version_compare($version, '1.14') < 0) {
                UrvanovSyntaxHighlighterLog::syslog("Updated to v1.14: Font size enabled");
                $settings[Urvanov_Syntax_Highlighter_Settings::FONT_SIZE_ENABLE] = TRUE;
            }

            if (version_compare($version, '1.17') < 0) {
                $settings[Urvanov_Syntax_Highlighter_Settings::HIDE_HELP] = FALSE;
            }

            // Save new version
            $settings[Urvanov_Syntax_Highlighter_Settings::VERSION] = $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
            Urvanov_Syntax_Highlighter_Settings_WP::save_settings($settings);
            UrvanovSyntaxHighlighterLog::syslog("Updated from $version to $URVANOV_SYNTAX_HIGHLIGHTER_VERSION");

            // Refresh to show new settings
            header('Location: ' . UrvanovSyntaxHighlighterUtil::current_url());
            exit();
        }
    }

    public static function basename() {
        return plugin_basename(__FILE__);
    }

    public static function pre_excerpt($e) {
        UrvanovSyntaxHighlighterLog::debug('pre_excerpt');
        self::$is_excerpt = TRUE;
        return $e;
    }

    public static function post_excerpt($e) {
        UrvanovSyntaxHighlighterLog::debug('post_excerpt');
        self::$is_excerpt = FALSE;
        $e = self::the_content($e);
        return $e;
    }

    public static function post_get_excerpt($e) {
        UrvanovSyntaxHighlighterLog::debug('post_get_excerpt');
        self::$is_excerpt = FALSE;
        return $e;
    }

    /**
     * Converts Crayon tags found in WP to <pre> form.
     * XXX: This will alter blog content, so backup before calling.
     * XXX: Do NOT call this while updating posts or comments, it may cause an infinite loop or fail.
     * @param $encode Whether to detect missing "decode" attribute and encode html entities in the code.
     */
    public static function convert_tags($encode = FALSE) {
        $crayon_posts = Urvanov_Syntax_Highlighter_Settings_WP::load_legacy_posts();
        if ($crayon_posts === NULL) {
            return;
        }

        self::init_legacy_tag_bits();
        $args = array(
            'callback' => 'Urvanov_Syntax_Highlighter_Plugin::capture_replace_pre',
            'callback_extra_args' => array('encode' => $encode),
            'ignore' => FALSE,
            'preserve_atts' => TRUE,
            'flags' => self::$legacy_flags,
            'skip_setting_check' => TRUE
        );

        foreach ($crayon_posts as $postID) {
            $post = get_post($postID);
            $post_content = $post->post_content;
            $post_captures = self::capture_crayons($postID, $post_content, array(), $args);

            if ($post_captures['has_captured'] === TRUE) {
                $post_obj = array();
                $post_obj['ID'] = $postID;
                $post_obj['post_content'] = addslashes($post_captures['content']);
                wp_update_post($post_obj);
                UrvanovSyntaxHighlighterLog::syslog("Converted Crayons in post ID $postID to pre tags", 'CONVERT');
            }

            if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::COMMENTS)) {
                $comments = get_comments(array('post_id' => $postID));
                foreach ($comments as $comment) {
                    $commentID = $comment->comment_ID;
                    $comment_captures = self::capture_crayons($commentID, $comment->comment_content, array(Urvanov_Syntax_Highlighter_Settings::DECODE => TRUE), $args);

                    if ($comment_captures['has_captured'] === TRUE) {
                        $comment_obj = array();
                        $comment_obj['comment_ID'] = $commentID;
                        $comment_obj['comment_content'] = $comment_captures['content'];
                        wp_update_comment($comment_obj);
                        UrvanovSyntaxHighlighterLog::syslog("Converted Crayons in post ID $postID, comment ID $commentID to pre tags", 'CONVERT');
                    }
                }
            }
        }

        self::refresh_posts();
    }

    // Used as capture_crayons callback
    public static function capture_replace_pre($capture, $original, $id, $is_inline, $wp_content, $args = array()) {
        $code = $capture['code'];
        $oldAtts = $capture['atts'];
        $newAtts = array();
        $encode = isset($args['encode']) ? $args['encode'] : FALSE;
        if (!isset($oldAtts[Urvanov_Syntax_Highlighter_Settings::DECODE]) && $encode) {
            // Encode the content, since no decode information exists.
            $code = UrvanovSyntaxHighlighterUtil::htmlentities($code);
        }
        // We always set decode=1 irrespectively - so at this point the code is assumed to be encoded
        $oldAtts[Urvanov_Syntax_Highlighter_Settings::DECODE] = TRUE;
        $newAtts['class'] = UrvanovSyntaxHighlighterUtil::html_attributes($oldAtts, Urvanov_Syntax_Highlighter_Global_Settings::val_str(Urvanov_Syntax_Highlighter_Settings::ATTR_SEP), '');
        return str_replace($original, UrvanovSyntaxHighlighterUtil::html_element('pre', $code, $newAtts), $wp_content);
    }

    // Add TinyMCE to comments
    public static function tinymce_comment_enable($args) {
        if (function_exists('wp_editor')) {
            ob_start();
            wp_editor('', 'comment', array('tinymce'));
            $args['comment_field'] = ob_get_clean();
        }
        return $args;
    }

    public static function allowed_tags() {
        global $allowedtags;
        $tags = array('pre', 'span', 'code');
        foreach ($tags as $tag) {
            $current_atts = isset($allowedtags[$tag]) ? $allowedtags[$tag] : array();
            // TODO data-url isn't recognised by WP
            $new_atts = array('class' => TRUE, 'title' => TRUE, 'data-url' => TRUE);
            $allowedtags[$tag] = array_merge($current_atts, $new_atts);
        }
    }

}

// Only if WP is loaded
if (defined('ABSPATH')) {
    if (!is_admin()) {
        // Filters and Actions

	// XXX add_filter('init', 'Urvanov_Syntax_Highlighter_Plugin::init');
	// XXX moved load_textdomain to after_setup_theme, so that it loads after init as it is required in WP 6.7
	add_filter('init', 'Urvanov_Syntax_Highlighter_Global::load_plugin_textdomain');

        Urvanov_Syntax_Highlighter_Settings_WP::load_settings(TRUE);
        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::MAIN_QUERY)) {
            add_action('wp', 'Urvanov_Syntax_Highlighter_Plugin::wp', 100);
        } else {
            add_filter('the_posts', 'Urvanov_Syntax_Highlighter_Plugin::the_posts', 100);
        }

        // XXX Some themes like to play with the content, make sure we replace after they're done
        add_filter('the_content', 'Urvanov_Syntax_Highlighter_Plugin::the_content', 100);

        // Highlight bbPress content
        add_filter('bbp_get_reply_content', 'Urvanov_Syntax_Highlighter_Plugin::highlight', 100);
        add_filter('bbp_get_topic_content', 'Urvanov_Syntax_Highlighter_Plugin::highlight', 100);
        add_filter('bbp_get_forum_content', 'Urvanov_Syntax_Highlighter_Plugin::highlight', 100);
        add_filter('bbp_get_topic_excerpt', 'Urvanov_Syntax_Highlighter_Plugin::highlight', 100);

        // Allow tags
        add_action('init', 'Urvanov_Syntax_Highlighter_Plugin::allowed_tags', 11);

        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::COMMENTS)) {
            /* XXX This is called first to match Crayons, then higher priority replaces after other filters.
             Prevents Crayon from being formatted by the filters, and also keeps original comment formatting. */
            add_filter('comment_text', 'Urvanov_Syntax_Highlighter_Plugin::pre_comment_text', 1, 3);
            add_filter('comment_text', 'Urvanov_Syntax_Highlighter_Plugin::comment_text', 100, 3);
        }

        // This ensures Crayons are not formatted by WP filters. Other plugins should specify priorities between 1 and 100.
        add_filter('get_the_excerpt', 'Urvanov_Syntax_Highlighter_Plugin::pre_excerpt', 1);
        add_filter('get_the_excerpt', 'Urvanov_Syntax_Highlighter_Plugin::post_get_excerpt', 100);
        add_filter('the_excerpt', 'Urvanov_Syntax_Highlighter_Plugin::post_excerpt', 100);

        add_action('template_redirect', 'Urvanov_Syntax_Highlighter_Plugin::wp_head', 0);

        if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::TAG_EDITOR_FRONT)) {
            add_filter('comment_form_defaults', 'Urvanov_Syntax_Highlighter_Plugin::tinymce_comment_enable');
        }
    } else {
        // Update between versions
        UrvanovSyntaxHighlighterLog::log("Calling update plugin...");
        Urvanov_Syntax_Highlighter_Plugin::update();
        // For marking a post as containing a Crayon
        add_action('update_post', 'Urvanov_Syntax_Highlighter_Plugin::save_post', 10, 2);
        add_action('save_post', 'Urvanov_Syntax_Highlighter_Plugin::save_post', 10, 2);
        add_filter('wp_insert_post_data', 'Urvanov_Syntax_Highlighter_Plugin::filter_post_data', '99', 2);
    }
    register_activation_hook(__FILE__, 'Urvanov_Syntax_Highlighter_Plugin::install');
    register_deactivation_hook(__FILE__, 'Urvanov_Syntax_Highlighter_Plugin::uninstall');
    if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::COMMENTS)) {
        add_action('comment_post', 'Urvanov_Syntax_Highlighter_Plugin::save_comment', 10, 2);
        add_action('edit_comment', 'Urvanov_Syntax_Highlighter_Plugin::save_comment', 10, 2);
    }
    add_filter('init', 'Urvanov_Syntax_Highlighter_Plugin::init_ajax');
    add_action( 'rest_after_insert_post', 'Urvanov_Syntax_Highlighter_Plugin::rest_after_insert', 10, 3 );
}

function register_urvanov_syntax_highlighter_gutenberg_block() {
    global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
    wp_register_style('urvanov-syntax-highlighter-editor',
        plugins_url(URVANOV_SYNTAX_HIGHLIGHTER_EDITOR_CSS, __FILE__),
        array( 'wp-edit-blocks' ), $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
    
    register_block_type( 'urvanov-syntax-highlighter/code-block', array(
        'editor_style' => 'urvanov-syntax-highlighter-editor'//,
        //'editor_script' => 'crayon_te_js',
    ) );
}

add_action( 'init', 'register_urvanov_syntax_highlighter_gutenberg_block' );

?>
