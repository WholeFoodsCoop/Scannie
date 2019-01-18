$(function(){
    var elmTypes = ['a', 'button', 'input'];
    $.each(elmTypes, function(index,type) {
        $(type+'[onclick]').each(function(){
            $(this).data('onclick', this.onclick);
            this.onclick = function(event){
                if ($(this).hasClass('click-confirm')) {
                    var text = $(this).attr('data-confirmation');
                    var c = confirm(text);
                    if (c == false) {
                        return false;
                    }
                }
                $(this).data('onclick').call(this, event || window.event);
            }
        });
    });
});

