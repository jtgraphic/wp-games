<?php

namespace jtgraphic\wp\games;

use \daftlabs\wp\Utility as Utility;

Utility::load_utility_class('custom-type');

use \daftlabs\wp\Custom_Type as Custom_Type;

class Game extends Custom_Type {
  public static $name_singular  = 'Game';
  public static $name_plural    = 'Games';
  public static $type           = 'page';
  public static $capability     = 'administrator';

  public static $fields = [
    'steam-id'  => [
      'type' => 'number',
    ],
    'price'  => [
      'type' => 'number',
    ],
    'status'  => [
      'type' => 'text',
    ],
    'name' => [
      'type'            => 'text',
      'source'          => 'wordpress',
      'context'         => 'page',
      'context_field'   => 'post_title',
    ],
  ];

  public static function init() {
    parent::init();

    $function_name = 'add_all_games';
    $hook_name = Plugin::$plugin_slug.'_'.$function_name;

    if (!wp_next_scheduled($hook_name)) {
      wp_schedule_event(time(), 'daily', $hook_name);
    }

    add_action($hook_name, [static::class, $function_name]);

    $function_name = 'iterate_game_details';
    $hook_name = Plugin::$plugin_slug.'_'.$function_name;

    if (!wp_next_scheduled($hook_name)) {
      wp_schedule_event(time(), 'minutely', $hook_name);
    }

    add_action($hook_name, [static::class, $function_name]);

    add_filter(
      'manage_'.static::$slug.'_posts_columns',
      [static::class, 'custom_columns']
    );

    add_action(
      'manage_'.static::$slug.'_posts_custom_column',
      [static::class, 'custom_column_data'],
      10,
      2
    );
  }

  public static function custom_columns($columns) {
    $new_columns = [];

    foreach($columns as $key => $title) {
      if ($key == 'date') {
        $new_columns['status'] = 'Status';
      }

      $new_columns[$key] = $title;
    }

    return $new_columns;
  }

  public static function custom_column_data($column, $post_id) {
    if ($column == 'status') {
      $post = get_post($post_id);
      echo static::get_meta($post, $column);
    }
  }

  public static function add_all_games() {
    $games = Provider_Steam::get_games();

    foreach ($games as $game) {
      $game_data = [
        'steam-id'  => $game['appid'],
        'status'    => 'prefetch',
        'name'      => $game['name'],
      ];

      Game::upsert('steam-id', $game_data['steam-id'], $game_data);
    }
  }

  public static function iterate_game_details() {
    $arguments = [
    	'posts_per_page'    => 35,
    	'offset'            => 0,
      'meta_key'          => 'status',
      'meta_value'        => 'offline',
      'meta_compare'      => '!=',
    	'orderby'           => 'modified',
    	'order'             => 'ASC',
    	'post_type'         => static::$slug,
    	'post_status'       => 'publish',
    	'suppress_filters'  => true,
    ];

    $games = get_posts($arguments);

    foreach ($games as $game) {
      $game_id = static::get_meta($game, 'steam-id');

      $game_data = Provider_Steam::get_game_details($game_id);

      if (!$game_data) {
        static::set_meta($game->ID, 'status', 'offline');
        continue;
      }

      static::set_meta($game->ID, 'status', 'online');
      static::set_meta($game->ID, 'price', $game_data['price_overview']['final']);

      $description = NULL;

      if ($game_data['short_description']) {
        $description = $game_data['short_description'];
      } elseif ($game_data['about_the_game']) {
        $description = $game_data['about_the_game'];
      } elseif ($game_data['detailed_description']) {
        $description = $game_data['detailed_description'];
      }

      $post = [
        'ID'           => $game->ID,
        'post_title'   => $game_data['name'],
        'post_content' => $description,
      ];

      wp_update_post($post);
    }
  }

  public static function metaboxes() {
    add_meta_box(
      Plugin::$plugin_slug.'-information',
      static::$name_singular.' Information',
      [static::class, 'metabox_information'],
      static::$slug,
      'normal',
      'low'
    );
  }

  public static function metabox_information($post, $parameters) {
    global $post;

    $data = static::hydrate($post);

    unset($data['post_title']);
    unset($data['post_content']);
    unset($data['name']);

    Utility::utility_partial('components/metabox', ['fields'  => $data]);
  }
}
