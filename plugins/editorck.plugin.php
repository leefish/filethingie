<?php
/**
 * @file
 * CK Editor plugin for File Thingie.
 * Author: Andreas Haugstrup Pedersen, Copyright 2010, All Rights Reserved
 *
 * Must be loaded after the edit plugin.
 */

/**
 * Implementation of hook_info.
 */
function ft_editorck_info() {
  return array(
    'name' => 'ckeditor: Edit files using the ckeditor editor.',
    'settings' => array(
      'list' => array(
        'default' => 'html htm',
        'description' => t('List of file extensions to edit using ckeditor.'),
      ),
      'path' => array(
        'default' => 'ckeditor/ckeditor.js',
        'description' => t('Path to ckeditor.js'),
      ),
    ),
  );
}

/**
 * Implementation of hook_add_js_file.
 */
function ft_editorck_add_js_file() {
  global $ft;
  $return = array();
  // Only add JS when we are on an edit page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'edit' && file_exists($ft['plugins']['editorck']['settings']['path'])) {
    $return[] = $ft['plugins']['editorck']['settings']['path'];
  }
  return $return;
}

/**
 * Implementation of hook_add_js_call.
 */
function ft_editorck_add_js_call() {
  global $ft;
  $return = '';
  // Only add JS when we're on an edit page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'edit' && file_exists($ft['plugins']['editorck']['settings']['path'])) {
    $list = explode(" ", $ft['plugins']['editorck']['settings']['list']);
    if (in_array(ft_get_ext(strtolower($_REQUEST['file'])), $list)) {
    	// Unbind save action and rebind with a ckeditor specific version.
    	$return .= '$("#save").unbind();$("#save").click(function(){
  			$("#savestatus").empty().append("<p class=\"ok\">'.t('Saving file&hellip;').'</p>");
  			// Get file content from ckeditor.
  			filecontent = CKEDITOR.instances.filecontent.getData();;
  			$.post("'.ft_get_self().'", {method:\'ajax\', act:\'saveedit\', file: $(\'#file\').val(), dir: $(\'#dir\').val(), filecontent: filecontent, convertspaces: $(\'#convertspaces\').val()}, function(data){
  				$("#savestatus").empty().append(data);
  			});
  		});';
    }
  }
  return $return;
}

/**
 * Implementation of hook_add_js_call_footer.
 */
function ft_editorck_add_js_call_footer() {
  global $ft;
  $return = '';
  // Only add JS when we're on an edit page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'edit') {
    if (file_exists($ft['plugins']['editorck']['settings']['path'])) {
      $list = explode(" ", $ft['plugins']['editorck']['settings']['list']);
      if (in_array(ft_get_ext(strtolower($_REQUEST['file'])), $list)) {
        $return = 'CKEDITOR.replace("filecontent");';
      } else {
        $return = '// File not in ckeditor edit list.';
      }
    } else {
      $return = '// ckeditor file not found: ' . $ft['plugins']['editorck']['settings']['path'];
    }
  }
  return $return;
}

