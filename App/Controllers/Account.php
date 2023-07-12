<?php

namespace App\Controllers;

use \App\Models\User;

/**
 * Account controller
 *
 * PHP version 7.0
 */
class Account extends \Core\Controller
{

    /**
     * validate if mail is avaliable (AJAX) for new signup.
     *
     * @return void
     */
    public function validateMailAction()
    {
      $is_valid = ! User::mailExists($_GET['mail'], $_GET['ignore_id'] ?? null);
      header('Content-Type: application/json');
      echo json_encode($is_valid);
    }

    /**
     * validate if username is avaliable (AJAX) for new signup.
     *
     * @return void
     */
    public function validateUsernameAction()
    {
      $is_valid = ! User::usernameExists($_GET['username'], $_GET['ignore_id'] ?? null);
      header('Content-Type: application/json');
      echo json_encode($is_valid);
    }
}
