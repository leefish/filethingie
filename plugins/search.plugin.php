<?php
/**
 * @file
 * Search plugin for File Thingie.
 * Author: Andreas Haugstrup Pedersen, Copyright 2008, All Rights Reserved
 */

/**
 * Implementation of hook_info.
 */
function ft_search_info() {
  return array(
    'name' => 'Search: Search files and folders.',
  );
}

/**
 * Implementation of hook_sidebar.
 */
function ft_search_sidebar() {
  $sidebar[] = array(
    "id" => "search_1",
    "content" => '<div class="section">
  		<h2>'.t('Search files &amp; folders').'</h2>
  		<form action="" method="post" id="searchform">
  			<div>
  				<input type="text" name="q" id="q" size="16" value="'.$_REQUEST['q'].'" />
  				<input type="button" id="dosearch" value="'.t('Search').'" />
  			</div>
  			<div id="searchoptions">
  				<input type="checkbox" name="type" id="type" checked="checked" /> <label for="type">'.t('Search only this folder and below').'</label>
  			</div>
  			<div id="searchresults"></div>
  		</form>
  	</div>'
  );
  return $sidebar;
}

/**
 * Implementation of hook_ajax.
 */
function ft_search_ajax($act) {
  if ($act == 'search') {
    $new = array();
  	$ret = "";
  	$q = $_POST['q'];
  	$type = $_POST['type'];
  	if (!empty($q)) {
  		if ($type == "true") {
  			$list = _ft_search_find_files(ft_get_dir(), $q);
  		} else {
  			$list = _ft_search_find_files(ft_get_root(), $q);
  		}
  		if (is_array($list)){
  			if (count($list) > 0) {
  				foreach ($list as $c) {
  					if (empty($c['dir'])) {
  						$c['dirlink'] = "/";
  					} else {
  						$c['dirlink'] = $c['dir'];
  					}
  					if ($c['type'] == "file") {
  					  $link = "<a href='".ft_get_root()."{$c['dir']}/{$c['name']}' title='" .t('Show !file', array('!file' => $c['name'])). "'>{$c['shortname']}</a>";
  					  if (HIDEFILEPATHS == TRUE) {
  					    $link = ft_make_link($c['shortname'], 'method=getfile&amp;dir='.rawurlencode($c['dir']).'&amp;file='.$c['name'], t('Show !file', array('!file' => $c['name'])));
  					  }
  						$ret .= "<dt>{$link}</dt><dd>".ft_make_link($c['dirlink'], "dir=".rawurlencode($c['dir'])."&amp;highlight=".rawurlencode($c['name'])."&amp;q=".rawurlencode($q), t("Highlight file in directory"))."</dd>";
  					} else {
  						$ret .= "<dt class='dir'>".ft_make_link($c['shortname'], "dir=".rawurlencode("{$c['dir']}/{$c['name']}")."&amp;q={$q}", t("Show files in !folder", array('!folder' => $c['name'])))."</dt><dd>".ft_make_link($c['dirlink'], "dir=".rawurlencode($c['dir'])."&amp;highlight=".rawurlencode($c['name'])."&amp;q=".rawurlencode($q), t("Highlight file in directory"))."</dd>";
  					}
  				}
  				return $ret;
  			} else {
  				return "<dt class='error'>".t('No files found').".</dt>";
  			}
  		} else {
  			return "<dt class='error'>".t('Error.')."</dt>";
  		}
  	} else {
  		return "<dt class='error'>".t('Enter a search string.')."</dt>";		
  	}
  }
}

/**
 * Implementation of hook_add_js_call.
 */
function ft_search_add_js_call() {
  $return = '';
  $return .= "$('#searchform').ft_search({\r\n";
  if (!empty($_REQUEST['dir'])) {
    $return .= "\tdirectory: '{$_REQUEST['dir']}',\r\n";
  } else {
    $return .= "\tdirectory: '',\r\n";
  }
  $return .= "\tformpost: '".ft_get_self()."',\r\n";
  $return .= "\theader: '".t('Results')."',\r\n";
  $return .= "\tloading: '".t('Fetching results&hellip;')."'\r\n";
  $return .= '});';
  return $return;
}

/**
 * Private function. Searches for file names and directories recursively.
 *
 * @param $dir
 *   Directory to search.
 * @param $q
 *   Search query.
 * @return An array of files. Each item is an array:
 *   array(
 *     'name' => '', // File name.
 *     'shortname' => '', // File name.
 *     'type' => '', // 'file' or 'dir'.
 *     'dir' => '', // Directory where file is located.
 *   )
 */
function _ft_search_find_files($dir, $q){
	$output = array();
	if (ft_check_dir($dir) && $dirlink = @opendir($dir)) {
		while(($file = readdir($dirlink)) !== false){
			if($file != "." && $file != ".." && ((ft_check_file($file) && ft_check_filetype($file)) || (is_dir($dir."/".$file) && ft_check_dir($file)))){
				$path = $dir.'/'.$file;
				// Check if filename/directory name is a match.
				if(stristr($file, $q)) {
					$new['name'] = $file;
					$new['shortname'] = ft_get_nice_filename($file, 20);
					$new['dir'] = substr($dir, strlen(ft_get_root()));
					if (is_dir($path)) {
            if (ft_check_dir($path)) {
  						$new['type'] = "dir";					    
    					$output[] = $new;
            }
					} else {
					  $new['type'] = "file";
  					$output[] = $new;
					}
				}
				// Check subdirs for matches.
				if(is_dir($path)) {
					$dirres = _ft_search_find_files($path, $q);
					if (is_array($dirres) && count($dirres) > 0) {
						$output = array_merge($dirres, $output);
						unset($dirres);						
					}
				}
			}
		}
		sort($output);
		closedir($dirlink);
		return $output;
	} else {
		return FALSE;
	}
}
