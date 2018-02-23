//$(function(){alert('hi');});
$('.col-hide').click(function(){
    var colName = $(this).text();
    var filterBtnID = '#col-filter-'+colName;
    $('.col-'+colName).hide();
    $(filterBtnID).show();
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
            closestTr.css('background-color','red');
            if (json.error) {
                alert(json.error);
            }
        }
    });
});
