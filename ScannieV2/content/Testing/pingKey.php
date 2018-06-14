<?php
include('../../config.php');
if (!class_exists('PageLayoutA')) {
    include(dirname(__FILE__).'/../PageLayoutA.php');
}
class pingKey extends PageLayoutA
{
    public function preprocess()
    {
        if (FormLib::get('reload', false)) {
            $this->ajaxResp();
            die();
        } else {
            $this->display_function = $this->body_content();
        }
    }

    private function pingAddr($ip)
    {
        $result = exec("/bin/ping -c 1 -W 1 $ip"); 
        return $result;
    }

    public function ajaxResp()
    {
        $res = $this->pingAddr('192.168.1.2');
        $ret = ($res) ? 'online' : 'offline';
        $sound = '';
        if ($res) {
            $sound = '<div>
<audio id="audio" autoplay>
    <source id="horseSound" src="horse.ogg" type="audio/ogg">
</audio></div>';
        } 
        $more = '<br/><i>ajax success</i>';

        echo $ret.$sound.$more;
        return false;
    }

    public function pingKey()
    {
        $res = $this->pingAddr('192.168.1.2');
        $ret = ($res) ? 'online' : 'offline';

        return <<<HTML
<input type="number" id="attempts" value=0><br/>
<div id="main">
    $ret
</div>
HTML;
    }

    public function body_content()
    {
        return $this->pingKey();
    }

    public function cssContent()
    {
        return <<<HTML
input {
    background-color: rgba(0,0,0,0);
    border: none;
}
.narrow {
    max-width: 250px;
    }
body {
    background-color: rgba(15,15,15,0.1);
}
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
$(function(){
    setInterval(recheck,5000);
});
function recheck()
{
    var attempts = $('#attempts').val();
    var attempts = parseInt(attempts,10) + 1;
    //alert('hi');
    $.ajax({
        type: 'post',
        data: 'reload=true'+'&attempts='+attempts,
        success: function(resp)
        {
            $('#main').html(resp);
            $('#attempts').val(attempts);
        }
    });
}
HTML;
    }
}
WebDispatch::conditionalExec();


