<?php

namespace App\Models;

use PDO;
use \App\Token;
use \App\tmdbSingleContent;
use \App\Mail;
use \Core\View;
/**
 * Example user model
 *
 * PHP version 7.0
 */
class User extends \Core\Model
{

    /**
    * Error Messages
    *
    * @var array
    */
    public $errors = [];

    /**
    * Example user model
    * PHP version 7.0
    */
    public function __construct($data = [])
    {
      foreach ($data as $key => $value) {
        $this->$key = $value;
      }
    }
    /**
    * Save the user model with the current property values
    *
    * @return void
    */
    public function save()
    {

      $this->validate();

      if (empty($this->errors)) {

        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        $token = new Token();
        $hashed_token = $token->getHash();
        $this->activation_token = $token->getValue();

        $sql = 'INSERT INTO users (username, nickname, mail, password, activation_hash)
                VALUES (:username, :nickname, :mail, :password, :activation_hash)';
        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':username', $this->username, PDO::PARAM_STR);
        $stmt->bindValue(':nickname', $this->nickname, PDO::PARAM_STR);
        $stmt->bindValue(':mail', $this->mail, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password_hash, PDO::PARAM_STR);
        $stmt->bindValue(':activation_hash', $hashed_token, PDO::PARAM_STR);

        return $stmt->execute();

      }

    return false;

    }

    /**
    * Validate current property values, adding validation error messags to the errors array property
    *
    * @return void
    */
    public function validate()
    {
      // Username
      if (!preg_match('/^[A-Za-z][A-Za-z0-9]{5,20}$/', $this->username)) // Must start with letter, 6-21 characters, Letters and numbers only
      {
        $this->errors[] = 'Username is invalid';
      }
      if (static::usernameExists($this->username, $this->id ?? null))
      {
        $this->errors[] = 'Username already taken';
      }

      // Nickname
      if (!preg_match('/^[A-Za-z][A-Za-z0-9]{5,20}$/', $this->nickname)) // Must start with letter, 6-21 characters, Letters and numbers only
      {
        $this->errors[] = 'Nickname is invalid';
      }

      // Mail
      if (filter_var($this->mail, FILTER_VALIDATE_EMAIL) === false)
      {
        $this->errors[] = 'Invalid mail';
      }
      if (static::mailExists($this->mail, $this->id ?? null))
      {
        $this->errors[] = 'Mail already taken';
      }

      // Password
      if (isset($this->password)) {
        if (strlen($this->password) < 6)
        {
          $this->errors[] = 'Please enter at least 6 characters for the password';
        }
        if (preg_match('/.*[a-z]+.*/i', $this->password) == 0)
        {
          $this->errors[] = 'Password needs at least one letter';
        }

        if (preg_match('/.*\d+.*/i', $this->password) == 0)
        {
          $this->errors[] = 'Password needs at least one number';
        }
      }

      // Bio
      if (isset($this->bio))
      {
        if (strlen($this->bio) > 300)
        {
          $this->errors[] = 'Please enter maximum 300 characters for the bio';
        }
      }

      // Instagram
      if (isset($this->instagram))
      {
        if ( !preg_match('/(?:(?:http|https):\/\/)?(?:www.)?(?:instagram.com|instagr.am)\/([A-Za-z0-9-_]+)/im', $this->instagram) )
        {
          $this->errors[] = 'invalid instagram url, please add your account with the http-s tags.';
        }
      }

      // WebUrl
      if (isset($this->webUrl))
      {
        if ( !filter_var($this->webUrl, FILTER_VALIDATE_URL) )
        {
          $this->errors[] = 'invalid web url, please add your site with the http-s tags.';
        }
      }

      // favMovie
      if (isset($this->favMovie))
      {
        $image_path = tmdbSingleContent::getContentByApi($this->favMovie, 'movie')['backdrop_path'];
        if ( $image_path == '' )
        {
          $this->errors[] = 'invalid fav movie, please select another one.';
        }
      }

    }

    /**
    * See if a user record already exists with the specified mail
    *
    * @param string $mail mail adress to search for
    * @param string $ignore_id, return false anyway if the record found has this ID
    *
    * @return boolean True if a record already exists with the specified mail, false otherwise
    */
    public static function mailExists($mail, $ignore_id = null)
    {
      $user = static::findByMail($mail);

      if ($user) {
        if ($user->id != $ignore_id) {
          return true;
        }
      }
      return false;
    }

    /**
    * See if a user record already exists with the specified username
    *
    * @param string $username username to search for
    * @param string $ignore_id, return false anyway if the record found has this ID
    *
    * @return boolean True if a record already exists with the specified username, false otherwise
    */
    public static function usernameExists($username, $ignore_id = null)
    {
      $user = static::findByUsername($username);

      if ($user) {
        if ($user->id != $ignore_id) {
          return true;
        }
      }
      return false;
    }

    /**
    * Find a user model by email address
    * @param string $mail mail address to search for
    *
    * @return mixed User object if found, false otherwise
    */
    public static function findByMail($mail)
    {
      $sql = 'SELECT * FROM users WHERE mail = :mail';

      $db = static::getDB();
      $stmt = $db->prepare($sql);
      $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);

      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

      $stmt->execute();

      return $stmt->fetch(); //This is the line 219
    }


    /**
    * Find a user model by username
    * @param string $username username to search for
    *
    * @return mixed User object if found, false otherwise
    */
    public static function findByUsername($username)
    {
      $sql = 'SELECT * FROM users WHERE username = :username';

      $db = static::getDB();
      $stmt = $db->prepare($sql);
      $stmt->bindParam(':username', $username, PDO::PARAM_STR);

      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

      $stmt->execute();

      return $stmt->fetch();
    }

    /**
    * Authenticate a user by mail or username and password
    *
    * @param string $name mail address or username
    * @param string $password password
    * @param string $type is the type of the authenticate
    *
    * @return mixed The user object or false if authentication fails
    */

    public static function authenticate($name, $password, $is_mail)
    {
      if ($is_mail === true)
      {
        $user = static::findByMail($name);
      }
      else {
        $user = static::findByUsername($name);
      }

      if ($user && $user->is_active) {
        if (password_verify($password, $user->password)) {

          return $user;

        }
      }
      return false;
    }

    /**
    * Find a user model by ID
    *
    * @param integer $id the user ID
    *
    * @return mixed User object if found, false otherwise
    */
    public static function findByID($id)
    {
      $sql = 'SELECT * FROM users WHERE id = :id';

      $db = static::getDB();
      $stmt = $db->prepare($sql);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);

      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

      $stmt->execute();



      return $stmt->fetch();
    }


    /**
    * Remember the login by inserting a new unique token into the remembered_logins table
    * for this user record
    *
    * @return boolean, true if the login was remembered successfully, false otherwise
    */
    public function rememberLogin()
    {
      $token = new Token();
      $hashed_token = $token->getHash();
      $this->remember_token = $token->getValue();

      $this->expiry_timestamp = time() + 60 * 60 * 24 * 30; // 30 days from now

      $sql = 'INSERT INTO remembered_logins (token_hash, user_id, expires_at)
              VALUES (:token_hash, :user_id, :expires_at)';

      $db = static::getDB();
      $stmt = $db->prepare($sql);

      $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
      $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
      $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $this->expiry_timestamp), PDO::PARAM_STR);

      return $stmt->execute();

    }

    /**
    * Send password reset instructions to the user specified
    *
    * @param string $mail, the mail address
    *
    * @return void
    */
    public static function sendPasswordReset($mail)
    {

      $user = static::findByMail($mail);
      if ($user) {

        if ($user->startPasswordReset()) {

          $user->sendPasswordResetMail();

        }

      }

    }

    /**
    * Start the password reset process by generating a new token and expiry
    *
    * @return void
    */
    protected function startPasswordReset()
    {
      $token = new Token();
      $hashed_token = $token->getHash();
      $this->password_reset_token = $token->getValue();

      $expiry_timestamp = time() + 60 * 60 * 3; // 3 hours from now

      $sql = 'UPDATE users
              SET password_reset_hash = :token_hash,
                  password_reset_expiry = :expires_at
              WHERE id = :id';
      $db = static::getDB();
      $stmt = $db->prepare($sql);
      $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
      $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $expiry_timestamp), PDO::PARAM_STR);
      $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

      return $stmt->execute();
    }

    /**
    * Send password reset instructions in an mail to the user
    *
    * @return void
    */
    protected function sendPasswordResetMail()
    {
      $url = 'http://' . $_SERVER['HTTP_HOST'] . '/password/reset/' . $this->password_reset_token;

      $html = View::getTemplate('Password/reset_mail.html', ['url' => $url]);

      Mail::send($this->mail, 'Password reset', 'Password reset', $html);
    }

    /**
    * Find a user model by password reset token and expiry
    *
    * @param string $token, Password reset token sent to user
    *
    * @return mixed User object if found and the token hasn't expired, null otherwise
    */
    public static function findByPasswordReset($token)
    {
      $token = new Token($token);
      $hashed_token = $token->getHash();

      $sql = 'SELECT * FROM users
              WHERE password_reset_hash = :token_hash';

      $db = static::getDB();
      $stmt = $db->prepare($sql);

      $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);

      $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

      $stmt->execute();

      $user = $stmt->fetch(); // returns false, when a record is not found

      if ($user) {
        if (strtotime($user->password_reset_expiry) > time()) {

          return $user;

        }

      }

    }

    /**
    * Reset the password
    *
    * @param string $password, the new password
    *
    * @return boolean, true if the password was updated successfully, false otherwise
    */
    public function resetPassword($password)
    {
      $this->password = $password;

      $this->validate();

      if (empty($this->errors)) {

        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        $sql = 'UPDATE users
                SET password = :password_hash,
                    password_reset_hash = NULL,
                    password_reset_expiry = NULL
                WHERE id = :id';
        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();

      }

      return false;
    }

    /**
    * Send a mail to the user containing the activation link
    *
    * @return void
    */
    public function sendActivationMail()
    {
      $url = 'http://' . $_SERVER['HTTP_HOST'] . '/signup/activate/' . $this->activation_token;

      $html = View::getTemplate('Signup/activation_mail.html', ['url' => $url]);

      Mail::send($this->mail, 'Account activation', 'Account activation', $html);
    }

    /**
    * Activate the user account with the specified activation token
    *
    * @param string $value, activation token from the URL
    *
    * @return void
    */
    public static function activate($value)
    {
      $token = new Token($value);
      $hashed_token = $token->getHash();

      $sql = 'UPDATE users
              SET is_active = 1,
                  activation_hash = null
              WHERE activation_hash = :hashed_token';
      $db = static::getDB();
      $stmt = $db->prepare($sql);

      $stmt->bindValue(':hashed_token', $hashed_token, PDO::PARAM_STR);

      $stmt->execute();

      return $stmt->rowCount();
    }

    /**
    * Update the user's profile
    *
    * @param array $data, data from the edit profile form
    *
    * @return boolean, true if the data was updated, false otherwise
    */
    public function updateProfile($data)
    {
      $this->username = $data['username'];
      $this->nickname = $data['nickname'];
      $defaultmail = $this->mail;
      $this->mail = $data['mail'];

      // Only validate and update the password if a value provided
      if ($data['password'] != '') {
        $this->password = $data['password'];
      } else {
        $this->password = null;
      }
      // Only validate and update the bio if a value provided
      if ($data['bio'] != '') {
        $this->bio = $data['bio'];
      } else {
        $this->bio = null;
      }
      // Only validate and update the instagram if a value provided
      if ($data['instagram'] != '') {
        $this->instagram = $data['instagram'];
      } else {
        $this->instagram = null;
      }
      // Only validate and update the weburl if a value provided
      if ($data['webUrl'] != '') {
        $this->webUrl = $data['webUrl'];
      } else {
        $this->webUrl = null;
      }
      // Only validate and update the fav movie if a value provided
      if ($data['fav'] != '') {
        $this->favMovie = $data['fav'];
      } else {
        $this->favMovie = null;
      }

      $this->validate();

      if (empty($this->errors)) {
        $sql = 'UPDATE users
                SET username = :username,
                    nickname = :nickname';

        // Add password if it's set
        if (isset($this->password)) {
          $sql .= ', password = :password';
        }

        // Add bio if it's set
        $sql .= ', bio = :bio';

        // Add instagram if it's set
        $sql .= ', instagram = :instagram';

        // Add webUrl if it's set
        $sql .= ', webUrl = :webUrl';

        // Add favMovie if it's set
        $sql .= ', favMovie = :favMovie';

        // Add Mail Confirm Token if User's mail and Data's mail are different
        if ($defaultmail != $data['mail'])
        {
          $sql .= ', confirmation_hash = :confirmation_hash, is_confirmed = 0';
        }


        $sql .= "\nWHERE id = :id";

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':username', $this->username, PDO::PARAM_STR);
        $stmt->bindValue(':nickname', $this->nickname, PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

        // Add password if it's set
        if (isset($this->password)) {
          $password_hash = password_hash($this->password, PASSWORD_DEFAULT);
          $stmt->bindValue(':password', $password_hash, PDO::PARAM_STR);
        }
        // Add bio if it's set
        if (isset($this->bio)) {
          $stmt->bindValue(':bio', $this->bio, PDO::PARAM_STR);
        } else {
          $stmt->bindValue(':bio', null, PDO::PARAM_NULL);
        }
        // Add instagram if it's set
        if (isset($this->instagram)) {
          $stmt->bindValue(':instagram', $this->instagram, PDO::PARAM_STR);
        } else {
          $stmt->bindValue(':instagram', null, PDO::PARAM_NULL);
        }
        // Add webUrl if it's set
        if (isset($this->webUrl)) {
          $stmt->bindValue(':webUrl', $this->webUrl, PDO::PARAM_STR);
        } else {
          $stmt->bindValue(':webUrl', null, PDO::PARAM_NULL);
        }
        // Add favMovie if it's set
        if (isset($this->favMovie)) {
          $stmt->bindValue(':favMovie', $this->favMovie, PDO::PARAM_STR);
        } else {
          $stmt->bindValue(':favMovie', '680', PDO::PARAM_STR);
        }
        // Add Mail Confirm Token if User's mail and Data's mail are different
        if ($defaultmail != $data['mail'])
        {
          $codec = new \App\JWTCodec(\App\Config::CONFIRM_KEY); //instantinate the codec and send the confirm key
          //jwt claims - iana.org/assignments/jwt/jwt.xhtml
          //set the payload
          $payload = [
            "sub" => $this->id, //id of the logged user
            "email" => $data['mail'], //new mail adress
          ];

          $confirmation_hash = $codec->encode($payload); //encode the payload
          $this->confirmation_token = $confirmation_hash;
          $stmt->bindValue(':confirmation_hash', $confirmation_hash, PDO::PARAM_STR);
          $this->sendConfirmationMail($defaultmail, $data['mail']);
        }


        return $stmt->execute();
      }

      return false;
    }


    /**
    * Send a mail to the user containing the NEW mail confirmation link
    *
    * @return void
    */
    public function sendConfirmationMail($defaultmail, $newMail)
    {
      $url = 'http://' . $_SERVER['HTTP_HOST'] . '/profile/confirmation/' . $this->confirmation_token;

      $html = View::getTemplate('Profile/confirmation_mail.html', ['url' => $url, 'newMail' => $newMail]);

      Mail::send($defaultmail, 'New Mail Confirmation', 'New Mail Confirmation', $html);
    }

    /**
    * Cancel the new mail confirmation
    *
    * @return void
    */
    public function cancelConfirmation()
    {
      $sql = 'UPDATE users
              SET confirmation_hash = NULL,
                  is_confirmed = 1
              WHERE id = :id';

      $db = static::getDB();
      $stmt = $db->prepare($sql);

      $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

      $stmt->execute();
    }

    /**
    * Confirm the NEW mail adress with the specified confirm token
    *
    * @param string $token, confirm token from the URL
    *
    * @param string $mail, mail address from the token
    *
    * @param int $id, id from the token
    *
    * @return void
    */
    public static function confirm($token, $mail, $id)
    {
      $sql = 'UPDATE users
              SET is_confirmed = 1,
                  confirmation_hash = null,
                  mail = :mail
              WHERE id = :id
              AND confirmation_hash = :confirmation_hash';
      $db = static::getDB();
      $stmt = $db->prepare($sql);

      $stmt->bindValue(':confirmation_hash', $token, PDO::PARAM_STR);
      $stmt->bindValue(':mail', $mail, PDO::PARAM_STR);
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);

      $stmt->execute();

      return $stmt->rowCount();
    }

    public static function updateImg($id, $image)
    {
      try{
        $db = static::getDB();
        $sql = "UPDATE users set photo=:image
                WHERE id=:id";
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':image', $image, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
      }
      catch(PDOExpection $e){
        echo $e->getMessage();
      }
    }

}
