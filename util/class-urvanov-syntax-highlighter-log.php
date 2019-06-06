<?php
require_once (URVANOV_SYNTAX_HIGHLIGHTER_ROOT_PATH . 'crayon_settings.class.php');

/* Manages logging variable values to the log file. */
class UrvanovSyntaxHighlighterLog {
	private static $file = NULL;

	// Logs a variable value to a log file

	public static function log($var = NULL, $title = '', $trim_url = TRUE) {
		if ($var === NULL) {
			// Return log

			if (($log = CrayonUtil::file(URVANOV_SYNTAX_HIGHLIGHTER_LOG_FILE)) !== FALSE) {
				return $log;
			} else {
				return '';
			}
		} else {
			try {
				if (self::$file == NULL) {
					self::$file = @fopen(URVANOV_SYNTAX_HIGHLIGHTER_LOG_FILE, 'a+');

					if (self::$file) {
						$header = /*URVANOV_SYNTAX_HIGHLIGHTER_DASH .*/ URVANOV_SYNTAX_HIGHLIGHTER_NL . '[Crayon Syntax Highlighter Log Entry - ' . date('g:i:s A - d M Y') . ']' . URVANOV_SYNTAX_HIGHLIGHTER_NL .
							/*URVANOV_SYNTAX_HIGHLIGHTER_DASH .*/ URVANOV_SYNTAX_HIGHLIGHTER_NL;
						fwrite(self::$file, $header);
					} else {
						return;
					}
				}
				// Capture variable dump
                $buffer = trim(strip_tags(var_export($var, true)));
				$title = (!empty($title) ? " [$title]" : '');

				// Remove absolute path to plugin directory from buffer
				if ($trim_url) {
					$buffer = CrayonUtil::path_rel($buffer);
				}
				$write = $title . ' ' . $buffer . URVANOV_SYNTAX_HIGHLIGHTER_NL /* . URVANOV_SYNTAX_HIGHLIGHTER_LINE . URVANOV_SYNTAX_HIGHLIGHTER_NL*/;
				
				// If we exceed max file size, truncate file first
				if (filesize(URVANOV_SYNTAX_HIGHLIGHTER_LOG_FILE) + strlen($write) > URVANOV_SYNTAX_HIGHLIGHTER_LOG_MAX_SIZE) {
					ftruncate(self::$file, 0);
					fwrite(self::$file, 'The log has been truncated since it exceeded ' . URVANOV_SYNTAX_HIGHLIGHTER_LOG_MAX_SIZE .
						' bytes.' . URVANOV_SYNTAX_HIGHLIGHTER_NL . /*URVANOV_SYNTAX_HIGHLIGHTER_LINE .*/ URVANOV_SYNTAX_HIGHLIGHTER_NL);
				}
				clearstatcache();
				fwrite(self::$file, $write, URVANOV_SYNTAX_HIGHLIGHTER_LOG_MAX_SIZE);
			} catch (Exception $e) {
				// Ignore fatal errors during logging
			}
		}
	}

	// Logs system-wide only if global settings permit

	public static function syslog($var = NULL, $title = '', $trim_url = TRUE) {
		if (Urvanov_Syntax_Highlighter_Global_Settings::val(Urvanov_Syntax_Highlighter_Settings::ERROR_LOG_SYS)) {
			$title = (empty($title)) ? 'SYSTEM LOG' : $title;
			self::log($var, $title, $trim_url);
		}
	}
	
	public static function debug($var = NULL, $title = '', $trim_url = TRUE) {
		if (URVANOV_SYNTAX_HIGHLIGHTER_DEBUG) {
			$title = (empty($title)) ? 'DEBUG' : $title;
			self::log($var, $title, $trim_url);
		}
	}

	public static function clear() {
		if (!@unlink(URVANOV_SYNTAX_HIGHLIGHTER_LOG_FILE)) {
			// Will result in nothing if we can't log

			self::log('The log could not be cleared', 'Log Clear');
		}
		self::$file = NULL; // Remove file handle

	}

	public static function email($to, $from = NULL) {
		if (($log_contents = CrayonUtil::file(URVANOV_SYNTAX_HIGHLIGHTER_LOG_FILE)) !== FALSE) {
			$headers = $from ? 'From: ' . $from : '';
			$result = @mail($to, 'Crayon Syntax Highlighter Log', $log_contents, $headers);
			self::log('The log was emailed to the admin.', 'Log Email');
		} else {
			// Will result in nothing if we can't email

			self::log("The log could not be emailed to $to.", 'Log Email');
		}
	}
}
?>