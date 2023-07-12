<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\socialUser;
use \App\Models\Contents;
use \App\tmdbSingleContent;
use \App\Auth;

/**
 * Panel controller
 *
 * PHP version 7.0
 */
class Social extends Authenticated
{


  /**
   * Show Profile Page
   *
   * get user data as properties from the user model via Auth's getUser function
   *
   * get favMovie information from the tmdb api via tmdbSingleContent
   *
   * Only the top of the page's data sent from here
   *
   * @var user contains all user data, beside, profile page uses these datas
   * @var user->Photo
   * @var user->Bio
   * @var user->Instagram
   * @var user->WebUrl
   *
   * @var favMovie contains all tmdb api variables, beside, the profile page only uses this data
   * @var favMovie->BackdropPath
   *
   * @return void
   */
  public function indexAction()
  {
    if (!preg_match('/^[A-Za-z][A-Za-z0-9]{5,14}$/', $this->route_params['username'])) {

      $this->redirect('/explore');

    }

    if ( ! $this->user = socialUser::findByUsername($this->route_params['username']) ) {

      View::renderTemplate('/404.html');
      return false;

    }

    if ($this->authorizeduser = Auth::getUser()) {
      if ($this->route_params['username'] == $this->authorizeduser->username) {

        $this->redirect('/');

      }
      // get same content count
      $sameContentCount = Contents::getTotal("SELECT COUNT(*) OVER () AS TotalRecords
      FROM contents
      WHERE author_id in (:author_id, :second_id)
      GROUP BY tv_id, kind
      HAVING count(1)>1;", $this->user->id, null, null, null, null, $this->authorizeduser->id);
    }

    // get the fav movie information
    if (property_exists($this->user, 'favMovie'))  {
      $favMovie = tmdbSingleContent::getContentByApi($this->user->favMovie, 'movie');
    }

      // get the instagram username
      if($this->user->instagram != null)
      {
      if (preg_match ('/(?:(?:http|https):\/\/)?(?:www\.)?(?:instagram\.com|instagr\.am)\/([A-Za-z0-9-_\.]+)/im',
                       $this->user->instagram,
                       $matches)) {
        $this->user->instagram = $matches[1];
      }
      }

      // get the web url
      if($this->user->webUrl != null)
      {
      $this->user->webUrl = preg_replace('#^https?://#', '', $this->user->webUrl);
      if(preg_match("/\//", $this->user->webUrl)){
          $this->user->webUrl = substr($this->user->webUrl, 0, strpos($this->user->webUrl, "/"));
      }
      }

    // get movie count
    $movieCount = Contents::getContentCount($this->user->id, 'movie');

    // get serie count
    $serieCount = Contents::getContentCount($this->user->id, 'serie');



    // return the view
    View::renderTemplate('Social/index.html', [
      'favMovie' => $favMovie,
      'user' => $this->user,
      'movieCount' => $movieCount,
      'serieCount' => $serieCount,
      'sameContentCount' => $sameContentCount
    ]);

  }
}
