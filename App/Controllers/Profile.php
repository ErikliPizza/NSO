<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\tmdbSingleContent;
use \App\Flash;
use \App\Models\User;
use \App\JWTCodec;
/**
* Profile controller
*
* PHP version 7.0
*/
class Profile extends Authenticated
{


  /**
  * Before filter - called before each action method
  *
  * @return void
  */
  protected function before()
  {
    parent::before();
    $this->user = Auth::getUser();
  }


  /**
  * Show the profile
  *
  * @return void
  */
  public function showAction()
  {
    $this->requireLogin();
    View::renderTemplate('Profile/show.html', [
      'user' => $this->user
    ]);
  }

  /**
  * Show the form for editing the profile
  *
  * @return void
  */
  public function editAction()
  {
    $this->requireLogin();
    $image_path = tmdbSingleContent::getContentByApi($this->user->favMovie, 'movie');
    View::renderTemplate('Profile/edit.html', [
      'user' => $this->user,
      'image_path' => $image_path
    ]);
  }

  /**
  * Update the profile
  *
  * @return void
  */
  public function updateAction()
  {
    $this->requireLogin();
    if ($this->user->updateProfile($_POST)) {

      Flash::addMessage('Changes saved');

      $this->redirect('/profile/show');

    } else {

      View::renderTemplate('Profile/edit.html', [
        'user' => $this->user
      ]);

    }

  }

  /**
  * Confirm the new mail
  *
  * @return void
  */
  public function confirmationAction()
  {
    $jwt = new JWTCodec(\App\Config::CONFIRM_KEY);
    $data = $jwt->decode($this->route_params['token']); //try decoding the accesstoken

    if (User::mailExists($data['email'], $data['sub'])) {
      $this->redirect('/profile/confirmationFailed');
    }

    if (User::confirm($this->route_params['token'], $data['email'], $data['sub'])) {
      $this->redirect('/profile/confirmed');
    } else {
      $this->redirect('/profile/confirmationFailed');
    }
  }

  /**
  * Show the confirm success page
  *
  * @return void
  */
  public function confirmedAction()
  {
    View::renderTemplate('Profile/confirmed.html');
  }

  /**
  * Show the confirm failed page
  *
  * @return void
  */
  public function confirmationFailedAction()
  {
    View::renderTemplate('Profile/confirmation_failed.html');
  }

  /**
  * Abort the new mail confirmation
  *
  * @return void
  */
  public function cancelConfirmationAction()
  {
    $this->requireLogin();
    $this->user->cancelConfirmation();
    $this->redirect('/profile/show');
  }
}
