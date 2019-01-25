$('#nav-search').keyup( function () {
    var text = $("#nav-search").val();
    if (text.length) {
        //alert(text);
        getSearchResults(text);
    } 
    if ( $('#nav-search').val() == '') {
        /*clear search results*/
        $('#search-resp').html('')
    }
});

var HOSTNAME = window.location.hostname;
function getSearchResults(search)
{
    $.ajax({
        url: 'http://'+HOSTNAME+'/scancoord/ScannieV2/common/ui/Search.php',
        data: 'search='+search,
        success: function(response)
        {
            $('#search-resp').html(response);
        }
    });
}

$('.search-resp').click(function(){
    alert('hi');
    $('#nav-search').val('1');

    return true;
});
$(function(){
    //alert('hi');
});
