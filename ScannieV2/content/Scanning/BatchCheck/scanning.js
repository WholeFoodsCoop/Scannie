$(document).ready(function(){
    enableLinea('#upc', function(){
        $('#upcForm').append('<input type=hidden name=linea value=1 />').submit();
    });
});
function sendToQueue(button, upc, queue_id, session,notes)
{
    $.ajax({
        url: 'salesChangeAjax2.php',
        data: 'upc='+upc+'&queue='+queue_id+'&session='+session+'&notes='+notes,
        success: function(response)
        {
            $('#ajax-resp').html(response);
        }
    });
}
function changeStoreID(button, store_id)
{
    $.ajax({
        url: 'salesChangeAjax3.php',
        data: 'store_id='+store_id,
        success: function(response)
        {
            $('#ajax-resp').html(response);
            window.location.reload();
        }
    });
}

function button(button, href) {
    window.open(href, '_blank');
}

function getErrNote(upc)
{
    $.ajax({
        url: 'salesChangeAjaxErrSigns.php',
        data: 'upc='+upc,
        success: function(response)
        {
            $('#ajax-form').html(response);
            $('#errBtn').hide();
            $('#noteTr').show();
        }
    });
}
