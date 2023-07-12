// add wishlist button //
$(document).on('click', '#wishlistButton', function()
{
  const query = $(this).val(); // tvid-kind
  let contentId = $(this).attr('data-content-id');
  if($(this).attr('data-status') == '0')
  {
    $(this).attr('data-status', "1");
    $(this).empty();
    $(this).html('<i class="fa-solid fa-bookmark fa-lg cardButtons"></i>');
  } else {
    $(this).attr('data-status', "0");
    $(this).empty();
    $(this).html('<i class="fa-regular fa-bookmark fa-lg cardButtons"></i>');
  }
  $.ajax({
      type: "POST", // post into l/profile/add
      url: "/panel/add",
      data: {
          tv_id: query+'-w-wishlist' // it contains tvid-kind-type, e.g. 680-movie-wishlist
      },
      success: function(result, textStatus, jqXHR)
      {
        if(jqXHR.status === 200) // if successfully modified
        {
          $('#watchedButton[data-content-id="'+contentId+'"]')[0].selectedIndex = 0;
          $('#content-'+contentId).effect("pulsate", {times:1}, "slow");
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
          $('#wishlistButton[data-content-id="'+contentId+'"]').attr('data-status', "0");
          $('#wishlistButton[data-content-id="'+contentId+'"]').empty();
          $('#wishlistButton[data-content-id="'+contentId+'"]').html('<i class="fa-regular fa-bookmark fa-lg"></i>');
          $('#content-'+contentId).effect("pulsate", {times:1}, "slow");
        }
      },
      error: function(jqXHR, textStatus) // other errors like 405 code
      {
        console.log(jqXHR.status);
      }
  });
});
// add watched button //
