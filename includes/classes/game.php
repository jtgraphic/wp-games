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
    'steam-price'  => [
      'type' => 'number',
    ],
    'fanatical-id'  => [
      'type' => 'text',
    ],
    'fanatical-slug'  => [
      'type' => 'text',
    ],
    'fanatical-price'  => [
      'type' => 'number',
    ],
    'gmg-price'  => [
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

    $function_name = 'add_steam_games';
    $hook_name = Plugin::$plugin_slug.'_'.$function_name;

    if (!wp_next_scheduled($hook_name)) {
      wp_schedule_event(time(), 'minutely', $hook_name);
    }

    add_action($hook_name, [static::class, $function_name]);

    $function_name = 'process_fanatical_games';
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

    add_filter('pre_get_posts', [static::class, 'filter_query']);

    add_filter('views_edit-'.static::$slug, [static::class, 'filter_links']);
  }

  public static function filter_links($views) {
    $views['status-online'] =
      '<a href="edit.php?status=online&post_type='.static::$slug.'">
        Online
      </a>';

    $views['status-offline'] =
      '<a href="edit.php?status=offline&post_type='.static::$slug.'">
        Offline
      </a>';

    $views['fanatical-id'] =
      '<a href="edit.php?fanatical-id=true&post_type='.static::$slug.'">
        Fanatical
      </a>';

    return $views;
  }

  public static function filter_query($query) {
    global $pagenow;

    $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : '';

    if (
      is_admin() &&
      $pagenow=='edit.php' &&
      $post_type == static::$slug
    ) {
      if (isset($_GET['status']) && $_GET['status'] != 'all') {
        $query->query_vars['meta_key'] = 'status';
        $query->query_vars['meta_value'] = $_GET['status'];
        $query->query_vars['meta_compare'] = '=';
      }

      if (isset($_GET['fanatical-id']) && $_GET['fanatical-id'] == 'true') {
        $query->query_vars['meta_key'] = 'fanatical-id';
        $query->query_vars['meta_value'] = '';
        $query->query_vars['meta_compare'] = 'EXISTS';
      }
    }
  }

  public static function custom_columns($columns) {
    $new_columns = [];

    foreach($columns as $key => $title) {
      if ($key == 'date') {
        $new_columns['steam-id'] = 'Steam ID';
        $new_columns['fanatical-slug'] = 'Fanatical Slug';
        $new_columns['price'] = 'Retail Price';
        $new_columns['fanatical-price'] = 'Fanatical Price';
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

    if ($column == 'fanatical-slug') {
      $post = get_post($post_id);
      echo static::get_meta($post, $column);
    }

    if ($column == 'steam-id') {
      $post = get_post($post_id);
      echo static::get_meta($post, $column);
    }

    if ($column == 'price') {
      $post = get_post($post_id);
      echo '$'.static::get_meta($post, $column) / 100;
    }

    if ($column == 'fanatical-price') {
      $post = get_post($post_id);

      $fanatical_price = static::get_meta($post, 'fanatical-price') / 100;
      $retail_price = static::get_meta($post, 'price') / 100;

      if ($retail_price > 0) {
        $discount_percent = number_format(100 - ($fanatical_price / $retail_price * 100));
        $discount_value = number_format($retail_price - $fanatical_price, 2);
      } else {
        $discount_percent = 0;
        $discount_value = 0;
      }

      echo '$'.$fanatical_price.' ('.$discount_percent.'%, $'.$discount_value.')';
    }
  }

  public static function add_steam_games() {
    $limit = 25;

    $option_name = Plugin::$plugin_slug.'-steam-offset';

    $offset = get_option($option_name, 0);

    $games = Provider_Steam::get_games($offset, $limit);

    if (!$games) {
      error_log('no STEAM games.');

      if ($games === FALSE) {
        return;
      }
    }

    foreach ($games as $index => $game) {
      Provider_Steam::update_game_details($game['appid']);

      $one_second = 1000000;
      $execution_buffer = $one_second / 10;

      error_log('STEAM: '.($execution_buffer / $one_second).'s ('.$index.', '.$offset.', '.$limit.', '.Provider_Steam::$last_fetch_size.')');

      usleep($execution_buffer); // breath
    }

    $new_offset = $offset + $limit;

    if (
      Provider_Steam::$last_fetch_size > 0 &&
      $new_offset > Provider_Steam::$last_fetch_size
    ) {
      $new_offset = 0;
    }

    update_option($option_name, $new_offset);
  }

  public static function process_fanatical_games() {
    $option_name = Plugin::$plugin_slug.'-fanatical-page';

    $page = get_option($option_name, 0);

    $games = Provider_Fanatical::get_games($page);

    if (!$games) {
      error_log('no FANATICAL games.');

      if ($games === FALSE) {
        return;
      }
    }

    foreach ($games as $index => $game) {
      $game_data = [
        'name'            => $game['name'],
        'fanatical-id'    => $game['product_id'],
        'fanatical-slug'  => $game['slug'],
        'fanatical-price' => $game['price']['USD'] * 100,
      ];

      $game_details = Provider_Fanatical::get_game_details($game_data['fanatical-slug']);

      $game_data['steam-id'] = $game_details['steam']['id'];

      if (isset($game_data['steam-id'])) {
        Provider_Steam::update_game_details($game_data['steam-id']);
        static::upsert('steam-id', $game_data['steam-id'], $game_data);
      }

      $one_second = 1000000;
      $execution_buffer = $one_second / 10;

      error_log('FANATICAL: '.($execution_buffer / $one_second).'s ('.$index.', '.$page.', '.Provider_Fanatical::$last_fetch_size.')');

      usleep($execution_buffer); // breath
    }

    $page++;

    if (
      Provider_Fanatical::$last_fetch_size > 0 &&
      $page > Provider_Fanatical::$last_fetch_size
    ) {
      $page = 0;
    }

    update_option($option_name, $page);
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
