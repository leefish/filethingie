<?php
/**
 * @file
 * File Info plugin for File Thingie.
 * Author: Andreas Haugstrup Pedersen, Copyright 2008, All Rights Reserved
 */

// TODO: Output descriptions next to file names.
// TODO: Ajax callback for editing/creating descriptions.

/**
 * Implementation of hook_info.
 */
function ft_fileinfo_info() {
  return array(
    'name' => 'Fileinfo: Add descriptions to files.',
  );
}

/**
 * Implementation of hook_init.
 */
function ft_fileinfo_init() {
  // Check if DB plugin is loaded.
  if (!ft_plugin_exists('db')) {
    ft_set_message(t('!name plugin not enabled because required !required plugin was not found.', array('!name' => 'Fileinfo', '!required' => 'db')), 'error');
    ft_plugin_unload('fileinfo');
  } else {
    // Check if we need to create new table.
    $sql = "CREATE TABLE fileinfo (
      dir TEXT NOT NULL,
      file TEXT NOT NULL,
      description TEXT
    )";
    ft_db_install_table('fileinfo', $sql);    
  }
}

/**
 * Implementation of hook_add_css.
 */
function ft_fileinfo_add_css() {
  return '#filelist p.fileinfo, #filelist span.addfileinfo {float:right;cursor:pointer;font-size:0.8em;color:#999;margin-top:0;margin-bottom:0;}
  #filelist p.fileinfo input {width:100%}';
}

/**
 * Implementation of hook_dirlist.
 */
function ft_fileinfo_dirlist() {
  global $ft;
  $sql = 'SELECT name, description FROM fileinfo WHERE dir = '.sqlite_escape_string(ft_get_dir());
  $result = sqlite_query($ft['db']['link'], $sql);
  if ($result) {
    while ($entry = sqlite_fetch_array($query, SQLITE_ASSOC)) {
      $ft['fileinfo']['descriptions'][$entry['file']] = $entry['description'];
    }
  }
}

/**
 * Implementation of hook_filename.
 */
function ft_fileinfo_filename($file) {
  global $ft;
  if (isset($ft['fileinfo']) && isset($ft['fileinfo']['descriptions']) && is_array($ft['fileinfo']['descriptions']) && array_key_exists($file, $ft['fileinfo']['descriptions'])) {
    // Found description.
    // return "<p class='fileinfo'>{$ft['fileinfo']['descriptions'][$file]}</p>";
  } else {
    // No description.
    // return "<span class='addfileinfo'>+</span>";
  }
  return '';
}

/**
 * Implementation of hook_add_js_file.
 */
function ft_fileinfo_add_js_file() {
  return array(PLUGINDIR.'/jquery.fileinfo.js');
}

/**
 * Implementation of hook_add_js_call.
 */
function ft_fileinfo_add_js_call() {
  $return = '';
  $return .= "$('#filelist').ft_fileinfo();";
  return $return;
}
