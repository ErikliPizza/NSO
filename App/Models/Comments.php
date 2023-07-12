<?php

namespace App\Models;

use PDO;
/**
 * Comment model
 *
 * PHP version 7.0
 */
class Comments extends \Core\Model
{

  /**
  *
  * @param CALLED FROM CONTROLLER\POST.PHP
  *
  * get the current user and comment
  *
  */
  public static function getCurrentUser($tv_id, $user_id, $kind)
  {

    $db = static::getDB();
    $sql = "SELECT b.photo, b.nickname, b.username, a.*
            FROM contents a
            INNER JOIN users b
            ON a.author_id = b.id
            WHERE a.tv_id=:tv_id
            AND a.author_id=:user_id
            AND a.kind = :kind";

      $stmt = $db->prepare($sql);
      $stmt->bindValue(':tv_id', $tv_id, PDO::PARAM_STR);
      $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);
      $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

      $stmt->execute();

      return $results = $stmt->fetch(PDO::FETCH_ASSOC);

  }

  /**
  *
  * @param CALLED FROM CONTROLLER\POST.PHP
  *
  * get the comments
  *
  */
  public static function getComments($tv_id, $kind, $limit, $offset)
  {

    $db = static::getDB();
    $sql = "SELECT a.id as contentid, b.username, b.photo, b.nickname, a.*
            FROM contents a
            INNER JOIN users b
            ON a.author_id = b.id
            WHERE a.tv_id=:tv_id
            AND a.kind = :kind
            AND a.wishlist = 0
            AND a.comment IS NOT NULL
            AND a.personal_rating IS NOT NULL
            ORDER BY a.vote desc
            LIMIT :limit
            OFFSET :offset";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(':tv_id', $tv_id, PDO::PARAM_STR);
    $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    return $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  }

  /**
  *
  * @param CALLED FROM CONTROLLER\POST.PHP
  *
  * get the comment's total count
  *
  */
  public static function getCommentsTotal($tv_id, $kind)
  {

    $db = static::getDB();
    $sql = "SELECT COUNT(*) OVER () AS TotalRecords
            FROM contents
            WHERE tv_id=:tv_id
            AND kind = :kind
            AND wishlist = 0
            AND personal_rating IS NOT NULL
            AND comment IS NOT NULL";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(':tv_id', $tv_id, PDO::PARAM_STR);
    $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);
    $stmt->execute();

    return $results = $stmt->fetchColumn();

  }

  /**
  *
  * @param CALLED FROM CONTROLLER\POST.PHP
  *
  * edit comment
  *
  */
  public static function editComment($comment, $tv_id, $user_id, $kind)
  {
    $db = static::getDB();
    $sql = "UPDATE contents set comment=:comment";


    if($comment === 'delete')
    {
      $sql .= ", vote=0";
    }
    $sql .= "\nWHERE tv_id=:tv_id
    AND author_id=:user_id
    AND wishlist=0
    AND kind = :kind";

    $stmt = $db->prepare($sql);

    if($comment === 'delete')
    {
      $stmt->bindValue(':comment', $comment, PDO::PARAM_NULL);
    }
    else
    {
      $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
    }

    $stmt->bindValue(':tv_id', $tv_id, PDO::PARAM_STR);
    $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    $result = $stmt->fetchColumn();

    if($stmt->rowCount()>0)
    {
        if($comment === 'delete')
        {
            static::deleteVoteCharts($tv_id, $user_id, $kind);
        }
      return true;
    }
    else
    {
      return false;
    }

  }
  
  public static function deleteVoteCharts($tv_id, $user_id, $kind)
  {
    $db = static::getDB();
    $sql = "DELETE comment_votes 
                from comment_votes 
                join contents 
                on comment_votes.commentID=contents.id 
                where contents.tv_id = :tv_id
                AND contents.author_id = :user_id
                AND contents.kind = :kind;";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':tv_id', $tv_id, PDO::PARAM_STR);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':kind', $kind, PDO::PARAM_STR);
    $stmt->execute();
  }

  /**
  *
  * @param CALLED FROM CONTROLLER\POST.PHP
  *
  * check comment for the current vote
  *
  * if comment exists and request is the same, rollback the vote
  *
  */
  public static function checkComment($user_id, $content_id)
  {

    $db = static::getDB();
    $sql = "SELECT vote
            FROM comment_votes
            WHERE userID = :user_id
            AND commentID = :content_id";

    $stmt = $db->prepare($sql);

    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);

    $stmt->execute();

    return $results = $stmt->fetch(PDO::FETCH_ASSOC);

  }

  /**
  *
  * vote a comment
  *
  */
  public static function voteComment($user_id, $content_id, $vote)
  {

    $db = static::getDB();

    $sql = "INSERT INTO comment_votes (commentID, userID, vote)
            VALUES (:content_id, :user_id, :voteValue)";
    $stmt = $db->prepare($sql);

    $stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':voteValue', $vote, PDO::PARAM_INT);

    if($stmt->execute())
    {
      if($vote == 1) $voteSum = "+1";
      else $voteSum = "-1";
      $sql = "UPDATE contents set vote = vote $voteSum
              WHERE id = :content_id";
      $stmt = $db->prepare($sql);

      $stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetchColumn();
      if($stmt->rowCount()>0)
      {
        return true;
      }
      else
      {
        return false;
      }
    }
    else
    {
      return false;
    }

  }

  /**
  *
  * delete comment vote
  *
  */
  public static function deleteVote($user_id, $content_id, $vote)
  {

    $db = static::getDB();

    $sql = "DELETE FROM comment_votes
            WHERE userID = :user_id
            AND commentID = :content_id ";
    $stmt = $db->prepare($sql);

    $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);


    if($stmt->execute())
    {
      if($vote == 1) $voteSum = "-1";
      else $voteSum = "+1";
      $sql = "UPDATE contents set vote = vote $voteSum
              WHERE id = :content_id";
      $stmt = $db->prepare($sql);

      $stmt->bindValue(':content_id', $content_id, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetchColumn();
      if($stmt->rowCount()>0)
      {
        return true;
      }
      else
      {
        return false;
      }
    }
    else
    {
      return false;
    }

  }

}
