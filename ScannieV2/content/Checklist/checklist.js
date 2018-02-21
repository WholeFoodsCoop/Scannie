$(document).ready( function() {
    $('.check').click( function() {
        var $this = $(this);
        var checked = $(this).closest('tr').find('[type=checkbox]').prop('checked')?true:false;
        var d = new Date();
        var month = parseInt(d.getMonth(),10) + 1;
        var today = d.getFullYear()+"-"+month+"-"+d.getDate();
        if (checked == true) {
            $this.parents('tr').css("background-color", "rgba(0,0,0,0.2)");
        } else {
            $this.parents('tr').css("background-color", "rgba(255,255,255,0.2)");
        }
        var id = $this.attr('id');
        var trimmedID = id.substring(1);
        if (!checked) {
            var c = confirm('Uncheck Task / Make New?');
        }
        if (checked || c == true) {
            $.ajax({
                type: 'post',
                data: 'id='+id+'&checked='+checked+'&checkbox=1'+'&date='+today,
                dataType: 'json',
                success: function(json)
                {
                    if (json.error) {
                        $('#ajaxResp').show();
                        $('#ajaxResp').addClass('alert alert-danger');
                        $('#ajaxResp').text(json.error);
                        $('#ajaxResp').text('saved').delay(800).fadeOut(400);
                    } else {
                        $('#ajaxResp').show();
                        $('#ajaxResp').addClass('alert alert-success');
                        $('#ajaxResp').text('saved').delay(800).fadeOut(400);
                    }
                    if (checked == true) {
                        $('#t'+trimmedID).text(today);
                    } else {
                        $('#t'+trimmedID).text('');
                    }  
                }
            });
        }
    });
    getCheckedOnload();
    getList();
});

$('.comments').change(function(){
    var id = $(this).attr('id');
    var text = $(this).val();
    $.ajax({
        type: 'post',
        data: 'id='+id+'&comments=1'+'&text='+text,
        dataType: 'json',
        success: function(json)
        {
            if (json.error) {
                $('#ajaxResp').show();
                $('#ajaxResp').addClass('alert alert-danger');
                $('#ajaxResp').text(json.error);
                $('#ajaxResp').text('saved').delay(800).fadeOut(400);
            } else {
                $('#ajaxResp').show();
                $('#ajaxResp').addClass('alert alert-success');
                $('#ajaxResp').text('saved').delay(800).fadeOut(400);
            }
        }
    });
});

$('#notes').change(function(){
    var text = $(this).val();
    $.ajax({
        type: 'post',
        data: 'notes=1'+'&text='+text,
        dataType: 'json',
        success: function(json)
        {
            if (json.error) {
                $('#ajaxResp').show();
                $('#ajaxResp').addClass('alert alert-danger');
                $('#ajaxResp').text(json.error);
                $('#ajaxResp').text('saved').delay(800).fadeOut(400);
            } else {
                $('#ajaxResp').show();
                $('#ajaxResp').addClass('alert alert-success');
                $('#ajaxResp').text('saved').delay(800).fadeOut(400);
            }
        }
    });
});

function getCheckedOnload()
{
    $('.check').each( function() {
        var $this = $(this);
        var checked = $(this).closest('tr').find('[type=checkbox]').prop('checked')?true:false
        if (checked == true) {
            $this.parents('tr').css("background-color", "rgba(0,0,0,0.2)");
            $this.closest('tr').show();
        } else {
            $this.parents('tr').css("background-color", "rgba(255,255,255,0.2)");
        }
    });
}

function getList()
{
    $('#upcBtn').click( function() {
        $('.check').each( function() {
            var $this = $(this);
            var checked = $(this).closest('tr').find('[type=checkbox]').prop('checked')?true:false
            if (checked == false) {
                $this.parents('tr').hide();
            }
        });
    });
    $('#upcBtnOppo').click( function() {
        $('.check').each( function() {
            var $this = $(this);
            var checked = $(this).closest('tr').find('[type=checkbox]').prop('checked')?true:false
            if (checked == true) {
                $this.parents('tr').hide();
            }
        });
     });
}
$('#addNewTableName').click(function(){
    document.forms['createTable'].submit();
});
$('#addTableRow').click(function(){
    document.forms['addTableRow'].submit();
});
