<?php

namespace App;


/**
* tmdb api, get single contents
*
* PHP version 7.0
*/

class tmdbSingleContent
{
  /**
  *
  */
  public static function getContentByApi($tv_id, $kind)
  {
    if ($kind === 'serie') $kind = 'tv';
    $ch = curl_init();
    curl_setopt_array($ch, [ //GET is default
      CURLOPT_URL => "https://api.themoviedb.org/3/" . $kind . "/" . $tv_id . "?api_key=" . \App\Config::TMDB_API_KEY,
      CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch); //get the response
    curl_close($ch); //close
    $data = json_decode($response, true); //decode the response

    if(isset($data['success']))
    {
      if($data['success'] === false)
      {
        return false;
      }
    }

    return $data;

  }
}
