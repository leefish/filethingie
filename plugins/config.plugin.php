<?php
/**
 * @file
 * Config plugin for File Thingie.
 * Author: Andreas Haugstrup Pedersen, Copyright 2008, All Rights Reserved
 */

/**
 * Implementation of hook_info.
 */
function ft_config_info() {
  return array(
    'name' => 'Config: Edit settings, users, group through a graphic interface.',
  );
}

/**
 * Implementation of hook_add_css.
 */
function ft_config_add_css() {
  return '#ft_group_form div.plugin_settings {margin-left:35px}
  #settings table, #plugins table, #group_name table, #ft_user_form table {border:none;width:auto;margin:0;}
  #settings td, #plugins td, #group_name td, #ft_user_form td {border:none;vertical-align:top}
  #settings td.setting, #group_name td.setting {width:150px}
  #plugins p.description {margin:0}
  #plugins .plugin_settings {background:#ccc;}
  #plugins, #settings, .submit_group, #group_name {margin:10px 25px}';
}

/**
 * Implementation of hook_add_js_call.
 */
function ft_config_add_js_call() {
  $return = '';
  // Handle group editing.
  if (!empty($_REQUEST['act']) && ($_REQUEST['act'] == 'user_edit_group' || $_REQUEST['act'] == 'user_add_group' || $_REQUEST['act'] == 'config_default')) {
    // Handle settings.
    $return .= '$("#settings :checkbox").each(function(){
      if ($(this).is(":checked")) {
      } else {
        $(this).parents("tr").find(".setting_data :input").attr({"disabled":"disabled"});
      }
    }).change(function(){
      if ($(this).is(":checked")) {
        $(this).parents("tr").find(".setting_data :input").removeAttr("disabled");
      } else {
        $(this).parents("tr").find(".setting_data :input").attr({"disabled":"disabled"});        
      }
    });';
    // Handle plugin settings.
    $return .= '$("#plugins :checkbox").each(function(){
      if ($(this).is(":checked")) {
      } else {
        $(this).parent().find("div.plugin_settings").hide();
      }
    }).change(function(){
      if ($(this).is(":checked")) {
        $(this).parent().find("div.plugin_settings").slideDown();
      } else {
        $(this).parent().find("div.plugin_settings").slideUp();
      }
    });';
    // Settings with OTHER values.
    $return .= '$("#settings select").each(function(){
      if ($(this).val() != "OTHER") {
        // Hide any OTHER values.
        $(this).parent().find("input.setting_other").hide();
      }
    }).change(function(){
      if ($(this).val() == "OTHER") {
        $(this).parent().find("input.setting_other").show();        
      } else {
        $(this).parent().find("input.setting_other").hide();        
      }
    });';
  }
  return $return;
}

/**
 * Implementation of hook_page.
 */
function ft_config_page($act) {
  global $ft;
  $str = '';
  if ($act == 'config') {
    // Default settings.
    $str .= '<div style="float:left;">';
    $str .= "<h2>".t('Default settings')."</h2>";
    $str .= "<p>".ft_make_link(t('Edit default settings'), 'act=config_default')."</p>";
    // Show a list of users.
    $str .= "<h2>".t('Users')."</h2>";
    $str .= "<p>".ft_make_link(t('Add new user'), 'act=user_add')."</p>";
    $str .= '<table><tr><th>'.t('Username').'</th><th>'.t('Group').'</th><th>'.t('Delete?').'</th></tr>';
    // Default user. Cannot be deleted or edited.
    if (USERNAME != '') {
      $str .= '<tr><td>'.USERNAME.'</td><td></td></tr>';      
    }
    // Other users.
    if (is_array($ft['users'])) {
      foreach($ft['users'] as $k => $v) {
        if (empty($v['group'])) {
          $v['group'] = '';
        }
        $str .= '<tr><td>'.ft_make_link($k, 'act=user_edit&amp;user='.$k, t('Edit !user', array('!user' => $k))).'</td><td>'.$v['group'].'</td><td>'.ft_make_link(t('delete'), 'act=user_delete&amp;value='.$k).'</td></tr>';
      }
    }
    $str .= '</table>';
    // Show groups
    $str .= "<h2>".t('Groups')."</h2>";
    $str .= "<p>".ft_make_link(t('Add new group'), 'act=user_add_group')."</p>";
    // Other users.
    if (is_array($ft['groups'])) {
      $str .= '<table><tr><th>'.t('Group').'</th><th>'.t('Users').'</th><th>'.t('Delete?').'</th></tr>';
      foreach($ft['groups'] as $k => $v) {
        $str .= '<tr><td>'.ft_make_link($k, 'act=user_edit_group&amp;group='.$k, t('Edit !user', array('!user' => $k))).'</td><td>';
        // Get users in this group.
        $c_users = ft_get_users_by_group($k);
        $str .= implode('<br>', $c_users);
        $str .= '</td><td>'.ft_make_link(t('delete'), 'act=user_delete_group&amp;value='.$k).'</td></tr>';
      }
    }
    $str .= '</table>';
    $str .= '</div>';
  } elseif ($act == 'config_default') {
    // Edit default settings and plugins.
    $str .= '<div style="float:left;">';
    $str .= "<h2>".t('Default settings and plugins')."</h2>";
    $str .= "<p>".t('Settings and plugins on this page will be used unless a group overrides the setting or disables the plugin.')."</p>";
    $str .= '<form action="'.ft_get_self().'" method="post" id="ft_group_form">';
    $ext = ft_settings_external_load();
    $str .= ft_config_make_settings('default', $ext['settings']);
    $str .= ft_config_make_plugins($ext['plugins']);
    $str .= '<div class="submit_group">';
    $str .= '<input type="hidden" name="act" value="config_default_submit">';        
    $str .= '<input type="submit" name="ft_submit" value="'.t('Save settings').'">';
    $str .= '<input type="submit" name="ft_submit" value="'.t('Cancel').'">
      </div>
    </form>';
    $str .= '</div>';
  } elseif ($act == 'user_add_group' || $act == 'user_edit_group') {
    // Add a new group.
    $group = '';
    $str .= '<div style="float:left;">';
    $str .= "<h2>".t('Add group')."</h2>";
    if ($act == 'user_edit_group') {
      $group = ft_settings_external_clean($_GET['group']);
      $str .= "<h2>".t('Edit user:')." ".$group."</h2>";      
    }
    $str .= '<form action="'.ft_get_self().'" method="post" id="ft_group_form">
      <fieldset id="group_name"><legend>'.t('Group name').'</legend><table><tr><td class="setting">
        <label for="user_group">'.t('Group name:').'</label></td><td>
        <input type="text" name="user_group" value="'.$group.'" id="user_user"></td></tr></table>
      </fieldset>';
    // Show default settings.
    if ($act == 'user_edit_group') {
      $str .= ft_config_make_settings('group', $ft['groups'][$group]);
      $str .= ft_config_make_plugins($ft['groups'][$group]['plugins']);
    } else {
      $str .= ft_config_make_settings('group', array());
      $str .= ft_config_make_plugins(array());
    }
    $str .= '</fieldset>';
    $str .= '<div class="submit_group">';
    if ($act == 'user_edit_group') {
      $str .= '<input type="hidden" name="group_old" value="'.$group.'">';
      $str .= '<input type="hidden" name="act" value="user_edit_submit_group">';
      $str .= '<input type="submit" name="ft_submit" value="'.t('Edit group').'">';
    } else {
      $str .= '<input type="hidden" name="act" value="user_add_submit_group">';        
      $str .= '<input type="submit" name="ft_submit" value="'.t('Add group').'">';
    }
    $str .= '<input type="submit" name="ft_submit" value="'.t('Cancel').'">
      </div>
    </form>';
    $str .= '</div>';
  } elseif ($act == 'user_add' || $act == 'user_edit') {
    $user = '';
    $group = NULL;
    $str .= '<div style="float:left;">';
    $str .= "<h2>".t('Add user')."</h2>";
    if (!empty($_GET['user'])) {
      $user = ft_settings_external_clean($_GET['user']);
      if (is_array($ft['users']) && is_array($ft['users'][$user]) && !empty($ft['users'][$user]['group'])) {
        $group = $ft['users'][$user]['group'];
      }
      $str .= "<h2>".t('Edit user:')." ".$user."</h2>";
    }
    $str .= '<form id="ft_user_form" action="'.ft_get_self().'" method="post"><table>
      <tr><td>
        <label for="user_user">'.t('Username:').'</label></td><td>
        <input type="text" name="user_user" value="'.$user.'" id="user_user">
      </td></tr>
      <tr><td>
        <label for="user_pass">'.t('Password:').'</label></td><td>
        <input type="password" name="user_pass" value="" id="user_pass">
      </td></tr>
      <tr><td>
        <label for="user_pass2">'.t('Confirm:').'</label></td><td>
        <input type="password" name="user_pass2" value="" id="user_pass2">
      </td></tr>
      <tr><td>
        <label for="user_group">'.t('Group:').'</label></td><td>
        <select name="user_group" id="user_group">
          <option value="">'.htmlentities(t('<none> (default settings)')).'</option>';
      // Get list of groups.
      if (is_array($ft['groups'])) {
        foreach($ft['groups'] as $k => $v) {
          if ($act == 'user_edit' && $k === $group) {
            $str .= '<option selected="selected">'.$k.'</option>';
          } else {
            $str .= '<option>'.$k.'</option>';            
          }
        }
      }
      $str .= '</select></td></tr></table>
      <div class="submit_group">';
      if ($act == 'user_edit') {
        $str .= '<input type="hidden" name="user_old" value="'.$user.'">';
        $str .= '<input type="hidden" name="act" value="user_edit_submit">';
        $str .= '<input type="submit" name="ft_submit" value="'.t('Edit user').'">';
      } else {
        $str .= '<input type="hidden" name="act" value="user_add_submit">';        
        $str .= '<input type="submit" name="ft_submit" value="'.t('Add user').'">';
      }
      $str .= '<input type="submit" name="ft_submit" value="'.t('Cancel').'">
      </div>
    </form>';
      $str .= '</div>';
  } elseif ($act == 'user_delete' || $act == 'user_delete_group') {
    $value = ft_settings_external_clean($_GET['value']);
    $str .= '<div style="float:left;">';
    $str .= "<h2>".t('Delete user: !user', array('!user' => $value))."</h2>";
    $str .= "<p>".t('Are you sure you want to continue? This will delete the user from the system.')."</p>";
    $type = 'user';
    if ($act == 'user_delete_group') {
      $str = "<h2>".t('Delete group: !group', array('!group' => $value))."</h2>";
      $userlist = ft_get_users_by_group($value);
      if (count($userlist) > 0) {
        $userlist = '<ul><li>'.implode('</li><li>', $userlist).'</li></ul>';        
      } else {
        $userlist = '<ul><li class="error">'.t('This group has no users to delete.').'</li></ul>';
      }
      $str .= "<p>".t('Are you sure you want to continue? This will delete the group and all users in this group.')."{$userlist}</p>";
      $type = 'group';
    }

    $str .= '<form action="'.ft_get_self().'" method="post">';
    $str .= '<div>';
    $str .= '<input type="hidden" name="value" value="'.$value.'">';
    $str .= '<input type="hidden" name="act" value="user_delete_submit">';
    if ($act == 'user_delete') {
      $str .= '<input type="hidden" name="type" value="user">';
      $str .= '<input type="submit" name="ft_submit" value="'.t('Yes, delete this user').'">';
    } else {
      $str .= '<input type="hidden" name="type" value="group">';
      $str .= '<input type="submit" name="ft_submit" value="'.t('Yes, delete this group and all users in it').'">';
    }
    $str .= '<input type="submit" name="ft_submit" value="'.t('Cancel').'">
      </div>
    </form>';
    $str .= '</div>';
  }
  return $str;
}

/**
 * Implementation of hook_secondary_menu.
 */
function ft_config_secondary_menu() {
  return ft_make_link(t('[config]'), "act=config", t("Change File Thingie settings"));
}

/**
 * Implementation of hook_action.
 */
function ft_config_action($act) {
  global $ft;
  // Change the default settings and plugins.
  if ($act == 'config_default_submit') {
    // Check for cancel.
    if (strtolower($_REQUEST["ft_submit"]) == strtolower(t("Cancel"))) {
		  ft_redirect("act=config");
		}
    // Loop through settings.
    $ext = ft_settings_external_load();
    foreach ($ft['default_settings'] as $k => $v) {
      if (isset($_POST['setting_setting'][$k])) {
        if ($_POST['setting_setting'][$k] == 'TRUE') {
          $ext['settings'][$k] = TRUE;
        } elseif ($_POST['setting_setting'][$k] == 'FALSE') {
          $ext['settings'][$k] = FALSE;
        } elseif (($k == 'UPLOAD' || $k == 'FILEACTIONS') && $_POST['setting_setting'][$k] == 'OTHER') {
          // UPLOAD and FILEACTIONS can have an 'other' value.
          if (empty($_POST['setting_other_setting'][$k])) {
            // If the other value is empty, turn off the setting.
            $ext['settings'][$k] = FALSE;
          } else {
            $ext['settings'][$k] = ft_settings_external_clean($_POST['setting_other_setting'][$k]);
          }
        } else {
          $ext['settings'][$k] = ft_settings_external_clean($_POST['setting_setting'][$k]);
        }
      }
    }
    // Loop through plugins. Reset existing plugins.
    $ext['plugins'] = array();
    foreach($_POST['plugins'] as $plugin_name => $plugin_status) {
      if ($plugin_status != '') {
        // Plugin enabled.
        $ext['plugins'][$plugin_name] = TRUE;
        // Check if there are settings attached to this plugin.
        if (isset($_POST['setting_plugins']) && is_array($_POST['setting_plugins']) && isset($_POST['setting_plugins'][$plugin_name])) {
          $ext['plugins'][$plugin_name] = array();
          $ext['plugins'][$plugin_name]['settings'] = array();
          foreach ($_POST['setting_plugins'][$plugin_name] as $plugin_setting_name => $plugin_setting_value) {
            if ($plugin_setting_value == 'TRUE') {
              $ext['plugins'][$plugin_name]['settings'][$plugin_setting_name] = TRUE;
            } elseif ($plugin_setting_value == 'FALSE') {
              $ext['plugins'][$plugin_name]['settings'][$plugin_setting_name] = FALSE;
            } else {
              $ext['plugins'][$plugin_name]['settings'][$plugin_setting_name] = ft_settings_external_clean($plugin_setting_value);              
            }
          }
        }
      }
    }
    // Save new settings.
    if (function_exists('ft_settings_external_load') && function_exists('ft_settings_external_save')) {
      // Save.
      if (ft_settings_external_save($ext)) {
        ft_set_message(t("Settings saved."), 'ok');
      } else {
        ft_set_message(t("Settings could not be saved. Config file could not be updated."), 'error');
      }
    } else {
      ft_set_message(t("External config file not found."), 'error');
      ft_redirect("act=config_default");
    }
    ft_redirect("act=config");
  // Add new group or edit group.
  } elseif ($act == 'user_add_submit_group' || $act == 'user_edit_submit_group') {
    // Check for cancel.
    if (strtolower($_REQUEST["ft_submit"]) == strtolower(t("Cancel"))) {
		  ft_redirect("act=config");
		}
    $group = array();
    $group_name = (string)$_POST['user_group'];
    $group_name = ft_settings_external_clean($group_name);
    $group_old = '';
    if ($act == 'user_edit_submit_group') {
      $group_old = (string)$_POST['group_old'];
      $group_old = ft_settings_external_clean($group_old);
    }
    // Loop through defaults.
    if (!empty($_POST['setting_default']) && is_array($_POST['setting_default'])) {
      foreach($_POST['setting_default'] as $setting => $v) {
        // Differs from default. Add setting.
        if ($v != '') {
          if ($_POST['setting_setting'][$setting] == 'TRUE') {
            $group[$setting] = TRUE;
          } elseif ($_POST['setting_setting'][$setting] == 'FALSE') {
            $group[$setting] = FALSE;
          } elseif (($setting == 'UPLOAD' || $setting == 'FILEACTIONS') && $_POST['setting_setting'][$setting] == 'OTHER') {
            // UPLOAD and FILEACTIONS can have an 'other' value.
            if (empty($_POST['setting_other_setting'][$setting])) {
              // If the other value is empty, turn off the setting.
              $group[$setting] = FALSE;            
            } else {
              $group[$setting] = ft_settings_external_clean($_POST['setting_other_setting'][$setting]);
            }
          } else {
            $group[$setting] = ft_settings_external_clean($_POST['setting_setting'][$setting]);
          }
        }
      }
    }
    // Loop through group plugins.
    if (!empty($_POST['plugins']) && is_array($_POST['plugins'])) {
      $group['plugins'] = array();
      foreach($_POST['plugins'] as $plugin_name => $plugin_status) {
        if ($plugin_status != '') {
          // Plugin enabled. Add to group.
          $group['plugins'][$plugin_name] = TRUE;
          // Check if there are settings attached to this plugin.
          if (isset($_POST['setting_plugins']) && is_array($_POST['setting_plugins']) && isset($_POST['setting_plugins'][$plugin_name])) {
            // $group['plugins'][$plugin_name] = array('test', 'test2');
            $group['plugins'][$plugin_name] = array();
            $group['plugins'][$plugin_name]['settings'] = array();
            foreach ($_POST['setting_plugins'][$plugin_name] as $plugin_setting_name => $plugin_setting_value) {
              if ($plugin_setting_value == 'TRUE') {
                $group['plugins'][$plugin_name]['settings'][$plugin_setting_name] = TRUE;
              } elseif ($plugin_setting_value == 'FALSE') {
                $group['plugins'][$plugin_name]['settings'][$plugin_setting_name] = FALSE;
              } else {
                $group['plugins'][$plugin_name]['settings'][$plugin_setting_name] = ft_settings_external_clean($plugin_setting_value);              
              }
            }
          }
        }
      }
    }
    // Make sure group name is not in use or that we're editing without changing name.
    if (!isset($ft['groups'][$group_name]) || ($act == 'user_edit_submit_group' && $group_name == $group_old)) {
      // New group okay.
      if (function_exists('ft_settings_external_load') && function_exists('ft_settings_external_save')) {
        $ext = ft_settings_external_load();
        if (!isset($ext['groups'])) {
          $ext['groups'] = array();
        }
        // Splice in new/updated user.
        $ext['groups'][$group_name] = $group;
        // Save.
        if (ft_settings_external_save($ext)) {
          ft_set_message(t("!group was added/updated.", array('!group' => $group_name)), 'ok');
        } else {
          ft_set_message(t("Group could not be added/updated. Config file could not be updated."), 'error');
        }
      } else {
        ft_set_message(t("External config file not found."), 'error');
        ft_redirect("act=user_add_group");
      }
    } else {
      // New group name already in use.
      ft_set_message(t("Group could not be added. Name already in use."), 'error');
      ft_redirect("act=user_add_group");
    }
    ft_redirect("act=config");
  } elseif ($act == 'user_add_submit' || $act == 'user_edit_submit') {
    // Adding new user or editing user.
    // Check for cancel.
    if (strtolower($_REQUEST["ft_submit"]) == strtolower(t("Cancel"))) {
		  ft_redirect("act=config");
		}
    $user = (string)$_POST['user_user'];
    $user_old = '';
    if (!empty($_POST['user_old'])) {
      $user_old = (string)$_POST['user_old'];      
    }
    $pass = (string)$_POST['user_pass'];
    $pass2 = (string)$_POST['user_pass2'];
    $group = (string)$_POST['user_group'];
    // Make sure passwords match.
    if ($pass == $pass2) {
      // Strip tags and other nasty characters.
      $user = ft_settings_external_clean($user);
      $user_old = ft_settings_external_clean($user_old);
      $pass = ft_settings_external_clean($pass);
      $group = ft_settings_external_clean($group);
      // Make sure there are no empty strings.
      if (strlen($user) > 0 && (strlen($pass) > 0 || $act == 'user_edit_submit')) {
        // Check that user doesn't already exists. Or that we're editing a user.
        if (!ft_check_user($user) || ($act == 'user_edit_submit' && ($user == $user_old))) {
          // Make new user.
          $new = array();
          $new['password'] = $pass;
          if (!empty($group) && isset($ft['groups']) && isset($ft['groups'][$group])) {
            $new['group'] = $group;          
          }
          // If we're editing and the password is blank load existing password.
          if ($act == 'user_edit_submit' && empty($pass)) {
            $new['password'] = $ft['users'][$user_old]['password'];
          }
          if (function_exists('ft_settings_external_load') && function_exists('ft_settings_external_save')) {
            $ext = ft_settings_external_load();
            // Splice in new/updated user.
            $ext['users'][$user] = $new;
            // Remove old user.
            if ($act == 'user_edit_submit' && $user != $user_old) {
              unset($ext['users'][$user_old]);
            }
            // Save.
            if (ft_settings_external_save($ext)) {
              ft_set_message(t("!user was added/updated.", array('!user' => $user)), 'ok');
            } else {
              ft_set_message(t("User could not be added/updated. Config file could not be updated."), 'error');
            }
          } else {
            ft_set_message(t("External config file not found."), 'error');
          }
        } else {
          ft_set_message(t("User could not be added. Username already in use."), 'error');
        }
      } else {
        ft_set_message(t("You must enter both a username and a password."), 'error');
      }
    } else {
      ft_set_message(t("Passwords didn't match."), 'error');
    }
    ft_redirect("act=config");
  } elseif ($act == 'user_delete_submit') {
    // Check for cancel.
    if (strtolower($_REQUEST["ft_submit"]) == strtolower(t("Cancel"))) {
		  ft_redirect("act=config");
		}
    $ext = ft_settings_external_load();
    if ($_POST['type'] == 'user') {
      $msg = t("User was deleted.");
      // Remove user.
      unset($ext['users'][$_POST['value']]);
    } elseif ($_POST['type'] == 'group') {
      $msg = t("Group was deleted.");
      // Remove group.
      unset($ext['groups'][$_POST['value']]);      
      // Loop through users and remove.
      foreach ($ext['users'] as $user => $v) {
        if ($v['group'] == $_POST['value']) {
          unset($ext['users'][$user]);
        }
      }
    }
    // Save config file.
    if (ft_settings_external_save($ext)) {
      ft_set_message($msg);
    } else {
      ft_set_message(t("Config file could not be updated."), 'error');
    }
    ft_redirect("act=config");
  }
}

/**
 * Build form widget for settings values.
 *
 * @param $name
 *   Name of setting.
 * @param $value
 *   Current value or default value.
 * @return HTML for element(s).
 */
function ft_config_build_widget($name, $value) {
  $str = '';

  // UPLOAD and FILEACTIONS are special cases with mixed BOOL/STRING content.
  if ($name == 'setting[UPLOAD]' || $name == 'setting[FILEACTIONS]') {
    $str .= '<select id="settings_'.$name.'" name="setting_'.$name.'">';
    if ($value === TRUE) {
      $str .= '<option value="TRUE">TRUE</option><option value="FALSE">FALSE</option><option value="OTHER">'.t('Only in this folder:').'</option>';
    } elseif ($value === FALSE) {
      $str .= '<option value="TRUE">TRUE</option><option selected="selected" value="FALSE">FALSE</option><option value="OTHER">'.t('Only in this folder:').'</option>';
    } else {
      $str .= '<option value="TRUE">TRUE</option><option value="FALSE">FALSE</option><option selected="selected" value="OTHER">'.t('Only in this folder:').'</option>';
    }
    $str .= '</select>';
    if (!is_string($value)) {
      $value = '';
    }
    $str .= '<input class="setting_other" type="text" value="'.$value.'" name="setting_other_'.$name.'" id="setting_'.$name.'_other">';
    
    return $str;
  }
  // We must convert the octal permission before output.
  if ($name == 'setting[PERMISSION]') {
    $value = decoct($value);
  }
  if (is_bool($value)) {
    if ($value === TRUE) {
      $str = '<select id="settings_'.$name.'" name="setting_'.$name.'"><option value="TRUE">TRUE</option><option value="FALSE">FALSE</option></select>';      
    } else {
      $str = '<select id="settings_'.$name.'" name="setting_'.$name.'"><option value="TRUE">TRUE</option><option selected="selected" value="FALSE">FALSE</option></select>';
    }      
  } elseif (is_string($value)) {
    $str = '<input type="text" value="'.$value.'" name="setting_'.$name.'" id="setting_'.$name.'">';    
  } elseif (is_int($value)) {
    $str = '<input type="text" value="'.$value.'" name="setting_'.$name.'" id="setting_'.$name.'">';      
  }
  return $str;
}

/**
 * Create the HTML fieldset for available settings.
 *
 * @param $type
 *   'default' to show all settings. 'group' to show only the group settings with checkboxes.
 * @param $currentvalues
 *   Array of current settings.
 * @return HTML for output.
 */
function ft_config_make_settings($type = 'default', $currentvalues) {
  global $ft;
  $str = '<fieldset id="settings"><legend>'.t('Customize settings').'</legend><table>';
  foreach($ft['default_settings'] as $name => $value) {
    $banned = array('PERMISSION');
    if ($type != 'default') {
      $banned = array('LOGIN', 'PERMISSION', 'REQUEST_URI');      
    }
    if (substr($name, 0, 6) != 'COLOUR' && !in_array($name, $banned)) {
      $override = '';
      // See if there is a group override when editing.
      if (isset($currentvalues[$name])) {
        // Set new $value for the override
        $value = $currentvalues[$name];
        $override = ' checked="checked"';
      }
      $str .= '<tr><td class="setting">';
      if ($type == 'default') {
        $str .= '<label for="setting_setting['.$name.']">'.strtolower($name).'</label>';
      } else {
        $str .= '<input type="checkbox"'.$override.' name="setting_default['.$name.']" id="setting_'.$name.'_default"><label for="setting_'.$name.'">'.strtolower($name).'</label>';        
      }
      $str .= '</td><td><span class="setting_data">';
      $str .= ft_config_build_widget('setting['.$name.']', $value);
      $str .= '</span></td></tr>';
    }
  }
  $str .= '</table></fieldset>';
  return $str;
}
/**
 * Create the HTML fieldset for available plugins.
 *
 * @param $currentvalues
 *   Array of current plugins.
 * @return HTML for output.
 */
function ft_config_make_plugins($currentvalues) {
  global $ft;

  $plugin_list = ft_plugins_list();
  $str = '<fieldset id="plugins"><legend>'.t('Customize plugins').'</legend>';
  foreach($plugin_list as $name => $info) {
    $override = '';
    // See if there is a override when editing.
    if (isset($currentvalues[$name])) {
      $override = ' checked="checked"';
    }
    $str .= '<div><input type="checkbox"'.$override.' name="plugins['.$name.']" id="plugins_'.$name.'"><label for="plugins_'.$name.'">'.$info['name'].'</label> ';
    // Loop through settings for this plugin.
    if (isset($info['settings']) && is_array($info['settings'])) {
      $str .= '<div class="plugin_settings"><table>';
      foreach ($info['settings'] as $setting_name => $setting_info) {
        $value = $setting_info['default'];
        if (isset($currentvalues[$name]['settings'][$setting_name])) {
          $value = $currentvalues[$name]['settings'][$setting_name];
        }
        $str .= '<tr><td>';
        $str .= '<label for="setting_plugins['.$name.']['.$setting_name.']">'.strtolower($setting_name).'</label></td><td>';
        $str .= ft_config_build_widget('plugins['.$name.']['.$setting_name.']', $value);
        $str .= '<p class="description">'.$setting_info['description'].'</p>';
        $str .= '</td></tr>';
      }
      $str .= '</table></div>';
    }
    $str .= '</div>';
  }
  $str .= '</fieldset>';
  return $str;
}