var slider = true;

$(document).ready(function(){
    //alert("JS add file test successful.")
});

function divslider() {
    $('div.divSlider div').css({opacity: 0.0});
    $('div.divSlider div:first').css({opacity: 1.0});
    $('#btnNext').click(function(slider) {
        slider = false;
        change();
        if (slider == false) {
            clearInterval(intervalID);
        }  
    });
    var intervalID = setInterval('change()',5000);
}

function change() {
    var current = ($('div.divSlider div.show')? $('div.divSlider div.show') : $('div.divSlider div:first'));
    if ( current.length == 0 ) current = $('div.divSlider img:first');
    var next = ((current.next().length) ? ((current.next().hasClass('show')) ? $('div.divSlider div:first') :current.next()) : $('div.divSlider div:first'));
    next.css({opacity: 0.0})
        .addClass('show')
        .animate({opacity: 1.0}, 1000);
    current.animate({opacity: 0.0}, 1000)
        .removeClass('show');
};

$(document).ready(function() {
    divslider();
    $('div.divSlider').fadeIn(1000); // works for all the browsers other than IE
    $('div.divSlider div').fadeIn(1000); // IE tweak
});

function slideshow()
{
    ids = [];
    $('.social').each(function() {
        var id = $(this).attr('id');
        ids.push(id);
    });
    $.each(ids, function(index,value) {
        if ( $('#'+value).is(':visible') ) {
            var curid = value;
            nextid = increment_last(value);
            alert(nextid);
        }
    });    
    $('#'+curid).removeClass('show').addClass('hide');
    $('#'+nextid).removeClass('hide').addClass('show');
    alert(nextid);
}

function increment_last(v) {
    return v.replace(/[0-9]+(?!.*[0-9])/, function(match) {
        return parseInt(match, 10)+1;
    });
}
