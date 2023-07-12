<?php

namespace App\Controllers;
use \Core\View;
use \App\Models\User;
use \App\Auth;

/**
* Password controller
*
* PHP version 7.0
*/

class Password extends \Core\Controller
{

    /**
    * Before filter - called before each action method
    *
    * @return void
    */
    protected function before()
    {
      if (Auth::getUser())
      {
        $this->redirect('/profile/show');
      }
    }

   /**
   * Show the forgotten password page
   *
   * @return void
   */
   public function forgotAction()
   {
     View::renderTemplate('Password/forgot.html');
   }

   /**
   * Send the password reset link to the supplied mail
   *
   * @return void
   */
   public function requestResetAction()
   {
     User::sendPasswordReset($_POST['mail']);
     $this->redirect('/password/request-reset-view');
   }

   /**
   * repost fixer
   *
   * @return void
   */
   public function requestResetViewAction()
   {
     View::renderTemplate('Password/reset_requested.html');
   }

   /**
   * Show the reset password form
   *
   * @return void
   */
   public function resetAction()
   {
     $token = $this->route_params['token'];

     $user = $this->getUserOrExit($token);

     View::renderTemplate('Password/reset.html', [
       'token' => $token
     ]);
   }

   /**
   * Reset the user's password
   *
   * @return void
   */
   public function resetPasswordAction()
   {
     $token = $_POST['token'];

     $user = $this->getUserOrExit($token);

     if ($user->resetPassword($_POST['password'])) {

       $this->redirect('/password/reset-password-view');

     } else {

       View::renderTemplate('Password/reset.html', [
         'token' => $token,
         'user' => $user
       ]);

     }
   }

   /**
   * repost fixer
   *
   * @return void
   */
   public function resetPasswordViewAction()
   {
     View::renderTemplate('Password/reset_success.html');
   }

   /**
   * Find the user model associated with the password reset token, or end the request with a message
   *
   * @param string $token, password reset token sent to user
   *
   * @return mixed User object if found and the token has not expired, null otherwise
   */
   protected function getUserOrExit($token)
   {
     $user = User::findByPasswordReset($token);

     if ($user) {

       return $user;

     } else {

       View::renderTemplate('Password/token_expired.html');
       exit;

     }
   }
}
