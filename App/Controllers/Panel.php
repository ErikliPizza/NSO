<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\User;
use \App\Models\Contents;
use \App\Auth;
use \App\tmdbSingleContent;
/**
 * Panel controller
 *
 * PHP version 7.0
 */
class Panel extends Authenticated
{
    
  /**
  * Require the user to be authenticated before giving access to all methods in the controller
  *
  * @return void
  */
  protected function before()
  {
    if (! Auth::getUser()) {
      $this->redirect('/explore');
    }
    parent::before();
    $this->user = Auth::getUser();
  }

    /**
     * Show Profile Page
     *
     * get user data as properties from the user model via Auth's getUser function
     *
     * get favMovie information from the tmdb api via tmdbSingleContent
     *
     * Only the top of the page's data sent from here
     *
     * @var user contains all user data, beside, profile page uses these datas
     * @var user->Photo
     * @var user->Bio
     * @var user->Instagram
     * @var user->WebUrl
     *
     * @var favMovie contains all tmdb api variables, beside, the profile page only uses this data
     * @var favMovie->BackdropPath
     *
     * @return void
     */
    public function indexAction()
    {
      // get the fav movie information
      if ($this->user->favMovie != '') {
        $favMovie = tmdbSingleContent::getContentByApi($this->user->favMovie, 'movie');
      }

      // get the instagram username
      if($this->user->instagram != null)
      {
      if (preg_match ('/(?:(?:http|https):\/\/)?(?:www\.)?(?:instagram\.com|instagr\.am)\/([A-Za-z0-9-_\.]+)/im',
                       $this->user->instagram,
                       $matches)) {
        $this->user->instagram = $matches[1];
      }
      }

      // get the web url
      if($this->user->webUrl != null)
      {
      $this->user->webUrl = preg_replace('#^https?://#', '', $this->user->webUrl);
      if(preg_match("/\//", $this->user->webUrl)){
          $this->user->webUrl = substr($this->user->webUrl, 0, strpos($this->user->webUrl, "/"));
      }
      }
      // get movie count
      $movieCount = Contents::getContentCount($this->user->id, 'movie');

      // get serie count
      $serieCount = Contents::getContentCount($this->user->id, 'serie');

      // return the view
      View::renderTemplate('Panel/index.html', [
        'favMovie' => $favMovie,
        'user' => $this->user,
        'movieCount' => $movieCount,
        'serieCount' => $serieCount
      ]);

    }

    /**
    * Change Profile Photo
    *
    * Get the uploaded file here via ajax post
    *
    * Move the photo into uploads/profiles folder
    *
    * if success change the photo data from the user table and delete the previous image
    *
    * if failed delete the image file from moved directory
    *
    * @var userid from Auth's getUer function
    *
    * @return void
    */
    public function changeImageAction()
    {
      try
      {
        if(empty($_FILES))
        {
          http_response_code(405);
          return false;
        }
        //catch errors
        switch($_FILES['file']['error'])
        {
            case UPLOAD_ERR_OK:
            http_response_code(405);
            break;

            case UPLOAD_ERR_NO_FILE:
            http_response_code(405);
            break;

            default:
            http_response_code(405);
            break;
          }

          if($_FILES['file']['size'] > 1048576)
          {
            http_response_code(405);
            exit;
          }
          //c


          //limit types
          $mime_types = ['image/gif', 'image/png', 'image/jpeg'];

          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $mime_type = finfo_file($finfo, $_FILES['file']['tmp_name']);
          if(!in_array($_FILES['file']['type'], $mime_types))
          {
            http_response_code(405);
            exit;
          }
          //lt

          $imgpath = 'uploads/profiles/';

          $previous_image = $this->user->photo;

          //choose path and limit name
          $pathinfo = pathinfo($_FILES["file"]["name"]);

          $base = $pathinfo['filename'];

          $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', $base);

          $base = mb_substr($base, 0, 200);

          $filename = $base . "." . $pathinfo['extension'];

          $destination = $imgpath.$filename;

          $i = 1;

          while (file_exists($destination))
          {
            $filename = $base . "-$i." . $pathinfo['extension'];
            $destination = $imgpath.$filename;
            $i++;
          }
          //cfaln

          if (move_uploaded_file($_FILES['file']['tmp_name'], $destination) )
          {
            if(User::updateImg($this->user->id, $filename))
            {
              if ($previous_image != '' && $previous_image != 'default.jpg')
              {
                unlink("uploads/profiles/$previous_image");
              }
              http_response_code(200);
              echo json_encode(["message" => "successfully uploaded"]);
            }
            else
            {
              unlink("uploads/profiles/$filename"); //son eklenen image dosyasını sil
              http_response_code(405);
              echo json_encode(["error" => "Database error"]);
            }
          }
          else
          {
            http_response_code(405);
            return false;
          }


        }
        catch(PDOExpection $e)
        {
          echo $e->getMessage();
        }
    }

    /**
    * @var tv_id from POSTED tv_id, part 0, e.g: 680
    * @var kind from posted tv_id, part 1, e.g: movie
    * @var type from posted tv_id, part 2, e.g: watched
    * @var userid from Auth's getUer function
    *
    * add to watched
    * add to wishlist
    * extract from wishlist/watched into watched/wishlist
    *
    * @return void
    */
    public function addAction()
    {
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tv_id'])) {
        $q = $_POST['tv_id'];
        $qParts = explode("-", $q);
        $id = $qParts[0];
        $kind = $qParts[1];
        $rating = $qParts[2];
        $type = $qParts[3];

        if ($kind != "serie" && $kind != "movie" || is_numeric($id) !== true || $type != "watched" && $type != "wishlist") {
          http_response_code(405);
          echo json_encode(["error" => 'method or data not valid']); //package and show the errors as json
          exit; //terminate
        }

        if ($type == "watched") {
          if ($rating == "delete") {
              $status = Contents::validateWithID($this->user->id, $id, $kind);
              Contents::delete($status[0]['id'], $this->user->id);
              exit;
          }
          if ($status = Contents::validateWithID($this->user->id, $id, $kind)) {
            if ($status[0]['wishlist'] == 0) {
              Contents::updateRating($status[0]['id'], $this->user->id, $rating);
            } else {
              if (Contents::extract($this->user->id, $status[0]['id'], '0', $rating)) {
                http_response_code(200); //OK status
                echo json_encode(["message" => "content with ID " .$id. " successfully IN WATCHED"]); //package and show the message
              } else {
                http_response_code(304);
                echo json_encode(["error" => "the request has been accepted for processing, but the processing has not been completed. Most possible reason is the database/connection error maybe"]);
              }
            }
          } else
          {
            if (Contents::add($this->user->id, $id, $kind, 0, $rating)) {
              http_response_code(200); //OK status
              echo json_encode(["message" => "added INTO WATCHED"]); //package and show the message
            }
            else {
              http_response_code(304);
              echo json_encode(["error" => "the request has been accepted for processing, but the processing has not been completed. Most possible reason is the database/connection error maybe"]);
            }
          }
        }
        else if ($type == "wishlist") {
          if ($status = Contents::validateWithID($this->user->id, $id, $kind)) {
            if ($status[0]['wishlist'] == 0) {
              if (Contents::extract($this->user->id, $status[0]['id'], '1')) {
                http_response_code(200); //OK status
                echo json_encode(["message" => "content with ID " .$id. " successfully IN WISHLIST"]); //package and show the message
              } else {
                http_response_code(304);
                echo json_encode(["error" => "the request has been accepted for processing, but the processing has not been completed. Most possible reason is the database/connection error maybe"]);
              }
            } else {
              Contents::delete($status[0]['id'], $this->user->id);
            }
          } else {
            if (Contents::add($this->user->id, $id, $kind, 1)) {
              http_response_code(200); //OK status
              echo json_encode(["message" => "added INTO WISHLIST"]); //package and show the message
            } else {
              http_response_code(304);
              echo json_encode(["error" => "the request has been accepted for processing, but the processing has not been completed. Most possible reason is the database/connection error maybe"]);
            }
          }
        }
      } else {
        http_response_code(405); //422 Unprocessable Entity
        echo json_encode(["error" => 'method or data not valid']); //package and show the errors as json
      }
    }



    /**
    * @var content_id The Posted Content id
    * @var userid from Auth's getUer function
    *
    * delete the content
    *
    * @return void
    */
    public function deleteAction()
    {
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content_id'])) {

        if (Contents::delete($_POST['content_id'], $this->user->id)) {

          http_response_code(200); //OK status
          echo json_encode(["message" => "content with ID " .$_POST['content_id']. " successfully DELETED"]); //package and show the message

        } else {

          http_response_code(202); //Accepted but proccess problem
          echo json_encode(["error" => "the request has been accepted for processing, but the processing has not been completed"]); //package and show the message

        }
      } else {

        http_response_code(405);
        echo json_encode(["error" => 'method or data not valid']); //package and show the errors as json

      }

    }



    /**
    * @var content_id The Posted Content id
    * @var userid from Auth's getUer function
    * @var personal_rating from Ajax Post
    * @var season from Ajax Post
    * @var episode from Ajax Post
    * delete the content
    *
    * @return void
    */
    public function updateAction()
    {
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['personal_rating']) && isset($_POST['content_id'])) { // Rating Update

        if (Contents::updateRating($_POST['content_id'], $this->user->id, $_POST['personal_rating'])) {

          http_response_code(200); //OK status
          echo json_encode(["message" => "content with ID " .$_POST['content_id']. " successfully RATED with RATE ".$_POST['personal_rating']]); //package and show the message

        } else {

          http_response_code(202); //Accepted but proccess problem
          echo json_encode(["error" => "the request has been accepted for processing, but the processing has not been completed"]); //package and show the message

        }
      } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['season']) && isset($_POST['episode']) && isset($_POST['content_id'])) { // S&E Update

        if (Contents::updateSE($_POST['content_id'], $this->user->id, $_POST['season'], $_POST['episode'])) {

          http_response_code(200); //OK status
          echo json_encode(["message" => "content with ID " .$_POST['content_id']. " successfully S&E UPDATED ".$_POST['season']."x".$_POST['episode']]); //package and show the message

        } else {

          http_response_code(202); //Accepted but proccess problem
          echo json_encode(["error" => "the request has been accepted for processing, but the processing has not been completed"]); //package and show the message

        }
      } else {

        http_response_code(405);
        echo json_encode(["error" => 'method or data not valid']); //package and show the errors as json

      }
    }

  }
