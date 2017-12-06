$("tr").each(function() { 
    var op_store = $('#storeID').val();
    var id = $(this).find('td.store_id').text();
    if (id == op_store) {
        $(this).closest('tr').hide();
    }
});

$("#notes").change( function() {
    var noteKey = $("#notes").val();
    var note = $("#notes").find(":selected").text();
    $("#mytable").each(function() {
        $(this).find("tr").each(function() {
            $(this).show();
        });
    });
    $("#mytable").each(function() {
        $(this).find("tr").each(function() {
        var notecell = $(this).find(".notescell").text();
            if (note != notecell) {
                $(this).closest("tr").hide();
            }
            if (noteKey == "viewall") {
                $(this).show();
            }
            $(".blankrow").show();
        });
    });
});

function fancyButtons()
{
    $("#clearNotesInput").click( function () {
        var r = confirm("Pressing OK will clear all notes from this queue.");
        if (r == true) {
            $("#clearNotesForm").submit();
        }
        event.stopPropagation();
    });
    $("#clearAllInput").click( function () {
        var r = confirm("Pressing OK will delete all data from this queue.");
        if (r == true) {
            $("#clearAllForm").submit();
        }
        event.stopPropagation();
    });
    $("#updateInput").click( function () {
        var r = confirm("Pressing OK will update product data from Fannie.");
        if (r == true) {
            $("#updateForm").submit();
        }
        event.stopPropagation();
    });
}

function linksToText() {
    $('.upc').each( function() {
        $(this).removeAttr('href');
    });
}

function deleteRow() {
    $('.delete-icon').click( function() {
        var upc = $(this).closest('td').attr('id');
        var store_id = $(this).closest('tr').find('.store_id').text();
        var username = $(this).closest('tr').find('.username').text();
        var rowclicked = $(this).closest('tr').attr('id')   ;
        var r = confirm('Remove '+upc+' from Queue?');
        if (r == true) {
            $.ajax({        
                url: 'AuditScannerReportAjax.php',
                type: 'post',
                data: 'store_id='+store_id+'&upc='+upc+'&username='+username+'&deleteRow=true',
                success: function(response)
                {
                    if($('#'+rowclicked).length == 0) {
                        $('#firstTr').hide();
                    } else {
                        $('#'+rowclicked).hide();
                    }
                    $('#resp').html(response);
                }
            });
        }
        event.stopPropagation();
    });
}

function redrawDataTable()
{
    $('#dataTable').each(function() {
        $('tr').each(function () {
            $(this).show();
        });
    });   
}

$(document).ready(function () {
    $('#red-toggle').click(function () {
        redrawDataTable();
        $('#dataTable').each(function() {
            $('tr').each(function () {
                if ( !$(this).hasClass('red') && !$(this).hasClass('key') ) {
                    $(this).hide();
                }
            });
        });       
    });
    $('#yellow-toggle').click(function () {
        redrawDataTable();
        $('#dataTable').each(function() {
            $('tr').each(function () {
                if ( !$(this).hasClass('yellow') && !$(this).hasClass('key') ) {
                    $(this).hide();
                }
            });
        });       
    });
    $('#blue-toggle').click(function () {
        redrawDataTable();
        $('#dataTable').each(function() {
            $('tr').each(function () {
                if ( !$(this).hasClass('blue') && !$(this).hasClass('key') ) {
                    $(this).hide();
                }
            });
        });       
    });
    $('#grey-toggle').click(function () {
        redrawDataTable();
        $('#dataTable').each(function() {
            $('tr').each(function () {
                if ( !$(this).hasClass('grey') && !$(this).hasClass('key') ) {
                    $(this).hide();
                }
            });
        });       
    });
});


function highlightRows()
{
    $('.rowz').click(function() {
        if ( $(this).hasClass('click-highlight') ) {
            $(this).removeClass('click-highlight');
        } else {
            $(this).addClass('click-highlight'); 
        }
    });
}

function getTablesorter()
{
   $('#dataTable').tablesorter();
}

$(document).ready( function() {
    fancyButtons();
    deleteRow();
    highlightRows();
    getTablesorter();
});
