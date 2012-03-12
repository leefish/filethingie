<?php
/**
 * @file
 * TinyMCE plugin for File Thingie.
 * Author: Andreas Haugstrup Pedersen, Copyright 2008, All Rights Reserved
 *
 * Must be loaded after the edit plugin.
 */

/**
 * Implementation of hook_info.
 */
function ft_tinymce_info() {
  return array(
    'name' => 'TinyMCE: Edit files using the TinyMCE editor.',
    'settings' => array(
      'list' => array(
        'default' => 'html htm',
        'description' => t('List of file extensions to edit using tinymce.'),
      ),
      'path' => array(
        'default' => 'tinymce/jscripts/tiny_mce/tiny_mce.js',
        'description' => t('Path to tiny_mce.js'),
      ),
    ),
  );
}

/**
 * Implementation of hook_add_js_file.
 */
function ft_tinymce_add_js_file() {
  global $ft;
  $return = array();
  // Only add JS when we are on an edit page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'edit' && file_exists($ft['plugins']['tinymce']['settings']['path'])) {
    $return[] = $ft['plugins']['tinymce']['settings']['path'];
  }
  return $return;
}

/**
 * Implementation of hook_add_js_call.
 */
function ft_tinymce_add_js_call() {
  global $ft;
  $return = '';
  // Only add JS when we're on an edit page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'edit' && file_exists($ft['plugins']['tinymce']['settings']['path'])) {
    $list = explode(" ", $ft['plugins']['tinymce']['settings']['list']);
    if (in_array(ft_get_ext(strtolower($_REQUEST['file'])), $list)) {
    	// Unbind save action and rebind with a tinymce specific version.
    	$return .= '$("#save").unbind();$("#save").click(function(){
  			$("#savestatus").empty().append("<p class=\"ok\">'.t('Saving file&hellip;').'</p>");
  			// Get file content from tinymce.
  			filecontent = tinyMCE.activeEditor.getContent();
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
function ft_tinymce_add_js_call_footer() {
  global $ft;
  $return = '';
  // Only add JS when we're on an edit page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'edit') {
    if (file_exists($ft['plugins']['tinymce']['settings']['path'])) {
      $list = explode(" ", $ft['plugins']['tinymce']['settings']['list']);
      if (in_array(ft_get_ext(strtolower($_REQUEST['file'])), $list)) {
        $return = 'tinyMCE.init({
          mode : "exact",
          elements : "filecontent",
          theme : "advanced",
          theme_advanced_toolbar_location : "top",
          theme_advanced_toolbar_align : "left"
        });';
      } else {
        $return = '// File not in TinyMCE edit list.';
      }
    } else {
      $return = '// TinyMCE file not found: ' . $ft['plugins']['tinymce']['settings']['path'];
    }
  }
  return $return;
}

