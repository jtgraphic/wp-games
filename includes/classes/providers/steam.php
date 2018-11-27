<?php

namespace jtgraphic\wp\games;

class Provider_Steam {
  public static $last_fetch_size = NULL;

  public static function get_games($offset = 0, $limit = 1000) {
    $response = wp_remote_get('https://api.steampowered.com/ISteamApps/GetAppList/v2/');

    if (is_object($response)) {
      error_log('error response from STEAM provider');
      return FALSE;
    }

    $games = json_decode($response['body'], TRUE);

    static::$last_fetch_size = sizeof($games['applist']['apps']);

    return array_slice($games['applist']['apps'], $offset, $limit);
  }

  public static function get_game_details($game_id) {
    $url = 'http://store.steampowered.com/api/appdetails?appids='.$game_id;

    $response = wp_remote_get($url);

    if (is_object($response)) {
      error_log('error response from STEAM provider');
      return FALSE;
    }

    $game = json_decode($response['body'], TRUE);

    $game = $game[$game_id];

    if ($game['success']) {
      return $game['data'];
    } else {
      return FALSE;
    }
  }

  public static function update_game_details($steam_id) {
    $game_details = static::get_game_details($steam_id);

    if (!$game_details) {
      return FALSE;
    }

    $game_data = [
      'steam-id'  => $steam_id,
      'status'    => 'prefetch',
      'name'      => $game_details['name'],
    ];

    if (!$game_details) {
      $game_data['status'] = 'offline';
    } else {
      $game_data['status'] = 'online';

      if (isset($game_details['price_overview'])) {
        if (isset($game_details['price_overview']['initial'])) {
          $game_data['price'] = $game_details['price_overview']['initial'];
        }

        if (isset($game_details['price_overview']['final'])) {
          $game_data['steam-price'] = $game_details['price_overview']['final'];
        }
      }

      if ($game_details['short_description']) {
        $game_data['description'] = $game_details['short_description'];
      } elseif ($game_details['about_the_game']) {
        $game_data['description'] = $game_details['about_the_game'];
      } elseif ($game_details['detailed_description']) {
        $game_data['description'] = $game_details['detailed_description'];
      } else {
        $game_data['description'] = '';
      }
    }

    Game::upsert('steam-id', $steam_id, $game_data);
  }
}
