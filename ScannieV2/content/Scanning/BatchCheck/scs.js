$(function(){
    //alert('hello');
});

$('.editable').each(function(){
    $(this).attr('contentEditable',true);
});
$('.editable').focusout(function(){
    var oldValue = $(this).attr('value');
    var newValue = $(this).text();
    var editField = $(this).attr('id');
    var upc = $('#upc').val();
    if (newValue != oldValue) {
        //change is detected, deploy $ajax
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
});

$('.editlocation').click(function(){
    var location = $('#formStoreID').val();
    var session = $('#formSession').val();
    var upc = $('#upc').val();
    c = confirm('Edit Location?');
    if (c == true) {
        window.location.href = '../../../../../git/fannie/item/ProdLocationEditor.php?store_id='+location+'&upc='+upc+'&searchupc=1&batchCheck=1';
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
$('#showMoreBatches').click(function(){
    $('#allBatches').show();
});
$('#closeAllBatches').click(function(){
    $('#allBatches').hide();
});
$('#menuBtn').click(function(){
    $('#menu').show();
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

;(function($) {
    $.fn.test = function(options) {
        var fontSize = options.maxFontWidth;
        var input = $('input:visible:first', this);
        var inputLength = input.length;
        do {
            input.css('font-size', fontSize);
            fontSize = fontSize - 1;
        } while (inputLength > 25);
        return this; 
    }
})(jQuery);
