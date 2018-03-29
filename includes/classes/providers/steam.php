<?php

namespace jtgraphic\wp\games;

\daftlabs\wp\Utility::load_class('game');

class Provider_Steam {
  public static function get_games() {
    $response = wp_remote_get('https://api.steampowered.com/ISteamApps/GetAppList/v2/');

    $games = json_decode($response['body'], TRUE);

    return $games['applist']['apps'];
  }

  public static function get_game_details($game_id) {
    $url = 'http://store.steampowered.com/api/appdetails?appids='.$game_id;

    $response = wp_remote_get($url);

    $game = json_decode($response['body'], TRUE);

    $game = $game[$game_id];

    if ($game['success']) {
      return $game['data'];
    } else {
      return FALSE;
    }
  }
}
