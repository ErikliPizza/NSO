<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\User;
use \App\Auth;

  /**
  * Signup controller
  * PHP version 7.0
  */

  class Signup extends \Core\Controller
  {

    /**
    * Before filter - called before each action method
    *
    * @return void
    */
    protected function before()
    {
      if(Auth::getUser())
      {
        $this->redirect('/profile/show');
      }
    }
    /**
    * Show the signup page
    * @return void
    */

    public function newAction()
    {
      View::renderTemplate('Signup/new.html');
    }

    /**
    * Sign up a new user
    *
    * @return void
    */
    public function createAction()
    {
      $user = new User($_POST);
      if ($user->save()) {

        $user->sendActivationMail();
        header("Location: https://nso.noircontact.tech/signup/success");
        die();
      } else {

        View::renderTemplate('Signup/new.html', [
          'user' => $user
        ]);

      }

    }

    /**
    * Show the signup success page
    *
    * @return void
    */
    public function successAction()
    {
      View::renderTemplate('Signup/success.html');
    }

    /**
    * Activate a new account
    *
    * @return void
    */
    public function activateAction()
    {
      if(User::activate($this->route_params['token'])) {
        $this->redirect('/signup/activated');
      } else {
        $this->redirect('/signup/activationFailed');
      }
    }

    /**
    * Show the activation success page
    *
    * @return void
    */
    public function activatedAction()
    {
      View::renderTemplate('Signup/activated.html');
    }

    /**
    * Show the activation failed page
    *
    * @return void
    */
    public function activationFailedAction()
    {
      View::renderTemplate('Signup/activation_failed.html');
    }

  }
