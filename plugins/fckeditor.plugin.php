<?php
/**
 * @file
 * FCKEditor plugin for File Thingie.
 * Author: Andreas Haugstrup Pedersen, Copyright 2008, All Rights Reserved
 *
 * Must be loaded after the edit plugin.
 */

/**
 * Implementation of hook_info.
 */
function ft_fckeditor_info() {
  return array(
    'name' => 'FCK editor: Enables editing using FCK editor',
    'settings' => array(
      'list' => array(
        'default' => 'html htm',
        'description' => t('List of file extensions to edit using fckeditor.'),
      ),
      'path' => array(
        'default' => 'fckeditor/fckeditor.js',
        'description' => t('Path to fckeditor.js'),
      ),
      'BasePath' => array(
        'default' => 'fckeditor/',
        'description' => t('BasePath'),
      ),
    ),
  );
}

/**
 * Implementation of hook_add_js_file.
 */
function ft_fckeditor_add_js_file() {
  global $ft;
  $return = array();
  // Only add JS when we are on an edit page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'edit' && file_exists($ft['plugins']['fckeditor']['settings']['path'])) {
    $return[] = $ft['plugins']['fckeditor']['settings']['path'];
  }
  return $return;
}

/**
 * Implementation of hook_add_js_call.
 */
function ft_fckeditor_add_js_call() {
  global $ft;
  $return = '';
  // Only add JS when we're on an edit page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'edit' && file_exists($ft['plugins']['fckeditor']['settings']['path'])) {
    $list = explode(" ", $ft['plugins']['fckeditor']['settings']['list']);
    if (in_array(ft_get_ext(strtolower($_REQUEST['file'])), $list)) {
      $return .= 'var oFCKeditor = new FCKeditor("filecontent") ;
      oFCKeditor.BasePath = "'.$ft['plugins']['fckeditor']['settings']['BasePath'].'" ;
      oFCKeditor.ReplaceTextarea() ;';
      
    	// Unbind save action and rebind with a fckeditor specific version.
      $return .= '$("#save").unbind();$("#save").click(function(){
       $("#savestatus").empty().append("<p class=\"ok\">'.t('Saving file&hellip;').'</p>");
       // Get file content from fckeditor.
       oEditor = FCKeditorAPI.GetInstance("filecontent");
       filecontent = oEditor.GetHTML();
       $.post("'.ft_get_self().'", {method:\'ajax\', act:\'saveedit\', file: $(\'#file\').val(), dir: $(\'#dir\').val(), filecontent: filecontent, convertspaces: $(\'#convertspaces\').val()}, function(data){
         $("#savestatus").empty().append(data);
       });
      });';
    }
  }
  return $return;
}