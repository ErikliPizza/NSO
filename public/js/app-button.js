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
          $('#watchedButton[data-content-id="'+contentId+'"]')[0].selectedIndex = 0;
          $('#content-'+contentId).effect("pulsate", {times:1}, "slow")
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
          $('#wishlistButton[data-content-id="'+contentId+'"]').prop('disabled', false);
          $('#content-'+contentId).effect("pulsate", {times:1}, "slow")
        }
      },
      error: function(jqXHR, textStatus) // other errors like 405 code
      {
        console.log(jqXHR.status);
      }
  });
});
// add watched button //
