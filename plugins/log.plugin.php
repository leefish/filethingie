<?php
/**
 * @file
 * Log plugin for File Thingie.
 * Author: Andreas Haugstrup Pedersen, Copyright 2009, All Rights Reserved
 *
 * Must be loaded after the db plugin.
 */

define('FT_LOG_EMERG', 0);
define('FT_LOG_ALERT', 1);
define('FT_LOG_CRITICAL', 2);
define('FT_LOG_ERROR', 3);
define('FT_LOG_WARNING', 4);
define('FT_LOG_NOTICE', 5);
define('FT_LOG_INFO', 5);
define('FT_LOG_DEBUG', 7);

/**
 * Implementation of hook_info.
 */
function ft_log_info() {
  return array(
    'name' => 'Log: Enable logging functionality',
    'settings' => array(
      'viewlogs' => array(
        'default' => FALSE,
        'description' => t('Can user view and prune logs?'),
      ),
    ),
  );
}

function ft_log_severity_levels() {
  return array(
    FT_LOG_EMERG    => t('emergency'),
    FT_LOG_ALERT    => t('alert'),
    FT_LOG_CRITICAL => t('critical'),
    FT_LOG_ERROR    => t('error'),
    FT_LOG_WARNING  => t('warning'),
    FT_LOG_NOTICE   => t('notice'),
    FT_LOG_INFO     => t('info'),
    FT_LOG_DEBUG    => t('debug'),
  );
}

/**
 * Implementation of hook_secondary_menu.
 */
function ft_log_secondary_menu() {
  global $ft;
  if (isset($ft['plugins']['log']['settings']['viewlogs']) && $ft['plugins']['log']['settings']['viewlogs'] === TRUE) {
    return ft_make_link(t('[logs]'), "act=log", t("View logs"));
  }
  return '';
}

/**
 * Implementation of hook_init.
 */
function ft_log_init() {
  global $ft;
  // Check if DB plugin is loaded.
  if (ft_plugin_exists('db')) {
    // Check if we need to create new table.
    $sql = "CREATE TABLE log (
    id INTEGER PRIMARY KEY,
    type TEXT NOT NULL,
    message TEXT NOT NULL,
    location TEXT NOL NULL,
    timestamp DATE NOT NULL,
    user TEXT NOT NULL,
    hostname TEXT NOT NULL,
    severity INTEGER NOT NULL
    )";
    ft_db_install_table('log', $sql);
  }
}

/**
 * Implementation of hook_download.
 */
function ft_log_download($dir, $filename) {
  ft_log_insert('download', t('File downloaded.'), FT_LOG_INFO, $dir . '/' . $filename);
}

/**
 * Implementation of hook_upload.
 */
function ft_log_upload($dir, $filename) {
  ft_log_insert('upload', t('File uploaded.'), FT_LOG_INFO, $dir . '/' . $filename);
}

/**
 * Implementation of hook_loginfail.
 */
function ft_log_loginfail($username) {
  ft_log_insert('user', t('Failed login attempt for @user.', array('@user' => $username)), FT_LOG_NOTICE);
}

/**
 * Implementation of hook_loginsuccess.
 */
function ft_log_loginsuccess($username) {
  ft_log_insert('user', t('@user logged in.', array('@user' => $username)), FT_LOG_INFO);
}

/**
 * Implementation of hook_logout.
 */
function ft_log_logout($username) {
  ft_log_insert('user', t('@user logged out.', array('@user' => $username)), FT_LOG_INFO);
}

/**
 * Get list of types in the DB.
 */
function ft_log_get_types() {
  global $ft;
  static $types;
  if (!is_array($types)) {
    $sql = "SELECT DISTINCT(type) as type FROM log";
    $result = $ft['db']['link']->query($sql);
    $types = array();
    if ($result) {
      foreach($result as $row) {
        $types[] = $row['type'];
      }
    }
  }
  return $types;
}

/**
 * Get oldest and newest log item.
 */
function ft_log_get_boundaries() {
  global $ft;
  $sql = "SELECT MIN(timestamp) as oldest, MAX(timestamp) as newest FROM log";
  $result = $ft['db']['link']->query($sql);
  if ($result) {
    foreach ($result as $row) {
      // $ft['db']['link']->closeCursor();
      return $row;
    }
  }
  return FALSE;
}

/**
 * Get a list of date <option> tags.
 */
function ft_log_get_date_options($type) {
  for ($i=1;$i<=31;$i++) {
    $str .= '<option ' . ($i == $_GET[$type] ? 'selected="selected"' : '') . '>'.$i.'</option>';
  }
  return $str;
}

/**
 * Format a month string for the filters.
 */
function ft_log_format_month_option($year, $month) {
  return date('F Y', mktime(0, 0, 0, $month, 1, $year));
}

/**
 * Get a list of date <option> tags.
 */
function ft_log_get_month_options($month_min, $month_max, $year_min, $year_max, $type) {
  for($i=$year_min;$i<=$year_max;$i++) {
    if ($i == $year_min) {
      if ($year_min != $year_max) {
        for($j=$month_min;$j<=12;$j++) {
          $str .= '<option value="'.$i.'-'.$j.'" ' . ($i.'-'.$j == $_GET[$type] ? 'selected="selected"' : '') . '>'.ft_log_format_month_option($i, $j).'</option>';
        }
      }
      else {
        for($j=$month_min;$j<=$month_max;$j++) {
          $str .= '<option value="'.$i.'-'.$j.'"' . ($i.'-'.$j == $_GET[$type] ? 'selected="selected"' : '') . '>'.ft_log_format_month_option($i, $j).'</option>';
        }
      }
    }
    elseif ($i == $year_max) {
      for($j=1;$j<=$month_max;$j++) {
        $str .= '<option value="'.$i.'-'.$j.'"' . ($i.'-'.$j == $_GET[$type] ? 'selected="selected"' : '') . '>'.ft_log_format_month_option($i, $j).'</option>';
      }
    }
    else {
      // Intermediate year. Loop through all months.
      for($j=1;$j<=12;$j++) {
        $str .= '<option value="'.$i.'-'.$j.'"' . ($i.'-'.$j == $_GET[$type] ? 'selected="selected"' : '') . '>'.ft_log_format_month_option($i, $j).'</option>';
      }
    }
  }
  return $str;
}

/**
 * Do log query.
 */
function ft_log_do_query($type = '', $from_day = '', $from = '', $to_day = '', $to = '') {
  global $ft;
  $items = array();
  
  $types = ft_log_get_types();

  // Type filter
  $wheres = array();
  if (!empty($type) && in_array($type, $types)) {
    $wheres[] = 'type = '.$ft['db']['link']->quote($type);
  }
  
  // From date filter
  if (!empty($from)) {
    $from = explode('-', $from);
    $day = '01';
    if (is_numeric($from_day)) {
      $day = $from_day;
      $day = str_pad($day, 2, '0', STR_PAD_LEFT);
    }
    $from[1] = str_pad($from[1], 2, '0', STR_PAD_LEFT);
    $wheres[] = 'timestamp >= ' . $from[0] . '-' . $from[1] . '-' . $day;
  }
  
  // To date filter
  if (!empty($to)) {
    $to = explode('-', $to);
    $to_day = '31';
    if (is_numeric($to_day)) {
      $to_day = $to_day;
      $to_day = str_pad($to_day, 2, '0', STR_PAD_LEFT);
    }
    $to[1] = str_pad($to[1], 2, '0', STR_PAD_LEFT);
    $wheres[] = 'timestamp <= ' . $to[0] . '-' . $to[1] . '-' . $to_day;
  }
  
  if (count($wheres) > 0) {
    $sql = "SELECT * FROM log WHERE ".implode(' AND ', $wheres)." ORDER BY timestamp DESC";
    // print $sql;
  }
  else {
    // No filters, load everything.
    $sql = "SELECT * FROM log ORDER BY timestamp DESC";
  }
  $result = $ft['db']['link']->query($sql);
  if ($result) {
    foreach($result as $row) {
      $items[] = array(
        'type' => $row['type'],
        'message' => $row['message'],
        'location' => $row['location'],
        'timestamp' => $row['timestamp'],
        'user' => $row['user'],
      );
    }
  }
  return $items;
}

/**
 * Implementation of hook_page.
 */
function ft_log_page($act) {
  global $ft;
  $str = '';
  if ($act == 'log' && isset($ft['plugins']['log']['settings']['viewlogs']) && $ft['plugins']['log']['settings']['viewlogs'] === TRUE) {
    
    $types = ft_log_get_types();
    $boundaries = ft_log_get_boundaries();
    $month_min = date('m', strtotime($boundaries['oldest']));
    $month_max = date('m', strtotime($boundaries['newest']));
    $year_min = date('Y', strtotime($boundaries['oldest']));
    $year_max = date('Y', strtotime($boundaries['newest']));
    
    $str = "<h2>".t('Log')."</h2>";
    $str .= '<div>';
    
    // Filters.
    $str .= '<form action="'.ft_get_self().'?act=log" method="get">';
    $str .= '<div>';

    $str .= ' <label for="type">'.t('Show').' </label>';
    $str .= '<select id="type" name="type">';
    $str .= '<option value="">All types</option>';
    foreach ($types as $type) {
      $str .= '<option value="' . $type . '" ' . ($type == $_GET['type'] ? 'selected="selected"' : '') . '>' . $type . '</option>';
    }
    $str .= '</select> ';

    $str .= '<label for="from">'.t('from:').' </label>';
    $str .= '<select id="from_day" name="from_day">';
    $str .= '<option value="">--</option>';
    $str .= ft_log_get_date_options('from_day');
    $str .= '</select> ';
    $str .= '<select id="from" name="from">';
    $str .= '<option value="">Any month</option>';
    $str .= ft_log_get_month_options($month_min, $month_max, $year_min, $year_max, 'from');
    $str .= '</select>';

    $str .= ' <label for="to">'.t('to:').' </label>';
    $str .= '<select id="to_day" name="to_day">';
    $str .= '<option value="">--</option>';
    $str .= ft_log_get_date_options('to_day');
    $str .= '</select> ';
    $str .= '<select id="to" name="to">';
    $str .= '<option value="">Any month</option>';
    $str .= ft_log_get_month_options($month_min, $month_max, $year_min, $year_max, 'to');
    $str .= '</select> ';
    
    $str .= '<input type="submit" name="ft_submit" value="'.t('Show').'">';
    $str .= '<input type="hidden" name="act" value="log">';
    $str .= '</div>';
    $str .= '</form>';
    
    $str .= '<table id="log_table" class="tablesorter"><thead><tr><th>'.t('Type').'</th><th>'.t('Message').'</th><th>'.t('Location').'<th>'.t('Timestamp').'</th></tr></thead><tbody>';
    
    $items = ft_log_do_query($_GET['type'], $_GET['from_day'], $_GET['from'], $_GET['to_day'], $_GET['to']);
    
    foreach ($items as $row) {
      $str .= '<tr><td>' . $row['type'] . '</td><td>' . htmlentities($row['message']) . '</td><td>' . htmlentities($row['location']) . '</td><td>' . $row['timestamp'] . '</td></tr>';
    }
    $str .= '</tbody></table>';
    $str .= '<form action="'.ft_get_self().'" method="post" accept-charset="utf-8" style="margin-left:300px;"><div><input type="submit" value="'.t('Delete records older than 7 months').'"><input type="hidden" name="act" value="log_prune" /> ';
    $query = 'act=log_csv&type='.$_GET['type'].'&from_day='.$_GET['from_day'].'&from='.$_GET['from'].'&to_day='.$_GET['to_day'].'&to='.$_GET['to'];
    $str .= ft_make_link(t('Download CSV report'), $query, t("Download CSV report of current view"));
    $str .= '</div></form>';
    
    $str .= '</div>';
  }
  return $str;
}

/**
 * Implementation of hook_add_css.
 */
function ft_log_add_css() {
  return ".headerSortUp, .headerSortDown {background:#c30}
  #log_table {width:660px}";
}

/**
 * Implementation of hook_action.
 */
function ft_log_action($act) {
  global $ft;
  // Prune old records.
  if ($act == 'log_prune' && isset($ft['plugins']['log']['settings']['viewlogs']) && $ft['plugins']['log']['settings']['viewlogs'] === TRUE) {
    $offset = date('Y-m-d', strtotime('-7 months', gmmktime()));
    $sql = "DELETE FROM log WHERE timestamp <= '".$ft['db']['link']->quote($offset)."'";
    $result = $ft['db']['link']->query($sql);
    ft_set_message(t('Log pruned'));
    // Redirect.
    ft_redirect("act=log");
  }
  
  // View CSV log.
  if ($act == 'log_csv' && isset($ft['plugins']['log']['settings']['viewlogs']) && $ft['plugins']['log']['settings']['viewlogs'] === TRUE) {
    header('Content-type: text/plain;charset=UTF-8');
    $headers = array(t('Type'), t('Message'), t('Location'), t('Timestamp'), t('User'));
    $items = ft_log_do_query($_GET['type'], $_GET['from_day'], $_GET['from'], $_GET['to_day'], $_GET['to']);
    
    print str_putcsv($headers, ';') . "\n";
    foreach ($items as $row) {
      print str_putcsv($row, ';') . "\n";
    }
    exit;
  }
}

if(!function_exists('str_putcsv')) {
  function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
    // Open a memory "file" for read/write...
    $fp = fopen('php://temp', 'r+');
    // ... write the $input array to the "file" using fputcsv()...
    fputcsv($fp, $input, $delimiter, $enclosure);
    // ... rewind the "file" so we can read what we just wrote...
    rewind($fp);
    // ... read the entire line into a variable...
    $data = fgets($fp);
    // ... close the "file"...
    fclose($fp);
    // ... and return the $data to the caller, with the trailing newline from fgets() removed.
    return rtrim( $data, "\n" );
  }
}

/**
 * Implementation of hook_add_js_file.
 */
function ft_log_add_js_file() {
  $return = array();
  // Only add JS when we are on the log page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'log') {
    $return[] = PLUGINDIR . '/tablesorter/jquery.tablesorter.min.js';
  }
  return $return;
}

/**
 * Implementation of hook_add_js_call.
 */
function ft_log_add_js_call() {
  global $ft;
  $return = '';
  // Only add JS when we're on the log page.
  if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'log') {
  	// Unbind save action and rebind with a tinymce specific version.
  	$return .= '$("#log_table").tablesorter();';
  }
  return $return;
}

/**
 * Insert a log item.
 */
function ft_log_insert($type, $message, $severity = FT_LOG_NOTICE, $location = '', $user = '') {
  global $ft;
  
  if ($user == '') {
    $user = $_SESSION['ft_user_'.MUTEX];
  }
  
  $datetime = date('Y-m-d H:i:s', gmmktime());

  $query = $ft['db']['link']->prepare("INSERT INTO log (
    type, 
    message, 
    location, 
    timestamp, 
    user, 
    hostname, 
    severity
  ) VALUES (
    :type,
    :message,
    :location,
    :timestamp,
    :user,
    :hostname,
    :severity
  )");
  if (!$query) {
    print_r($ft['db']['link']->errorInfo());
  }
  $query->execute(array(
    ':type' => $type,
    ':message' => $message,
    ':location' => $location,
    ':timestamp' => $datetime,
    ':user' => $user,
    ':hostname' => $_SERVER['REMOTE_ADDR'],
    ':severity' => $severity
  ));
}
