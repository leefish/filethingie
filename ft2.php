<?php
/**
 * @file
 * File Thingie - Andreas Haugstrup Pedersen <andreas@solitude.dk>
 * The newest version of File Thingie can be found at <http://www.solitude.dk/filethingie/>
*
* Copyright (c) 2003-2012 Andreas Haugstrup Pedersen
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
* LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
* OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
* WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

# Version information #
define("VERSION", "2.5.7"); // Current version of File Thingie.
define("INSTALL", "EXPANDED"); // Type of File Thingie installation. EXPANDED or SIMPLE.
define("MUTEX", $_SERVER['PHP_SELF']);

$ft = array();
$ft['settings'] = array();
$ft['groups'] = array();
$ft['users'] = array();
$ft['plugins'] = array();

/**
 * Check if a login cookie is valid.
 *
 * @param $c
 *   The login cookie from $_COOKIE.
 * @return The username of the cookie user. FALSE if cookie is not valid.
 */
function ft_check_cookie($c) {
  global $ft;
  // Check primary user.
  if ($c == md5(USERNAME.PASSWORD)) {
    return USERNAME;
  }
  // Check users array.
	if (is_array($ft['users']) && sizeof($ft['users']) > 0) {
	  // Loop through users.
	  foreach ($ft['users'] as $user => $a) {
	    if ($c == md5($user.$a['password'])) {
	      return $user;
	    }
	  }
	}
	return FALSE;
}

/**
 * Check if directory is on the blacklist.
 *
 * @param $dir
 *   Directory path.
 * @return TRUE if directory is not blacklisted.
 */
function ft_check_dir($dir) {
	// Check against folder blacklist.
	if (FOLDERBLACKLIST != "") {
		$blacklist = explode(" ", FOLDERBLACKLIST);
		foreach ($blacklist as $c) {
      if (substr($dir, 0, strlen(ft_get_root().'/'.$c)) == ft_get_root().'/'.$c) {
        return FALSE;
      }
		}
		return TRUE;
	} else {
		return TRUE;
	}
}

/**
 * Check if file actions are allowed in the current directory.
 *
 * @return TRUE is file actions are allowed.
 */
function ft_check_fileactions() {
  if (FILEACTIONS === TRUE) {
    // Uploads are universally turned on.
    return TRUE;
  } else if (FILEACTIONS == TRUE && FILEACTIONS == substr(ft_get_dir(), 0, strlen(FILEACTIONS))) {
    // Uploads are allowed in the current directory and subdirectories only.
    return TRUE;
  }
  return FALSE;
}

/**
 * Check if file is on the blacklist.
 *
 * @param $file
 *   File name.
 * @return TRUE if file is not blacklisted.
 */
function ft_check_file($file) {
	// Check against file blacklist.
	if (FILEBLACKLIST != "") {
		$blacklist = explode(" ", strtolower(FILEBLACKLIST));
		if (in_array(strtolower($file), $blacklist)) {
			return FALSE;
		} else {
			return TRUE;
		}
	} else {
		return TRUE;
	}
}

/**
 * Check if file type is on the blacklist.
 *
 * @param $file
 *   File name.
 * @return TRUE if file is not blacklisted.
 */
function ft_check_filetype($file) {
	$type = strtolower(ft_get_ext($file));
	// Check if we are using a whitelist.
	if (FILETYPEWHITELIST != "") {
		// User wants a whitelist
		$whitelist = explode(" ", FILETYPEWHITELIST);
		if (in_array($type, $whitelist)) {
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		// Check against file blacklist.
		if (FILETYPEBLACKLIST != "") {
			$blacklist = explode(" ", FILETYPEBLACKLIST);
			if (in_array($type, $blacklist)) {
				return FALSE;
			} else {
				return TRUE;
			}
		} else {
			return TRUE;
		}
	}
}

/**
 * Check if a user is authenticated to view the page or not. Must be called on all pages.
 *
 * @return TRUE if the user is authenticated.
 */
function ft_check_login() {
	global $ft;
  $valid_login = 0;
	if (LOGIN == TRUE) {
		if (empty($_SESSION['ft_user_'.MUTEX])) {
		  $cookie_mutex = str_replace('.', '_', MUTEX);
			// Session variable has not been set. Check if there is a valid cookie or login form has been submitted or return false.
      if (REMEMBERME == TRUE && !empty($_COOKIE['ft_user_'.$cookie_mutex])) {
        // Verify cookie.
        $cookie = ft_check_cookie($_COOKIE['ft_user_'.$cookie_mutex]);
        if (!empty($cookie)) {
  			  // Cookie valid. Login.
  				$_SESSION['ft_user_'.MUTEX] = $cookie;
  				ft_invoke_hook('loginsuccess', $cookie);
  				ft_redirect();
        }
			}
			if (!empty($_POST['act']) && $_POST['act'] == "dologin") {
				// Check username and password from login form.
				if (!empty($_POST['ft_user']) && $_POST['ft_user'] == USERNAME && $_POST['ft_pass'] == PASSWORD) {
					// Valid login.
					$_SESSION['ft_user_'.MUTEX] = USERNAME;
					$valid_login = 1;
				}
				// Default user was not valid, we check additional users (if any).
				if (is_array($ft['users']) && sizeof($ft['users']) > 0) {
					// Check username and password.
					if (array_key_exists($_POST['ft_user'], $ft['users']) && $ft['users'][$_POST['ft_user']]['password'] == $_POST['ft_pass']) {
						// Valid login.
						$_SESSION['ft_user_'.MUTEX] = $_POST['ft_user'];
						$valid_login = 1;
					}
				}
				if ($valid_login == 1) {
				  // Set cookie.
					if (!empty($_POST['ft_cookie']) && REMEMBERME) {
					  setcookie('ft_user_'.MUTEX, md5($_POST['ft_user'].$_POST['ft_pass']), time()+60*60*24*3);
					} else {
					  // Delete cookie
					  setcookie('ft_user_'.MUTEX, md5($_POST['ft_user'].$_POST['ft_pass']), time()-3600);
					}
					ft_invoke_hook('loginsuccess', $_POST['ft_user']);
					ft_redirect();
				} else {
				  ft_invoke_hook('loginfail', $_POST['ft_user']);
  				ft_redirect("act=error");
				}
			}
			return FALSE;
		} else {
			return TRUE;
		}
	} else {
		return TRUE;
	}
}

/**
 * Check if a move action is inside the file actions area if FILEACTIONS is set to a specific director.
 *
 * @param $dest
 *   The directory to move to.
 * @return TRUE if move action is allowed.
 */
function ft_check_move($dest) {
  if (FILEACTIONS === TRUE) {
    return TRUE;
  }
  // Check if destination is within the fileactions area.
  $dest = substr($dest, 0, strlen($dest));
  $levels = substr_count(substr(ft_get_dir(), strlen(FILEACTIONS)), '/');
  if ($levels <= substr_count($dest, '../')) {
    return TRUE;
  } else {
    return FALSE;
  }
}

/**
 * Check if uploads are allowed in the current directory.
 *
 * @return TRUE if uploads are allowed.
 */
function ft_check_upload() {
  if (UPLOAD === TRUE) {
    // Uploads are universally turned on.
    return TRUE;
  } else if (UPLOAD == TRUE && UPLOAD == substr(ft_get_dir(), 0, strlen(UPLOAD))) {
    // Uploads are allowed in the current directory and subdirectories only.
    return TRUE;
  }
  return FALSE;
}

/**
 * Check if a user exists.
 *
 * @param $username
 *   Username to check.
 * @return TRUE if user exists.
 */
function ft_check_user($username) {
  global $ft;
  if ($username == USERNAME) {
    return TRUE;
  } elseif (is_array($ft['users']) && sizeof($ft['users']) > 0 && array_key_exists($username, $ft['users'])) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Remove unwanted characters from the settings array.
 */
function ft_clean_settings($settings) {
  // TODO: Clean DIR, UPLOAD and FILEACTIONS so they can't start with ../
  return $settings;
}

/**
 * Run all system actions based on the value of $_REQUEST['act'].
 */
function ft_do_action() {
	if (!empty($_REQUEST['act'])) {

    // Only one callback action is allowed. So only the first hook that acts on an action is run.
    ft_invoke_hook('action', $_REQUEST['act']);

		# mkdir
		if ($_REQUEST['act'] == "createdir" && CREATE === TRUE) {
		  $_POST['newdir'] = trim($_POST['newdir']);
      if ($_POST['type'] == 'file') {
        // Check file against blacklists
        if (strlen($_POST['newdir']) > 0 && ft_check_filetype($_POST['newdir']) && ft_check_file($_POST['newdir'])) {
          // Create file.
  				$newfile = ft_get_dir()."/{$_POST['newdir']}";
  				if (file_exists($newfile)) {
  					// Redirect
            ft_set_message(t("File could not be created. File already exists."), 'error');
  					ft_redirect("dir=".$_REQUEST['dir']);
  				} elseif (@touch($newfile)) {
  					// Redirect.
  					ft_set_message(t("File created."));
  					ft_redirect("dir=".$_REQUEST['dir']);
  				} else {
  					// Redirect
  					ft_set_message(t("File could not be created."), 'error');
  					ft_redirect("dir=".$_REQUEST['dir']);
  				}
  			} else {
					// Redirect
					ft_set_message(t("File could not be created."), 'error');
					ft_redirect("dir=".$_REQUEST['dir']);
  			}
  		} elseif ($_POST['type'] == 'url') {
  		  // Create from URL.
        $newname = trim(substr($_POST['newdir'], strrpos($_POST['newdir'], '/')+1));
        if (strlen($newname) > 0 && ft_check_filetype($newname) && ft_check_file($newname)) {
          // Open file handlers.
          $rh = fopen($_POST['newdir'], 'rb');
          if ($rh === FALSE) {
  					ft_set_message(t("Could not open URL. Possible reason: URL wrappers not enabled."), 'error');
  					ft_redirect("dir=".$_REQUEST['dir']);
          }
          $wh = fopen(ft_get_dir().'/'.$newname, 'wb');
          if ($wh === FALSE) {
  					ft_set_message(t("File could not be created."), 'error');
  					ft_redirect("dir=".$_REQUEST['dir']);
          }
          // Download anf write file.
          while (!feof($rh)) {
            if (fwrite($wh, fread($rh, 1024)) === FALSE) {
    					ft_set_message(t("File could not be saved."), 'error');
             }
          }
          fclose($rh);
          fclose($wh);
					ft_redirect("dir=".$_REQUEST['dir']);
  			} else {
					// Redirect
					ft_set_message(t("File could not be created."), 'error');
					ft_redirect("dir=".$_REQUEST['dir']);
  			}
      } else {
  			// Create directory.
  			// Check input.
        // if (strstr($_POST['newdir'], ".")) {
  				// Throw error (redirect).
          // ft_redirect("status=createddirfail&dir=".$_REQUEST['dir']);
        // } else {
  				$_POST['newdir'] = ft_stripslashes($_POST['newdir']);
  				$newdir = ft_get_dir()."/{$_POST['newdir']}";
  				$oldumask = umask(0);
  				if (strlen($_POST['newdir']) > 0 && @mkdir($newdir, DIRPERMISSION)) {
  					ft_set_message(t("Directory created."));
  					ft_redirect("dir=".$_REQUEST['dir']);
  				} else {
  					// Redirect
  					ft_set_message(t("Directory could not be created."), 'error');
  					ft_redirect("dir=".$_REQUEST['dir']);
  				}
  				umask($oldumask);
        // }
      }
		# Move
		} elseif ($_REQUEST['act'] == "move" && ft_check_fileactions() === TRUE) {
			// Check that both file and newvalue are set.
			$file = trim(ft_stripslashes($_REQUEST['file']));
			$dir = trim(ft_stripslashes($_REQUEST['newvalue']));
			if (substr($dir, -1, 1) != "/") {
				$dir .= "/";
			}
			// Check for level.
			if (substr_count($dir, "../") <= substr_count(ft_get_dir(), "/") && ft_check_move($dir) === TRUE) {
				$dir  = ft_get_dir()."/".$dir;
				if (!empty($file) && file_exists(ft_get_dir()."/".$file)) {
					// Check that destination exists and is a directory.
					if (is_dir($dir)) {
						// Move file.
						if (@rename(ft_get_dir()."/".$file, $dir."/".$file)) {
							// Success.
							ft_set_message(t("!old was moved to !new", array('!old' => $file, '!new' => $dir)));
							ft_redirect("dir={$_REQUEST['dir']}");
						} else {
							// Error rename failed.
							ft_set_message(t("!old could not be moved.", array('!old' => $file)), 'error');
							ft_redirect("dir={$_REQUEST['dir']}");
						}
					} else {
						// Error dest. isn't a dir or doesn't exist.
						ft_set_message(t("Could not move file. !old does not exist or is not a directory.", array('!old' => $dir)), 'error');
						ft_redirect("dir={$_REQUEST['dir']}");
					}
				} else {
					// Error source file doesn't exist.
					ft_set_message(t("!old could not be moved. It doesn't exist.", array('!old' => $file)), 'error');
					ft_redirect("dir={$_REQUEST['dir']}");
				}
			} else {
				// Error level
				ft_set_message(t("!old could not be moved outside the base directory.", array('!old' => $file)), 'error');
				ft_redirect("dir={$_REQUEST['dir']}");
			}
		# Delete
		} elseif ($_REQUEST['act'] == "delete" && ft_check_fileactions() === TRUE) {
			// Check that file is set.
			$file = ft_stripslashes($_REQUEST['file']);
			if (!empty($file) && ft_check_file($file)) {
				if (is_dir(ft_get_dir()."/".$file)) {
          if (DELETEFOLDERS == TRUE) {
            ft_rmdir_recurse(ft_get_dir()."/".$file);
          }
					if (!@rmdir(ft_get_dir()."/".$file)) {
					  ft_set_message(t("!old could not be deleted.", array('!old' => $file)), 'error');
						ft_redirect("dir={$_REQUEST['dir']}");
					} else {
					  ft_set_message(t("!old deleted.", array('!old' => $file)));
						ft_redirect("dir={$_REQUEST['dir']}");
					}
				} else {
					if (!@unlink(ft_get_dir()."/".$file)) {
					  ft_set_message(t("!old could not be deleted.", array('!old' => $file)), 'error');
						ft_redirect("dir={$_REQUEST['dir']}");
					} else {
					  ft_set_message(t("!old deleted.", array('!old' => $file)));
						ft_redirect("dir={$_REQUEST['dir']}");
					}
				}
			} else {
			  ft_set_message(t("!old could not be deleted.", array('!old' => $file)), 'error');
				ft_redirect("dir={$_REQUEST['dir']}");
			}
		# Rename && Duplicate && Symlink
		} elseif ($_REQUEST['act'] == "rename" || $_REQUEST['act'] == "duplicate" || $_REQUEST['act'] == "symlink" && ft_check_fileactions() === TRUE) {
			// Check that both file and newvalue are set.
			$old = trim(ft_stripslashes($_REQUEST['file']));
			$new = trim(ft_stripslashes($_REQUEST['newvalue']));
			if ($_REQUEST['act'] == 'rename') {
			  $m['typefail'] = t("!old was not renamed to !new (type not allowed).", array('!old' => $old, '!new' => $new));
			  $m['writefail'] = t("!old could not be renamed (write failed).", array('!old' => $old));
			  $m['destfail'] = t("File could not be renamed to !new since it already exists.", array('!new' => $new));
			  $m['emptyfail'] = t("File could not be renamed since you didn't specify a new name.");
			} elseif ($_REQUEST['act'] == 'duplicate') {
			  $m['typefail'] = t("!old was not duplicated to !new (type not allowed).", array('!old' => $old, '!new' => $new));
			  $m['writefail'] = t("!old could not be duplicated (write failed).", array('!old' => $old));
			  $m['destfail'] = t("File could not be duplicated to !new since it already exists.", array('!new' => $new));
			  $m['emptyfail'] = t("File could not be duplicated since you didn't specify a new name.");
			} elseif ($_REQUEST['act'] == 'symlink') {
			  $m['typefail'] = t("Could not create symlink to !old (type not allowed).", array('!old' => $old, '!new' => $new));
			  $m['writefail'] = t("Could not create symlink to !old (write failed).", array('!old' => $old));
			  $m['destfail'] = t("Could not create symlink !new since it already exists.", array('!new' => $new));
			  $m['emptyfail'] = t("Symlink could not be created since you didn't specify a name.");
			}
			if (!empty($old) && !empty($new)) {
				if (ft_check_filetype($new) && ft_check_file($new)) {
					// Make sure destination file doesn't exist.
					if (!file_exists(ft_get_dir()."/".$new)) {
						// Check that file exists.
						if (is_writeable(ft_get_dir()."/".$old)) {
							if ($_REQUEST['act'] == "rename") {
								if (@rename(ft_get_dir()."/".$old, ft_get_dir()."/".$new)) {
									// Success.
									ft_set_message(t("!old was renamed to !new", array('!old' => $old, '!new' => $new)));
									ft_redirect("dir={$_REQUEST['dir']}");
								} else {
									// Error rename failed.
									ft_set_message(t("!old could not be renamed.", array('!old' => $old)), 'error');
									ft_redirect("dir={$_REQUEST['dir']}");
								}
							} elseif ($_REQUEST['act'] == 'symlink') {
							  if (ADVANCEDACTIONS == TRUE) {
  								if (@symlink(realpath(ft_get_dir()."/".$old), ft_get_dir()."/".$new)) {
  								  @chmod(ft_get_dir()."/{$new}", PERMISSION);
  									// Success.
  									ft_set_message(t("Created symlink !new", array('!old' => $old, '!new' => $new)));
  									ft_redirect("dir={$_REQUEST['dir']}");
  								} else {
  									// Error symlink failed.
  									ft_set_message(t("Symlink to !old could not be created.", array('!old' => $old)), 'error');
  									ft_redirect("dir={$_REQUEST['dir']}");
  								}
							  }
							} else {
								if (@copy(ft_get_dir()."/".$old, ft_get_dir()."/".$new)) {
									// Success.
									ft_set_message(t("!old was duplicated to !new", array('!old' => $old, '!new' => $new)));
									ft_redirect("dir={$_REQUEST['dir']}");
								} else {
									// Error rename failed.
									ft_set_message(t("!old could not be duplicated.", array('!old' => $old)), 'error');
									ft_redirect("dir={$_REQUEST['dir']}");
								}
							}
						} else {
							// Error old file isn't writeable.
							ft_set_message($m['writefail'], 'error');
							ft_redirect("dir={$_REQUEST['dir']}");
						}
					} else {
						// Error destination exists.
						ft_set_message($m['destfail'], 'error');
						ft_redirect("dir={$_REQUEST['dir']}");
					}
				} else {
					// Error file type not allowed.
					ft_set_message($m['typefail'], 'error');
					ft_redirect("dir={$_REQUEST['dir']}");
				}
			} else {
				// Error. File name not set.
				ft_set_message($m['emptyfail'], 'error');
				ft_redirect("dir={$_REQUEST['dir']}");
			}
		# upload
		} elseif ($_REQUEST['act'] == "upload" && ft_check_upload() === TRUE && (LIMIT <= 0 || LIMIT > ROOTDIRSIZE)) {
			// If we are to upload a file we will do so.
			$msglist = 0;
			foreach ($_FILES as $k => $c) {
				if (!empty($c['name'])) {
					$c['name'] = ft_stripslashes($c['name']);
					if ($c['error'] == 0) {
						// Upload was successfull
						if (ft_check_filetype($c['name']) && ft_check_file($c['name'])) {
							if (file_exists(ft_get_dir()."/{$c['name']}")) {
							  $msglist++;
							  ft_set_message(t('!file was not uploaded.', array('!file' => ft_get_nice_filename($c['name'], 20))) . ' ' . t("File already exists"), 'error');
							} else {
								if (@move_uploaded_file($c['tmp_name'], ft_get_dir()."/{$c['name']}")) {
									@chmod(ft_get_dir()."/{$c['name']}", PERMISSION);
									// Success!
  							  $msglist++;
                  ft_set_message(t('!file was uploaded.', array('!file' => ft_get_nice_filename($c['name'], 20))));
                  ft_invoke_hook('upload', ft_get_dir(), $c['name']);
								} else {
									// File couldn't be moved. Throw error.
  							  $msglist++;
                  ft_set_message(t('!file was not uploaded.', array('!file' => ft_get_nice_filename($c['name'], 20))) . ' ' . t("File couldn't be moved"), 'error');
								}
							}
						} else {
							// File type is not allowed. Throw error.
						  $msglist++;
              ft_set_message(t('!file was not uploaded.', array('!file' => ft_get_nice_filename($c['name'], 20))) . ' ' . t("File type not allowed"), 'error');
						}
					} else {
						// An error occurred.
						switch($_FILES["localfile"]["error"]) {
							case 1:
						    $msglist++;
							  ft_set_message(t('!file was not uploaded.', array('!file' => ft_get_nice_filename($c['name'], 20))) . ' ' . t("The file was too large"), 'error');
								break;
							case 2:
						    $msglist++;
							  ft_set_message(t('!file was not uploaded.', array('!file' => ft_get_nice_filename($c['name'], 20))) . ' ' . t("The file was larger than MAXSIZE setting."), 'error');
								break;
							case 3:
						    $msglist++;
							  ft_set_message(t('!file was not uploaded.', array('!file' => ft_get_nice_filename($c['name'], 20))) . ' ' . t("Partial upload. Try again"), 'error');
								break;
							case 4:
						    $msglist++;
							  ft_set_message(t('!file was not uploaded.', array('!file' => ft_get_nice_filename($c['name'], 20))) . ' ' . t("No file was uploaded. Please try again"), 'error');
								break;
							default:
						    $msglist++;
							  ft_set_message(t('!file was not uploaded.', array('!file' => ft_get_nice_filename($c['name'], 20))) . ' ' . t("Unknown error"), 'error');
								break;
						}
					}
				}
			}
			if ($msglist > 0) {
				ft_redirect("dir=".$_REQUEST['dir']);
			} else {
			  ft_set_message(t("Upload failed."), 'error');
				ft_redirect("dir=".$_REQUEST['dir']);
			}
    # Unzip
    } elseif ($_REQUEST['act'] == "unzip" && ft_check_fileactions() === TRUE) {
			// Check that file is set.
			$file = ft_stripslashes($_REQUEST['file']);
			if (!empty($file) && ft_check_file($file) && ft_check_filetype($file) && strtolower(ft_get_ext($file)) == 'zip' && is_file(ft_get_dir()."/".$file)) {
			  $escapeddir = escapeshellarg(ft_get_dir()."/");
			  $escapedfile = escapeshellarg(ft_get_dir()."/".$file);
				if (!@exec("unzip -n ".$escapedfile." -d ".$escapeddir)) {
          ft_set_message(t("!old could not be unzipped.", array('!old' => $file)), 'error');
					ft_redirect("dir={$_REQUEST['dir']}");
				} else {
          ft_set_message(t("!old unzipped.", array('!old' => $file)));
					ft_redirect("dir={$_REQUEST['dir']}");
				}
			} else {
        ft_set_message(t("!old could not be unzipped.", array('!old' => $file)), 'error');
				ft_redirect("dir={$_REQUEST['dir']}");
			}
    # chmod
    } elseif ($_REQUEST['act'] == "chmod" && ft_check_fileactions() === TRUE && ADVANCEDACTIONS == TRUE) {
			// Check that file is set.
			$file = ft_stripslashes($_REQUEST['file']);
			if (!empty($file) && ft_check_file($file) && ft_check_filetype($file)) {
  			// Check that chosen permission i valid
  			if (is_numeric($_REQUEST['newvalue'])) {
  			  $chmod = $_REQUEST['newvalue'];
  			  if (substr($chmod, 0, 1) == '0') {
  			    $chmod = substr($chmod, 0, 4);
  			  } else {
  			    $chmod = '0'.substr($chmod, 0, 3);
  			  }
  			  // Chmod
  			  if (@chmod(ft_get_dir()."/".$file, intval($chmod, 8))) {
  			    ft_set_message(t("Permissions changed for !old.", array('!old' => $file)));
  			    ft_redirect("dir={$_REQUEST['dir']}");
    			  clearstatcache();
  			  } else {
  			    ft_set_message(t("Could not change permissions for !old.", array('!old' => $file)), 'error');
    				ft_redirect("dir={$_REQUEST['dir']}");
  			  }
  			} else {
			    ft_set_message(t("Could not change permissions for !old.", array('!old' => $file)), 'error');
  				ft_redirect("dir={$_REQUEST['dir']}");
  			}
			} else {
		    ft_set_message(t("Could not change permissions for !old.", array('!old' => $file)), 'error');
				ft_redirect("dir={$_REQUEST['dir']}");
			}
		# logout
		} elseif ($_REQUEST['act'] == "logout") {
		  ft_invoke_hook('logout', $_SESSION['ft_user_'.MUTEX]);
			$_SESSION = array();
			if (isset($_COOKIE[session_name()])) {
			   setcookie(session_name(), '', time()-42000, '/');
			}
			session_destroy();
			// Delete persistent cookie
		  setcookie('ft_user_'.MUTEX, '', time()-3600);
			ft_redirect();
		}
	}
}

/**
 * Convert PHP ini shorthand notation for file size to byte size.
 *
 * @return Size in bytes.
 */
function ft_get_bytes($val) {
	$val = trim($val);
	$last = strtolower($val{strlen($val)-1});
	switch($last) {
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}
	return $val;
}

/**
 * Get the total disk space consumed by files available to the current user.
 * Files and directories on blacklists are not counted.
 *
 * @param $dirname
 *   Name of the directory to scan.
 * @return Space consumed by this directory in bytes (not counting files and directories on blacklists).
 */
function ft_get_dirsize($dirname) {
  if (!is_dir($dirname) || !is_readable($dirname)) {
    return false;
  }
  $dirname_stack[] = $dirname;
  $size = 0;
  do {
    $dirname = array_shift($dirname_stack);
    $handle = opendir($dirname);
    while (false !== ($file = readdir($handle))) {
      if ($file != '.' && $file != '..' && is_readable($dirname . '/' . $file)) {
        if (is_dir($dirname . '/' . $file)) {
          if (ft_check_dir($dirname . '/' . $file)) {
            $dirname_stack[] = $dirname . '/' . $file;
          }
        } else {
          if (ft_check_file($file) && ft_check_filetype($file)) {
            $size += filesize($dirname . '/' . $file);
          }
        }
      }
    }
    closedir($handle);
  } while (count($dirname_stack) > 0);
  return $size;
}

/**
 * Get the current directory.
 *
 * @return The current directory.
 */
function ft_get_dir() {
	if (empty($_REQUEST['dir'])) {
		return ft_get_root();
	} else {
		return ft_get_root().$_REQUEST['dir'];
	}
}
/**
 * Get file extension from a file name.
 *
 * @param $name
 *   File name.
 * @return The file extension without the '.'
 */
function ft_get_ext($name) {
	if (strstr($name, ".")) {
		$ext = str_replace(".", "", strrchr($name, "."));
	} else {
		$ext = "";
	}
	return $ext;
}

/**
 * Get a list of files in a directory with metadata.
 *
 * @param $dir
 *   The directory to scan.
 * @param $sort
 *   Sorting parameter. Possible values: name, type, size, date. Defaults to 'name'.
 * @return An array of files. Each item is an array:
 *   array(
 *     'name' => '', // File name.
 *     'shortname' => '', // File name.
 *     'type' => '', // 'file' or 'dir'.
 *     'ext' => '', // File extension.
 *     'writeable' => '', // TRUE if writeable.
 *     'perms' => '', // Permissions.
 *     'modified' => '', // Last modified. Unix timestamp.
 *     'size' => '', // File size in bytes.
 *     'extras' => '' // Array of extra classes for this file.
 *   )
 */
function ft_get_filelist($dir, $sort = 'name') {
	$filelist = array();
	$subdirs = array();
	if (ft_check_dir($dir) && $dirlink = @opendir($dir)) {
		// Creates an array with all file names in current directory.
		while (($file = readdir($dirlink)) !== false) {
			if ($file != "." && $file != ".." && ((!is_dir("{$dir}/{$file}") && ft_check_file($file) && ft_check_filetype($file)) || is_dir("{$dir}/{$file}") && ft_check_dir("{$dir}/{$file}"))) { // Hide these two special cases and files and filetypes in blacklists.
				$c = array();
				$c['name'] = $file;
        // $c['shortname'] = ft_get_nice_filename($file, 20);
        $c['shortname'] = $file;
				$c['type'] = "file";
				$c['ext'] = ft_get_ext($file);
				$c['writeable'] = is_writeable("{$dir}/{$file}");

        // Grab extra options from plugins.
				$c['extras'] = array();
				$c['extras'] = ft_invoke_hook('fileextras', $file, $dir);

				// File permissions.
				if ($c['perms'] = @fileperms("{$dir}/{$file}")) {
  				if (is_dir("{$dir}/{$file}")) {
            $c['perms'] = substr(base_convert($c['perms'], 10, 8), 2);
          } else {
            $c['perms'] = substr(base_convert($c['perms'], 10, 8), 3);
          }
				}
        $c['modified'] = @filemtime("{$dir}/{$file}");
				$c['size'] = @filesize("{$dir}/{$file}");
				if (ft_check_dir("{$dir}/{$file}") && is_dir("{$dir}/{$file}")) {
					$c['size'] = 0;
					$c['type'] = "dir";
					if ($sublink = @opendir("{$dir}/{$file}")) {
						while (($current = readdir($sublink)) !== false) {
							if ($current != "." && $current != ".." && ft_check_file($current)) {
								$c['size']++;
							}
						}
						closedir($sublink);
					}
					$subdirs[] = $c;
				} else {
					$filelist[] = $c;
				}
			}
		}
		closedir($dirlink);
    // sort($filelist);

		// Obtain a list of columns
		$ext = array();
		$name = array();
		$date = array();
		$size = array();
    foreach ($filelist as $key => $row) {
      $ext[$key]  = strtolower($row['ext']);
      $name[$key] = strtolower($row['name']);
      $date[$key] = $row['modified'];
      $size[$key] = $row['size'];
    }

    if ($sort == 'type') {
      // Sort by file type and then name.
      array_multisort($ext, SORT_ASC, $name, SORT_ASC, $filelist);
    } elseif ($sort == 'size') {
      // Sort by filesize date and then name.
      array_multisort($size, SORT_ASC, $name, SORT_ASC, $filelist);
    } elseif ($sort == 'date') {
      // Sort by last modified date and then name.
      array_multisort($date, SORT_DESC, $name, SORT_ASC, $filelist);
    } else {
      // Sort by file name.
      array_multisort($name, SORT_ASC, $filelist);
    }
		// Always sort dirs by name.
		sort($subdirs);
		return array_merge($subdirs, $filelist);
	} else {
		return "dirfail";
	}
}

/**
 * Determine the max. size for uploaded files.
 *
 * @return Human-readable string of upload limit.
 */
function ft_get_max_upload() {
  $post_max = ft_get_bytes(ini_get('post_max_size'));
  $upload = ft_get_bytes(ini_get('upload_max_filesize'));
  // Compare ini settings.
  $max = (($post_max > $upload) ? $upload : $post_max);
  // Compare with MAXSIZE.
  if ($max > MAXSIZE) {
    $max = MAXSIZE;
  }
  return ft_get_nice_filesize($max);
}

/**
 * Shorten a file name to a given length maintaining the file extension.
 *
 * @param $name
 *   File name.
 * @param $limit
 *   The maximum length of the file name.
 * @return The shortened file name.
 */
function ft_get_nice_filename($name, $limit = -1) {
  if ($limit > 0) {
    $noext = $name;
    if (strstr($name, '.')) {
      $noext = substr($name, 0, strrpos($name, '.'));
    }
    $ext = ft_get_ext($name);
    if (strlen($noext)-3 > $limit) {
      $name = substr($noext, 0, $limit).'...';
      if ($ext != '') {
        $name = $name. '.' .$ext;
      }
    }
  }
  return $name;
}

/**
 * Convert a number of bytes to a human-readable format.
 *
 * @param $size
 *   Integer. File size in bytes.
 * @return String. Human-readable file size.
 */
function ft_get_nice_filesize($size) {
  if (empty($size)) {
    return "&mdash;";
	} elseif (strlen($size) > 6) { // Convert to megabyte
		return round($size/(1024*1024), 2)."&nbsp;MB";
	} elseif (strlen($size) > 4 || $size > 1024) { // Convert to kilobyte
		return round($size/1024, 0)."&nbsp;Kb";
	} else {
		return $size."&nbsp;b";
	}
}

/**
 * Get the root directory.
 *
 * @return The root directory.
 */
function ft_get_root() {
	return DIR;
}

/**
 * Get the name of the File Thingie file. Used in <form> actions.
 *
 * @return File name.
 */
function ft_get_self() {
	return basename($_SERVER['PHP_SELF']);
}

/**
 * Retrieve the contents of a URL.
 *
 * @return The contents of the URL as a string.
 */
function ft_get_url($url) {
	$url_parsed = parse_url($url);
	$host = $url_parsed["host"];
	$port = 0;
	$in = '';
	if (!empty($url_parsed["port"])) {
  	$port = $url_parsed["port"];
	}
	if ($port==0) {
		$port = 80;
	}
	$path = $url_parsed["path"];
	if ($url_parsed["query"] != "") {
		$path .= "?".$url_parsed["query"];
	}
	$out = "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n";
	$fp = fsockopen($host, $port, $errno, $errstr, 30);
	fwrite($fp, $out);
	$body = false;
	while ($fp && !feof($fp)) {
		$s = fgets($fp, 1024);
		if ( $body ) {
			$in .= $s;
		}
		if ( $s == "\r\n" ) {
			$body = true;
		}
	}
	fclose($fp);
	return $in;
}
/**
 * Get users in a group.
 *
 * @param $group
 *   Name of group.
 * @return Array of usernames.
 */
function ft_get_users_by_group($group) {
  global $ft;
  $userlist = array();
  foreach ($ft['users'] as $user => $c) {
    if (!empty($c['group']) && $c['group'] == $group) {
      $userlist[] = $user;
    }
  }
  return $userlist;
}

/**
 * Invoke a hook in all loaded plugins.
 *
 * @param $hook
 *   Name of the hook to invoke.
 * @param ...
 *   Arguments to pass to the hook.
 * @return Array of results from all hooks run.
 */
function ft_invoke_hook() {
  global $ft;
  $args = func_get_args();
  $hook = $args[0];
  unset($args[0]);
  // Loop through loaded plugins.
  $return = array();
  if (isset($ft['loaded_plugins']) && is_array($ft['loaded_plugins'])) {
    foreach ($ft['loaded_plugins'] as $name) {
      if (function_exists('ft_'.$name.'_'.$hook)) {
        $result = call_user_func_array('ft_'.$name.'_'.$hook, $args);
        if (isset($result) && is_array($result)) {
          $return = array_merge_recursive($return, $result);
        }
        else if (isset($result)) {
          $return[] = $result;
        }
      }
    }
  }
  return $return;
}

/**
 * Create HTML for the page body. Defaults to a file list.
 */
function ft_make_body() {
	$str = "";

  // Make system messages.
	$status = '';
	if (ft_check_upload() === TRUE && is_writeable(ft_get_dir()) && (LIMIT > 0 && LIMIT < ROOTDIRSIZE)) {
	  $status = '<p class="error">' . t('Upload disabled. Total disk space use of !size exceeds the limit of !limit.', array('!limit' => ft_get_nice_filesize(LIMIT), '!size' => ft_get_nice_filesize(ROOTDIRSIZE))) . '</p>';
	}
	$status .= ft_make_messages();
	if (empty($status)) {
    $str .= "<div id='status' class='hidden'></div>";
	} else {
		$str .= "<div id='status' class='section'>{$status}</div>";
	}

	// Invoke page hook if an action has been set.
	if (!empty($_REQUEST['act'])) {
    return $str . '<div id="main">'.implode("\r\n", ft_invoke_hook('page', $_REQUEST['act'])).'</div>';
	}

	// If no action has been set, show a list of files.

	if (empty($_REQUEST['act']) && (empty($_REQUEST['status']) || $_REQUEST['status'] != "dirfail")) { // No action set - we show a list of files if directory has been proven openable.
    $totalsize = 0;
    // Set sorting type. Default to 'name'.
    $sort = 'name';
    $cookie_mutex = str_replace('.', '_', MUTEX);
    // If there's a GET value, use that.
    if (!empty($_GET['sort'])) {
      // Set the cookie.
      setcookie('ft_sort_'.MUTEX, $_GET['sort'], time()+60*60*24*365);
      $sort = $_GET['sort'];
    } elseif (!empty($_COOKIE['ft_sort_'.$cookie_mutex])) {
      // There's a cookie, we'll use that.
      $sort = $_COOKIE['ft_sort_'.$cookie_mutex];
    }
		$files = ft_get_filelist(ft_get_dir(), $sort);
		if (!is_array($files)) {
			// List couldn't be fetched. Throw error.
      // ft_set_message(t("Could not open directory."), 'error');
      // ft_redirect();
      $str .= '<p class="error">'.t("Could not open directory.").'</p>';
		} else {
			// Show list of files in a table.
			$colspan = 3;
			if (SHOWDATES) {
			  $colspan = 4;
			}
			$str .= "<table id='filelist'>";
			$str .= "<thead><tr><th colspan=\"{$colspan}\"><div style='float:left;'>".t('Files')."</div>";
      $str .= "<form action='".ft_get_self()."' id='sort_form' method='get'><div><!--<label for='sort'>Sort by: </label>--><select id='sort' name='sort'>";
      $sorttypes = array('name' => t('Sort by name'), 'size' => t('Sort by size'), 'type' => t('Sort by type'), 'date' => t('Sort by date'));
      foreach ($sorttypes as $k => $v) {
        $str .= "<option value='{$k}'";
        if ($sort == $k) {
          $str .= " selected='selected'";
        }
        $str .= ">{$v}</option>";
      }
      $str .= "</select><input type=\"hidden\" name=\"dir\" value=\"".$_REQUEST['dir']."\" /></div></form></th>";
			$str .= "</tr></thead>";
			$str .= "<tbody>";
			$countfiles = 0;
			$countfolders = 0;
			if (count($files) <= 0) {
				$str .= "<tr><td colspan='{$colspan}' class='error'>".t('Directory is empty.')."</td></tr>";
			} else {
				$i = 0;
				$previous = $files[0]['type'];
				foreach ($files as $c) {
					$odd = "";
					$class = '';
					if ($c['writeable']) {
						$class = "show writeable ";
					}
					if ($c['type'] == 'dir' && $c['size'] == 0) {
					  $class .= " empty";
					}
          // Loop through extras and set classes.
					foreach ($c['extras'] as $extra) {
					  $class .= " {$extra}";
					}

					if (isset($c['perms'])) {
						$class .= " perm-{$c['perms']} ";
					}
					if (!empty($_GET['highlight']) && $c['name'] == $_GET['highlight']) {
						$class .= " highlight ";
						$odd = "highlight ";
					}
					if ($i%2 != 0) {
						$odd .= "odd";
					}
					if ($previous != $c['type']) {
						// Insert seperator.
						$odd .= " seperator ";
					}
					$previous = $c['type'];
					$str .= "<tr class='{$c['type']} $odd'>";
					if ($c['writeable'] && ft_check_fileactions() === TRUE) {
						$str .= "<td class='details'><span class='{$class}'>&loz;</span><span class='hide' style='display:none;'>&loz;</span></td>";
					} else {
						$str .= "<td class='details'>&mdash;</td>";
					}
				  $plugin_data = implode('', ft_invoke_hook('filename', $c['name']));
					if ($c['type'] == "file"){
					  $link = "<a href=\"".ft_get_dir()."/".rawurlencode($c['name'])."\" title=\"" .t('Show !file', array('!file' => $c['name'])). "\">{$c['shortname']}</a>";
					  if (HIDEFILEPATHS == TRUE) {
					    $link = ft_make_link($c['shortname'], 'method=getfile&amp;dir='.rawurlencode($_REQUEST['dir']).'&amp;file='.$c['name'], t('Show !file', array('!file' => $c['name'])));
					  }
						$str .= "<td class='name'>{$link}{$plugin_data}</td><td class='size'>".ft_get_nice_filesize($c['size']);
						$countfiles++;
					} else {
						$str .= "<td class='name'>".ft_make_link($c['shortname'], "dir=".rawurlencode($_REQUEST['dir'])."/".rawurlencode($c['name']), t("Show files in !folder", array('!folder' => $c['name'])))."{$plugin_data}</td><td class='size'>{$c['size']} ".t('files');
						$countfolders++;
					}
					// Add filesize to total.
					if ($c['type'] == 'file') {
					  $totalsize = $totalsize+$c['size'];
					}
					if (SHOWDATES) {
            if (isset($c['modified']) && $c['modified'] > 0) {
              $str .= "</td><td class='date'>".date(SHOWDATES, $c['modified'])."</td></tr>";
            } else {
              $str .= "</td><td class='date'>&mdash;</td></tr>";
            }
          }
          else {
            $str .= "</td></tr>";
          }
					$i++;
				}
			}
			if ($totalsize == 0) {
			  $totalsize = '';
			} else {
			  $totalsize = " (".ft_get_nice_filesize($totalsize).")";
			}
			$str .= "</tbody><tfoot><tr><td colspan=\"{$colspan}\">".$countfolders." ".t('folders')." - ".$countfiles." ".t('files')."{$totalsize}</td></tr></tfoot>";
			$str .= "</table>";
		}
	}
	return $str;
}

/**
 * Create HTML for page footer.
 */
function ft_make_footer() {
	return "<div id=\"footer\"><p><a href=\"http://www.solitude.dk/filethingie/\" target=\"_BLANK\">File Thingie &bull; PHP File Manager</a> &copy; <!-- Copyright --> 2003-".date("Y")." <a href=\"http://www.solitude.dk\" target=\"_BLANK\">Andreas Haugstrup Pedersen</a>.</p><p><a href=\"http://www.solitude.dk/filethingie/documentation\" target=\"_BLANK\">".t('Online documentation')."</a></p></div>";
}

/**
 * Create HTML for top header that shows breadcumb navigation.
 */
function ft_make_header() {
  global $ft;
	$str = "<h1 id='title'>".ft_make_link(t("Home"), '', t("Go to home folder"))." ";
	if (empty($_REQUEST['dir'])) {
		$str .= "/</h1>";
	} else {
		// Get breadcrumbs.
		if (!empty($_REQUEST['dir'])) {
			$crumbs = explode("/", $_REQUEST['dir']);
			// Remove first empty element.
			unset($crumbs[0]);
			// Output breadcrumbs.
			$path = "";
			foreach ($crumbs as $c) {
				$path .= "/{$c}";
				$str .= "/";
				$str .= ft_make_link($c, "dir=".rawurlencode($path), t("Go to folder"));
			}
		}
		$str .= "</h1>";
	}
	// Display logout link.
  if (LOGIN == TRUE) {
	  $str .= '<div id="logout"><p>';
	  if (isset($ft['users']) && @count($ft['users']) > 0 && LOGIN == TRUE) {
	    $str .= t('Logged in as !user ', array('!user' => $_SESSION['ft_user_'.MUTEX]));
	  }
	  $str .= ft_make_link(t("[logout]"), "act=logout", t("Logout of File Thingie")).'</p>';
	  $str .= '<div id="secondary_menu">' . implode("", ft_invoke_hook('secondary_menu')) . '</div>';
	  $str .= '</div>';
	}
	return $str;
}

/**
 * Create HTML for error message in case output was sent to the browser.
 */
function ft_make_headers_failed() {
	return "<h1>File Thingie Cannot Run</h1><div style='margin:1em;width:76ex;'><p>Your copy of File Thingie has become damaged and will not function properly. The most likely explanation is that the text editor you used when setting up your username and password added invisible garbage characters. Some versions of Notepad on Windows are known to do this.</p><p>To use File Thingie you should <strong><a href='http://www.solitude.dk/filethingie/'>download a fresh copy</a></strong> from the official website and use a different text editor when editing the file. On Windows you may want to try using <a href='http://www.editpadpro.com/editpadlite.html'>EditPad Lite</a> as your text editor.</p></div>";
}

/**
 * Create an internal HTML link.
 *
 * @param $text
 *   Link text.
 * @param $query
 *   The query string for the link. Optional.
 * @param $title
 *   String for the HTML title attribute. Optional.
 * @return String containing the HTML link.
 */
function ft_make_link($text, $query = "", $title = "") {
	$str = "<a href=\"".ft_get_self();
	if (!empty($query)) {
		$str .= "?{$query}";
	}
	$str .= "\"";
	if (!empty($title)) {
		$str .= "title=\"{$title}\"";
	}
	$str .= ">{$text}</a>";
	return $str;
}

/**
 * Create HTML for login box.
 */
function ft_make_login() {
	$str = "<h1>".t('File Thingie Login')."</h1>";
	$str .= '<form action="'.ft_get_self().'" method="post" id="loginbox">';
	if (!empty($_REQUEST['act']) && $_REQUEST['act'] == "error") {
		$str .= "<p class='error'>".t('Invalid username or password')."</p>";
	}
	$str .= '<div>
			<div>
				<label for="ft_user" class="login"><input type="text" size="25" name="ft_user" id="ft_user" tabindex="1" /> '.t('Username:').'</label>
			</div>
			<div>
				<label for="ft_pass" class="login"><input type="password" size="25" name="ft_pass" id="ft_pass" tabindex="2" /> '.t('Password:').'</label>
				<input type="hidden" name="act" value="dologin" />
			</div>  <div class="checkbox">
    			  <input type="submit" value="'.t('Login').'" id="login_button" tabindex="10" />';
	if (REMEMBERME) {
		$str .= '<label for="ft_cookie" id="cookie_label"><input type="checkbox" name="ft_cookie" id="ft_cookie" tabindex="3" /> '.t('Remember me').'</label>';
	}
	$str .= '</div></div>
	</form>';
	return $str;
}

/**
 * Create HTML for current status messages and reset status messages.
 */
function ft_make_messages() {
  $str = '';
  $msgs = array();
  if (isset($_SESSION['ft_status']) && is_array($_SESSION['ft_status'])) {
    foreach ($_SESSION['ft_status'] as $type => $messages) {
      if (is_array($messages)) {
        foreach ($messages as $m) {
          $msgs[] = "<p class='{$type}'>{$m}</p>";
        }
      }
    }
    // Reset messages.
    unset($_SESSION['ft_status']);
  }
  if (count($msgs) == 1) {
    return $msgs[0];
  } elseif (count($msgs) > 1) {
    $str .= "<ul>";
    foreach ($msgs as $c) {
      $str .= "<li>{$c}</li>";
    }
    $str .= "</ul>";
  }
  return $str;
}

/**
 * Create and output <script> tags for the page.
 */
function ft_make_scripts() {
  global $ft;
  $scripts = array();
  if (INSTALL != "SIMPLE") {
    $scripts[] = 'jquery-1.2.1.pack.js';
    $scripts[] = 'filethingie.js';
  }
  $result = ft_invoke_hook('add_js_file');
  $scripts = array_merge($scripts, $result);
  foreach ($scripts as $c) {
    echo "<script type='text/javascript' charset='utf-8' src='js/{$c}'></script>\r\n";
  }
}

/**
 * Create inline javascript for the HTML footer.
 *
 * @return String containing inline javascript.
 */
function ft_make_scripts_footer() {
  $result = ft_invoke_hook('add_js_call_footer');
  $str = "\r\n";
  if (count($result) > 0) {
    $str .= '<script type="text/javascript" charset="utf-8">';
    $str .= implode('', $result);
    $str .= '</script>';
  }
  return $str;
}

/**
 * Create HTML for sidebar.
 */
function ft_make_sidebar() {
	$str = '<div id="sidebar">';
  // $status = '';
  // if (ft_check_upload() === TRUE && is_writeable(ft_get_dir()) && (LIMIT > 0 && LIMIT < ROOTDIRSIZE)) {
  //   $status = '<p class="alarm">' . t('Upload disabled. Total disk space use of !size exceeds the limit of !limit.', array('!limit' => ft_get_nice_filesize(LIMIT), '!size' => ft_get_nice_filesize(ROOTDIRSIZE))) . '</p>';
  // }
  // $status .= ft_make_messages();
  // if (empty($status)) {
  //     $str .= "<div id='status' class='hidden'></div>";
  // } else {
  //  $str .= "<div id='status' class='section'><h2>".t('Results')."</h2>{$status}</div>";
  // }
	if (ft_check_upload() === TRUE && is_writeable(ft_get_dir())) {
	  if (LIMIT <= 0 || LIMIT > ROOTDIRSIZE) {
    	$str .= '
    	<div class="section" id="create">
    		<h2>'.t('Upload files').'</h2>
    		<form action="'.ft_get_self().'" method="post" enctype="multipart/form-data">
    			<div id="uploadsection">
    				<input type="hidden" name="MAX_FILE_SIZE" value="'.MAXSIZE.'" />
    				<input type="file" class="upload" name="localfile" id="localfile-0" size="12" />
    				<input type="hidden" name="act" value="upload" />
    				<input type="hidden" name="dir" value="'.$_REQUEST['dir'].'" />
    			</div>
    			<div id="uploadbutton">
    				<input type="submit" name="submit" value="'.t('Upload').'" />
    			</div>
          <div class="info">' . t('Max:') . ' <strong>' . ft_get_max_upload() . ' / ' . ft_get_nice_filesize((ft_get_bytes(ini_get('upload_max_filesize')) < ft_get_bytes(ini_get('post_max_size')) ? ft_get_bytes(ini_get('upload_max_filesize')) : ft_get_bytes(ini_get('post_max_size')))) . '</strong></div>
      		<div style="clear:both;"></div>
    		</form>
    	</div>';
	  }
	}
	if (CREATE) {
		$str .= '
	<div class="section" id="new">
		<h2>'.t('Create folder').'</h2>
		<form action="'.ft_get_self().'" method="post">
		<div>
		  <input type="radio" name="type" value="folder" id="type-folder" checked="checked" /> <label for="type-folder" class="label_highlight">'.t('Folder').'</label>
		  <input type="radio" name="type" value="file" id="type-file" /> <label for="type-file">'.t('File').'</label>
		  <input type="radio" name="type" value="url" id="type-url" /> <label for="type-url">'.t('From URL').'</label>
		</div>
			<div>
				<input type="text" name="newdir" id="newdir" size="16" />
				<input type="hidden" name="act" value="createdir" />
				<input type="hidden" name="dir" value="'.$_REQUEST['dir'].'" />
				<input type="submit" id="mkdirsubmit" name="submit" value="'.t('Ok').'" />
			</div>
		</form>
	</div>';
	}
  $sidebar = array();
  $result = ft_invoke_hook('sidebar');
  $sidebar = array_merge($sidebar, $result);

  if (is_array($sidebar)) {
    foreach ($sidebar as $c) {
      $str .= $c['content'];
    }
  }
	$str .= '</div>';
	return $str;
}

/**
 * Check if a plugin has been loaded.
 *
 * @param $plugin
 *   Name of the plugin to test.
 * @return TRUE if plugin is loaded.
 */
function ft_plugin_exists($plugin) {
  global $ft;
  foreach ($ft['loaded_plugins'] as $k => $v) {
    if ($v == $plugin) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Get a list of available plugins.
 */
function ft_plugins_list() {
  $plugin_list = array();
  // Get all files in the plugin dir.
	if ($dirlink = @opendir(PLUGINDIR)) {
		while (($file = readdir($dirlink)) !== false) {
		  // Only grab files that end in .plugin.php
			if (strstr($file, '.plugin.php')) {
			  // Load plugin files if they're not already there.
        $name = substr($file, 0, strpos($file, '.'));
        if (!ft_plugin_exists($name)) {
          include_once(PLUGINDIR.'/'.$file);
        }
        // Get plugin info. We can't use ft_invoke_hook since we need to loop through all plugins, not just the loaded plugins.
        if (function_exists('ft_'.$name.'_info')) {
          $plugin_list[$name] = call_user_func('ft_'.$name.'_info');
        } else {
          // If there's no info hook, we at least create some basic info.
          $plugin_list[$name] = array('name' => $name);
        }
			}
		}
	}
  return $plugin_list;
}

/**
 * Load plugins found in the current settings.
 */
function ft_plugins_load() {
  global $ft;
  $core = array('search', 'edit', 'tinymce');
  $ft['loaded_plugins'] = array();
  if (isset($ft['plugins']) && is_array($ft['plugins'])) {
    foreach ($ft['plugins'] as $name => $v) {
      // Include plugin file. We only need to load core modules if the install type is expanded.
      if (!in_array($name, $core) || (in_array($name, $core) && INSTALL != 'SIMPLE')) {
        // Not a core plugin or we're in expanded mode. Load file.
        if (file_exists(PLUGINDIR.'/'.$name.'.plugin.php')) {
          @include_once(PLUGINDIR.'/'.$name.'.plugin.php');
          $ft['loaded_plugins'][] = $name;
        } else {
          ft_set_message(t('Could not load !name plugin. File not found.', array('!name' => $name)), 'error');
        }
      } elseif (in_array($name, $core) && INSTALL == 'SIMPLE') {
        // Core plugin and we're in simple mode. Plugin file is already loaded.
        $ft['loaded_plugins'][] = $name;
      }
    }
  }
}

/**
 * Remove a plugin that has been loaded.
 *
 * @param $plugin
 *   Name of the plugin to remove.
 */
function ft_plugin_unload($plugin) {
  global $ft;
  foreach ($ft['loaded_plugins'] as $k => $v) {
    if ($v == $plugin) {
      unset($ft['loaded_plugins'][$k]);
    }
  }
}

/**
 * Recursively remove a directory.
 */
function ft_rmdir_recurse($path) {
  $path= rtrim($path, '/').'/';
  $handle = opendir($path);
  for (;false !== ($file = readdir($handle));) {
    if($file != "." and $file != ".." ) {
      $fullpath = $path.$file;
      if(is_dir($fullpath)) {
        ft_rmdir_recurse($fullpath);
        if (!@rmdir($fullpath)) {
          return FALSE;
        }
      }
      else {
        if(!@unlink($fullpath)) {
          return FALSE;
        }
      }
    }
  }
  closedir($handle);
}

/**
 * Redirect to a File Thingie page.
 *
 * @param $query
 *   Query string to append to redirect.
 */
function ft_redirect($query = '') {
  if (REQUEST_URI) {
    $_SERVER['REQUEST_URI'] = REQUEST_URI;
  }
  $protocol = 'http://';
  if (HTTPS) {
    $protocol = 'https://';
  }
  if (isset($_SERVER['REQUEST_URI'])) {
  	if (stristr($_SERVER["REQUEST_URI"], "?")) {
  		$requesturi = substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "?"));
  		$location = "Location: {$protocol}{$_SERVER["HTTP_HOST"]}{$requesturi}";
  	} else {
  		$requesturi = $_SERVER["REQUEST_URI"];
  		$location = "Location: {$protocol}{$_SERVER["HTTP_HOST"]}{$requesturi}";
  	}
  } else {
		$location = "Location: {$protocol}{$_SERVER["HTTP_HOST"]}{$_SERVER['PHP_SELF']}";
  }
	if (!empty($query)) {
		$location .= "?{$query}";
	}
	header($location);
	exit;
}

/**
 * Clean user input in $_REQUEST.
 */
function ft_sanitize_request() {
  // Kill null bytes
  foreach ($_REQUEST as $k => $v) {
    $_REQUEST[$k] = str_replace("\0", 'NULL', $_REQUEST[$k]);
    $_REQUEST[$k] = str_replace(chr(0), 'NULL', $_REQUEST[$k]);
  }
  if ($_FILES && is_array($_FILES)) {
    foreach ($_FILES as $k => $v) {
      $_FILES[$k]['name'] = str_replace("\0", 'NULL', $_FILES[$k]['name']);
      $_FILES[$k]['name'] = str_replace(chr(0), 'NULL', $_FILES[$k]['name']);
      $_FILES[$k]['name'] = urldecode($_FILES[$k]['name']);
      $_FILES[$k]['name'] = str_replace("&#00", 'NULL', $_FILES[$k]['name']);
    }
  }

	// Make sure 'dir' cannot be changed to open directories outside the stated FT directory.
	if (!empty($_REQUEST['dir']) && strstr($_REQUEST['dir'], "..") || !empty($_REQUEST['dir']) && strstr($_REQUEST['dir'], "./") || empty($_REQUEST['dir'])) {
		unset($_REQUEST['dir']);
	}
	// Set 'dir' to empty if it isn't set.
	if (!isset($_REQUEST['dir']) || empty($_REQUEST['dir'])) {
		$_REQUEST['dir'] = "";
	}
	// If 'dir' is set to just / it is a security risk.
	if (trim($_REQUEST['dir']) == '/') {
	  unset($_REQUEST['dir']);
  }
	// Nuke slashes from 'file' and 'newvalue'
	if (!empty($_REQUEST['file'])) {
		$_REQUEST['file'] = trim(str_replace("/", "", $_REQUEST['file']));
	}
	if (!empty($_REQUEST['act']) && $_REQUEST['act'] != "move") {
		if (!empty($_REQUEST['newvalue'])) {
			$_REQUEST['newvalue'] = str_replace("/", "", $_REQUEST['newvalue']);
			// Nuke ../ for 'newvalue' when not moving files.
			if (stristr($_REQUEST['newvalue'], "..") || empty($_REQUEST['newvalue'])) {
				unset($_REQUEST['newvalue']);
			}
		}
	}
	// Nuke ../ for 'file' and newdir
	if (!empty($_REQUEST['file']) && stristr($_REQUEST['file'], "..") || empty($_REQUEST['file'])) {
		unset($_REQUEST['file']);
	}
	if (!empty($_POST['newdir']) && stristr($_POST['newdir'], "..") || empty($_POST['newdir'])) {
		unset($_POST['newdir']);
	}
	// Set 'q' (search queries) to empty if it isn't set.
	if (empty($_REQUEST['q'])) {
		$_REQUEST['q'] = "";
	}
}

/**
 * Set status message for display.
 *
 * @param $message
 *   Message string to display.
 * @param $type
 *   Message type. Possible values: ok, error. Default is 'ok'.
 */
function ft_set_message($message = NULL, $type = 'ok') {
  if ($message) {
    if (!isset($_SESSION['ft_status'])) {
      $_SESSION['ft_status'] = array();
    }
    if (!isset($_SESSION['ft_status'][$type])) {
      $_SESSION['ft_status'][$type] = array();
    }
    $_SESSION['ft_status'][$type][] = $message;
  }
}

/**
 * Load external configuration file.
 *
 * @param $file
 *   Path to external file to load.
 * @return Array of settings, users, groups and plugins.
 */
function ft_settings_external($file) {
  if (file_exists($file)) {
    @include_once($file);
    $json = ft_settings_external_load();
    if (!$json) {
      // Not translateable. Language info is not available yet.
      ft_set_message('Could not load external configuration.', 'error');
      return FALSE;
    }
    return $json;
  }
  return FALSE;
}

/**
 * Prepare settings. Loads configuration file is any and
 * sets the needed setting constants according to user group.
 */
function ft_settings_load() {
  global $ft;
  $settings = array();

  // Load external configuration if any.
  $json = ft_settings_external('config.php');
  if ($json) {
    // Merge settings.
    if (is_array($json['settings'])) {
      foreach ($json['settings'] as $k => $v) {
        $ft['settings'][$k] = $v;
      }
    }
    // Merge users.
    if (is_array($json['users'])) {
      foreach ($json['users'] as $k => $v) {
        $ft['users'][$k] = $v;
      }
    }
    // Merge groups.
    if (is_array($json['groups'])) {
      foreach ($json['groups'] as $k => $v) {
        $ft['groups'][$k] = $v;
      }
    }
    // Overwrite plugins
    if (is_array($json['plugins'])) {
      $ft['plugins'] = $json['plugins'];
      // foreach ($json['plugins'] as $k => $v) {
      //   $ft['plugins'][$k] = $v;
      // }
    }
  }
  else {
    die('Could not open config file. Please create config.php');
  }

  // Save default settings before groups overwrite them.
  $ft['default_settings'] = $ft['settings'];

  // Check if current user is a member of a group.
  $current_group = FALSE;
  $current_group_name = FALSE;
  if (
    !empty($_SESSION['ft_user_'.MUTEX]) &&
    is_array($ft['groups']) &&
    is_array($ft['users']) &&
    array_key_exists($_SESSION['ft_user_'.MUTEX], $ft['users']) &&
    isset($ft['groups'][$ft['users'][$_SESSION['ft_user_'.MUTEX]]['group']]) &&
    is_array($ft['groups'][$ft['users'][$_SESSION['ft_user_'.MUTEX]]['group']])) {
      $current_group = $ft['groups'][$ft['users'][$_SESSION['ft_user_'.MUTEX]]['group']];
      // $current_group_name = $ft['users'][$_SESSION['ft_user_'.MUTEX]]['group'];
  }

  // Break out plugins in the group settings.
  if (is_array($current_group) && array_key_exists('plugins', $current_group)) {
    $ft['plugins'] = $current_group['plugins'];
    unset($current_group['plugins']);
  }

  // Loop through settings. Use group values if set.
  // foreach ($constants as $k => $v) {
  foreach ($ft['settings'] as $k => $v) {
    // $new_k = substr($k, 1);
    $new_k = $k;
    if (is_array($current_group) && array_key_exists($k, $current_group)) {
      // define($new_k, $current_group[$k]);
      $settings[$new_k] = $current_group[$k];
    } else {
      // Use original value.
      // define($new_k, $v);
      $settings[$new_k] = $v;
    }
  }
  // Define constants.
  $settings = ft_clean_settings($settings);
  foreach ($settings as $k => $v) {
    define($k, $v);
  }
  // Clean up $ft.
  unset($ft['settings']);
}

/**
 * Strips slashes from string if magic quotes are on.
 *
 * @param $string
 *   String to filter.
 * @return The filtered string.
 */
function ft_stripslashes($string) {
  if (get_magic_quotes_gpc()) {
    return stripslashes($string);
  } else {
    return $string;
  }
}

/**
 * Translate a string to the current locale.
 *
 * @param $msg
 *   A string to be translated.
 * @param $vars
 *   An associative array of replacements for placeholders.
 *   Array keys in $msg will be replaced with array values.
 * @param $js
 *   Boolean indicating if return values should be escaped for JavaScript.
 *   Defaults to FALSE.
 * @return The translated string.
 */
function t($msg, $vars = array(), $js = FALSE) {
  global $ft_messages;
  if(isset($ft_messages[LANG]) && isset($ft_messages[LANG][$msg])) {
   $msg = $ft_messages[LANG][$msg];
  } else {
   $msg = $msg;
  }
  // Replace vars
  if (count($vars) > 0) {
    foreach ($vars as $k => $v) {
      $msg = str_replace($k, $v, $msg);
    }
  }
  if ($js) {
    return str_replace("'", "\'", $msg);
  }
  return $msg;
}

# Plugins #

# Set timezone if PHP version is larger than 5.10. #
if (function_exists('date_default_timezone_set')) {
  date_default_timezone_set(date_default_timezone_get());
}

# Start running File Thingie #
// Check if headers has already been sent.
if (headers_sent()) {
  $str = ft_make_headers_failed();
} else {
  session_start();
  header("Content-Type: text/html; charset=UTF-8");
  header("Connection: close");
  // Prep settings
  ft_settings_load();
  // Load plugins
  ft_plugins_load();
  ft_invoke_hook('init');
  // Prep language.
  if (file_exists("locales/".LANG.".php")) {
    @include_once("locales/".LANG.".php");
  }
  // Only calculate total dir size if limit has been set.
  if (LIMIT > 0) {
  	define('ROOTDIRSIZE', ft_get_dirsize(ft_get_root()));
  }

  $str = "";
  // Request is a file download.
  if (!empty($_GET['method']) && $_GET['method'] == 'getfile' && !empty($_GET['file'])) {
    if (ft_check_login()) {
      ft_sanitize_request();
      // Make sure we don't run out of time to send the file.
      @ignore_user_abort();
      @set_time_limit(0);
      @ini_set("zlib.output_compression", "Off");
      @session_write_close();
      // Open file for reading
      if(!$fdl=@fopen(ft_get_dir().'/'.$_GET['file'],'rb')){
          die("Cannot Open File!");
      } else {
        ft_invoke_hook('download', ft_get_dir(), $_GET['file']);
        header("Cache-Control: ");// leave blank to avoid IE errors
        header("Pragma: ");// leave blank to avoid IE errors
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"".htmlentities($_GET['file'])."\"");
        header("Content-length:".(string)(filesize(ft_get_dir().'/'.$_GET['file'])));
        header ("Connection: close");
        sleep(1);
        fpassthru($fdl);
      }
    } else {
			// Authentication error.
      ft_redirect();
    }
    exit;
  } elseif (!empty($_POST['method']) && $_POST['method'] == "ajax") {
		if (ft_check_login()) {
			ft_sanitize_request();
			// Run the ajax hook for modules implementing ajax.
			echo implode('', ft_invoke_hook('ajax', $_POST['act']));
		} else {
			// Authentication error. Send 403.
			header("HTTP/1.1 403 Forbidden");
			echo "<dt class='error'>".t('Login error.')."</dt>";
		}
  	exit;
  }
  if (ft_check_login()) {
  	// Run initializing functions.
  	ft_sanitize_request();
  	ft_do_action();
  	$str = ft_make_header();
  	$str .= ft_make_sidebar();
  	$str .= ft_make_body();
  } else {
    	$str .= ft_make_login();
  }
  $str .= ft_make_footer();
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo LANG;?>" lang="<?php echo LANG;?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>File Thingie <?php echo VERSION;?></title>
	<link rel="author" href="http://www.solitude.dk/" title="Andreas Haugstrup Pedersen" />
	<link rel="home" href="<?php echo ft_get_self();?>" title="<?php echo t('Go to home folder');?>" />
	<link rel="help" href="http://www.solitude.dk/filethingie/documentation" title="<?php echo t('Online documentation');?>" />

<?php ft_make_scripts();?>

  <script type="text/javascript" charset="utf-8">
	$(document).ready(function(){
		// Set focus on login username.
		if (document.getElementById("ft_user")) {
			document.getElementById("ft_user").focus();
		}
		// Set global object.
		var ft = {fileactions:{}};
		// Prep upload section.
		$('#uploadsection').parent().ft_upload({
		  header:"<?php echo t('Files for upload:');?>",
		  cancel: "<?php echo t('Cancel upload of this file');?>",
		  upload: "<?php echo t('Now uploading files. Please wait...');?>"
		});
		// Prep file actions.
		$('#filelist').ft_filelist({
		  fileactions: ft.fileactions,
		  rename_link: "<?php echo t('Rename');?>",
		  move_link: "<?php echo t('Move');?>",
		  del_link: "<?php echo t('Delete');?>",
		  duplicate_link: "<?php echo t('Duplicate');?>",
		  unzip_link: "<?php echo t('Unzip');?>",
		  chmod_link: "<?php echo t('chmod');?>",
		  symlink_link: "<?php echo t('Symlink');?>",
		  rename: "<?php echo t('Rename to:');?>",
      move: "<?php echo t('Move to folder:');?>",
      del: "<?php echo t('Do you really want to delete file?');?>",
      del_warning: "<?php echo t('You can only delete empty folders.');?>",
      del_button: "<?php echo t('Yes, delete it');?>",
      duplicate: "<?php echo t('Duplicate to file:');?>",
      unzip: "<?php echo t('Do you really want to unzip file?');?>",
      unzip_button: "<?php echo t('Yes, unzip it');?>",
      chmod: "<?php echo t('Set permissions to:');?>",
      symlink: "<?php echo t('Create symlink called:');?>",
		  directory: "<?php if (!empty($_REQUEST['dir'])) {echo $_REQUEST['dir'];}?>",
		  ok: "<?php echo t('Ok');?>",
		  formpost: "<?php echo ft_get_self();?>",
		  advancedactions: "<?php if (ADVANCEDACTIONS === TRUE) {echo 'true';} else {echo 'false';}?>"
		});

		// Sort select box.
		$('#sort').change(function(){
		  $('#sort_form').submit();
		});
		// Label highlight in 'create' box.
    $('#new input[type=radio]').change(function(){
      $('label').removeClass('label_highlight');
      $('label[@for='+$(this).attr('id')+']').addClass('label_highlight');
    });
<?php echo implode("\r\n", ft_invoke_hook('add_js_call'));?>
	});
	</script>
	<style type="text/css">
	  @import "css/ft.css";
    <?php echo implode("\r\n", ft_invoke_hook('add_css'));?>
	</style>
</head>
<body>

	<?php echo $str;?>
  <?php echo ft_make_scripts_footer();?>
  <?php echo implode("\r\n", ft_invoke_hook('destroy'));?>
</body>
</html>
