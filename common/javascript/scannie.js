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
            $('#quick_lookups').modal('toggle');
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
	newwindow=window.open(url,'name','height=300,width=300');
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
            $('#backToTop').show();
        } else {
            $('#backToTop').hide();
        }

        $('.background1, .background2').each(function() {
            var topDistance = $(this).offset().top;

            if ( (topDistance+100) < scrollTop ) {
                alert( $(this).text() + ' was scrolled to the top' );
            }
        });

        if ($(window).scrollTop() > $('body').height() / 2) {
            $('#backToTop').show();
        }
    });

    $('#backToTop').click(function(){
        $("html, body").animate({ scrollTop: 0 }, "fast");
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
        url: '../common/ui/searchbar.php',
        //dataType: 'POST',
        data: 'search='+search,
        success: function(response)
        {
            $('#search-resp').html(response);
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

