var mode = 'tall';
$(function(){
    //alert('hello');
    var h = document.body.clientHeight; 
    var w = document.body.clientWidth; 
    //alert('width: '+w+' | height: '+h);
    if (parseInt(w,10) > parseInt(h,10)) {
        mode = 'wide' 
    }
});

$('.editable').each(function(){
    $(this).attr('contentEditable',true);
});
$('.editable').focusout(function(){
    var oldValue = $(this).attr('value');
    var newValue = $(this).text();

    //don't use ipod's default apostrophe
    var newValueChars = newValue.split('');
    var newChars = [];
    $.each(newValueChars, function(k,v) {
        var charCode = v.charCodeAt(0);
        if (charCode == 8217) {
            newChars.push("'");
        } else {
            newChars.push(v);
        }
    });
    newValue = newChars.join('');

    var editField = $(this).attr('id');
    var upc = $('#upc').val();
    if (newValue != oldValue) {
        //change is detected, deploy $ajax
        newValue = encodeURIComponent(newValue);
        $.ajax({
            type: 'post',
            url: 'SCS.php',
            data: 'newValue='+newValue+'&edit='+editField+'&upc='+upc,
            dataType: 'json',
            success: function(json)
            {
                oldValue = newValue;
                $(this).val(newValue);

                if (json.error) {
                    $('#response').show();
                    $('#response').addClass('alert alert-danger');
                    $('#response').text('error').delay(1500).fadeOut(400);
                } else {
                    //$('#response').append('<div class="alert alert-success">Saved</div>');
                    $('#response').show();
                    $('#response').addClass('alert alert-success');
                    $('#response').text('saved').delay(1500).fadeOut(400);
                }
                //replace "div.showQueueContainer" with current queue list.
                var queues = {};
                queues = json.queues;
                var queueText = '';
                $.each(queues, function(key,value) {
                    queueText += "<div class='showQueue btn btn-"+queuesToButtons[value]+"' id='id"+key+"'>"+value+"</div>";
                    $('#id'+key).addClass('showQueue');
                });
                $('#showQueueContainer').html(queueText);
            }
        });
    }
})

$('.editlocation').unbind().click(function(){
    var location = $('#formStoreID').val();
    var session = $('#formSession').val();
    var upc = $('#upc').val();
    var device = 'ipod'; 
    if (!upc) {
        //editLocation being called from BCQ 
        upc = $(this).closest('tr:first-child').html();
        var upcAnchor = $(this).closest('tr').find('a');
        upc = upcAnchor.html();
        device = 'tablet';
    }
    c = confirm('Edit Location?');
    if (c == true) {
        if (device == 'ipod') {
            window.location.href = '../../../../../git/fannie/item/ProdLocationEditor.php?store_id='+location+'&upc='+upc+'&searchupc=1&batchCheck=1';
        } else {
            window.open('../../../../../git/fannie/item/ProdLocationEditor.php?store_id='+location+'&upc='+upc+'&searchupc=1&batchCheck=1','_blank');

        }
    }
});

var queuesToButtons = {
    1:'success',
    2:'warning',
    3:'danger',
    4:'primary',
    5:'surprise',
    6:'inverse',
    7:'inverse',
    8:'inverse',
    9:'danger',
    10:'danger',
    11:'danger',
};
$('.btn-queue').click(function(){
    var upc = $('#upc').val();
    var queue = $(this).text();
    var qval = $(this).val();
    //alert(qval);
    if (parseInt(qval,10) == parseInt(6,10)) {
        $('#capButtons').hide();
    }
    if (parseInt(qval,10) == parseInt(7,10)) {
        $('#capButtons').hide();
    }
    if (parseInt(qval,10) == parseInt(8,10)) {
        $('#capButtons').hide();
    }
    if (parseInt(qval,10) == parseInt(9,10)) {
        $('#discoButtons').hide();
    }
    if (parseInt(qval,10) == parseInt(10,10)) {
        $('#discoButtons').hide();
    }
    //alert(queue);
        $.ajax({
        type: 'post',
        url: 'SCS.php',
        data: 'upc='+upc+'&queue='+queue+'&qval='+qval, 
        dataType: 'json',
        success: function(json)
        {
            if (json.error) {
                $('#response').show();
                $('#response').addClass('alert alert-danger');
                $('#response').text(json.error).delay(1500).fadeOut(400);
            } else {
                $('#response').show();
                $('#response').addClass('alert alert-success');
                $('#response').text('saved').delay(1500).fadeOut(400);
            }
            //replace "div.showQueueContainer" with current queue list.
            var queues = {};
            queues = json.queues;
            var queueText = '';
            $.each(queues, function(key,value) {
                queueText += "<div class='showQueue btn btn-"+queuesToButtons[value]+"' id='id"+key+"'>"+value+"</div>";
                $('#id'+key).addClass('showQueue');
            });
            $('#showQueueContainer').html(queueText);
        }
    });
});

/* button/click events */
$('#capBtn').click(function(){
    $('#capButtons').show();
});
$('#discoBtn').click(function(){
    $('#discoButtons').show();
});
$('#showMoreBatches').click(function(){
    $('#allBatches').show();
});
$('#closeAllBatches').click(function(){
    $('#allBatches').hide();
});
$('#menuBtn').click(function(){
    // $('#menu').show();
    window.location.href = 'BatchCheckMenu.php';
});
$('#closeMenu').click(function(){
    $('#menu').hide();
});

$('.showQueue').click(function(){
    var id = $(this).attr('id');
    var queue = $(this).text();
    id = id.substring(2);
    if (queue != 11) {
        c = confirm('Remove from Queue '+queue);
    }
    if (c == true && queue != 11) {
        $.ajax({
            type: 'post',
            url: 'SCS.php',
            data: 'qid='+id+'&removeQueue=1', 
            dataType: 'json',
            success: function(json)
            {
                if (json.error) {
                    $('#response').show();
                    $('#response').addClass('alert alert-danger');
                    $('#response').text(json.error).delay(1500).fadeOut(400);
                } else {
                    $('#id'+id).hide();
                }
            }
        });
    }
});

$('.useBatch').click(function(){
    var id = $(this).attr('id');
    var bid = id.substring(6);
    //alert(bid);
    c = confirm('Force Batch '+bid+'?');
    if (c == true) {
        $.ajax({
            type: 'post',
            url: 'SCS.php',
            data: 'forceBatch=1&bid='+bid, 
            dataType: 'json',
            success: function(json)
            {
                if (json.error) {
                    $('#response').show();
                    $('#response').addClass('alert alert-danger');
                    $('#response').text(json.error).delay(1500).fadeOut(400);
                } else {
                    $('#response').show();
                    $('#response').addClass('alert alert-success');
                    $('#response').text('saved').delay(1500).fadeOut(400);
                    $('#allBatches').hide();
                }
            }
        });
    }
});

$('#submitUpc').click(function(){
    document.forms['upcForm'].submit();
});

;(function($) {
    $.fn.test = function(options) {
        var fontSize = options.maxFontWidth;
        var input = $('input:visible:first', this);
        var inputLength = input.length;
        do {
            input.css('font-size', fontSize);
            fontSize = fontSize - 0;
        } while (inputLength > 25);
        return this; 
    }
})(jQuery);
 
var timer, clicker = $('#goodBtn');

$(document).bind('touchstart', function(event) {
    $(event.target).trigger('mousedown');
});
$(document).bind('touchend', function(event) {
    $(event.target).trigger('mouseup');
});

clicker.mousedown(function(){
    timeout = setInterval(function(){
        var upc = $('#upc').val();
        c = confirm('Mark product line Good?');
        if (c == true) {
            $.ajax({
                type: 'post',
                url: 'SCS.php',
                data: 'upc='+upc+'&qval=1&q=Good&lineCheck=1',
                dataType: 'json',
                success: function(json)
                {
                    if (json.error) {
                        alert(json.error);
                    } else {
                        alert('Line Queued as Good');
                    }
                }
            });
        }
    }, 1000);
    stopProp = setInterval(function(){
        clicker.trigger('mouseup');
    }, 1100);
});

$(document).mouseup(function(){
    clearInterval(timeout);
    return false;
});
