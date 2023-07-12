<?php

namespace App\Models;

use PDO;
use \App\tmdbSingleContent;
/**
 * Content model
 *
 * PHP version 7.0
 */
class Contents extends \Core\Model
{

  /**
  * @param CALLED FROM CONTROLLER\CONTENT.PHP
  *
  * @param sql the query, answers multiple type of query, so I decided to make it dynamic, - unknown if it's healthy or not
  * @param author_id The user's id
  * @param limit The limit
  * @param offset The offset
  * @param kind The kind of the content
  * @param wishlist The type of the content, watched|wishlist
  * @param search The search string
  * @param category The search string
  *
  * Get the contents
  *
  * Profile and Social Profiles gets contents on here
  *
  * @return mixed
  */
  public static function getContent($sql, $author_id, $limit, $offset, $kind = null, $wishlist = null, $search = null, $category = null, $second_id = null)
  {

    $db = static::getDB();
    $stmt = $db->prepare($sql);


    if ($author_id != null) $stmt->bindValue(':author_id', $author_id, PDO::PARAM_INT);

    if ($kind != null) $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);

    if ($wishlist != null) $stmt->bindValue(':wishlist', $wishlist, PDO::PARAM_INT);

    if ($search != null) {

      $fullQ = '%' . $search . '%';
      $stmt->bindValue(':search', $fullQ, PDO::PARAM_STR);

    }

    if ($category != null) {

      $category = trim($category);
      $category = "+".$category;
      $category = preg_replace('/\s+/', '+ ', $category); // replace whitespaces with +, necessesery for query
      $stmt->bindValue(':category', $category, PDO::PARAM_STR);

    }

    if ($second_id != null) $stmt->bindValue(':second_id', $second_id, PDO::PARAM_INT);

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    return $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  }


  /**
  * @param CALLED FROM CONTROLLER\CONTENT.PHP
  *
  * @param sql the query, answers multiple type of query, so I decided to make it dynamic, - unknown if it's healthy or not
  * @param author_id The user's id
  * @param limit The limit
  * @param offset The offset
  * @param kind The kind of the content
  * @param wishlist The type of the content, watched|wishlist
  * @param search The search string
  * @param category The search string
  *
  * Get the @var LIMIT
  *
  * Profile and Social Profiles gets total contents on here
  *
  * @return LIMIT
  */
  public static function getTotal($sql, $author_id, $kind = null, $wishlist = null, $search = null, $category = null, $second_id = null)
  {

    $db = static::getDB();
    $stmt = $db->prepare($sql);


    if ($author_id != null) $stmt->bindValue(':author_id', $author_id, PDO::PARAM_INT);

    if ($kind != null) $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);

    if ($wishlist != null) $stmt->bindValue(':wishlist', $wishlist, PDO::PARAM_INT);

    if ($search != null) {

      $fullQ = '%' . $search . '%';
      $stmt->bindValue(':search', $fullQ, PDO::PARAM_STR);

    }

    if ($category != null) {

      $category = trim($category);
      $category = "+".$category;
      $category = preg_replace('/\s+/', '+ ', $category); //boşlukları +'ya çevir, query için şart
      $stmt->bindValue(':category', $category, PDO::PARAM_STR);

    }

    if ($second_id != null) $stmt->bindValue(':second_id', $second_id, PDO::PARAM_INT);

    $stmt->execute();

    return $results = $stmt->fetchColumn();

  }

  /**
  * @param CALLED FROM CONTROLLER\CONTENT.PHP
  *
  * @param tv_id The tv_id
  * @param kind The kind of content
  *
  * Profile and Social Profiles gets average personal rating and popularity on here
  *
  * @return mixed
  *
  */
  public static function getSP($tv_id, $kind)
  {
    $sql = "SELECT ROUND(AVG(personal_rating), 1) as SP, COUNT(*) as POP FROM contents
            WHERE tv_id=:tv_id
            AND wishlist=0
            AND kind=:kind;";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->bindValue(':tv_id', $tv_id, PDO::PARAM_STR);
    $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);
    $stmt->execute();
    return $results = $stmt->fetch(PDO::FETCH_ASSOC);
  }


  /**
  * @param CALLED FROM CONTROLLER\CONTENT.PHP
  *
  * @param tv_id The tv_id
  * @param kind The kind of content
  *
  * Profile and Social Profiles gets total wishlist count on here
  *
  * @return WISHLISTED
  *
  */
  public static function getWL($tv_id, $kind)
  {
    $sql = "SELECT COUNT(*) as WISHLISTED FROM contents
            WHERE tv_id=:tv_id
            AND wishlist=true
            AND kind = :kind;";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->bindValue(':tv_id', $tv_id, PDO::PARAM_STR);
    $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);
    $stmt->execute();
    return $results = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
  * @param CALLED from CONTROLLER/PANEL.PHP and CONTROLLER/SOCIAL.PHP
  *
  * @param id The user's id
  * @param kind The kind of content
  *
  * Profile and Social Profiles gets count of content on here
  *
  * @return integer
  *
  */
  public static function getContentCount($id, $kind)
  {
    $sql = 'SELECT count(id) as ' . $kind . '
            FROM contents WHERE author_id = :id AND kind = :kind AND wishlist=0';

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':kind', $kind, PDO::PARAM_STR);

    $stmt->execute();
    return $results = $stmt->fetchColumn();
  }

  /**
  * @param CALLED FROM CONTROLLER\PANEL.PHP
  *
  * @param user_id The user's id
  * @param tv_id The tv_id
  * @param kind The kind of content
  * @param wishlist The type of content
  *
  * add the content into contents table
  *
  * get some informations from the tmdb api via tmdbSingleContent
  *
  * @return void
  *
  */
  public static function add($user_id, $tv_id, $kind, $wishlist, $personal_rating = null)
  {
    date_default_timezone_set('Asia/Istanbul'); /** @param you can change it on localization settings @param*/

    if($kind == "serie")
    {
      $data = tmdbSingleContent::getContentByApi($tv_id, 'tv');
    }
    else if($kind == "movie")
    {
      $data = tmdbSingleContent::getContentByApi($tv_id, 'movie');
    }

    //GET VALUES
    if ($data)
    {
      if ($kind == "movie")
      {
        $title = $data["original_title"];
        $publish_date = $data["release_date"];
      }
      else if ($kind == 'serie')
      {
        $title = $data["original_name"];
        $publish_date = $data["first_air_date"];
      }
      $category = "";
      foreach($data["genres"] as $value)
      {
        $category .= $value["name"].", ";
      }
      $category = substr($category, 0, -2); //kategoriyi düzenle
      $rating = $data["vote_average"];
      $publish_date = strtok($publish_date, '-');

      if(empty($data["poster_path"]))
      {
        $image_path = "https://tinypng.com/images/social/website.jpg";
      }
      else
      {
        $image_path = $data["poster_path"];
      }
      if(empty($data["overview"]))
      {
        $overview = "?";
      }
      else
      {
        $overview= $data["overview"];
      }
    }
    //GET VALUES

    $db = static::getDB();
    $sql = "INSERT INTO contents(title, category, rating, publish_date, kind, image_path, overview, updated_date, inserted_date, author_id, tv_id, wishlist, personal_rating)
            VALUES (:title, :category, :rating, :publish_date, :kind, :image_path, :overview, :updated_date, :inserted_date, :author_id, :tv_id, :wishlist, :personal_rating)";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(":title", $title, PDO::PARAM_STR);
    $stmt->bindValue(":category", $category, PDO::PARAM_STR);
    $stmt->bindValue(":rating", $rating, PDO::PARAM_STR);
    $stmt->bindValue(":publish_date", $publish_date, PDO::PARAM_STR);
    $stmt->bindValue(":kind", $kind, PDO::PARAM_STR);
    $stmt->bindValue(":image_path", $image_path, PDO::PARAM_STR);
    $stmt->bindValue(":overview", $overview, PDO::PARAM_STR);
    $stmt->bindValue(":updated_date", date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stmt->bindValue(":inserted_date", date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stmt->bindValue(":author_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":tv_id", $tv_id, PDO::PARAM_STR);
    $stmt->bindValue(":wishlist", $wishlist, PDO::PARAM_INT);
    if ($personal_rating == null) {
      $stmt->bindValue(":personal_rating", $personal_rating, PDO::PARAM_NULL);
    } else {
      $stmt->bindValue(":personal_rating", $personal_rating, PDO::PARAM_INT);
    }

    return $stmt->execute();
  }


  /**
  * @param CALLED FROM CONTROLLER\PANEL.PHP
  *
  * @param user_id The user's id
  * @param content_id The content's id
  * @param wishlist The type of content
  *
  * extract from wishlist/watched into watched/wishlist
  *
  * @return void
  *
  */
  public static function extract($user_id, $content_id, $wishlist, $personal_rating = null)
  {
    date_default_timezone_set('Asia/Istanbul');
    $sql = "UPDATE contents set wishlist=:wishlist, inserted_date=:inserted_date";

    if($wishlist == 1)
    {
      $sql .= ', personal_rating = null';
    }
    else if($wishlist == 0)
    {
      $sql .= ', personal_rating = :personal_rating';
    }

    $sql .= "\nWHERE author_id=:user_id AND id=:content_id";

    $db = static::getDB();
    $stmt = $db->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":wishlist", $wishlist, PDO::PARAM_INT);
    $stmt->bindValue(":content_id", $content_id, PDO::PARAM_INT);
    $stmt->bindValue(":inserted_date", date('Y-m-d H:i:s'), PDO::PARAM_STR);
    if($wishlist == 0)
    {
      $stmt->bindValue(":personal_rating", $personal_rating, PDO::PARAM_INT);
    }

    $result = $stmt->execute();
    return $result;
  }
  


  /**
  * @param user_id The user's id
  * @param tv_id The tv_id
  * @param kind The kind of content
  *
  * use this function while getting contents via Social Profile
  * compare the user's and social user's data on here
  *
  * @return mixed
  *
  */
  public static function validateWithID($user_id, $tv_id, $kind)
  {
    $sql = "SELECT id, wishlist, personal_rating
            FROM contents
            WHERE author_id = :user_id
            AND tv_id = :tv_id
            AND kind = :kind";

    $db = static::getDB();
    $stmt = $db->prepare($sql);
    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(":tv_id", $tv_id, PDO::PARAM_STR);
    $stmt->bindValue(":kind", $kind, PDO::PARAM_STR);

    if($stmt->execute())
    {
      return $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    else
    {
      return false;
    }

  }

  /**
  * @param CALLED FROM CONTROLLER\PANEL.PHP
  * @param CALLED FROM CONTROLLER\CONTENT.PHP
  *
  * @param content_id The content's id
  * @param user_id The user's id
  * @param personal_rating The Posted Personal Rating
  *
  * Update personal rating
  *
  * @return void
  *
  */
  public static function updateRating($content_id, $user_id, $personal_rating)
  {
    if($personal_rating>10) $personal_rating = 10;
    else if($personal_rating<1) $personal_rating = 1;

    date_default_timezone_set('Asia/Istanbul');

    $sql = "UPDATE contents
            SET personal_rating=:personal_rating, updated_date=:updated_date
            WHERE id=:content_id
            AND author_id=:author_id";


    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->bindValue(':personal_rating', $personal_rating, PDO::PARAM_INT);
    $stmt->bindValue(":updated_date", date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);
    $stmt->bindValue(':author_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->rowCount();
  }


  /**
  * @param CALLED FROM CONTROLLER\PANEL.PHP
  *
  * @param content_id The content's id
  * @param user_id The user's id
  * @param season The Posted Season
  * @param episode The Posted Episode
  * Update season and episode
  *
  * @return void
  *
  */
  public static function updateSE($content_id, $user_id, $season, $episode)
  {
    if($season<1) $season = 1;
    else if($season>100) $season = 100;

    if($episode<1) $episode = 1;
    else if($episode>100) $episode = 100;

    date_default_timezone_set('Asia/Istanbul');

    $sql = "UPDATE contents
            SET season=:season, episode=:episode, inserted_date=:inserted_date
            WHERE id=:content_id
            AND author_id=:author_id";


    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->bindValue(':season', $season, PDO::PARAM_INT);
    $stmt->bindValue(':episode', $episode, PDO::PARAM_INT);
    $stmt->bindValue(":inserted_date", date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);
    $stmt->bindValue(':author_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->rowCount();
  }

  /**
  * @param CALLED FROM CONTROLLER\PANEL.PHP
  *
  * @param content_id The content's id
  * @param author_id The user's id
  *
  * Delete The Content from contents table based on content id and user id
  *
  * @return void
  *
  */
  public static function delete($content_id, $author_id)
  {
    $db = static::getDB();
    $sql = "DELETE FROM contents
            WHERE id=:content_id
            AND author_id=:author_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);
    $stmt->bindValue(':author_id', $author_id, PDO::PARAM_INT);
    $stmt->execute();
    if($stmt->rowCount()>0)
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  /**
  * @param CALLED FROM CONTROLLER\HOME.PHP
  *
  *
  * return the last week's popular show
  *
  * @return mixed
  *
  */
  public static function popularLastWeek()
  {

    $db = static::getDB();
    $sql = "SELECT COUNT(*) as total, title, tv_id, image_path, kind
    FROM contents
    WHERE inserted_date >= curdate() - INTERVAL DAYOFWEEK(curdate())+6 DAY
    AND inserted_date < curdate() - INTERVAL DAYOFWEEK(curdate())-1 DAY
    GROUP BY tv_id, kind
    ORDER BY total desc LIMIT 1";

    $stmt = $db->prepare($sql);

    $stmt->execute();

    return $results = $stmt->fetch(PDO::FETCH_ASSOC);

  }

  /**
  * @param CALLED FROM CONTROLLER\HOME.PHP
  *
  * @param $type The type of wishlist, detect watched or wishlisted content requested
  *
  * return the most watched or wishlisted content of all time
  *
  * @return mixed
  *
  */
  public static function most($type)
  {
    $db = static::getDB();
    $sql = "SELECT COUNT(*) as total, title, tv_id, image_path, kind
            FROM contents
            WHERE wishlist=:type
            GROUP BY tv_id, kind
            ORDER BY total desc LIMIT 2;";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(':type', $type, PDO::PARAM_INT);

    $stmt->execute();
    return $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

}
