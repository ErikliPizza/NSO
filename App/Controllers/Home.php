<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Models\Contents;
use \App\Models\socialUser;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Home extends \Core\Controller
{

    /**
     * Show the index page
     *
     * Show the wellcome page if user doesn't logged in
     * Show the home page if the user logged in
     *
     * @return void
     */
    public function indexAction()
    {
      if (! Auth::getUser()) {
        View::renderTemplate('Home/index.html');
      } else {
        $mwaContent = Contents::most("0");
        $mwiContent = Contents::most("1");
        $popLastWeek = Contents::popularLastWeek();
        View::renderTemplate('Home/logged.html', [
          "watched" => $mwaContent,
          "wishlist" => $mwiContent,
          "popularLastWeek" => $popLastWeek
        ]);
      }
    }

    /**
     * Search the users
     *
     * Show the wellcome page if user doesn't logged in
     * Show the home page if the user logged in
     *
     * @return void
     */
    public function searchUserAction()
    {
      $this->requireLogin();
      $data = (array) json_decode(file_get_contents("php://input"), true); //get the POSTed value as an array

      if (!isset($data['username'])) {

        http_response_code(404); //404 not found
        return false; //return false, terminate

      }

      if ($results = socialUser::searchUser($data['username'])) {

        http_response_code(200);
        echo json_encode($results);

      } else {

        echo json_encode('nothing found..');

      }

    }
}
