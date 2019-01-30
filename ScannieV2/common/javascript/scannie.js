$(function backToTop()
{
    $(window).scroll(function () {
        var scrollTop = $(this).scrollTop();
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
});
