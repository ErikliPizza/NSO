<?php

namespace App\Controllers;

use \App\tmdbSingleContent;
use \Core\View;
use \App\Models\Contents;
use \App\Auth;


/**
 * Search controller
 *
 * PHP version 7.0
 */
class Search extends Authenticated
{

    /**
    * show index
    *
    */
    public function indexAction()
    {
      View::renderTemplate('Search/index.html');
    }

    /**
    *
    * search for view/search/index.html
    *
    * get the query and kind and return the result which created by tmdb api
    *
    * @return mixed
    *
    */
    public function searchAction()
    {
      $this->user = Auth::getUser();

      $postedData = (array) json_decode(file_get_contents("php://input"), true); //get the POSTED value as an array

      if(isset($postedData['search'])) {

        $q=$postedData['search']; // veriyi aktar
        $q = preg_replace('/\s+/', '+', $q); //boşlukları +'ya çevir, query için şart

      } else {

        http_response_code(204);
        echo json_encode(["error" => "There is no content."]);
        return false;

      }

      if ($postedData['kind'] == 'serie') $type = 'tv';
      else if ($postedData['kind'] == 'movie') $type = 'movie';

      $ch = curl_init();
      //get the movies
      curl_setopt_array($ch, [ //GET is default
        CURLOPT_URL => "https://api.themoviedb.org/3/search/" . $type . "?api_key=" . \App\Config::TMDB_API_KEY . "&query=$q",
        CURLOPT_RETURNTRANSFER => true
      ]);

      $response = curl_exec($ch); //get the response
      curl_close($ch); //close

      $data = json_decode($response, true); //decode the response

      if (empty($data["results"])) {

        http_response_code(204);
        echo json_encode(["error" => "There is no content."]);
        return false;

      }

      $response_array = []; //create an array

      $limit = sizeof($data["results"]) < 10 ? sizeof($data["results"]) : 9; //detect the limitation

      for ($i=0; $i < $limit; $i++) {
        $genre=""; //set up the genre
        $img_path = "https://image.tmdb.org/t/p/w500"; //set up the image path

        for ($a=0; $a < sizeof($data["results"][$i]["genre_ids"]) ; $a++) //mevcut kategori sayısı kadar tekrarla, kategorileri tek tek yazdır
        {
          switch ($data["results"][$i]["genre_ids"][$a]) {
            case '16':
            $genre .= "Animation, ";
              break;
            case '18':
            $genre .= "Drama, ";
              break;
            case '37':
            $genre .= "Western, ";
              break;
            case '80':
            $genre .= "Crime, ";
              break;
            case '99':
            $genre .= "Documentary, ";
              break;
            case '10759':
            $genre .= "Action&Adventure, ";
              break;
            case '10762':
            $genre .= "Kids, ";
              break;
            case '10763':
            $genre .= "News, ";
              break;
            case '10764':
            $genre .= "Reality, ";
              break;
            case '10765':
            $genre .= "Sci-Fi&Fantasy, ";
              break;
            case '10766':
            $genre .= "Soap, ";
              break;
            case '10767':
            $genre .= "Talk, ";
              break;
            case '10768':
            $genre .= "War&Politics, ";
              break;
              case '28':
            $genre .= "Action, ";
              break;
            case '12':
            $genre .= "Adventure, ";
              break;
            case '35':
            $genre .= "Comedy, ";
              break;
            case '10751':
            $genre .= "Family, ";
              break;
            case '14':
            $genre .= "Fantasy, ";
              break;
            case '36':
            $genre .= "History, ";
              break;
            case '27':
            $genre .= "Horror, ";
              break;
            case '10402':
            $genre .= "Music, ";
              break;
            case '9648':
            $genre .= "Mystery, ";
              break;
            case '10749':
            $genre .= "Romance, ";
              break;
            case '878':
            $genre .= "Science Fiction, ";
              break;
            case '10770':
            $genre .= "TV Movie, ";
              break;
            case '53':
            $genre .= "Thriller, ";
              break;
            case '10752':
            $genre .= "War, ";
              break;
          }
        }
        $genre = substr($genre, 0, -2); //kategoriyi düzenle x, , ", " kısmını sil
        $response_array[$i]["id"] = $data["results"][$i]["id"]; //id'yi al
        if ($postedData['kind'] == 'serie')
        {
          $response_array[$i]["title"] = $data["results"][$i]["original_name"]; //başlığı al

          if(empty($data["results"][$i]["first_air_date"]))
          {
            $publish_date = "?";
            $response_array[$i]["publish_date"] = $publish_date;
          }
          else
          {
            $publish_date = $data["results"][$i]["first_air_date"]; //publish_date
            $publish_date = strtok($publish_date, '-');
            $response_array[$i]["publish_date"] = $publish_date;
          }
        }
        else if ($postedData['kind'] == 'movie')
        {
          $response_array[$i]["title"] = $data["results"][$i]["original_title"]; //başlığı al

          if(empty($data["results"][$i]["release_date"]))
          {
            $publish_date = "?";
            $response_array[$i]["publish_date"] = $publish_date;
          }
          else
          {
            $publish_date = $data["results"][$i]["release_date"]; //publish_date
            $publish_date = strtok($publish_date, '-');
            $response_array[$i]["publish_date"] = $publish_date;
          }
        }

        if($sinePoint = Contents::getSP($data["results"][$i]["id"], $postedData['kind']))
        {
          $response_array[$i]["sp"] = $sinePoint['SP'];
          $response_array[$i]["pop"] = $sinePoint['POP'];
        }
        else
        {
          $response_array[$i]["sp"] = "?";
        }
        if(empty($data["results"][$i]["poster_path"])) //eğer image mevcut değilse
        {
          $response_array[$i]["image"] = "/uploads/profiles/default.jpg"; //default image belirle
        }
        else //eğer image mevcutsa
        {
          $img_path .= $data["results"][$i]["poster_path"]; //mevcut yola image pathı ekle
          $response_array[$i]["image"] = $img_path; //image pathı gönder
        }
        if(empty($data["results"][$i]["vote_average"]))
        {
          $response_array[$i]["rating"] = "?"; //rating
        }
        else
        {
          $response_array[$i]["rating"] = $data["results"][$i]["vote_average"]; //rating
        }


        if(empty($data["results"][$i]["overview"]))
        {
          $response_array[$i]["overview"] = "?";
          $response_array[$i]["overviewParam"] = "?";
        }
        else
        {
          $response_array[$i]["overview"] = $data["results"][$i]["overview"];
        }

        if($checking = Contents::validateWithID($this->user->id, $data["results"][$i]["id"], $postedData['kind']))
        {
          if($checking[0]['wishlist'] == 0)
          {
            $response_array[$i]['matched'] = 'watched';
          }
          else if ($checking[0]['wishlist'] == 1)
          {
            $response_array[$i]['matched'] = 'wishlist';
          }
          $response_array[$i]['personal_rating'] = $checking[0]['personal_rating'];
        }
        else
        {
          $response_array[$i]["wishlist"] = false;
          $response_array[$i]["watched"] = false;
        }

        if($wishlisted = Contents::getWL($data["results"][$i]["id"], $postedData['kind']))
        {
          $response_array[$i]["wishlistedCount"] = $wishlisted["WISHLISTED"];
        }

        $response_array[$i]["category"] = $genre; //kategori
        $response_array[$i]["kind"] = $postedData['kind'];

        if($postedData['findSimilar'] === true)
        {
          $similarQuery = $response_array[$i]["title"]."-".$response_array[$i]["publish_date"];
          $q = $similarQuery;

          $qParts = explode("-", $q);

          $name = $qParts[0];

          $year = $qParts[1];

          if($name != '') //name verisi varsa
          {
            $q = preg_replace('/\s+/', '-', $q); //boşlukları +'ya çevir, query için şart
          }

          $url = 'https://www.film-fish.com/movieslike/'.$q;

          if(static::get_http_response_code($url) == "200")
          {
            $homepage = file_get_contents($url);

            $fullstring = $homepage;

            $s = '<meta name="description" content="Stream movies like '.$name.':';

            $parsed = static::get_string_between($fullstring, $s, '" >');
            $parsed = substr($parsed, 0, -2);
            $response_array[$i]["similars"] = $parsed;
          } else {
            $response_array[$i]["similars"] = '';
          }
        } else {
          $response_array[$i]["similars"] = '';
        }

      }

      http_response_code(200); // h200 OK
      echo json_encode($response_array); // send it

    }

    public function searchMobileAction()
    {
      $this->user = Auth::getUser();

      $postedData = (array) json_decode(file_get_contents("php://input"), true); //get the POSTED value as an array

      if(isset($postedData['search'])) {

        $q=$postedData['search']; // veriyi aktar
        $q = preg_replace('/\s+/', '+', $q); //boşlukları +'ya çevir, query için şart

      } else {

        http_response_code(204);
        echo json_encode(["error" => "There is no content."]);
        return false;

      }

      if ($postedData['kind'] == 'serie') $type = 'tv';
      else if ($postedData['kind'] == 'movie') $type = 'movie';

      $ch = curl_init();
      //get the movies
      curl_setopt_array($ch, [ //GET is default
        CURLOPT_URL => "https://api.themoviedb.org/3/search/" . $type . "?api_key=" . \App\Config::TMDB_API_KEY . "&query=$q",
        CURLOPT_RETURNTRANSFER => true
      ]);

      $response = curl_exec($ch); //get the response
      curl_close($ch); //close

      $data = json_decode($response, true); //decode the response

      if (empty($data["results"])) {

        http_response_code(204);
        echo json_encode(["error" => "There is no content."]);
        return false;

      }

      $response_array = []; //create an array

      $limit = sizeof($data["results"]) < 3 ? sizeof($data["results"]) : 2; //detect the limitation

      for ($i=0; $i < $limit; $i++) {
        $img_path = "https://image.tmdb.org/t/p/w500"; //set up the image path


        $response_array[$i]["id"] = $data["results"][$i]["id"]; //id'yi al
        if ($postedData['kind'] == 'serie')
        {
          $response_array[$i]["title"] = $data["results"][$i]["original_name"]; //başlığı al

          if(empty($data["results"][$i]["first_air_date"]))
          {
            $publish_date = "?";
            $response_array[$i]["publish_date"] = $publish_date;
          }
          else
          {
            $publish_date = $data["results"][$i]["first_air_date"]; //publish_date
            $publish_date = strtok($publish_date, '-');
            $response_array[$i]["publish_date"] = $publish_date;
          }
        }
        else if ($postedData['kind'] == 'movie')
        {
          $response_array[$i]["title"] = $data["results"][$i]["original_title"]; //başlığı al

          if(empty($data["results"][$i]["release_date"]))
          {
            $publish_date = "?";
            $response_array[$i]["publish_date"] = $publish_date;
          }
          else
          {
            $publish_date = $data["results"][$i]["release_date"]; //publish_date
            $publish_date = strtok($publish_date, '-');
            $response_array[$i]["publish_date"] = $publish_date;
          }
        }

        if(empty($data["results"][$i]["poster_path"])) //eğer image mevcut değilse
        {
          $response_array[$i]["image"] = "/uploads/profiles/default.jpg"; //default image belirle
        }
        else //eğer image mevcutsa
        {
          $img_path .= $data["results"][$i]["poster_path"]; //mevcut yola image pathı ekle
          $response_array[$i]["image"] = $img_path; //image pathı gönder
        }

        $response_array[$i]["kind"] = $postedData['kind'];

      }

      http_response_code(200); // h200 OK
      echo json_encode($response_array); // send it

    }

    public function findSimilarAction()
    {
      $postedData = (array) json_decode(file_get_contents("php://input"), true); //get the POSTED value as an array

      if(!isset($postedData['content']) || !isset($postedData['kind'])) {
        return false;
      }
      $similarQuery = $postedData['content'];
      $kind = $postedData['kind'];

      $q = $similarQuery;

      $qParts = explode("-", $q);

      $name = $qParts[0];

      $year = $qParts[1];

      if($kind == 'movie') //name verisi varsa
      {
        $q = preg_replace('/\s+/', '-', $q); //boşlukları +'ya çevir, query için şart
        $url = 'https://www.film-fish.com/movieslike/'.$q;
      }
      else
      {
        $qName = $name;
        $qName = preg_replace('/\s+/', '-', $qName); //boşlukları +'ya çevir, query için şart
        $url = 'https://www.film-fish.com/showslike/'.$qName;

      }


      if(static::get_http_response_code($url) == "200")
      {
        $homepage = file_get_contents($url);

        $fullstring = $homepage;
        if($kind == 'movie') $s = '<meta name="description" content="Stream movies like '.$name.':';
        else $s = '<meta name="description" content="Stream shows like '.$name.':';

        $parsed = static::get_string_between($fullstring, $s, '" >');
        $parsed = substr($parsed, 1, -2);
        $result = [];
        $result = explode(",", $parsed);
      } else {
        $result = '';
      }
      echo json_encode($result); // send it

    }

    public static function get_http_response_code($url)
    {
      $headers = get_headers($url);
      return substr($headers[0], 9, 3);
    }

    public static function get_string_between($string, $start, $end)
    {
      $string = ' ' . $string;
      $ini = strpos($string, $start);
      if ($ini == 0) return '';
      $ini += strlen($start);
      $len = strpos($string, $end, $ini) - $ini;
      return substr($string, $ini, $len);
    }
}
