$(document).ready( function () {
});
$('#searchbar').keyup( function () {
    var text = $("#searchbar").val();
    if (text.length) {
        //alert(text);
        getSearchResults(text);
    } 
    if ( $('#searchbar').val() == '') {
        /*clear search results*/
        $('#search-resp').html('')
    }
});

function getSearchResults(search)
{
    $.ajax({
        url: 'http://192.168.1.2/scancoord/common/ui/searchbar.php',
        data: 'search='+search,
        success: function(response)
        {
            $('#search-resp').html(response);
        }
    });
}
