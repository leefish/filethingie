<?php
/**
 * @file
 * File Thingie hooks documentation.
 */

/**
 * Add a callback action.
 *
 * @param $act
 *   The current action value.
 * @return Nothing. Use ft_redirect to redirect after action.
 */
function ft_hook_action($act) {}

/**
 * Add inline CSS to the page.
 *
 * @return String of CSS.
 */
function ft_hook_add_css() {}

/**
 * Add javascript to run on document.ready.
 *
 * Use this hook to add any javascript that should be run whenever the document is ready.
 * This is useful for activating jQuery plugins and running other unobtrusive javascript.
 *
 * @return String of javascript (do not include script tags).
 */
function ft_hook_add_js_call() {}

/**
 * Add inline javascript to the page footer.
 *
 * Use this hook to add any javascript that should be placed at the bottom of the page.
 * This is useful for old style javascript that can't be run unobtrusively.
 *
 * @return String of javascript (do not include script tags).
 */
function ft_hook_add_js_call_footer() {}

/**
 * Add a javascript file to the page.
 *
 * @return Array of file paths to javascript files (paths are relative to the ft2.php).
 */
function ft_hook_add_js_file() {}

/**
 * Add Ajax callback.
 *
 * @param $act
 *   The current action value.
 * @return Output results directly with echo or print.
 */
function ft_hook_ajax($act) {}

/**
 * Perform cleaup tasks.
 *
 * This hook is run at the end of every page request.
 *
 * @return Nothing.
 */
function ft_hook_destroy() {}

/**
 * Act on entire directory lists.
 */
function ft_hook_dirlist() {}

/**
 * File is being downloaded.
 *
 * This hook only runs if HIDEFILEPATHS is enabled.
 *
 * @param $dir
 *   Directory download happens from.
 * @param $file
 *   Name of file.
 */
function ft_hook_download($dir, $file) {}

/**
 * Add options to file actions.
 * 
 * @param $file
 *   Current file name.
 * @param $dir
 *   Current dir.
 * @return Space-separated list of extra file actions to add.
 */
function ft_hook_fileextras($file, $dir) {}

/**
 * Add content to file names.
 *
 * Run on every file and directory in a list of files.
 *
 * @param $file
 * An array of files. Each item is an array:
 * - 'name': File name.
 * - 'shortname': File name.
 * - 'type': 'file' or 'dir'.
 * - 'ext': File extension.
 * - 'writeable': TRUE if writeable.
 * - 'perms': Permissions.
 * - 'modified': Last modified. Unix timestamp.
 * - 'size': File size in bytes.
 * - 'extras': Array of extra classes for this file.
 * @return String of HTML to add to file listing.
 */
function ft_hook_filename($file) {}

/**
 * Get information about the current plugin.
 *
 * All File Thingie plugins must implement this hook.
 * It provides information about the plugin such as the name
 * and default settings.
 *
 * @return An associative array of plugin information with this structure:
 * - "name": Human-friendly name of the plugin.
 * - "settings": Associative array of setting arrays. The key of each item is the setting name and the values are:
 *   - "description": Human-friendly name of the settings.
 *   - "default": The default value of the setting.
 */
function ft_hook_info() {}

/**
 * Perform setup tasks.
 *
 * This hook is run once at the beginning of all page requests.
 *
 * @return Nothing.
 */
function ft_hook_init() {}

/**
 * User fails login.
 *
 * This hook is run whenever a login is attempted, but fails.
 *
 * @param $username
 *   The username entered.
 *
 * @return Nothing.
 */
function ft_hook_loginfail() {}

/**
 * User successfully logs in.
 *
 * This hook is run whenever a login is attempted and succeeds.
 *
 * @param $username
 *   The username entered.
 *
 * @return Nothing.
 */
function ft_hook_loginsuccess() {}

/**
 * Add content to the menu.
 * 
 * The menu is located beneath the logout link and is meant to contain administration links.
 *
 * @return String of HTML to add to the menu container.
 */
function ft_hook_menu() {}

/**
 * Add a page.
 *
 * @param $act
 *   The current action value.
 * @return HTML for the current page content.
 */
function ft_hook_page($act) {}

/**
 * Add content to the sidebar.
 * 
 * You can add several blocks to the sidebar.
 *
 * @return Array of arrays. Each containing these elements:
 *
 * - "id": Unique string identifying this block.
 * - "content": HTML content of this block.
 */
function ft_hook_sidebar() {}

/**
 * File has been uploaded.
 *
 * @param $dir
 *   Directory download happens from.
 * @param $file
 *   Name of file.
 * @return Nothing.
 */
function ft_hook_upload($dir, $file) {}
