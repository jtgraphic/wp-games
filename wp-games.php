<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://twitter.com/daftlabs
 * @since             0.0.1
 * @package           WP-Games
 *
 * @wordpress-plugin
 * Plugin Name:       WP-Games
 * Plugin URI:        http://jtgraphic.net
 * Description:       This is WP-Games.
 * Version:           0.0.1
 * Author:            James Thompson
 * Author URI:        http://jtgraphic.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       jtgraphic.net
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once(plugin_dir_path( __FILE__ ).'includes/classes/plugin.php');

use jtgraphic\wp\games\Plugin as Plugin;

Plugin::$version = '0.0.1';

function x440a0f3() {
  Plugin::$plugin_directory = plugin_dir_path( __FILE__ );
	Plugin::init();
}

x440a0f3();
