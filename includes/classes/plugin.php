<?php

namespace jtgraphic\wp\games;

require_once(plugin_dir_path( __FILE__ ).'../../vendor/daftlabs/wp-utilities/utility.php');

use daftlabs\wp\Utility as Utility;

class Plugin {
  public static $version;
  public static $plugin_directory;

  public static $plugin_name = 'Games';
  public static $plugin_slug = 'wp-games';

  public static function init() {
    add_action('init', [static::class, 'run']);
  }

  public static function run() {
    Utility::$plugin_directory = static::$plugin_directory;

    Utility::load_class('providers/steam');
    Utility::load_class('providers/fanatical');
    Utility::load_class('game');

    add_filter('cron_schedules', [Utility::class, 'add_cron_schedules']);

    Game::init();
  }
}
