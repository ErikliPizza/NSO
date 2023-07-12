// delete content button //
$(document).on('click', '#deleteButton', function()
{
  const thisid = $(this).val(); //get this value
  $.ajax({
      type: "POST", //post into l/profile/delete
      url: "/panel/delete",
      data: {
          content_id: thisid //it contains only content's id
      },
      success: function(result, textStatus, jqXHR)
      {
        if(jqXHR.status === 200) // if successfully deleted
        {

          $('#content-'+thisid).hide("scale", "center", function()
          {
            $('#content-'+thisid).remove(); // remove, it'll change with animated
          });

        }
      },
      error: function(jqXHR, textStatus) // other errors like 405 code
      {
        console.log(jqXHR.status);
      }
  });
});
// delete content button //

// add wishlist button //
$(document).on('click', '#wishlistButton', function()
{
  const query = $(this).val(); // tvid-kind
  let contentId = $(this).attr('data-content-id');
  $(this).prop('disabled', true); // disable itself
  $.ajax({
      type: "POST", // post into l/profile/add
      url: "/panel/add",
      data: {
          tv_id: query+'-wishlist-wishlist' // it contains tvid-kind-type, e.g. 680-movie-wishlist
      },
      success: function(result, textStatus, jqXHR)
      {
        if(jqXHR.status === 200) // if successfully modified
        {
          $('#content-'+contentId).effect("fold", "slow", function()
          {
            $('#content-'+contentId).remove(); // remove, it'll change with animated
          });
        }
      },
      error: function(jqXHR, textStatus) // other errors like 405 code
      {
        console.log(jqXHR.status);
      }
  });
});
// add wishlist button //

// add watched button //
$(document).on('change', '#watchedButton', function()
{
  const query = $(this).val(); // tvid-kind
  let contentId = $(this).attr('data-content-id');
  $.ajax({
      type: "POST", // post into l/profile/add
      url: "/panel/add",
      data: {
          tv_id: query+'-watched' // it contains tvid-kind-type, e.g. 680-serie-watched
      },
      success: function(result, textStatus, jqXHR)
      {
        if(jqXHR.status === 200) // if successfully modified
        {
          $('#content-'+contentId).effect("fold", "slow", function()
          {
            $('#content-'+contentId).remove(); // remove, it'll change with animated
          });
        }
      },
      error: function(jqXHR, textStatus) // other errors like 405 code
      {
        console.log(jqXHR.status);
      }
  });
});
// add watched button //
