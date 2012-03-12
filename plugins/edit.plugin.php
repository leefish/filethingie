<?php
/**
 * @file
 * Edit file plugin for File Thingie.
 * Author: Andreas Haugstrup Pedersen, Copyright 2008, All Rights Reserved
 *
 * Must be loaded after the db plugin if file locking is to be used.
 */

/**
 * Implementation of hook_info.
 */
function ft_edit_info() {
  return array(
    'name' => 'Edit: Enabling editing of text-based files.',
    'settings' => array(
      'editlist' => array(
        'default' => 'txt html htm css',
        'description' => t('List of file extensions to edit.'),
      ),
      'converttabs' => array(
        'default' => FALSE,
        'description' => t('Convert tabs to spaces'),
      ),
    ),
  );
}

/**
 * Implementation of hook_init.
 */
function ft_edit_init() {
  global $ft;
  // Check if DB plugin is loaded.
  // if (ft_plugin_exists('db')) {
  //   // Check if we need to create new table.
  //   $sql = "CREATE TABLE edit (
  //     dir TEXT NOT NULL,
  //     file TEXT NOT NULL,
  //     user TEXT NOT NULL,
  //     timestamp INTEGER
  //   )";
  //   ft_db_install_table('edit', $sql);    
  // }
}

/**
 * Implementation of hook_page.
 */
function ft_edit_page($act) {
  global $ft;
  $str = '';
  if ($act == 'edit') {
		$_REQUEST['file'] = trim(ft_stripslashes($_REQUEST['file']));
		$str = "<h2>".t('Edit file:')." {$_REQUEST['file']}</h2>";
		// Check that file exists and that it's writeable.
		if (is_writeable(ft_get_dir()."/".$_REQUEST['file'])) {
			// Check that filetype is editable.
			if (ft_check_dir(ft_get_dir()) && ft_check_edit($_REQUEST['file']) && ft_check_fileactions() === TRUE && ft_check_filetype($_REQUEST['file']) && ft_check_filetype($_REQUEST['file'])) {
				// Get file contents.
				$filecontent = implode ("", file(ft_get_dir()."/{$_REQUEST["file"]}"));
				$filecontent = htmlspecialchars($filecontent);
				if ($ft['plugins']['edit']['settings']['converttabs'] == TRUE) {
					$filecontent = str_replace("\t", "    ", $filecontent);
				}
				$lock = FALSE;
				// Lock file if db plugin is loaded.
				$lock = ft_edit_lock_get($_REQUEST["file"], ft_get_dir());
        if ($lock !== FALSE) {
          if ($lock === $_SESSION['ft_user_'.MUTEX]) {
            // File is in use by current user. Quietly update lock.
            // $str .= '<p class="ok">'.t('You are already editing this file.').'</p>';
            $lock = FALSE;
          }
        }
				if ($lock === FALSE) {
				  // File is not locked. Set a new lock for the current user.
  				ft_edit_lock_set($_REQUEST["file"], ft_get_dir(), $_SESSION['ft_user_'.MUTEX]);
				  // Make form or show lock message.
  				$str .= '<form id="edit" action="'.ft_get_self().'" method="post">
  					<div>
  						<textarea cols="76" rows="20" name="filecontent" id="filecontent">'.$filecontent.'</textarea>
  					</div>
  					<div>
  						<input type="hidden" name="file" id="file" value="'.$_REQUEST['file'].'" />
  						<input type="hidden" name="dir" id="dir" value="'.$_REQUEST['dir'].'" />
  						<input type="hidden" name="act" value="savefile" />
              <button type="button" id="save">'.t('Save').'</button>
  						<input type="submit" value="'.t('Save &amp; exit').'" name="submit" />
  						<input type="submit" value="'.t('Cancel').'" name="submit" />
  						<input type="checkbox" name="convertspaces"              id="convertspaces"'.($ft['plugins']['edit']['settings']['converttabs'] == TRUE ? ' checked="checked"' : '').' /> <label              for="convertspaces">'.t('Convert spaces to tabs').'</label>
    					<div id="savestatus"></div>
  					</div>
  				</form>';				  
				} else {
				  $str .= '<p class="error">'.t('Cannot edit file. This file is currently being edited by !name', array('!name' => $lock)).'</p>';				    
				}
			} else {
				$str .= '<p class="error">'.t('Cannot edit file. This file type is not editable.').'</p>';				
			}
		} else {
			$str .= '<p class="error">'.t('Cannot edit file. It either does not exist or is not writeable.').'</p>';
		}
  }
  return $str;
}

/**
 * Implementation of hook_fileextras.
 */
function ft_edit_fileextras($file, $dir) {
  if (ft_check_edit($file) && !is_dir("{$dir}/{$file}")) {
		return 'edit';
	}
  return FALSE;
}

/**
 * Implementation of hook_action.
 */
function ft_edit_action($act) {
  global $ft;
  if ($act == 'savefile') {
		$file = trim(ft_stripslashes($_REQUEST["file"]));
    if (ft_check_fileactions() === TRUE) {
			// Save a file that has been edited.
			// Delete any locks on this file.
			ft_edit_lock_clear($file, ft_get_dir());
			// Check for edit or cancel
			if (strtolower($_REQUEST["submit"]) != strtolower(t("Cancel"))) {        
				// Check if file type can be edited.
				if (ft_check_dir(ft_get_dir()) && ft_check_edit($file) && ft_check_fileactions() === TRUE && ft_check_filetype($file) && ft_check_filetype($file)) {
					$filecontent = ft_stripslashes($_REQUEST["filecontent"]);
					if ($_REQUEST["convertspaces"] != "") {
						$filecontent = str_replace("    ", "\t", $filecontent);
					}
					if (is_writeable(ft_get_dir()."/{$file}")) {
						$fp = @fopen(ft_get_dir()."/{$file}", "wb");
						if ($fp) {
							fputs ($fp, $filecontent);
							fclose($fp);
							ft_set_message(t("!old was saved.", array('!old' => $file)));
							ft_redirect("dir={$_REQUEST['dir']}");
						} else {
							ft_set_message(t("!old could not be edited.", array('!old' => $file)), 'error');
							ft_redirect("dir={$_REQUEST['dir']}");
						}
					} else {
						ft_set_message(t("!old could not be edited.", array('!old' => $file)), 'error');
						ft_redirect("dir={$_REQUEST['dir']}");
					}
				} else {
					ft_set_message(t("Could not edit file. This file type is not editable."), 'error');
					ft_redirect("dir={$_REQUEST['dir']}");
				}
			} else {
				ft_redirect("dir=".rawurlencode($_REQUEST['dir']));
			}
		}
  }
}

/**
 * Implementation of hook_ajax.
 */
function ft_edit_ajax($act) {
  if ($act == 'saveedit') {
		// Do save file.
		$file = trim(ft_stripslashes($_POST["file"]));
		// Check if file type can be edited.
		if (ft_check_dir(ft_get_dir()) && ft_check_edit($file) && ft_check_fileactions() === TRUE && ft_check_filetype($file) && ft_check_filetype($file)) {
			$filecontent = ft_stripslashes($_POST["filecontent"]);
			if ($_POST["convertspaces"] != "") {
				$filecontent = str_replace("    ", "\t", $filecontent);
			}
			if (is_writeable(ft_get_dir()."/{$file}")) {
				$fp = @fopen(ft_get_dir()."/{$file}", "wb");
				if ($fp) {
					fputs ($fp, $filecontent);
					fclose($fp);
          // edit
          echo '<p class="ok">' . t("!old was saved.", array('!old' => $file)) . '</p>';
				} else {
				  // editfilefail
				  echo '<p class="error">' . t("!old could not be edited.", array('!old' => $file)) . '</p>';
				}
			} else {
        // editfilefail
        echo '<p class="error">' . t("!old could not be edited.", array('!old' => $file)) . '</p>';
			}
		} else {
      // edittypefail
      echo '<p class="error">' . t("Could not edit file. This file type is not editable.") . '</p>';
		}
  } elseif ($act == 'edit_get_lock') {
    ft_edit_lock_set($_POST['file'], $_POST['dir'], $_SESSION['ft_user_'.MUTEX]);
    echo 'File locked.';
  }
}

/**
 * Implementation of hook_add_js_call.
 */
function ft_edit_add_js_call() {
  $return = '';
  // Save via ajax (opposed to save & exit)
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'edit') {
    $return .= '$("#save").click(function(){
  	$("#savestatus").empty().append("<p class=\"ok\">'.t('Saving file&hellip;').'</p>");
  	$.post("'.ft_get_self().'", {method:\'ajax\', act:\'saveedit\', file: $(\'#file\').val(), dir: $(\'#dir\').val(), filecontent: $(\'#filecontent\').val(), convertspaces: $(\'#convertspaces\').val()}, function(data){
  		$("#savestatus").empty().append(data);
  	});
  });';
  // Heartbeat to keep file locked.
  $return .= 'ft.edit_beat = function(){
    $.post("'.ft_get_self().'", {method:\'ajax\', act:\'edit_get_lock\', file: $(\'#file\').val(), dir: $(\'#dir\').val()}, function(data){
  	});
  };
  ft.edit_heartbeat = setInterval(function() {
    // Make ajax call to make sure file stays locked.
    ft.edit_beat();
  }, 30000);';
  } else {
    $return = 'ft.fileactions.edit = {type: "sendoff", link: "'.t('Edit').'", text: "'.t('Do you want to edit this file?').'", button: "'.t('Yes, edit file').'"};';
  }
  return $return;
}

/**
 * Check if file is on the edit list.
 *
 * @param $file
 *   File name.
 * @return TRUE if file is on the edit list.
 */
function ft_check_edit($file) {
  global $ft;
	// Check against file blacklist.
	if ($ft['plugins']['edit']['settings']['editlist'] != "") {
		$list = explode(" ", $ft['plugins']['edit']['settings']['editlist']);
		if (in_array(ft_get_ext(strtolower($file)), $list)) {
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}

/**
 * Clear a lock on a file.
 *
 * @param $file
 *   File name to clear.
 * @param $dir
 *   Directory where file resides.
 */
function ft_edit_lock_clear($file, $dir) {
  global $ft;
  // if (ft_plugin_exists('db')) {
  //   $sql = "DELETE FROM edit WHERE dir = '".sqlite_escape_string($dir)."' AND file = '".sqlite_escape_string($file)."'";
  //   sqlite_query($ft['db']['link'], $sql);
  // }
}

/**
 * Get a lock status on a file.
 *
 * @param $file
 *   File name to clear.
 * @param $dir
 *   Directory where file resides.
 * @return Username if the file has a lock. FALSE if it doesn't.
 */
function ft_edit_lock_get($file, $dir) {
  global $ft;
  // if (ft_plugin_exists('db')) {
  //   // See if file has been locked.
  //   $sql = "SELECT user, timestamp FROM edit WHERE dir = '".sqlite_escape_string($dir)."' AND file = '".sqlite_escape_string($file)."' ORDER BY timestamp DESC";
  //   $result = sqlite_query($ft['db']['link'], $sql);
  //   if ($result) {
  //     if (sqlite_num_rows($result) > 0) {
  //       $user = sqlite_fetch_array($result);
  //       // Check timestamp. Locks expire after 2 minutes.
  //       if ($user['timestamp'] < time()-120) {
  //         // Lock has expired. Clear it.
  //         ft_edit_lock_clear($file, $dir);
  //         return FALSE;
  //       } else {
  //         // Someone is already editing this.
  //         return $user['user'];
  //       }
  //     } else {
  //       return FALSE;
  //     }
  //   }
  // }
  return FALSE;
}

/**
 * Set a lock on a file.
 *
 * @param $file
 *   File name to clear.
 * @param $dir
 *   Directory where file resides.
 * @param $user
 *   Username of the user to lock the file for.
 */
function ft_edit_lock_set($file, $dir, $user) {
  global $ft;
  // if (ft_plugin_exists('db')) {
  //   // Clear any locks.
  //   ft_edit_lock_clear($file, $dir);
  //   // Set new lock.
  //   $sql = "INSERT INTO edit (dir, file, user, timestamp) VALUES ('" . sqlite_escape_string($dir) . "','" . sqlite_escape_string($file) . "','" . sqlite_escape_string($user) . "'," . time() . ")";
  //   sqlite_query($ft['db']['link'], $sql);              
  // }
}