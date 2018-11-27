<?php

namespace jtgraphic\wp\games;

class Provider_Fanatical {
  public static $last_fetch_size = NULL;

  public static $api_headers = [
    'Referer' => 'https://www.fanatical.com/en/search',
  ];

  public static $api_key =
    'NTM0OTQ2MTFiMWJkMWVmYWJlMGQ3ZGE0Mzg1MDlhMjhlODU2ZDFkNzM2OWM4OTRkNmExOGVjZWJj'.
    'YmNhNTE3MGZpbHRlcnM9ZGlzYWJsZWQlMjAlM0QlMjAwJTIwQU5EJTIwYXZhaWxhYmxlX3ZhbGlk'.
    'X2Zyb20lMjAlM0MlM0QlMjAxNTIyNTQ0NTQ3JTIwQU5EKGF2YWlsYWJsZV92YWxpZF91bnRpbCUy'.
    'MCUzRCUyMDAlMjBPUiUyMGF2YWlsYWJsZV92YWxpZF91bnRpbCUyMCUzRSUzRCUyMDE1MjI1NDQ1'.
    'NDcpJmZhY2V0RmlsdGVycz0lNUIlMjJpbmNsdWRlZF9yZWdpb25zJTNBVVMlMjIlNUQmcmVzdHJp'.
    'Y3RJbmRpY2VzPWZhbl9uYW1lJTJDZmFuX2xhdGVzdF9kZWFscyUyQ2Zhbl9kaXNjb3VudCUyQ2Zh'.
    'bl9yZWxlYXNlX2RhdGVfYXNjJTJDZmFuX3JlbGVhc2VfZGF0ZV9kZXNjJTJDZmFuX3ByaWNlX2Fz'.
    'YyUyQ2Zhbl9wcmljZV9kZXNjJTJDZmFuX2VuZGluZ19zb29uJTJDZmFu';

  public static function get_games($page = 0) {
    $body = [
      'requests' => [
        [
          'indexName' => 'fan',
          'params' => http_build_query([
            'query' => '',
            'hitsPerPage' => 25,
            'page' => $page,
            'numericFilters' => 'discount_percent > 0',
          ])
        ],
      ],
      'apiKey' => static::$api_key,
    ];

    $arguments = [
      'body' => json_encode($body),
      'headers' => static::$api_headers,
    ];

    $response = wp_remote_post('https://w2m9492ddv-dsn.algolia.net/1/indexes/*/queries/?x-algolia-application-id=W2M9492DDV', $arguments);

    if (is_object($response)) {
      error_log('error response from FANATICAL provider ('.var_dump($response).')');
      return FALSE;
    }

    $results = json_decode($response['body'], TRUE);

    static::$last_fetch_size = $results['results'][0]['nbPages'];

    $games = $results['results'][0]['hits'];

    return $games;
  }

  public static function get_game_details($slug) {
    $url = 'https://api.fanatical.com/api/products/'.$slug;

    $response = wp_remote_get($url);

    if (is_object($response)) {
      error_log('error response from FANATICAL provider');
      return FALSE;
    }

    $game = json_decode($response['body'], TRUE);

    return $game;
  }
}
