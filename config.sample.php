<?php

function ft_settings_external_load() {
  $ft = array();
  $ft['settings'] = array();
  $ft['groups'] = array();
  $ft['users'] = array();
  $ft['plugins'] = array();

  # Settings - Change as appropriate. See online documentation for explanations. #
  define("USERNAME", ""); // Your default username.
  define("PASSWORD", ""); // Your default password.

  $ft["settings"]["DIR"]               = "."; // Your default directory. Do NOT include a trailing slash!
  $ft["settings"]["LANG"]              = "en"; // Language. Do not change unless you have downloaded language file.
  $ft["settings"]["MAXSIZE"]           = 2000000; // Maximum file upload size - in bytes.
  $ft["settings"]["PERMISSION"]        = 0644; // Permission for uploaded files.
  $ft["settings"]["DIRPERMISSION"]     = 0777; // Permission for newly created folders.
  $ft["settings"]["LOGIN"]             = TRUE; // Set to FALSE if you want to disable password protection.
  $ft["settings"]["UPLOAD"]            = TRUE; // Set to FALSE if you want to disable file uploads.
  $ft["settings"]["CREATE"]            = TRUE; // Set to FALSE if you want to disable file/folder/url creation.
  $ft["settings"]["FILEACTIONS"]       = TRUE; // Set to FALSE if you want to disable file actions (rename, move, delete, edit, duplicate).
  $ft["settings"]["HIDEFILEPATHS"]     = FALSE; // Set to TRUE to pass downloads through File Thingie.
  $ft["settings"]["DELETEFOLDERS"]     = FALSE; // Set to TRUE to allow deletion of non-empty folders.
  $ft["settings"]["SHOWDATES"]         = FALSE; // Set to a date format to display last modified date (e.g. 'Y-m-d'). See http://dk2.php.net/manual/en/function.date.php
  $ft["settings"]["FILEBLACKLIST"]     = "ft2.php ft.css config.php index.php config.sample.php LICENSE README.markdown .DS_store .gitignore"; // Specific files that will not be shown.
  $ft["settings"]["FOLDERBLACKLIST"]   = "plugins js css locales data"; // Specifies folders that will not be shown. No starting or trailing slashes!
  $ft["settings"]["FILETYPEBLACKLIST"] = "php phtml php3 php4 php5"; // File types that are not allowed for upload.
  $ft["settings"]["FILETYPEWHITELIST"] = ""; // Add file types here to *only* allow those types to be uploaded.
  $ft["settings"]["ADVANCEDACTIONS"]   = FALSE; // Set to TRUE to enable advanced actions like chmod and symlinks.
  $ft["settings"]["LIMIT"]             = 0; // Restrict total dir file usage to this amount of bytes. Set to "0" for no limit.
  $ft["settings"]["REQUEST_URI"]       = FALSE; // Installation path. You only need to set this if $_SERVER['REQUEST_URI'] is not being set by your server.
  $ft["settings"]["HTTPS"] = FALSE; // Change to TRUE to enable HTTPS support.
  $ft["settings"]["REMEMBERME"]        = FALSE; // Set to TRUE to enable the "remember me" feature at login.
  $ft["settings"]["PLUGINDIR"]         = 'plugins'; // Set to the path to your plugin folder. Do NOT include a trailing slash!

  # Plugin settings #
  $ft["plugins"]["search"] = TRUE;
  $ft["plugins"]["edit"] = array(
   "settings" => array(
     "editlist" => "txt html htm css",
     "converttabs" => FALSE
   )
  );
  /*
  $ft["plugins"]["tinymce"] = array(
    "settings" => array(
      "path" => "tinymce/jscripts/tiny_mce/tiny_mce.js",
      "list" => "html htm"
    )
  );
  */

  # Additional users - See guide at http://www.solitude.dk/filethingie/documentation/users #

  /*
  $ft['users']['REPLACE_WITH_USERNAME'] = array(
    'password' => 'REPLACE_WITH_PASSWORD',
    'group' => 'REPLACE_WITH_GROUPNAME'
  );
  */

  # User groups for additional users -  - See guide at http://www.solitude.dk/filethingie/documentation/users #

  /*
  $ft['groups']['REPLACE_WITH_GROUPNAME'] = array(
    'DIR' => 'REPLACE_WITH_CUSTOM_DIR',
  );
  */

  return $ft;
}
