<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\User;
use \App\Auth;
use \App\Flash;
/**
 * Login controller
 *
 * PHP version 7.0
 */
class Login extends \Core\Controller
{
    /**
     * Show the login page
     *
     * @return void
     */
    public function newAction()
    {
      if(Auth::getUser())
      {
        $this->redirect('/profile/show');
      }
      View::renderTemplate('Login/new.html');
    }

    /**
     * Log in a user
     *
     * Check if it's mail or username
     * @return void
     */
    public function createAction()
    {
      if(Auth::getUser())
      {
        $this->redirect('/profile/show');
      }
      if (filter_var($_POST['name'], FILTER_VALIDATE_EMAIL) === false) // if it is username
      {
        $is_mail = false;
      }
      else // if it is mail
      {
        $is_mail = true;
      }

      $user = User::authenticate($_POST['name'], $_POST['password'], $is_mail);

      $remember_me = isset($_POST['remember_me']);

      if ($user) {

        Auth::login($user, $remember_me);

        Flash::addMessage('Login successful');

        $this->redirect(Auth::getReturnToPage());

      } else {

        Flash::addMessage('Login failed, please try again', Flash::WARNING);

        View::renderTemplate('Login/new.html', [
          'name' => $_POST['name'],
          'remember_me' => $remember_me
        ]);
      }

    }

    /**
    * Log out a user
    *
    * @return void
    */
    public function destroyAction()
    {
      Auth::logout();

      $this->redirect('/login/show-logout-message');
    }

    /**
    * Show a "logged out" flash message and redirect to the homepage. Necessary to use the flash messages
    * as they use the session and at the end of the logout method (destroyAction) the session is destroyed
    * so a new action needs to be called in order to use the session.
    *
    * @return void
    */
    public function showLogoutMessageAction()
    {
      Flash::addMessage('Logout successful');

      $this->redirect('/explore');
    }

}
