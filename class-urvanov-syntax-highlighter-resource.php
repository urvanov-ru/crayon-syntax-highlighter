<?php
require_once ('class-urvanov-syntax-highlighter-global.php');
require_once (URVANOV_SYNTAX_HIGHLIGHTER_LANGS_PHP);
require_once (URVANOV_SYNTAX_HIGHLIGHTER_THEMES_PHP);
require_once (URVANOV_SYNTAX_HIGHLIGHTER_FONTS_PHP);

// Old name: CrayonResources
class Urvanov_Syntax_Highlighter_Resources {
	private static $langs = NULL;
	private static $themes = NULL;
	private static $fonts = NULL;

	private function __construct() {}

	public static function langs() {
        if (self::$langs == NULL) {
            self::$langs = new Urvanov_Syntax_Highlighter_Langs();
        }
		return self::$langs;
	}

	public static function themes() {
        if (self::$themes == NULL) {
            self::$themes = new Urvanov_Syntax_Highlighter_Themes();
        }
		return self::$themes;
	}

	public static function fonts() {
        if (self::$fonts == NULL) {
            self::$fonts = new Urvanov_Syntax_Highlighter_Fonts();
        }
		return self::$fonts;
	}
}

// Old name: CrayonResourceCollection
class Urvanov_Syntax_Highlighter_Resource_Collection {
	// Properties and Constants ===============================================

	// Loaded resources

	private $collection = array();
	// Loading state

	private $state = self::UNLOADED;
	// Directory containing resources

	private $dir = '';
	private $default_id = '';
	private $default_name = '';
	const UNLOADED = -1;
	const LOADING = 0;
	const LOADED = 1;

	// Methods ================================================================

	/* Override in subclasses. Returns the absolute path for a given resource. Does not check for its existence. */
	public function path($id) {
		return '';
	}

	/* Verifies a resource exists. */
	public function exists($id) {
		return file_exists($this->path($id));
	}

	/* Load all the available languages. Doesn't parse them for their names and regex. */
	public function load() {
		// Load only once

		if (!$this->is_state_unloaded()) {
			return;
		}
		$this->state = self::LOADING;
		$this->load_process();
		$this->state = self::LOADED;
	}

	public function load_resources($dir = NULL) {
        if ($dir === NULL) {
            $dir = $this->dir;
        }

		if (!$this->is_state_loading()) {
            // Load only once
			return;
		}
		try {
			// Look in directory for resources

			if (!is_dir($dir)) {
				UrvanovSyntaxHighlighterLog::syslog('The resource directory is missing, should be at \'' . $dir . '\'.');
			} else if (($handle = @opendir($dir)) != FALSE) {
				// Loop over directory contents
				while (($file = readdir($handle)) !== FALSE) {
					if ($file != "." && $file != "..") {
						// Check if $file is directory, remove extension when checking for existence.

						if (!is_dir($dir . $file)) {
							$file = UrvanovSyntaxHighlighterUtil::path_rem_ext($file);
						}
						if ($this->exists($file)) {
							$this->add_resource($this->resource_instance($file));
						}
					}
				}
				closedir($handle);
			}
		} catch (Exception $e) {
			UrvanovSyntaxHighlighterLog::syslog('An error occured when trying to load resources: ' . $e->getFile() . $e->getLine());
		}
	}

	/* Override in subclasses. */
	public function load_process() {
		if (!$this->is_state_loading()) {
			return;
		}
		$this->load_resources();
		$this->add_default();
	}

	/* Override in subclasses */
	public function add_default() {
		if (!$this->is_state_loading()) {
			return FALSE;
		} else if (!$this->is_loaded($this->default_id)) {
			UrvanovSyntaxHighlighterLog::syslog('The default resource could not be loaded from \'' . $this->dir . '\'.');
			// Add the default, but it will not be functionable

			$default = $this->resource_instance($this->default_id, $this->default_name);
			$this->add($this->default_id, $default);
			return TRUE;
		}
		return FALSE;
	}

	/* Returns the default resource */
	public function set_default($id, $name) {
		$this->default_id = $id;
		$this->default_name = $name;
	}

	/* Returns the default resource */
	public function get_default() {
		return $this->get($this->default_id);
	}

	/* Override in subclasses to create subclass object if needed */
	public function resource_instance($id, $name = NULL) {
		return new Urvanov_Syntax_Highlighter_Resource($id, $name);
	}

	public function add($id, $resource) {
		if (is_string($id) && !empty($id)) {
			$this->collection[$id] = $resource;
			asort($this->collection);
            UrvanovSyntaxHighlighterLog::debug('Added resource: ' . $this->path($id));
		} else {
            UrvanovSyntaxHighlighterLog::syslog('Could not add resource: ', $id);
        }
	}

	public function add_resource($resource) {
		$this->add($resource->id(), $resource);
	}

	public function remove($name) {
		if (is_string($name) && !empty($name) && array_key_exists($name, $this->collection)) {
			unset($this->collection[$name]);
		}
	}

	public function remove_all() {
		$this->collection = array();
	}

	/* Returns the resource for the given id or NULL if it can't be found */
	public function get($id = NULL) {
		$this->load();
		if ($id === NULL) {
			return $this->collection;
		} else if (is_string($id) && $this->is_loaded($id)) {
			return $this->collection[$id];
		}
		return NULL;
	}

	public function get_array() {
		$array = array();
		foreach ($this->get() as $resource) {
			$array[$resource->id()] = $resource->name();
		}
		return $array;
	}

	public function is_loaded($id) {
		if (is_string($id)) {
			return array_key_exists($id, $this->collection);
		}
		return FALSE;
	}

	public function get_state() {
		return $this->state;
	}

	public function is_state_loaded() {
		return $this->state == self::LOADED;
	}

	public function is_state_loading() {
		return $this->state == self::LOADING;
	}

	public function is_state_unloaded() {
		return $this->state == self::UNLOADED;
	}

	public function directory($dir = NULL) {
        if ($dir === NULL) {
            return $this->dir;
        } else {
            $this->dir = UrvanovSyntaxHighlighterUtil::path_slash($dir);
        }
	}

	public function url($id) {
		return '';
	}

	public function get_css($id, $ver = NULL) {
		$resource = $this->get($id);
		return '<link rel="stylesheet" type="text/css" href="' . $this->url($resource->id()) . ($ver ? "?ver=$ver" : '') . '" />' . URVANOV_SYNTAX_HIGHLIGHTER_NL;
	}
}

// Old name: CrayonUsedResourceCollection
class Urvanov_Syntax_Highlighter_Used_Resource_Collection extends Urvanov_Syntax_Highlighter_Resource_Collection {

	// Checks if any resoruces are being used
	public function is_used($id = NULL) {
		if ($id === NULL) {
			foreach ($this->get() as $resource) {
				if ($resource->used()) {
					return TRUE;
				}
			}
			return FALSE;
		} else {
			$resource = $this->get($id);
			if (!$resource) {
				return FALSE;
			} else {
				return $resource->used();
			}
		}
	}

	public function set_used($id, $value = TRUE) {
		$resource = $this->get($id);
		if ($resource !== NULL && !$resource->used()) {
			$resource->used($value == TRUE);
			return TRUE;
		}
		return FALSE;
	}

	public function get_used() {
		$used = array();
		foreach ($this->get() as $resource) {
			if ($resource->used()) {
				$used[] = $resource;
			}
		}
		return $used;
	}

	// XXX Override
	public function resource_instance($id, $name = NULL) {
		return new Urvanov_Syntax_Highlighter_Used_Resource($id, $name);
	}

	public function get_used_css() {
		$used = $this->get_used();
		$css = array();
		foreach ($used as $resource) {
			$url = $this->url($resource->id());
			$css[$resource->id()] = $url;
		}
		return $css;
	}
}

// Old name: CrayonUserResourceCollection
class Urvanov_Syntax_Highlighter_User_Resource_Collection extends Urvanov_Syntax_Highlighter_Used_Resource_Collection {
    private $user_dir = '';
    private $curr_dir = NULL;
    // TODO better to use a base dir and relative
    private $relative_directory = NULL;
    // TODO move this higher up inheritance
    private $extension = '';

    // XXX Override
    public function resource_instance($id, $name = NULL) {
        $resource = $this->create_user_resource_instance($id, $name);
        $resource->user($this->curr_dir == $this->user_directory());
        return $resource;
    }

    public function create_user_resource_instance($id, $name = NULL) {
        return new Urvanov_Syntax_Highlighter_User_Resource($id, $name);
    }

    public function user_directory($dir = NULL) {
        if ($dir === NULL) {
            return $this->user_dir;
        } else {
            $this->user_dir = UrvanovSyntaxHighlighterUtil::path_slash($dir);
        }
    }

    public function relative_directory($relative_directory = NULL) {
        if ($relative_directory == NULL) {
            return $this->relative_directory;
        }
        $this->relative_directory = $relative_directory;
    }

    public function extension($extension = NULL) {
        if ($extension == NULL) {
            return $this->extension;
        }
        $this->extension = $extension;
    }

    public function load_resources($dir = NULL) {
        $this->curr_dir = $this->directory();
        parent::load_resources($this->curr_dir);
        $this->curr_dir = $this->user_directory();
        parent::load_resources($this->curr_dir);
        $this->curr_dir = NULL;
    }

    public function current_directory() {
        return $this->curr_dir;
    }

    public function dir_is_user($id, $user = NULL) {
        if ($user === NULL) {
            if ($this->is_state_loading()) {
                // We seem to be loading resources - use current directory
                $user = $this->current_directory() == $this->user_directory();
            } else {
                $theme = $this->get($id);
                if ($theme) {
                    $user = $theme->user();
                } else {
                    $user = FALSE;
                }
            }
        }
        return $user;
    }

    public function dirpath($user = NULL) {
        $path = $user ? $this->user_directory() : $this->directory();
        return UrvanovSyntaxHighlighterUtil::path_slash($path);
    }

    public function dirpath_for_id($id, $user = NULL) {
        $user = $this->dir_is_user($id, $user);
        return $this->dirpath($user) . $id;
    }

    public function dirurl($user = NULL) {
        $path = $user ? Urvanov_Syntax_Highlighter_Global_Settings::upload_url() : Urvanov_Syntax_Highlighter_Global_Settings::plugin_path();
        return UrvanovSyntaxHighlighterUtil::path_slash($path . $this->relative_directory());
    }

    // XXX Override
    public function path($id, $user = NULL) {
        $user = $this->dir_is_user($id, $user);
        return $this->dirpath($user) . $this->filename($id, $user);
    }

    // XXX Override
    public function url($id, $user = NULL) {
        $user = $this->dir_is_user($id, $user);
        return $this->dirurl($user) . $this->filename($id, $user);
    }

    public function filename($id, $user = NULL) {
        return "$id.$this->extension";
    }

}

// Old name: CrayonResource
class Urvanov_Syntax_Highlighter_Resource {
	private $id = '';
	private $name = '';

	function __construct($id, $name = NULL) {
		$id = $this->clean_id($id);
		UrvanovSyntaxHighlighterUtil::str($this->id, $id);
		( empty($name) ) ? $this->name( self::clean_name($this->id) ) : $this->name($name);
	}

	function __tostring() {
		return $this->name;
	}

	function id() {
		return $this->id;
	}

	function name($name = NULL) {
		if ($name === NULL) {
			return $this->name;
		} else {
			$this->name = $name;
		}
	}

	function clean_id($id) {
        $id = UrvanovSyntaxHighlighterUtil::space_to_hyphen( strtolower(trim($id)) );
        return preg_replace('#[^\w-]#msi', '', $id);
	}

	public static function clean_name($id) {
		$id = UrvanovSyntaxHighlighterUtil::hyphen_to_space( strtolower(trim($id)) );
		return UrvanovSyntaxHighlighterUtil::ucwords($id);
	}

}

// Old name: CrayonUsedResource
class Urvanov_Syntax_Highlighter_Used_Resource extends Urvanov_Syntax_Highlighter_Resource {
    // Keeps track of usage
	private $used = FALSE;

	function used($used = NULL) {
		if ($used === NULL) {
			return $this->used;
		} else {
			$this->used = ($used ? TRUE : FALSE);
		}
	}
}

// Old name: CrayonUserResource
class Urvanov_Syntax_Highlighter_User_Resource extends Urvanov_Syntax_Highlighter_Used_Resource {
    // Keeps track of user modifications
    private $user = FALSE;

    function user($user = NULL) {
        if ($user === NULL) {
            return $this->user;
        } else {
            $this->user = ($user ? TRUE : FALSE);
        }
    }
}

// Old name: CrayonVersionResource
class Urvanov_Syntax_Highlighter_Version_Resource extends Urvanov_Syntax_Highlighter_User_Resource {
    // Adds version
	private $version = '';

	function __construct($id, $name = NULL, $version = NULL) {
		parent::__construct($id, $name);
		$this->version($version);
	}

	function version($version = NULL) {
		if ($version === NULL) {
			return $this->version;
		} else if (is_string($version)) {
			$this->version = $version;
		}
	}
}

?>