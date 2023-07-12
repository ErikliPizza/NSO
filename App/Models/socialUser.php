<?php

namespace App\Models;

use PDO;
/**
 * Example user model
 *
 * PHP version 7.0
 */
class socialUser extends \Core\Model
{
  /**
  * Find a user model by username
  *
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
  * @param CALLED from CONTROLLER/HOME.PHP
  *
  * search users table by username
  *
  * @param string $username username to search for
  *
  * @return mixed User array if found, false otherwise
  */
  public static function searchUser($username)
  {
    $db = static::getDB();
    $fullQ = '%' . $username . '%';
    $sql = "SELECT username, nickname, id, bio, photo FROM users
              WHERE username LIKE :username or nickname LIKE :username LIMIT 3";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(':username', $fullQ, PDO::PARAM_STR);

    $stmt->execute();

    return $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
