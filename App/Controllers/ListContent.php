<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\Contents;
use \App\Models\Lists;
use \App\Auth;


/**
 * List controller
 *
 * PHP version 7.0
 */
class ListContent extends Authenticated
{


  /**
   * show list page
   *
   */
    public function indexAction()
    {
      View::renderTemplate('List/index.html');
    }

    /**
     * asking from view/list/index.html ajax's post
     *
     * get the parameters and return contents based on
     *
     * @return mixed
     *
     */
    public function getListAction()
    {
      $data = (array) json_decode(file_get_contents("php://input"), true); //get the POSTed value as an array
      if(!(isset($data['page']) || isset($data['tv']) || isset($data['movie']) || isset($data['minYear']) || isset($data['maxYear']) || isset($data['rating']) || isset($data['nsoRating']) || isset($data['orderBy']) || isset($data['category']) || isset($data['comment'])))
      {
        return false;
      }
      $this->user = Auth::getUser();
      $dataComment = $data['comment'];
      $dataPage = $data['page'];
      $tv = $data['tv'];
      $movie = $data['movie'];
      $minYear = $data['minYear'];
      $maxYear = $data['maxYear'];
      $rating = $data['rating'];
      $nsoRating = $data['nsoRating'];
      $category = $data['category'];
      $orderBy = $data['orderBy'];
      $kind = "";
      if($tv == 1 && $movie == 0)
      {
        $kind = "AND kind = 'serie'";
      }
      else if ($movie == 1 && $tv == 0)
      {
        $kind = "AND kind = 'movie'";
      }
      if($orderBy == "orderRating")
      {
        $orderBy = "t.rating";
      }
      else if ($orderBy == "orderNsoRating")
      {
        $orderBy = "s.sp";
      }
      else
      {
        $orderBy = "s.pop";
      }
      $mainQuery = "SELECT s.sp as sp, s.pop as pop, t.id, t.tv_id, t.kind, t.image_path, t.title, t.publish_date, t.category, t.rating, t.overview
                    FROM contents t inner join ( select id, ROUND(AVG(personal_rating), 1) as SP, COUNT(*) as pop
                    FROM contents
                    WHERE publish_date >= $minYear
                    AND publish_date <= $maxYear
                    AND rating >= $rating
                    AND MATCH(category) AGAINST(:category IN BOOLEAN MODE)
                    $kind
                    AND wishlist = 0 GROUP BY tv_id, kind ) s
                    on ( t.id = s.id and s.SP >= $nsoRating)
                    GROUP BY tv_id, kind
                    ORDER BY $orderBy desc
                    LIMIT :limit
                    OFFSET :offset";

      $totalQuery = "SELECT COUNT(*) OVER () AS TotalRecords
                    FROM contents t inner join ( select id, ROUND(AVG(personal_rating), 1) as SP, COUNT(*) as pop
                    FROM contents
                    WHERE publish_date >= $minYear
                    AND publish_date <= $maxYear
                    AND rating >= $rating
                    AND MATCH(category) AGAINST(:category IN BOOLEAN MODE)
                    $kind
                    AND wishlist = 0 GROUP BY tv_id, kind ) s
                    on ( t.id = s.id and s.SP >= $nsoRating)
                    GROUP BY tv_id, kind";





      $page = $dataPage ?? 1;
      $records_per_page = 6;
      if(!$total_records = Lists::getTotal($totalQuery, $category))
      {
        return false;
      }

      $page = filter_var($page, FILTER_VALIDATE_INT, [
        'options' => [
          'default' => 1,
          'min_range' => 1
        ]
      ]);

      $previous = $page - 1; //1

      $total_pages = ceil($total_records / $records_per_page);

      if($page < $total_pages) //
      {
        $next = $page + 1;
      }
      else
      {
        $next = false;
      }

      $offset = $records_per_page * ($page - 1);
      if($listedContents = Lists::getListContents($mainQuery, $category, $records_per_page, $offset))
      {
        $limit = sizeof($listedContents); //detect the limitation

        for ($i=0; $i < $limit ; $i++) //list the founded movies based on limitations
        {
          if($checking = Contents::validateWithID($this->user->id, $listedContents[$i]['tv_id'], $listedContents[$i]['kind']))
          {
            if($checking[0]['wishlist'] == 0)
            {
              $listedContents[$i]['matched'] = 'watched';
            }
            else if ($checking[0]['wishlist'] == 1)
            {
              $listedContents[$i]['matched'] = 'wishlist';
            }
            $listedContents[$i]['logged_personal_rating'] = $checking[0]['personal_rating'];
          }

          if($wishlisted = Contents::getWL($listedContents[$i]["tv_id"], $listedContents[$i]["kind"]))
          {
            $listedContents[$i]["wishlistedCount"] = $wishlisted["WISHLISTED"];
          }
          if($dataComment == true)
          {
            if($comment = Lists::getCM($listedContents[$i]["tv_id"], $listedContents[$i]["kind"]))
            {
              $listedContents[$i]["comment"] = $comment["comment"];
              $listedContents[$i]["photo"] = $comment["photo"];
              $listedContents[$i]["username"] = $comment["username"];
            }
          }


          if(filter_var($listedContents[$i]['image_path'], FILTER_VALIDATE_URL))
          {
            $listedContents[$i]['default_image_path'] = true;
          }

          if(filter_var($listedContents[$i]['image_path'], FILTER_VALIDATE_URL))
          {
            $listedContents[$i]['default_image_path'] = true;
          }
        }
        echo json_encode($listedContents);
      }
    }

}
