$(function(){
});

$('.loc').click(function(){
    //alert('clicking locations doesn\'t do anything.');
    //var text = $(this).text();
    //var id = $(this).closest('tr').attr('id');
    //upc = id.substr(2);
    //$(this).html('');
    //$().find('.loc-input').append('<td><input type="text" value="'+text+'"></td>');
});

$(function(){
    $('#loading').hide();
});

function addUpcForm() {
    $('#clickToShowForm').hide();
    $('#addUpcForm').show();
    $('#addUpcForm').css('display','inline-block');
}

function addNote()
{
    var notes = $('#addUpcUpc').val();
    var count = $('#countUpcs').text();
    var upc =  parseInt(count) + 1; 
    upc = ''+upc+'';
    upc = padUpc(upc);
    var queue = $('#addUpcQueue').val();
    var session = $('#addUpcSession').val();
    var storeID = $('#addUpcStoreID').val();

    $.ajax({
        url: 'salesChangeAjax2.php',
        data: 'upc='+upc+'&queue='+queue+'&session='+session+'&storeID='+storeID+'&notes='+notes,
        success: function(response)
        {
            //$('#ajax-resp').html('AJAX call returned: ' + response);
            window.location.reload();
        }
    });

}

function padUpc(upc)
{
    var length = upc.length;
    var pad = 13 - length;
    for (var i=0; i<pad; i++) {
        upc = '0'+upc;
    }

    return upc;

}

function submitAddUpc()
{
    var upc = $('#addUpcUpc').val();
    upc = padUpc(upc);
    var queue = $('#addUpcQueue').val();
    var session = $('#addUpcSession').val();
    var storeID = $('#addUpcStoreID').val();
    $.ajax({
        url: 'salesChangeAjax2.php',
        data: 'upc='+upc+'&queue='+queue+'&session='+session+'&storeID='+storeID,
        success: function(response)
        {
            //$('#ajax-resp').html('AJAX call returned: ' + response);
            window.location.reload();
        }
    });
}

function hideUnsold() {
    $('td').each(function() {
        if ($(this).hasClass('red')) {
            $(this).closest('tr').hide();
        }
    });
}

function sendToQueue(button, upc, queue_id, session, delQ)
{
    $(button).closest('tr').hide();
    $.ajax({
        url: 'salesChangeAjax2.php',
        data: 'upc='+upc+'&queue='+queue_id+'&session='+session+'&delQ='+delQ,
        success: function(response)
        {
            $('#ajax-resp').html('AJAX call returned: ' + response);
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
$(document).ready( function() {
    hideLoc();
    $('#collapseLoc').click( function() {
        if ( $('#locTh').css('display') == 'none' ) {
            showLoc();        
        } else {
            hideLoc();
        }
    });
    hideMenu();
});

function hideMenu()
{
    $('.switchQ').on('collapse', function () {
        alert("hi");    
    });
    $('#switchBtn').css({
        //'left' : '-170px'
    });
}

function hideLoc() {
    $('td').each( function() {
         if ( $(this).hasClass('loc') ) {
            $(this).hide();
        }
    });
    $('th').each( function() {
        if ( $(this).hasClass('loc') ) {
            $(this).hide();
        }
    });
}
function showLoc() {
    $('td').each( function() {
         if ( $(this).hasClass('loc') ) {
            $(this).show();
        }
    });
    $('th').each( function() {
        if ( $(this).hasClass('loc') ) {
            $(this).show();
        }
    });

}
