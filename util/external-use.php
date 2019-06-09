<?php

require_once ('../class-urvanov-syntax-highlighter-global.php');
require_once (URVANOV_SYNTAX_HIGHLIGHTER_HIGHLIGHTER_PHP);

// These will depend on your framework
Urvanov_Syntax_Highlighter_Global_Settings::site_url('http://localhost/urvanov-syntax-highlighter/wp-content/plugins/urvanov-syntax-highlighter/');
Urvanov_Syntax_Highlighter_Global_Settings::site_path(dirname(__FILE__));
Urvanov_Syntax_Highlighter_Global_Settings::plugin_path('http://localhost/urvanov-syntax-highlighter/wp-content/plugins/urvanov-syntax-highlighter/');

// Should be in the header
urvanov_syntax_highlighter_resources();

$urvanov_syntax_highlighter = new Urvanov_Syntax_Highlighter();
$urvanov_syntax_highlighter->code('some code');
$urvanov_syntax_highlighter->language('php');
$urvanov_syntax_highlighter->title('the title');
$urvanov_syntax_highlighter->marked('1-2');
$urvanov_syntax_highlighter->is_inline(FALSE);

// Settings
$settings = array(
	// Just regular settings
	Urvanov_Syntax_Highlighter_Settings::NUMS => FALSE,
	Urvanov_Syntax_Highlighter_Settings::TOOLBAR => TRUE,
	// Enqueue supported only for WP
	Urvanov_Syntax_Highlighter_Settings::ENQUEUE_THEMES => FALSE,
	Urvanov_Syntax_Highlighter_Settings::ENQUEUE_FONTS => FALSE);
$settings = Urvanov_Syntax_Highlighter_Settings::smart_settings($settings);
$urvanov_syntax_highlighter->settings($settings);

// Print the Urvanov Syntax Highlighter
$urvanov_syntax_highlighter_formatted = $urvanov_syntax_highlighter->output(TRUE, FALSE);
echo $urvanov_syntax_highlighter_formatted;

// Utility Functions

function urvanov_syntax_highlighter_print_style($id, $url, $version) {
	echo '<link id="',$id,'" href="',$url,'?v=',$version,'" type="text/css" rel="stylesheet" />',"\n";
}

function urvanov_syntax_highlighter_print_script($id, $url, $version) {
	echo '<script id="',$id,'" src="',$url,'?v=',$version,'" type="text/javascript"></script>',"\n";
}

function urvanov_syntax_highlighter_resources() {
	global $URVANOV_SYNTAX_HIGHLIGHTER_VERSION;
	$plugin_url = Urvanov_Syntax_Highlighter_Global_Settings::plugin_path();
	// jQuery only needed once! Don't have two jQuerys, so remove if you've already got one in your header :)
	urvanov_syntax_highlighter_print_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js', $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
	urvanov_syntax_highlighter_print_style('urvanov-syntax-highlighter-style', $plugin_url.URVANOV_SYNTAX_HIGHLIGHTER_STYLE, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
	urvanov_syntax_highlighter_print_script('urvanov_syntax_highlighter_util_js', $plugin_url.URVANOV_SYNTAX_HIGHLIGHTER_JS_UTIL, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
	urvanov_syntax_highlighter_print_script('urvanov-syntax-highlighter-js', $plugin_url.URVANOV_SYNTAX_HIGHLIGHTER_JS, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
	urvanov_syntax_highlighter_print_script('urvanov-syntax-highlighter-jquery-popup', $plugin_url.URVANOV_SYNTAX_HIGHLIGHTER_JQUERY_POPUP, $URVANOV_SYNTAX_HIGHLIGHTER_VERSION);
}

?>