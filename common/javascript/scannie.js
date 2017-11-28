/*
    CorePage-JS
*/

/**
 *  KeyDown(evt)
 *  Map events to Keys Pressed
 */
function KeyDown(evt) {
    switch (evt.keyCode) {
        case 39:  /* Right Arrow */
            break;

        case 37:  /* Left Arrow */
            break;

        case 40:  /* Down Arrow */
            break;

        case 38:  /* Up Arrow */
            break;

        case 192:  /* Tilde */
            $('#quick_lookups').toggle('modal');
            break;
    }
}
window.addEventListener('keydown', KeyDown);

/*
    Menu-JS
*/

/**
 * popitup(url)
 * xs pop-up window.
 */
function popitup(url) {
	newwindow=window.open(url,'name','height=700,width=290');
	if (window.focus) {newwindow.focus()}
	return false;
}

function popitupIpod(url) {
	newwindow=window.open(url,'name','height=600,width=330');
	if (window.focus) {newwindow.focus()}
	return false;
}

$(document).ready( function () {
    $('#searchbar').keypress( function () {
        var text = $("#searchbar").val();
        if (text.length) {
            //alert(text);
            getSearchResults(text);
        } else {
            $('#search-resp').html('')
        }
    });
    backToTop();
    getMobileMenu();
    closeMenu();
});

function backToTop()
{
    $(window).scroll(function () {
        var scrollTop = $(this).scrollTop();
        //alert(scrollTop);
        if (scrollTop != 0) {
            $('#backToTop').fadeIn('slow');
        } else {
            $('#backToTop').fadeOut('slow');
        }

        if ($(window).scrollTop() > $('body').height() / 2) {
            $('#backToTop').show();
        }
    });

    $('#backToTop').click(function(){
        $("html, body").animate({ scrollTop: 0 }, "fast");
    });
}

function scrollRight()
{
    $(window).scroll(function () {
        var scrollRight = $(this).scrollRight();
        //alert(scrollRight);
        if (scrollRight != 0) {
            $('#scrollRight').show();
        } else {
            $('#scrollRight').hide();
        }

        if ($(window).scrollRight() > $('.panelScroll').width() / 2) {
            $('#scrollRight').show();
        }
    });

    $('#scrollRight').click(function(){
        $("html, .panelScroll").animate({ scrollRight: 0 }, "fast");
    });
}

function getMobileMenu()
{
    $('#mobileMenuBtn').click( function () {
        $('#mobileMenu').show();
    });
}

function closeMenu()
{
    $('#closeMenu').click( function () {
        $('#mobileMenu').hide();
    });
}

function getSearchResults(search)
{
    $.ajax({
        url: 'http://192.168.1.2/scancoord/common/ui/searchbar.php',
        dataType: 'json',
        type: 'GET',
        data: 'search='+search,
        success: function(response)
        {
            $('#search-resp').html(response);
            $('#search-resp').html("<h1>hello!</h1>");
        }
    });
}

function calcView(name)
{
    if ( $('#'+name).is(":visible") ) {
        $('#'+name).hide();
    } else {
        $('#'+name).show();
    }
}

$(function(){
        $( ".draggable" ).draggable();
});

function hideModal()
{
    $('#quick_lookups').toggle('modal');
}
