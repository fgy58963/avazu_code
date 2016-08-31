$(function(){
    $('input.numeric').keyup(function(event){
        this.value = this.value.replace(/\D+/, '');
    });
    
    //自动绑定日期控件
    $('input.datepicker').live('focus', function() {
        var $t = $(this);
        if ($t.data('datepicker')) {
            return;
        }
        $t.data('datepicker', 1);
        $t.datepicker({
            onSelect: function(){
                if ($t.prev().is('.input-bg')) {
                    $t.prev().hide();
                }
            }
        });
    });    

    (function(){
        var $popup_msg = $('#popup-msg'), hideTimer = null, hideInterval = 10000,
        minShowTime = 500, startTime = 0;
        function popup_msg(msg, type)
        {
            type = type || 'error';
            $popup_msg.html(msg).show();
            $popup_msg.attr('class', type);
            var left = ($(window).width() - $popup_msg.attr('offsetWidth')) / 2;
            $popup_msg.css('left', left);//.hide().slideDown();
            startTime = + new Date;
            hideTimer = setTimeout(function(){ hide_msg() }, hideInterval);
        }
        
        function hide_msg()
        {
            if (hideTimer) {
                window.clearTimeout(hideTimer);
                hideTimer = null;
            }
            var showTime = + new Date - startTime;
            if (showTime < minShowTime) {
                hideTimer = setTimeout(function() { hide_msg() }, minShowTime - showTime);
                return;
            }
            $popup_msg.hide();
        }
        window.popup_msg = popup_msg;
        window.hide_popup_msg = hide_msg;
    })();

});
