<?php

namespace App\Models;

use PDO;
/**
 * List model
 *
 * PHP version 7.0
 */
class Lists extends \Core\Model
{

  /**
  * @param CALLED FROM CONTROLLER\ListContent.PHP
  *
  *
  * get the requested contents
  *
  * @return mixed
  *
  */
  public static function getListContents($sql, $category, $limit, $offset)
  {
    $db = static::getDB();
    $stmt = $db->prepare($sql);
    if ($category != null) {

      $category = trim($category);
      $category = "+".$category;
      $category = preg_replace('/\s+/', '+ ', $category); // replace whitespaces with +, necessesery for query
      $stmt->bindValue(':category', $category, PDO::PARAM_STR);

    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getTotal($sql, $category)
  {
    $db = static::getDB();
    $stmt = $db->prepare($sql);

    if ($category != null) {

      $category = trim($category);
      $category = "+".$category;
      $category = preg_replace('/\s+/', '+ ', $category); // replace whitespaces with +, necessesery for query
      $stmt->bindValue(':category', $category, PDO::PARAM_STR);

    }
    $stmt->execute();
    return $results = $stmt->fetchColumn();
  }

  /**
  * @param CALLED FROM CONTROLLER\ListContent.PHP
  *
  *
  * get the requested content's comment here if it exists
  *
  * @return mixed
  *
  */
  public static function getCM($tv_id, $kind)
  {
    $db = static::getDB();
    $sql = "SELECT users.username as username, users.photo as photo, a.comment
            FROM contents a
            INNER JOIN
            (
              SELECT tv_id, kind, MAX(vote) vote
              FROM contents
              WHERE comment IS NOT NULL AND LENGTH(comment) > 5 AND tv_id = :tv_id AND kind = :kind AND wishlist = 0
              GROUP BY tv_id, kind
            )
            b ON a.tv_id = b.tv_id AND a.kind = b.kind AND a.vote = b.vote
            INNER JOIN users on users.id = a.author_id
            WHERE a.comment IS NOT NULL and LENGTH(a.comment) > 5 AND a.tv_id = :tv_id AND a.kind = :kind AND a.wishlist = 0
            GROUP BY a.tv_id, a.kind";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':tv_id', $tv_id, PDO::PARAM_STR);
    $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);
    $stmt->execute();
    return $results = $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
