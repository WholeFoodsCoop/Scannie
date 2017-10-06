$(document).ready(function(){
    enableLinea('#upc', function(){$('#my-form').submit();});
});

function queue(store_id)
{
    var upcB = document.getElementById("upc").value;
    $.ajax({
		type: 'post',
        url: 'AuditUpdate.php',
        data: 'upc='+upcB+'&store_id='+store_id,
		error: function(xhr, status, error)
		{
			alert('error:' + status + ':' + error + ':' + xhr.responseText)
		},
        success: function(response)
        {
            $('#ajax-resp').html(response);
        }
    })
	.done(function(data){

	})
}

$( "button" ).click(function() {
  var text = $( this ).text();
  $( "note" ).val( text );
});

function qm(msg)
{
    document.getElementById("note").value = msg;
}

$(document).ready(function(){
    var upc = $('#upc').val() + '';
    $( "#keyCL" ).click(function() {
        $('#upc').val('0');
        upc = 0;
        updateModalText();
    });
    $( "#key1" ).click(function() {
        $('#upc').val(upc+'1');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key2" ).click(function() {
        $('#upc').val(upc+'2');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key3" ).click(function() {
        $('#upc').val(upc+'3');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key4" ).click(function() {
        $('#upc').val(upc+'4');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key5" ).click(function() {
        $('#upc').val(upc+'5');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key6" ).click(function() {
        $('#upc').val(upc+'6');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key7" ).click(function() {
        $('#upc').val(upc+'7');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key8" ).click(function() {
        $('#upc').val(upc+'8');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key9" ).click(function() {
        $('#upc').val(upc+'9');
        upc = $('#upc').val();
        updateModalText();
    });
    $( "#key0" ).click(function() {
        $('#upc').val(upc+'0');
        upc = $('#upc').val();
        updateModalText();
    });
});

function get_auto_par()
{
    var par = $('#auto_par_value').val();
    par = parseFloat(par);
    par = par.toPrecision(3);
    $('#auto_par').text('PAR: ');
    $('#par_val').text(par);
}
$(document).ready( function() {
   get_auto_par();
   updateModalOnload();
});

function formSubmitter()
{
    $('#my-form').submit();
}

function updateModalOnload()
{
    $('#btn-modal').click( function () {
        updateModalText();
    });
}

function updateModalText()
{
    $text = $('#upc').val();
    $('#modal-text').text($text);
}

function startLoading()
{
    $('#progressBar').show();
}
function endLoading()
{
    $('#progressBar').hide();
}