<?php

require_once ('../global.php');
require_once (URVANOV_SYNTAX_HIGHLIGHTER_HIGHLIGHTER_PHP);

// These will depend on your framework
Urvanov_Syntax_Highlighter_Global_Settings::site_url('http://localhost/crayon/wp-content/plugins/crayon-syntax-highlighter/');
Urvanov_Syntax_Highlighter_Global_Settings::site_path(dirname(__FILE__));
Urvanov_Syntax_Highlighter_Global_Settings::plugin_path('http://localhost/crayon/wp-content/plugins/crayon-syntax-highlighter/');

// Should be in the header
crayon_resources();

$crayon = new Urvanov_Syntax_Highlighter();
$crayon->code('some code');
$crayon->language('php');
$crayon->title('the title');
$crayon->marked('1-2');
$crayon->is_inline(FALSE);

// Settings
$settings = array(
	// Just regular settings
	Urvanov_Syntax_Highlighter_Settings::NUMS => FALSE,
	Urvanov_Syntax_Highlighter_Settings::TOOLBAR => TRUE,
	// Enqueue supported only for WP
	Urvanov_Syntax_Highlighter_Settings::ENQUEUE_THEMES => FALSE,
	Urvanov_Syntax_Highlighter_Settings::ENQUEUE_FONTS => FALSE);
$settings = Urvanov_Syntax_Highlighter_Settings::smart_settings($settings);
$crayon->settings($settings);

// Print the Crayon
$crayon_formatted = $crayon->output(TRUE, FALSE);
echo $crayon_formatted;

// Utility Functions

function crayon_print_style($id, $url, $version) {
	echo '<link id="',$id,'" href="',$url,'?v=',$version,'" type="text/css" rel="stylesheet" />',"\n";
}

function crayon_print_script($id, $url, $version) {
	echo '<script id="',$id,'" src="',$url,'?v=',$version,'" type="text/javascript"></script>',"\n";
}

function crayon_resources() {
	global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
	$plugin_url = Urvanov_Syntax_Highlighter_Global_Settings::plugin_path();
	// jQuery only needed once! Don't have two jQuerys, so remove if you've already got one in your header :)
	crayon_print_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js', $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
	crayon_print_style('crayon-style', $plugin_url.URVANOV_SYNTAX_HIGHLIGHTER_STYLE, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
	crayon_print_script('crayon_util_js', $plugin_url.URVANOV_SYNTAX_HIGHLIGHTER_JS_UTIL, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
	crayon_print_script('crayon-js', $plugin_url.URVANOV_SYNTAX_HIGHLIGHTER_JS, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
	crayon_print_script('crayon-jquery-popup', $plugin_url.URVANOV_SYNTAX_HIGHLIGHTER_JQUERY_POPUP, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
}

?>