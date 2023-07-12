<?php

namespace App\Controllers;

use \App\Models\Contents;
use \App\Models\Comments;
use \App\Auth;

/**
 * Panel controller
 *
 * PHP version 7.0
 */
class Content extends Authenticated
{

    /**
     * fetch the content data
     *
     * NORMAL fetch, TITLE_FILTER fetch, CATEGORY_FILTER fetch
     *
     * @var userid The Posted user_id, fetching only so you can allow setting the user id from outside
     *
     * @return mixed
     */
    public function getAction()
    {
      $this->user = Auth::getUser();
      $data = (array) json_decode(file_get_contents("php://input"), true); //get the POSTED value as an array
      $page = $data['page'] ?? 1;

      // setup normal fetch
      if($data['getType'] == 'get') {

        $totalSql = "SELECT COUNT(*) OVER () AS TotalRecords
        FROM contents
        WHERE author_id=:author_id
        AND kind=:kind
        AND wishlist=:wishlist";

        $total_records = Contents::getTotal($totalSql, $data['userid'], $data['kind'], $data['wishlist'], null, null);

        $getSql = "SELECT * FROM contents
                WHERE author_id=:author_id
                AND kind=:kind
                AND wishlist=:wishlist
                ORDER BY inserted_date desc
                LIMIT :limit
                OFFSET :offset;";

      }

      // setup TITLE filtered fetch
      else if ($data['getType'] == 'filterByTitle') {

        $totalSql = "SELECT COUNT(*) OVER () AS TotalRecords
        FROM contents
        WHERE author_id=:author_id
        AND title LIKE :search";

        $total_records = Contents::getTotal($totalSql, $data['userid'], null, null, $data['search'], null);

        $getSql = "SELECT * FROM contents
                WHERE author_id=:author_id
                AND title LIKE :search
                ORDER BY updated_date desc
                LIMIT :limit
                OFFSET :offset;";

      }

      // setup CATEGORY filtered fetch
      else if ($data['getType'] == 'filterByCategory') {

        $totalSql = "SELECT COUNT(*) OVER () AS TotalRecords
        FROM contents
        WHERE author_id=:author_id
        AND MATCH(category) AGAINST(:category IN BOOLEAN MODE)";

        $total_records = Contents::getTotal($totalSql, $data['userid'], null, null, null, $data['search']);

        $getSql = "SELECT * FROM contents
                WHERE author_id=:author_id
                AND MATCH(category) AGAINST(:category IN BOOLEAN MODE)
                ORDER BY updated_date desc
                LIMIT :limit
                OFFSET :offset;";

      }

      // setup SHOW SAME content fetch
      else if ($data['getType'] == 'sameContent') {

        $totalSql = "SELECT COUNT(*) OVER () AS TotalRecords
        FROM contents
        WHERE author_id in (:author_id, :second_id)
        GROUP BY tv_id, kind
        HAVING count(1)>1;";

        $total_records = Contents::getTotal($totalSql, $data['userid'], null, null, null, null, $this->user->id);

        $getSql = "SELECT * FROM contents
              WHERE author_id in (:author_id, :second_id)
              GROUP BY tv_id, kind
              HAVING count(1)>1
              ORDER BY updated_date desc
              LIMIT :limit
              OFFSET :offset;";
      }

      // setup HOME content fetch
      else if ($data['getType'] == 'homeContent') {
        if ($data['category'] == '') {
          $totalSql = "SELECT COUNT(*) OVER () AS TotalRecords
          FROM contents
          WHERE comment IS NOT NULL
          and LENGTH(comment) > 5
          and personal_rating > 0
          GROUP BY contents.tv_id, contents.kind";

          $total_records = Contents::getTotal($totalSql, null, null, null, null, null);

          $getSql = "SELECT users.username, users.photo, a.*
          FROM contents a
          INNER JOIN
          (
            SELECT tv_id, kind, MAX(vote) vote
            FROM contents
            WHERE comment IS NOT NULL AND LENGTH(comment) > 5 AND wishlist = 0 AND personal_rating > 0
            GROUP BY tv_id, kind
          )
          b ON a.tv_id = b.tv_id AND a.kind = b.kind AND a.vote = b.vote
          INNER JOIN users on users.id = a.author_id
          WHERE a.comment IS NOT NULL and LENGTH(a.comment) > 5 AND a.wishlist = 0 AND a.personal_rating > 0
          GROUP BY a.tv_id, a.kind
          ORDER BY a.inserted_date desc
          LIMIT :limit
          OFFSET :offset";
        } else {
          $totalSql = "SELECT COUNT(*) OVER () AS TotalRecords
          FROM contents
          WHERE comment IS NOT NULL
          and LENGTH(comment) > 5
          and personal_rating > 0
          AND MATCH(category) AGAINST(:category IN BOOLEAN MODE)
          GROUP BY contents.tv_id, contents.kind";

          $total_records = Contents::getTotal($totalSql, null, null, null, null, $data['category']);

          $getSql = "SELECT users.username, users.photo, a.*
          FROM contents a
          INNER JOIN
          (
            SELECT tv_id, kind, MAX(vote) vote
            FROM contents
            WHERE comment IS NOT NULL AND LENGTH(comment) > 5 AND wishlist = 0 AND personal_rating > 0 AND MATCH(category) AGAINST(:category IN BOOLEAN MODE)
            GROUP BY tv_id, kind
          )
          b ON a.tv_id = b.tv_id AND a.kind = b.kind AND a.vote = b.vote
          INNER JOIN users on users.id = a.author_id
          WHERE a.comment IS NOT NULL and LENGTH(a.comment) > 5 AND a.wishlist = 0 AND a.personal_rating > 0 AND MATCH(a.category) AGAINST(:category IN BOOLEAN MODE)
          GROUP BY a.tv_id, a.kind
          ORDER BY a.inserted_date desc
          LIMIT :limit
          OFFSET :offset";
        }
      } else {

        return false;

      }

      // pagination
      $records_per_page = 6;
      $page = filter_var($page, FILTER_VALIDATE_INT, [
        'options' => [
          'default' => 1,
          'min_range' => 1
        ]
      ]);
      $previous = $page - 1;

      $total_pages = ceil($total_records / $records_per_page);
      if($page < $total_pages)
      {
        $next = $page + 1;
      }
      else
      {
        $next = false;
      }
      $offset = $records_per_page * ($page - 1);
      // pagination

      // normal fetch
      if($data['getType'] == 'get') {

        /**
        *
        * get the contents based on request type
        *
        * @var $getSql The select query
        * @var $data['userid'] The author_id
        * @var $records_per_page The limit
        * @var $offset The offset
        * @var $data['kind'] The kind
        * @var $data['wishlist'] The wishlist
        * @var null The search
        * @var null The category
        * from the Contents Model's getContent function
        *
        * @return normal
        */
        if(!$contents = Contents::getContent($getSql, $data['userid'], $records_per_page, $offset, $data['kind'], $data['wishlist'])) {

          http_response_code(204);
          echo json_encode(["error" => "There is no content."]);
          return false;

        }
      } else if ($data['getType'] == 'filterByTitle') { // search fetch

        /**
        *
        * get the contents based on request type
        *
        * @var $getSql The select query
        * @var $data['userid'] The author_id
        * @var $records_per_page The limit
        * @var $offset The offset
        * @var null The kind
        * @var null The wishlist
        * @var $data['search'] The search
        * @var null The category
        * from the Contents Model's getContent function
        *
        * @return TITLE filtered data
        */
        if(!$contents = Contents::getContent($getSql, $data['userid'], $records_per_page, $offset, null, null, $data['search'])) {

          http_response_code(204);
          echo json_encode(["error" => "There is no content."]);
          return false;

        }
      } else if ($data['getType'] == 'filterByCategory') { // category filter fetch

        /**
        *
        * get the contents based on request type
        *
        * @var $getSql The select query
        * @var $data['userid'] The author_id
        * @var $records_per_page The limit
        * @var $offset The offset
        * @var null The kind
        * @var null The wishlist
        * @var null The search
        * @var $data['search'] The category
        * from the Contents Model's getContent function*
        * @return CATEGORY filtered data
        */
        if(!$contents = Contents::getContent($getSql, $data['userid'], $records_per_page, $offset, null, null, null, $data['search'])) {

          http_response_code(204);
          echo json_encode(["error" => "There is no content."]);
          return false;

        }
      } else if ($data['getType'] == 'sameContent') {

        /**
        *
        * get the contents based on request type
        *
        * @var $getSql The select query
        * @var $data['userid'] The author_id
        * @var $records_per_page The limit
        * @var $offset The offset
        * @var null The kind
        * @var null The wishlist
        * @var null The search
        * @var $data['search'] The category
        * from the Contents Model's getContent function*
        * @return CATEGORY filtered data
        */
        if(!$contents = Contents::getContent($getSql, $data['userid'], $records_per_page, $offset, null, null, null, null, $this->user->id)) {

          http_response_code(204);
          echo json_encode(["error" => "There is no content."]);
          return false;

        }

      } else if ($data['getType'] == 'homeContent') {

        /**
        *
        * get the contents based on request type
        *
        * @var $getSql The select query
        * @var $data['userid'] The author_id
        * @var $records_per_page The limit
        * @var $offset The offset
        * @var null The kind
        * @var null The wishlist
        * @var null The search
        * @var $data['search'] The category
        * from the Contents Model's getContent function*
        * @return CATEGORY filtered data
        */
        if ($data['category'] == '') {

          if(!$contents = Contents::getContent($getSql, null, $records_per_page, $offset)) {

            http_response_code(204);
            echo json_encode(["error" => "There is no content."]);
            return false;

          }

        } else {

          if(!$contents = Contents::getContent($getSql, null, $records_per_page, $offset, null, null, null, $data['category'], null,)) {

            http_response_code(204);
            echo json_encode(["error" => "There is no content."]);
            return false;

          }

        }
      } else {

        return false;

      }

      // setup the content's array
      if($data['social'] == true && !empty($this->user && $data['getType'] != 'homeContent')) {
        $contents[0]['totalRecord'] = Contents::getTotal("SELECT COUNT(*) OVER () AS TotalRecords
        FROM contents
        WHERE author_id in (:author_id, :second_id)
        GROUP BY tv_id, kind
        HAVING count(1)>1;", $data['userid'], null, null, null, null, $this->user->id);
      }
      foreach ($contents as $key => $value) {

        if($data['social'] == true && !empty($this->user))
        {
          if($checking = Contents::validateWithID($this->user->id, $contents[$key]['tv_id'], $contents[$key]['kind']))
          {
            if($checking[0]['wishlist'] == 0)
            {
              $contents[$key]['matched'] = 'watched';
            }
            else if ($checking[0]['wishlist'] == 1)
            {
              $contents[$key]['matched'] = 'wishlist';
            }
            $contents[$key]['logged_personal_rating'] = $checking[0]['personal_rating'];
          }
        }
        if ($data['getType'] == 'homeContent')
        {
          if($voteStatus = Comments::checkComment($this->user->id, $contents[$key]['id']))
          {
            if($voteStatus['vote'] == 1)
            $contents[$key]['up'] = 1;
            else if ($voteStatus['vote'] == 0)
            $contents[$key]['down'] = 1;
          }
        }
        if(empty($this->user))
        {
          $contents[$key]['notlogged'] = true;
        }
        $sp = Contents::getSP($value['tv_id'], $value['kind']);
        $contents[$key]['sp'] = $sp['SP'];
        $contents[$key]['pop'] = $sp['POP'];
        $contents[$key]['wishlistedCount'] = Contents::getWL($value['tv_id'], $value['kind'])['WISHLISTED'];

        // date analyzer
        $originalStr = new \DateTime("now", new \DateTimeZone('UTC'));
        $timezoneNameStr = timezone_name_from_abbr("", 3*3600, false);
        $nowStr = $originalStr->setTimezone(new \DateTimezone($timezoneNameStr));
        $nowResult = $nowStr->format('Y-m-d H:i:s');
        $nowResult = strtotime($nowResult);
        $contentDate = strtotime($value['inserted_date']);
        $datediff = $nowResult - $contentDate;
        $pastedDay = round($datediff / (60 * 60 * 24));
        if($pastedDay < 7)
        {
          $level = 1;
          $original = new \DateTime("now", new \DateTimeZone('UTC'));
          $timezoneName = timezone_name_from_abbr("", 3*3600, false);
          $now = $original->setTimezone(new \DateTimezone($timezoneName));

          $ago = new \DateTime($value["inserted_date"]);
          $diff = $now->diff($ago);

          $diff->w = floor($diff->d / 7);
          $diff->d -= $diff->w * 7;

          $string = array(
              'y' => 'year',
              'm' => 'month',
              'w' => 'week',
              'd' => 'day',
              'h' => 'hour',
              'i' => 'min',
              's' => 'sec',
          );
          foreach ($string as $k => &$v)
          {
            if ($diff->$k)
            {
              $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            }
            else
            {
              unset($string[$k]);
            }
          }
          $string = array_slice($string, 0, $level);
          $contents[$key]["inserted_date"] = $string ? implode(', ', $string)  : 'just now';
        }
        else
        {
          $contents[$key]["inserted_date"] = substr($value["inserted_date"], 2, -9);
        }
        if(filter_var($value['image_path'], FILTER_VALIDATE_URL))
        {
          $contents[$key]['default_image_path'] = true;
        }
      }
      // setup the content's array

      http_response_code(200); // h200 OK
      echo json_encode($contents); // send it

    }


  }
