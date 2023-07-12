<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\Contents;
use \App\Models\Comments;
use \App\Auth;
use \App\tmdbSingleContent;

/**
 * Post controller
 *
 * PHP version 7.0
 */
class Post extends Authenticated
{

  protected function before()
  {
    parent::before();
    $this->user = Auth::getUser();
  }
  /**
  * show content index
  *
  * find by tmdb api based on url's tv_id and kind
  *
  * @return mixed as twig
  */
  public function indexAction()
  {
    if(!isset($this->route_params['id']) || !isset($this->route_params['kind']) || ($this->route_params['kind'] != 'serie' && $this->route_params['kind'] != 'movie'))
    {

      View::renderTemplate('/404.html');
      return false;

    }

    $tv_id = $this->route_params['id'];
    $kind = $this->route_params['kind'];

    if (!$data = tmdbSingleContent::getContentByApi($tv_id, $kind)) {

      View::renderTemplate('/404.html');
      return false;

    }

    $data['kind'] = $kind;
    $category = '';
    foreach($data["genres"] as $value)
    {
      $category .= $value["name"].", ";
    }

    $category = substr($category, 0, -2); //kategoriyi düzenle
    $data["category"] = $category;

    if (isset($data['original_title'])) $data['title'] = $data['original_title'];
    else $data['title'] = $data['original_name'];
    if (isset($data['release_date'])) $data['publish_date'] = strtok($data['release_date'], '-');
    else $data['publish_date'] = strtok($data['first_air_date'], '-');

    if($checking = Contents::validateWithID($this->user->id, $tv_id, $kind))
    {
      if($checking[0]['wishlist'] == 0)
      {
        $data['matched'] = 'watched';
      }
      else if ($checking[0]['wishlist'] == 1)
      {
        $data['matched'] = 'wishlist';
      }
    }

    $sp = Contents::getSP($tv_id, $kind);

    $data['sp'] = $sp['SP'];
    $data['pop'] = $sp['POP'];
    $data['wishlistedCount'] = Contents::getWL($tv_id, $kind)['WISHLISTED'];

    if ( ! $cUser = Comments::getCurrentUser($tv_id, $this->user->id, $kind)) $cUser = null;

    $page = $_GET['page'] ?? 1;
    $base = strtok($_SERVER["REQUEST_URI"], '?');
    $records_per_page = 3;
    $total_records = Comments::getCommentsTotal($tv_id, $kind);
    $page = filter_var($page, FILTER_VALIDATE_INT, [
      'options' => [
        'default' => 1,
        'min_range' => 1
        ]
      ]);

    $previous = $page - 1;

    $total_pages = ceil($total_records / $records_per_page);

    if($page < $total_pages)
    {
      $next = $page + 1;
    }
    else
    {
      $next = false;
    }

    $offset = $records_per_page * ($page - 1);

    if(!$comments = Comments::getComments($tv_id, $kind, $records_per_page, $offset))
    {
      $comments = null;
    } else {
      $limit = sizeof($comments); //detect the limitation
      for ($i=0; $i < $limit ; $i++) //list the founded movies based on limitations
      {
        if($voteStatus = Comments::checkComment($this->user->id, $comments[$i]['contentid']))
        {
          if($voteStatus['vote'] == 1)
          $comments[$i]['up'] = 1;
          else if ($voteStatus['vote'] == 0)
          $comments[$i]['down'] = 1;
        }
      }
    }
    $data['vote_average'] = number_format((float)$data['vote_average'], 1, '.', '');

    View::renderTemplate('/Post/index.html', [
      'content' => $data,
      'user' => $cUser,
      'comments' => $comments,
      'base' => $base,
      'next' => $next,
      'previous' => $previous
    ]);

  }

  /**
  *
  * vote comment
  *
  */
  public function voteCommentAction()
  {
    if(($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && isset($_POST['vote'])) && ($_POST['vote'] == 1 || $_POST['vote'] == 0))
    {
      if($voteStatus = Comments::checkComment($this->user->id, $_POST['content']))
      {
        if($voteStatus['vote'] == $_POST['vote'])
        {
          Comments::deleteVote($this->user->id, $_POST['content'], $voteStatus['vote']);
          http_response_code(201); //OK status
          echo json_encode(["message" => "vote canceled"]); //package and show the message
          exit;
        }
        else
        {
          if(Comments::deleteVote($this->user->id, $_POST['content'], $voteStatus['vote']))
          {
            Comments::voteComment($this->user->id, $_POST['content'], $_POST['vote']);
            http_response_code(200); //OK status
            echo json_encode(["message" => "voted"]); //package and show the message
            exit;
          }
        }
      }
      else
      {
        if(Comments::voteComment($this->user->id, $_POST['content'], $_POST['vote']))
        {
          http_response_code(200); //OK status
          echo json_encode(["message" => "voted"]); //package and show the message
          exit;
        }
      }

    }
    else
    {
      http_response_code(405); //422 Unprocessable Entity
      echo json_encode(["error" => 'method or data not valid']); //package and show the errors as json
    }
  }


  /**
  *
  * edit comment
  *
  */
  public function editCommentAction()
  {
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && isset($_POST['tv_id']) && isset($_POST['kind']))
    {
      if($_POST['kind'] != "serie" && $_POST['kind'] != "movie")
      {
        http_response_code(405);
        echo json_encode(["error" => 'method or data not valid']); //package and show the errors as json
      }
      if(strlen($_POST['comment']) <= 500 && strlen($_POST['comment']) > 5)
      {
        if(Comments::editComment($_POST['comment'], $_POST['tv_id'], $this->user->id, $_POST['kind']))
        {
          http_response_code(200); //OK status
          echo json_encode("comment updated.");
        }
        else
        {
          http_response_code(202); //Accepted but proccess problem
          echo json_encode(["error" => "the request has been accepted for processing, but the processing has not been completed"]); //package and show the message
        }
      }
      else
      {
        http_response_code(405);
        echo json_encode(["error" => 'method or data not valid']); //package and show the errors as json
      }
    }
    else
    {
      http_response_code(405);
      echo json_encode(["error" => 'method or data not valid']); //package and show the errors as json
    }
  }
}
