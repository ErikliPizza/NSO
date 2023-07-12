var input = document.getElementById("searchIn");
input.addEventListener("keypress", function(event) {
  if (event.key === "Enter") {
    event.preventDefault();
    document.getElementById("searchInBtn").click();
  }
});
$(document).ready(function(){

var is_social = location.pathname == "/";
is_social = !is_social;
$("#searchRight").hide();
$("#searchRightButton").show();
$("#lookLeftButton").hide();
$("#lookLeft").show();

$("[wishlist-movie-container]").hide();
$("[wishlist-serie-container]").hide()
$("[watched-movie-container]").hide();
var scrollTimer;
var scrollType = 'watchedSerie';
var watchedSeriePage = 1;
var watchedMoviePage = 1;
var wishlistSeriePage = 1;
var wishlistMoviePage = 1;
var sameContentPage = 1;
var searchPage = 1;
var searching = false;
var saming = false;
var wishlist = false;
callWatchedSerie();

// collapse for bio //
$('#firstBio').on('click', function()
{
  $('#headingOne').hide(500);
});
$('#secondBio').on('click', function()
{
  $('#headingOne').show(500);
});
// collapse for bio //

// same content clicked
$('#showSameContents').on('click', function()
{
  searching = false;
  saming = true;
  $("[content-container]").hide("drop", { direction: "down" }, "fast", function()
  {
    $('#lookLeft').hide("fold", "fast", function()
    {
      $('#lookLeftButton').show("fold", "fast");
    });

    $('#rightItems').hide("fold", "fast", function()
    {
      $('#rightSpan').show("fold", "fast")
    });

    $('#leftItems').hide("fold", "fast", function()
    {
      $('#leftSpan').show("fold", "fast")
    });

    $("[search-content-container]").show("drop", { direction: "down" }, "fast");
    $("[watched-movie-container]").empty();
    $("[watched-serie-container]").empty();
    $("[wishlist-movie-container]").empty();
    $("[wishlist-serie-container]").empty();
    sameContentPage = 1;
    watchedMoviePage = 1;
    watchedSeriePage = 1;
    wishlistMoviePage = 1;
    wishlistSeriePage = 1;
    $("[search-content-container]").empty();
    callSame();
    scrollTimer = setTimeout(function ()
    {
      document.getElementById("search-content-container-scroll").scrollIntoView();
    }, 100);
  });
});
// same content clicked

// show overviews
$(document).on('click', '.overview',function()
{
  if($(this).hasClass("text-truncate"))
  {
    $(this).removeClass("text-truncate text-secondary");
  }
  else
  {
    $(this).addClass("text-truncate text-secondary");
  }
});
// show overviews

// collapse for searching and showing contents //
$('#lookLeftButton').on('click', function()
{
  $("[search-content-container]").hide("drop", { direction: "down" }, "fast", function()
  {
    searching = false;
    saming = false;
    $("[content-container]").show("drop", { direction: "down" }, "fast");
    $("[search-content-container]").empty();
    switch(scrollType)
    {
      case 'watchedSerie':
      callWatchedSerie();
      break;

      case 'watchedMovie':
      callWatchedMovie();
      break;

      case 'wishlistSerie':
      callWishlistSerie();
      break;

      case 'wishlistMovie':
      callWishlistMovie();
      break;
    }
    $('#searchRight').hide("fold", "fast", function()
    {
      $('#searchRightButton').show("fold", "fast", function()
      {
        $('#lookLeftButton').hide("fold", "fast", function()
        {
          $('#lookLeft').show("fold", "fast");
        });
      });
    });
  });
});
$('#searchRightButton').on('click', function()
{
  saming = false;
  searching = true;
  $("[content-container]").hide("drop", { direction: "down" }, "fast", function()
  {
    $("[search-content-container]").show("drop", { direction: "down" }, "fast");
    $("[watched-movie-container]").empty();
    $("[watched-serie-container]").empty();
    $("[wishlist-movie-container]").empty();
    $("[wishlist-serie-container]").empty();
    sameContentPage = 1;
    watchedMoviePage = 1;
    watchedSeriePage = 1;
    wishlistMoviePage = 1;
    wishlistSeriePage = 1;
    $("[search-content-container]").empty();
    $('#lookLeft').hide("fold", "fast", function()
    {
      $('#lookLeftButton').show("fold", "fast", function()
      {
        $('#searchRightButton').hide("fold", "fast", function()
        {
          $('#searchRight').show("fold", "fast");
        });
      });
    });
  });
});
// collapse for searching and showing contents //

// search button for user's content //
$( "#searchInBtn").click(function()
{
  searchPage = 1;
  let searchInField = $('#searchIn').val();
  callSearch(searchInField, true);
  clearTimeout(scrollTimer);
  scrollTimer = setTimeout(function ()
  {
    document.getElementById("search-content-container-scroll").scrollIntoView();
  }, 100);
});
// search button for user's content //


// personal rating //
let rateChangeTimer; // set the timer
$(document).on('input', 'select[name="personal_rating"]', function (event)
{
  const rating = $(this).val(); // rating value
  const id = $(this).attr('data-id'); // get the data id
  clearTimeout(rateChangeTimer); // clear the timer if changed again
  rateChangeTimer = setTimeout(function ()
  {
    $.ajax({
        type: "POST", // post into l/profile/update
        url: "/panel/update",
        data: {
            personal_rating: rating, // the rating
            content_id: id // the content id
        },
        success: function(result, textStatus, jqXHR)
        {
          if(jqXHR.status === 200) // if successfully modified
          {
            console.log("successfully updated");
          }
        },
        error: function(jqXHR, textStatus) // other errors like 405 code
        {
          console.log(jqXHR.status);
        }
    });
  }, 500);
});
// personal rating //

// season change //
let seasonChangeTimer; // set the timer
$(document).on('input', 'input[name="season"], input[name="episode"]', function (event)
{
  const id = $(this).attr('data-id'); // get the data id
  const season = $('#season-'+id).val(); // get the season
  const episode = $('#episode-'+id).val(); // get the episode
  clearTimeout(seasonChangeTimer);
  seasonChangeTimer = setTimeout(function ()
  {
    $.ajax({
        type: "POST", // post into l/profile/update
        url: "/panel/update",
        async: true,
        data: {
            season: season, // season info
            episode: episode, // episode info
            content_id: id // content's id
        },
        success: function(result, textStatus, jqXHR)
        {
          if(jqXHR.status === 200) // if successfully modified
          {
            console.log("successfully season&episode changed");
          }
        },
        error: function(jqXHR, textStatus) // other errors like 405 code
        {
          console.log(jqXHR.status);
        }
    });
  }, 300);
});
// season change //

// scrollSearch
$(window).scroll(function()
{
  var divTop = $('#baseContainer').offset().top,
      divHeight = $('#baseContainer').outerHeight(),
      wHeight = $(window).height(),
      windowScrTp = $(this).scrollTop();
  if(windowScrTp > (divTop+divHeight-wHeight-100))
  {
    if(searching == false && saming == false)
    {
      if(scrollType == 'watchedSerie')
      {
        callWatchedSerie();
      }
      else if(scrollType == 'watchedMovie')
      {
        callWatchedMovie();
      }
      else if(scrollType == 'wishlistSerie')
      {
        callWishlistSerie();
      }
      else if(scrollType == 'wishlistMovie')
      {
        callWishlistMovie();
      }
    }
    else if (searching == true)
    {
      let searchInField = $('#searchIn').val();
      callSearch(searchInField, false);
    }
    else if (saming == true)
    {
      callSame();
    }
  }
});
// scrollSearch


// show the wishlisted contents
$("#wishlistBtn").click(function()
{
wishlistMoviePage = 1;
wishlistSeriePage = 1;
$("[wishlist-movie-container]").empty();
$("[wishlist-serie-container]").empty();

$("#wishlistBtn").addClass("d-none");

wishlist = true;

if ($("#movieRad").is(":checked"))
{
  $("[watched-movie-container]").hide("drop", { direction: "down" }, "fast", function()
  {
    scrollType = 'wishlistMovie';
    $("[wishlist-movie-container]").show("drop", { direction: "up" }, "fast");
    callWishlistMovie();
    if(wishlistMoviePage > 1)
    {
      document.getElementById("content-container-scroll").scrollIntoView();
    }
  });
}
else if ($("#serieRad").is(":checked"))
{
  $("[watched-serie-container]").hide("drop", { direction: "down" }, "fast", function()
  {
    scrollType = 'wishlistSerie';
    $("[wishlist-serie-container]").show("drop", { direction: "up" }, "fast");
    callWishlistSerie();
    if(wishlistSeriePage > 1)
    {
      document.getElementById("content-container-scroll").scrollIntoView();
    }
  });
}
$("#watchedBtn").removeClass("d-none");

});
// show the wishlisted contents

// show the watched contents
$("#watchedBtn").click(function()
{
watchedMoviePage = 1;
watchedSeriePage = 1;
$("[watched-movie-container]").empty();
$("[watched-serie-container]").empty();

$("#watchedBtn").addClass("d-none");

wishlist = false;

if ($("#movieRad").is(":checked"))
{
  $("[wishlist-movie-container]").hide("drop", { direction: "down" }, "fast", function()
  {
    scrollType = 'watchedMovie';
    $("[watched-movie-container]").show("drop", { direction: "down" }, "fast");
    callWatchedMovie();
    if(watchedMoviePage > 1)
    {
      document.getElementById("content-container-scroll").scrollIntoView();
    }
  });
}
else if ($("#serieRad").is(":checked"))
{
  $("[wishlist-serie-container]").hide("drop", { direction: "down" }, "fast", function()
  {
    scrollType = 'watchedSerie';
    $("[watched-serie-container]").show("drop", { direction: "down" }, "fast");
    callWatchedSerie();
    if(watchedSeriePage > 1)
    {
      document.getElementById("content-container-scroll").scrollIntoView();
    }
  });
}
$("#wishlistBtn").removeClass("d-none");

});
// show the watched contents

// radio input based show contents
$('input[type=radio][name=kind]').click(function()
{
if (this.value == 'movie')
{
  if(wishlist == false)
  {
    scrollType = 'watchedMovie';
    $("[watched-serie-container]").hide("drop", { direction: "down" }, "fast", function()
    {
      $("[watched-movie-container]").show("drop", { direction: "down" }, "fast");
      callWatchedMovie();
      if(watchedMoviePage > 1)
      {
        document.getElementById("content-container-scroll").scrollIntoView();
      }
    });
  }
  else
  {
    scrollType = 'wishlistMovie';
    $("[wishlist-serie-container]").hide("drop", { direction: "down" }, "fast", function()
    {
      $("[wishlist-movie-container]").show("drop", { direction: "down" }, "fast");
      callWishlistMovie();
      if(wishlistMoviePage > 1)
      {
        document.getElementById("content-container-scroll").scrollIntoView();
      }
    });

  }
}
else if (this.value == 'serie')
{
  if(wishlist == false)
  {
    scrollType = 'watchedSerie';
    $("[watched-movie-container]").hide("drop", { direction: "down" }, "fast", function()
    {
      $("[watched-serie-container]").show("drop", { direction: "down" }, "fast");
      callWatchedSerie();
      if(watchedSeriePage > 1)
      {
        document.getElementById("content-container-scroll").scrollIntoView();
      }
    });

  }
  else
  {
    scrollType = 'wishlistSerie';
    $("[wishlist-movie-container]").hide("drop", { direction: "down" }, "fast", function()
    {
      $("[wishlist-serie-container]").show("drop", { direction: "down" }, "fast");
      callWishlistSerie();
      if(wishlistSeriePage > 1)
      {
        document.getElementById("content-container-scroll").scrollIntoView();
      }
    });
  }
}
});
// radio input based show contents

// appenders
function callWatchedSerie()
{
  if(searching == true || saming == true) // if searching true, return false
  {
    return false;
  }
$.ajax(
  {
    url:"/content/get", // post into l/profile/update
    type:"POST",
    dataType:"json",
    data: JSON.stringify({
      'page': watchedSeriePage, // pagination info
      'userid': userid,
      'kind': 'serie',
      'wishlist': "0",
      'social': is_social,
      'getType': 'get'
    }),
    success: function(res, textStatus, jqXHR)
    {
      if(jqXHR.status === 200) // response OK
      {
        let successfullyAppended = false; // check if it's appended
        $.each(res, function(index, value) // loop
        {
          if(!$('#content-'+value.id).length) // check if there is any matched content id with this id, if there is no match you can append this content
          {
            if(is_social === false)
            {
              appendWatched(value, '[watched-serie-container]', true);

            }
            else
            {
              appendSocial(value, '[watched-serie-container]');
            }
            successfullyAppended = true; // you appended NEW content successfully
          }
          else
          {
            successfullyAppended = false; // if there is matched content, make it false and return false
            return false;
          }

        });
        if(successfullyAppended == true) // if appended successfully, you can increase the pagination for watched series
        {
          if(watchedSeriePage == 1)
          {
            document.getElementById("content-container-scroll").scrollIntoView();
          }
          watchedSeriePage++;
        }
      }
    },
    error: function(jqXHR, textStatus) // other errors
    {
      console.log(jqXHR.status);
    }
 });
}

function callWatchedMovie()
{
  if(searching == true || saming == true) // if searching true, return false
  {
    return false;
  }
$.ajax(
  {
    url:"/content/get", // post into l/profile/update
    type:"POST",
    dataType:"json",
    data: JSON.stringify({
      'page': watchedMoviePage ,// pagination info
      'userid': userid,
      'kind': 'movie',
      'wishlist': "0",
      'social': is_social,
      'getType': 'get',
    }),
    success: function(res, textStatus, jqXHR)
    {
      if(jqXHR.status === 200) // response OK
      {
        let successfullyAppended = false; // check if it's appended
        $.each(res, function( index, value ) // loop
        {
          if(!$('#content-'+value.id).length) // check if there is any matched content id with this id, if there is no match you can append this content
          {
            if(is_social === false)
            {
              appendWatched(value, '[watched-movie-container]', false);

            }
            else
            {
              appendSocial(value, '[watched-movie-container]');
            }
            successfullyAppended = true; // you appended NEW content successfully
          }
          else
          {
            successfullyAppended = false; // if there is matched content, make it false and return false
            return false;
          }

        });
        if(successfullyAppended == true) // if appended successfully, you can increase the pagination for watched movies
        {
          if(watchedMoviePage == 1)
          {
            document.getElementById("content-container-scroll").scrollIntoView();
          }
          watchedMoviePage++;
        }
      }
    },
    error: function(jqXHR, textStatus) // other errors
    {
      console.log(jqXHR.status);
    }
 });
}

function callWishlistSerie()
{
  if(searching == true || saming == true) // if searching true, return false
  {
    return false;
  }
$.ajax(
  {
    url:"/content/get",
    type:"POST",
    dataType:"json",
    data: JSON.stringify({
      'page': wishlistSeriePage,
      'userid': userid,
      'kind': 'serie',
      'wishlist': "1",
      'social': is_social,
      'getType': 'get',
    }),
    success: function(res, textStatus, jqXHR)
    {
      if(jqXHR.status === 200) // response OK
      {
        let successfullyAppended = false; // check if it's appended
        $.each(res, function( index, value ) // loop
        {
          if(!$('#content-'+value.id).length) // check if there is any matched content id with this id, if there is no match you can append this content
          {
            if(is_social === false)
            {
              appendWishlist(value, '[wishlist-serie-container]');
            }
            else
            {
              appendSocial(value, '[wishlist-serie-container]');
            }
            successfullyAppended = true; // you appended NEW content successfully
          }
          else
          {
            successfullyAppended = false; // if there is matched content, make it false and return false
            return false;
          }

        });
        if(successfullyAppended == true) // if appended successfully, you can increase the pagination for watched movies
        {
          if(wishlistSeriePage == 1)
          {
            document.getElementById("content-container-scroll").scrollIntoView();
          }
          wishlistSeriePage++;
        }
      }
    },
    error: function(jqXHR, textStatus) // other errors
    {
      console.log(jqXHR.status);
    }
 });
}

function callWishlistMovie()
{
  if(searching == true || saming == true) // if searching true, return false
  {
    return false;
  }
$.ajax(
  {
    url:"/content/get",
    type:"POST",
    dataType:"json",
    data: JSON.stringify({
      'page': wishlistMoviePage,
      'userid': userid,
      'kind': 'movie',
      'wishlist': "1",
      'social': is_social,
      'getType': 'get',
    }),
    success: function(res, textStatus, jqXHR)
    {
      if(jqXHR.status === 200) // response OK
      {
        let successfullyAppended = false; // check if it's appended
        $.each(res, function( index, value ) // loop
        {
          if(!$('#content-'+value.id).length) // check if there is any matched content id with this id, if there is no match you can append this content
          {
            if(is_social === false)
            {
              appendWishlist(value, '[wishlist-movie-container]');

            }
            else
            {
              appendSocial(value, '[wishlist-movie-container]');
            }
            successfullyAppended = true; // you appended NEW content successfully
          }
          else
          {
            successfullyAppended = false; // if there is matched content, make it false and return false
            return false;
          }

        });
        if(successfullyAppended == true) // if appended successfully, you can increase the pagination for watched movies
        {
          if(wishlistMoviePage == 1)
          {
            document.getElementById("content-container-scroll").scrollIntoView();
          }
          wishlistMoviePage++;
        }
      }
    },
    error: function(jqXHR, textStatus) // other errors
    {
      console.log(jqXHR.status);
    }
 });
}

var searchType = 'filterByTitle';
function callSearch(param, isnew)
{
if(param == '') return false;
if(isnew == true)
{
  $('[search-content-container]').empty();
  searchPage = 1;
  if($('#searchInKindSelect').find(":selected").val() == 'name')
  {
    searchType = 'filterByTitle';
  }
  else if($('#searchInKindSelect').find(":selected").val() == 'category')
  {
    searchType = 'filterByCategory';
  }
}
$.ajax(
  {
    url:"/content/get",
    type:"POST",
    dataType:"json",
    data: JSON.stringify({
      'page': searchPage,
      'search': param,
      'userid': userid,
      'social': is_social,
      'getType': searchType,
    }),
    success: function(res)
    {
      let successfullyAppended = false;
      $.each(res, function( index, value )
      {
        if(!$('[search-content-container] #content-'+value.id).length)
        {
          if(value.wishlist == 1)
          {
            if(is_social === false)
            {
              appendWishlist(value, '[search-content-container]');
            }
            else
            {
              appendSocial(value, '[search-content-container]');
            }
          }
          else if(value.wishlist == 0)
          {
            if(is_social === false)
            {
              if(value.kind == 'movie') appendWatched(value, '[search-content-container]', false);
              else if (value.kind == 'serie') appendWatched(value, '[search-content-container]', true);
            }
            else
            {
              if(value.kind == 'movie') appendSocial(value, '[search-content-container]');
              else if (value.kind == 'serie') appendSocial(value, '[search-content-container]');
            }

          }
          successfullyAppended = true;

        }
        else
        {
          successfullyAppended = false;
          return false;
        }

      });
      if(successfullyAppended == true)
      {
        searchPage++;
      }
    },
    error: function(jqXHR, textStatus) // other errors
    {
      console.log(jqXHR.status);
    }
 });
}

function callSame()
{
  $.ajax(
    {
      url:"/content/get",
      type:"POST",
      dataType:"json",
      data: JSON.stringify({
        'page': sameContentPage,
        'userid': userid,
        'social': is_social,
        'getType': 'sameContent',
      }),
      success: function(res)
      {
        $('#totalSameRecord').html(res[0].totalRecord);
        let successfullyAppended = false;
        $.each(res, function( index, value )
        {

          if(!$('[search-content-container] #content-'+value.id).length)
          {
            appendSocial(value, '[search-content-container]', true);
            successfullyAppended = true;

          }
          else
          {
            successfullyAppended = false;
            return false;
          }

        });
        if(successfullyAppended == true)
        {
          sameContentPage++;
        }
      },
      error: function(jqXHR, textStatus) // other errors
      {
        console.log(jqXHR.status);
      }
   });
}
// appenders

});


function appendWishlist(value, container)
{
  if(value.default_image_path == true) // check if there is default_image_path key in this index
  {
    value.image_path = '/uploads/profiles/default.jpg'; // if it is, sign it as default image_path
  }
  else
  {
    value.image_path = 'https://image.tmdb.org/t/p/w500'+value.image_path; // if it's not, it's okay there is already an image path, set it up
  }
  $(container).append
  ('\
  <div class="col-md-6 col-lg-4 pb-3" id="content-'+value.id+'">\
    <div class="card profile-custom bg-white border-white border-0" style="height: 450px">\
      <a class="text-decoration-none" href="/content/'+value.kind+'/'+value.tv_id+'"><div class="profile-custom-img" style="background-image: url('+value.image_path+');"></div></a>\
      <div class="card-body" style="overflow-y: auto">\
      <h4 class="mt-2 col-12 card-title text-truncate text-capitalize">'+value.title+'</h4>\
      <hr style="width:50%; margin: auto;">\
      <span style="font-style:italic; font-size:11px;" class="text-center"><small class="text-muted">'+value.category+'</small></span>\
      <p class="card-text overview text-truncate text-secondary" style="cursor: alias;">'+value.overview+'</p>\
      </div>\
      <div class="card-footer d-flex justify-content-center" style="background: inherit; border-color: inherit;">\
      <div class="input-group input-group-sm">\
        <i class="fa-solid fa-star ms-2 text-warning input-group-text"></i>\
        <select class="form-select fw-bold" name="watchedButton-'+value.id+'" class="btn btn-success" value="'+value.tv_id+'-'+value.kind+'" id="watchedButton" data-content-id="'+value.id+'">\
          <option selected disabled>Rate This Content</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-1" '+(value.personal_rating == 1 ? 'selected':'')+'>1</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-2" '+(value.personal_rating == 2 ? 'selected':'')+'>2</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-3" '+(value.personal_rating == 3 ? 'selected':'')+'>3</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-4" '+(value.personal_rating == 4 ? 'selected':'')+'>4</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-5" '+(value.personal_rating == 5 ? 'selected':'')+'>5</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-6" '+(value.personal_rating == 6 ? 'selected':'')+'>6</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-7" '+(value.personal_rating == 7 ? 'selected':'')+'>7</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-8" '+(value.personal_rating == 8 ? 'selected':'')+'>8</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-9" '+(value.personal_rating == 9 ? 'selected':'')+'>9</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-10" '+(value.personal_rating == 10 ? 'selected':'')+'>10</option>\
        </select>\
      </div>\
          <button type="button" name="button" class="btn btn-sm btn-danger ms-1" value="'+value.id+'" id="deleteButton">Delete</button>\
      </div>\
      <ul class="list-group">\
      <li class="flip-front front-'+value.id+'" value="'+value.id+'">\
      <div class="d-flex justify-content-between p-3">\
        <i class="fa-solid fa-pen-to-square"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;"> '+value.inserted_date+'</small></span></i>\
        <i class="fa-regular fa-moon text-info"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;">'+value.sp+'</small>/<small class="text-secondary" style="font-size:10px; position: relative; bottom: 2px;">'+value.pop+'</small></span></i>\
      </div>\
      </li>\
      </ul>\
      \
      <ul class="list-group">\
      <li class="flip-back back-'+value.id+'" style="display: none;" value="'+value.id+'">\
      <div class="d-flex justify-content-between p-3">\
      <i class="fa-solid fa-calendar-day"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;"> '+value.publish_date+'</small></span></i>\
      <i class="fa-brands fa-mdb text-info"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: 1px; font-size:15px; position: relative; bottom: 2px;"> '+value.rating+'</small></span></i>\
      </div>\
      </li>\
      </ul>\
  </div>\
  ');
}

function appendWatched(value, container, extra)
{
  let cardBody="";
  let isComment = false;
  if(value.default_image_path == true) // check if there is default_image_path key in this index
  {
    value.image_path = '/uploads/profiles/default.jpg'; // if it is, sign it as default image_path
  }
  else
  {
    value.image_path = 'https://image.tmdb.org/t/p/w500'+value.image_path; // if it's not, it's okay there is already an image path, set it up
  }
  if(value.comment != null)
  {
    isComment = true;
    cardBody = value.comment;
  }
  else
  {
    isComment = false;
    cardBody = value.overview;
  }
  $(container).append
  ('\
  <div class="col-md-6 col-lg-4 pb-3" id="content-'+value.id+'">\
    <div class="card profile-custom bg-white border-white border-0" style="height: 450px">\
      <a class="text-decoration-none" href="/content/'+value.kind+'/'+value.tv_id+'"><div class="profile-custom-img" style="background-image: url('+value.image_path+');"></div></a>\
      <div class="card-body" style="overflow-y: auto">\
      <h4 class="mt-2 col-12 card-title text-truncate text-capitalize">'+value.title+'</h4>\
      <hr style="width:50%; margin: auto;">\
      <span style="font-style:italic; font-size:11px;" class="text-center"><small class="text-muted">'+value.category+'</small></span>\
      <p class="card-text overview text-truncate" style="cursor: pointer;">'+(isComment == true ? "<i class=\"fa-regular fa-comment text-success\"></i> ":"")+cardBody+'</p>\
      </div>\
      <div class="card-footer d-flex justify-content-center" style="background: inherit; border-color: inherit;">\
          <button type="button" name="button" class="btn btn-dark" value="'+value.tv_id+'-'+value.kind+'" id="wishlistButton" data-content-id="'+value.id+'">Wishlist</button>\
          <button type="button" name="button" class="btn btn-outline-dark ms-1" value="'+value.id+'" id="deleteButton"><i class="fa-regular fa-trash-can fa-lg"></i></button>\
          '+(extra === true ? '<input type="number" class="form-control ms-1" min="1" max="50" id="season-'+value.id+'" data-id="'+value.id+'" name="season" value="'+value.season+'"/> <input type="number" class="form-control ms-1" min="1" max="50" id="episode-'+value.id+'" data-id="'+value.id+'" name="episode" value="'+value.episode+'"/>': '')+'\
      </div>\
      <ul class="list-group">\
      <li class="front-'+value.id+'">\
      <div class="d-flex justify-content-between p-3">\
        <i class="fa-solid fa-pen-to-square flip-front" value="'+value.id+'"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;"> '+value.inserted_date+'</small></span></i>\
        <div class="d-flex justify-content-around col-lg-4">\
          <div class="input-group input-group-sm ms-2">\
            <i class="fa-solid fa-star text-warning input-group-text"></i>\
            <select class="form-select" id="personal_rating-'+value.id+'" data-id='+value.id+' name="personal_rating">\
              <option value="1" '+(value.personal_rating == 1 ? 'selected':'')+'>1</option>\
              <option value="2" '+(value.personal_rating == 2 ? 'selected':'')+'>2</option>\
              <option value="3" '+(value.personal_rating == 3 ? 'selected':'')+'>3</option>\
              <option value="4" '+(value.personal_rating == 4 ? 'selected':'')+'>4</option>\
              <option value="5" '+(value.personal_rating == 5 ? 'selected':'')+'>5</option>\
              <option value="6" '+(value.personal_rating == 6 ? 'selected':'')+'>6</option>\
              <option value="7" '+(value.personal_rating == 7 ? 'selected':'')+'>7</option>\
              <option value="8" '+(value.personal_rating == 8 ? 'selected':'')+'>8</option>\
              <option value="9" '+(value.personal_rating == 9 ? 'selected':'')+'>9</option>\
              <option value="10" '+(value.personal_rating == 10 ? 'selected':'')+'>10</option>\
            </select>\
          </div>\
        </div>\
      </div>\
      </li>\
      </ul>\
      \
      <ul class="list-group">\
      <li class="flip-back back-'+value.id+'" style="display: none;" value="'+value.id+'">\
      <div class="d-flex justify-content-between p-3">\
      <i class="fa-regular fa-moon text-info"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;">'+value.sp+'</small>/<small class="text-secondary" style="font-size:10px; position: relative; bottom: 2px;">'+value.pop+'</small></span></i>\
      <i class="fa-brands fa-mdb"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: 1px; font-size:15px; position: relative; bottom: 2px;"> '+value.rating+'</small></span></i>\
      <i class="fa-solid fa-calendar-day"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;"> '+value.publish_date+'</small></span></i>\
      </div>\
      </li>\
      </ul>\
    </div>\
  </div>\
  ');
}

function appendSocial(value, container, saming = false)
{
  if(value.default_image_path == true) // check if there is default_image_path key in this index
  {
    value.image_path = '/uploads/profiles/default.jpg'; // if it is, sign it as default image_path
  }
  else
  {
    value.image_path = 'https://image.tmdb.org/t/p/w500'+value.image_path; // if it's not, it's okay there is already an image path, set it up
  }
  if(value.comment != null)
  {
    isComment = true;
    cardBody = value.comment;
  }
  else
  {
    isComment = false;
    cardBody = value.overview;
  }
  $(container).append
  ('\
  <div class="col-md-6 col-lg-4 pb-3" id="content-'+value.id+'">\
    <div class="card card-custom bg-white border-white border-0" style="height: 450px">\
      <a class="text-decoration-none" href="/content/'+value.kind+'/'+value.tv_id+'"><div class="profile-custom-img" style="background-image: url('+value.image_path+');"></div></a>\
      <div class="card-body" style="overflow-y: auto">\
        <h4 class="mt-2 col-12 card-title text-truncate text-capitalize">'+value.title+'</h4>\
        <hr style="width:50%; margin: auto;">\
        <span style="font-style:italic; font-size:11px;" class="text-center"><small class="text-muted">'+value.category+'</small></span>\
        <p class="card-text overview text-truncate" style="cursor: pointer;">'+(isComment == true ? "<i class=\"fa-regular fa-comment text-success\"></i> ":"")+cardBody+'</p>\
      </div>\
      <div class="card-footer d-flex justify-content-center" style="background: inherit; border-color: inherit;">\
      <div class="card-footer d-flex justify-content-center" style="background: inherit; border-color: inherit;">\
      <div class="input-group input-group-sm">\
        <select class="fw-bold custom-select" name="watchedButton-'+value.id+'" class="btn btn-success" value="'+value.tv_id+'-'+value.kind+'" id="watchedButton" data-content-id="'+value.id+'">\
          <option value="'+value.tv_id+'-'+value.kind+'-delete" selected>R</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-1" '+(value.logged_personal_rating == 1 ? 'selected':'')+'>1</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-2" '+(value.logged_personal_rating == 2 ? 'selected':'')+'>2</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-3" '+(value.logged_personal_rating == 3 ? 'selected':'')+'>3</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-4" '+(value.logged_personal_rating == 4 ? 'selected':'')+'>4</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-5" '+(value.logged_personal_rating == 5 ? 'selected':'')+'>5</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-6" '+(value.logged_personal_rating == 6 ? 'selected':'')+'>6</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-7" '+(value.logged_personal_rating == 7 ? 'selected':'')+'>7</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-8" '+(value.logged_personal_rating == 8 ? 'selected':'')+'>8</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-9" '+(value.logged_personal_rating == 9 ? 'selected':'')+'>9</option>\
          <option value="'+value.tv_id+'-'+value.kind+'-10" '+(value.logged_personal_rating == 10 ? 'selected':'')+'>10</option>\
        </select>\
      </div>\
      <button type="button" name="wishlistButton-'+value.id+'" class="cardButtons btn btn-sm form-control ms-1" value="'+value.tv_id+'-'+value.kind+'" id="wishlistButton" data-content-id="'+value.id+'" data-status="' +(value.matched == "wishlist" ? "1":"0")+ '"> ' +(value.matched == "wishlist" ? "<i class=\"fa-solid fa-bookmark fa-lg\"></i>":"<i class=\"fa-regular fa-bookmark fa-lg\"></i>")+ '</button>\
      </div>\
      </div>\
      <ul class="list-group">\
      <li class="front-'+value.id+' flip-front" value="'+value.id+'">\
      <div class="d-flex justify-content-between p-3">\
        <i class="fa-solid fa-pen-to-square"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;"> '+value.inserted_date+'</small></span></i>\
        <i class="fa-solid fa-star text-warning"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;"> '+value.personal_rating+'</small></span></i>\
      </div>\
      </li>\
      </ul>\
      \
      <ul class="list-group">\
      <li class="flip-back back-'+value.id+'" style="display: none;" value="'+value.id+'">\
      <div class="d-flex justify-content-between p-3">\
      <i class="fa-regular fa-moon text-info"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;">'+value.sp+'</small>/<small class="text-secondary" style="font-size:10px; position: relative; bottom: 2px;">'+value.pop+'</small></span></i>\
      <i class="fa-brands fa-mdb"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: 1px; font-size:15px; position: relative; bottom: 2px;"> '+value.rating+'</small></span></i>\
      <i class="fa-solid fa-calendar-day"><span class="card-text text-truncate"><small class="text-muted" style="letter-spacing: -1px;"> '+value.publish_date+'</small></span></i>\
      </div>\
      </li>\
      </ul>\
    </div>\
  </div>\
  ');
}

$(document).on('click', '.flip-front', function() {
  let id = $(this).attr('value');
  $(".front-"+id).hide("slide", { direction: 'right', mode: 'hide' }, "slow", function()
  {
    $(".back-"+id).show("slide", { direction: 'left', mode: 'show' }, "slow", function()
    {
    });
  });
});
$(document).on('click', '.flip-back', function() {
  let id = $(this).attr('value');
  $(".back-"+id).hide("slide", { direction: 'left', mode: 'hide' }, "slow", function()
  {
    $(".front-"+id).show("slide", { direction: 'right', mode: 'show' }, "slow", function()
    {
    });
  });
});
