$(document).ready(function() {
    //resizeOnload();
    //resizeInsideLR();
});

function resizeInsideLR()
{
    $(window).on('resize', function() {
        var lh = $('.insideLeft').css('height');
        var rh = $('.insideRight').css('height');
        if (lh != rh) {
            if (lh > rh) {
                $('.insideRight').css({'height' : lh});
            } else {
                $('.insideLeft').css({'height' : rh});
            }
        }
    });    
}

function resizeOnload()
{
    var lh = $('.insideLeft').css('height');
    var rh = $('.insideRight').css('height');
    if (lh != rh) {
        if (lh > rh) {
            $('.insideRight').css({'height' : lh});
        } else {
            $('.insideLeft').css({'height' : rh});
        }
    }
}
