$(function(){
    //alert('hi');
    $('#mytable').tablesorter({
        selectorSort : 'button.sorter'    
    });
    $('.col-hide').click(function(){
        var colName = $(this).val();
        var filterBtnID = '#col-filter-'+colName;
        $('.col-'+colName).hide();
        $(filterBtnID).show();
    });
});
$('.col-filter').click(function(){
    var colName = $(this).text(); 
    $('.col-'+colName).show();
    $(this).hide()
});

$('.queue-btn').click(function(){
    var qv = $(this).val();
    var queueName = $(this).text();
    var id = $(this).attr('id');
    var upc = id.substring(5);
    var closestTr = $(this).closest('tr');
    var sessionName = $('#sessionName').val();
    var storeID = $('#storeID').val();
    //alert('val: '+qv+', id: '+id+',upc: '+upc+',sn: '+sessionName+',store: '+storeID);
    $.ajax({
        type: 'post',
        url: 'SCS.php',
        data: 'upc='+upc+'&queue='+queueName+'&qval='+qv+'&sessionName='+sessionName+'&storeID='+storeID,
        dataType: 'json',
        success: function(json) {
            //alert('success');
            if (qv == 0) {
                closestTr.css('background-color','white');
            } else {
                closestTr.css('background-color','red');
            }
            if (json.error) {
                alert(json.error);
            }
        }
    });
});

var altNames = {
    Spec: 'special_price',
    Sale: 'salePrice',
};
$('th').each(function(){
    var nameElm = $(this).find('.name');
    var thName = nameElm.text(); 
    $.each(altNames, function(k,v) {
        if (thName == v) {
            nameElm.text(k);
        }
    });
    //thName = thName.toUpperCase();
    //nameElm.text(thName);
});

//do something based on current option. I don't think this is in-use. 
$(function(){
    var option = $('#curOption').val();
    if (parseInt(option,10) == 3) {
        $('#blank-th').show();
        $('#blank-th').html('Notes');
    }
});

$('.filter').on('change',function(){
    var select = $(this).find(':selected');
    var filter = $(this).attr('name');
    if (select.text() == 'View All') {
        $('td').each(function(){
            $(this).closest('tr').show();
        });
    } else {
        $('td').each(function(){
            $(this).closest('tr').show();
        });
        $('td').each(function(){
            var tdvalue = $(this).text();
            if ($(this).hasClass('col-'+filter)) {
                if (tdvalue != select.text()) {
                    /*
                    alert(
                        'selected: '+select.text()+
                        ', fiter: '+filter+
                        ', tdvalue: '+tdvalue
                    );*/
                    $(this).closest('tr').hide();
                }
            } 
        });
    }
});
