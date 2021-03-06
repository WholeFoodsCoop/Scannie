// keep <thead> at top of window.
var theadOnTop = 0;
$(function(){
    var distance = $('#mythead').offset().top, $window = $(window);
    $window.scroll(function(){
        if ($window.scrollTop() >= distance && theadOnTop == 0) {
            $('#mythead-clone').show();
            theadOnTop = 1;
        } else if ($window.scrollTop() <= distance && theadOnTop == 1) {
            $('#mythead-clone').hide();
            theadOnTop = 0;
        }
    });
});

// sort & hide columns
$(function(){
    $('#mytable').tablesorter({
        selectorSort : 'button.sorter'    
    }).bind("sortEnd",function(e, t) {
        stripeTable();    
    });
    $('.col-hide').click(function(){
        var colName = $(this).val();
        var filterBtnID = '#col-filter-'+colName;
        $('.col-'+colName).hide();
        $(filterBtnID).show();
    });
    stripeTable();
});
$('.col-filter').click(function(){
    var colName = $(this).text(); 
    $('.col-'+colName).show();
    $(this).hide();
});

// queue button events
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
                closestTr.css('background-color', queueNamesToColors[queueName]);
            }
            if (json.error) {
                alert(json.error);
            }
        }
    });
});

var queueNamesToColors = {
    'Good' : 'Green',
    'Miss' : 'Yellow',
    'Unchecked' : 'White',
    'Clear' : 'Grey',
    'DNC' : 'Black',
};

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

//do something based on current option. I don't think this is being used
$(function(){
    var option = $('#curOption').val();
    if (parseInt(option,10) == 3) {
        $('#blank-th').show();
        $('#blank-th').html('Notes');
    }
});

// filter events
$('.filter').on('change',function(){
    var select = $(this).find(':selected');
    var filter = $(this).attr('name');
    if (select.text() == 'View All') {
        $('td').each(function(){
            $(this).closest('tr').show();
        });
    } else if (select.text() == 'Hide Yellow') {
        $('td').each(function(){
            if ($(this).hasClass('text-warning') || $(this).text() == '') {
                $(this).closest('tr').hide();
            }
        });
    } else if (select.text() == 'Hide Red & Yellow') {
        $('td').each(function(){
            if ($(this).hasClass('text-warning') || $(this).hasClass('text-danger') || $(this).text() == '') {
                $(this).closest('tr').hide();
            }
        });
    } else if (select.text() == 'Show Only Coop+Deals') {
        $('td').each(function(){
            var str = $(this).text();
            var index = str.indexOf('Co-op Deals');
            if ($(this).hasClass('col-batchName') && str.indexOf('Co-op Deals') == -1) {
                $(this).closest('tr').hide();
            }
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
    stripeTable();
});


// recognize dates on page as current, past and ancient 
$('td').each(function(){
    var text = $(this).text(); 
    var col = $(this).attr('class');
    if (col == 'col-last_sold ') {
        var tdate = new Date(text);
        var year = tdate.getFullYear();
        var month = tdate.getMonth()+1;
        var day = tdate.getDate()+1;

        var check1 = new Date();
        check1.setMonth(check1.getMonth() - 2);
        var cy = check1.getFullYear();
        var cm = check1.getMonth()+1;
        var cd = check1.getDate()+1;
        
        var check2 = new Date();
        check2.setMonth(check2.getMonth() - 12);
        var cy = check2.getFullYear();
        var cm = check2.getMonth()+1;
        var cd = check2.getDate()+1;

        var date1 = year+'-'+month+'-'+day;
        var date2 = cy+'-'+cm+'-'+cd;

        year = parseInt(year,10);
        cy = parseInt(cy,10);
        if (tdate < check2) {
            $(this).addClass('text-danger');
        } else if (tdate < check1) {
            $(this).addClass('text-warning');
        }
    }
});


// remove duplicate rows inserted when finding items left out by mysql  
var missUpcs = [];
$('td').each(function() {
    if ( $(this).hasClass('col-upc') ) {
        html = $(this).html(); 
        if ( $.inArray(html, missUpcs) != -1 ) {
            $(this).closest('tr').hide();
        } else {
            missUpcs.push(html)
        }
    }
});

// dynamically add stipe to table
function stripeTable(){
    var i = 0;
    $('tr').each(function(){
        $(this).css('background', 'white');
    });
    $('tr').each(function(){
        if ( $(this).is(':visible') ) {
            if (i % 2 != 0) {
                $(this).css('background', 'orange');   
            }
            i++;
        }
    });
};
stripeTable();

// clear all in queue
$('#clearAll').click(function(){
    c = confirm("Remove all items from this queue?");
    if (c === true) {
        var qv = $(this).val();
        var queueName = $(this).text();
        var id = $(this).attr('id');
        var sessionName = $('#sessionName').val();
        var storeID = $('#storeID').val();
        $.ajax({
            type: 'post',
            url: 'SCS.php',
            data: 'queue='+queueName+'&qval='+qv+'&sessionName='+sessionName+'&storeID='+storeID+'&clearAll=1',
            dataType: 'json',
            success: function(json) {
                $('td').each(function(){
                    $(this).closest('tr').hide();
                });
                $('#textarea').val("");
                $('#qcount').html("[0]");
                if (json.error) {
                    alert(json.error);
                }
            }
        });
    }
});
