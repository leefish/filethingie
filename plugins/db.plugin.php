<?php
/**
 * @file
 * Database plugin for File Thingie.
 * Author: Andreas Haugstrup Pedersen, Copyright 2008, All Rights Reserved
 */

/**
 * Implementation of hook_info.
 */
function ft_db_info() {
  return array(
    'name' => 'Database: Enabled a database for other plugins to use.',
    'settings' => array(
      'path' => array(
        'default' => 'data/ft_db',
        'description' => t('The path to the database file.'),
      ),
      'flushtables' => array(
        'default' => FALSE,
        'description' => t('Can user delete database tables?'),
      ),
    ),
  );
}

/**
 * Implementation of hook_init.
 */
function ft_db_init() {
  global $ft;

  if (extension_loaded('pdo_sqlite')) {
    if ($ft['db']['link'] = new PDO('sqlite:'.$ft['plugins']['db']['settings']['path'])) {
      // Get a list of tables. That way other modules can determine if they need to install themselves or not.
      $result = $ft['db']['link']->query("SELECT name FROM SQLITE_MASTER WHERE type='table'");
      if ($result) {
        foreach ($result as $row) {
          $ft['db']['tables'][] = $row['name'];
        }
      }
    } else {
      ft_set_message(t('Database module could not be enabled. Database could not be created at !db.', array('!db' => $ft['plugins']['db']['settings']['path'])), 'error');
      ft_plugin_unload('db');
    }
  } else {
    ft_set_message(t('Database module could not be enabled. PHP PDO is not installed.'), 'error');
    ft_plugin_unload('db');
  }
}

/**
 * Implementation of hook_secondary_menu.
 */
function ft_db_secondary_menu() {
  global $ft;
  if (isset($ft['plugins']['db']['settings']['flushtables']) && $ft['plugins']['db']['settings']['flushtables'] === TRUE) {
    return ft_make_link(t('[db]'), "act=db", t("Manage database"));
  }
  return '';
}

/**
 * Implementation of hook_page.
 */
function ft_db_page($act) {
  global $ft;

  $str = '';
  if ($act == 'db' && isset($ft['plugins']['db']['settings']['flushtables']) && $ft['plugins']['db']['settings']['flushtables'] === TRUE) {
    // Show all DB tables.
    $str .= "<h2>".t('Database tables')."</h2>";
    // Other users.
    if (is_array($ft['db']) && is_array($ft['db']['tables']) && count($ft['db']['tables']) > 0) {
      $str .= '<table><tr><th>'.t('Table name').'</th><th>'.t('Delete?').'</th></tr>';
      foreach($ft['db']['tables'] as $name) {
        $str .= '<tr><td>'.$name.'</td><td>'.ft_make_link(t('delete'), 'act=db_delete&amp;value='.$name).'</td></tr>';
      }
      $str .= '</table>';
    } else {
      $str .= '<p>'.t('No tables present in database.').'</p>';
    }
  } elseif ($act == 'db_delete') {
    $display_value = htmlentities($_GET['value']);
    $str = "<h2>".t('Delete database table: !table', array('!table' => $display_value))."</h2>";
    $str .= "<p>".t('Are you sure you want to continue? This will delete the table from the database.')."</p>";
    $str .= '<form action="'.ft_get_self().'" method="post">';
    $str .= '<div>';
    $str .= '<input type="hidden" name="value" value="'.$display_value.'">';
    $str .= '<input type="hidden" name="act" value="db_delete_submit">';
    $str .= '<input type="submit" name="ft_submit" value="'.t('Yes, delete this table').'">';
    $str .= '<input type="submit" name="ft_submit" value="'.t('Cancel').'">
      </div>
    </form>';
  }
  return $str;
}

/**
 * Implementation of hook_action.
 */
function ft_db_action($act) {
  global $ft;
  // Delete a database table.
  if ($act == 'db_delete_submit' && isset($ft['plugins']['db']['settings']['flushtables']) && $ft['plugins']['db']['settings']['flushtables'] === TRUE) {
    // Check for cancel.
    if (strtolower($_REQUEST["ft_submit"]) == strtolower(t("Cancel"))) {
      ft_redirect("act=db");
    }
		// Check if table exists.
    $display_value = htmlentities($_POST['value']);
    if (is_array($ft['db']) && is_array($ft['db']['tables']) && count($ft['db']['tables']) > 0) {
      // Drop table.
      $result = $ft['db']['link']->query('DROP TABLE '.$ft['db']['link']->quote($_POST['value']));
      if ($result) {
        ft_set_message(t('Database table !table dropped.', array('!table' => $display_value)));
      } else {
        ft_set_message(t("Database table !table could not be dropped.", array('!table' => $display_value)), 'error');
      }
    } else {
      ft_set_message(t("Database table !table could not be removed because it doesn't exists.", array('!table' => $display_value)), 'error');
    }
		// Redirect.
    ft_redirect("act=db");
  }
}

/**
 * Create database table if it doesn't exists.
 *
 * @param $table
 *   Name of table name.
 * @param $sql
 *   SQL query to run to create table.
 * @return TRUE if table already exists or is created successfully.
 */
function ft_db_install_table($table, $sql) {
  global $ft;
  if (is_array($ft['db']['tables'])) {
    foreach ($ft['db']['tables'] as $c) {
      if ($c == $table) {
        return TRUE;
      }
    }
  }
  return $ft['db']['link']->query($sql);
}
