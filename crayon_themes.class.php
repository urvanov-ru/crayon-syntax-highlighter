<?php
require_once ('global.php');
require_once (URVANOV_SYNTAX_HIGHLIGHTER_RESOURCE_PHP);

/* Manages themes once they are loaded. */
class CrayonThemes extends Urvanov_Syntax_Highlighter_User_Resource_Collection {
    // Properties and Constants ===============================================

    const DEFAULT_THEME = 'classic';
    const DEFAULT_THEME_NAME = 'Classic';
    const CSS_PREFIX = '.crayon-theme-';

    private $printed_themes = array();

    // Methods ================================================================

    function __construct() {
        $this->set_default(self::DEFAULT_THEME, self::DEFAULT_THEME_NAME);
        $this->directory(URVANOV_SYNTAX_HIGHLIGHTER_THEME_PATH);
        $this->relative_directory(URVANOV_SYNTAX_HIGHLIGHTER_THEME_DIR);
        $this->extension('css');

        CrayonLog::debug("Setting theme directories");
        $upload = CrayonGlobalSettings::upload_path();
        if ($upload) {
            $this->user_directory($upload . URVANOV_SYNTAX_HIGHLIGHTER_THEME_DIR);
            if (!is_dir($this->user_directory())) {
                CrayonGlobalSettings::mkdir($this->user_directory());
                CrayonLog::debug($this->user_directory(), "THEME USER DIR");
            }
        } else {
            CrayonLog::syslog("Upload directory is empty: " . $upload . " cannot load themes.");
        }
        CrayonLog::debug($this->directory());
        CrayonLog::debug($this->user_directory());
	}

    // XXX Override
    public function filename($id, $user = NULL) {
        return CrayonUtil::path_slash($id) . parent::filename($id, $user);
    }

}

?>