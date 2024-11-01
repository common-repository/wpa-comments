<?php
/**
 * Uninstall WPA Comments
 *
 * Cleans all plugins data
 */
if ( !defined('WP_UNINSTALL_PLUGIN') ) exit();

delete_option('wpa_comments_comments');
delete_option('wpa_comments_authors');
delete_option('wpac_version');
