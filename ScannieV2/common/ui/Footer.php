<?php
include(__DIR__.'/../../config.php');
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../../content/PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../sqlconnect/SQLManager.php');
}
class Footer extends CoreNav
{

    public function body_content()
    {
        $ret = '';
        include(__DIR__.'/../../config.php');

        return <<<HTML
<div>footer</div>
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
$(document).ready(function(){
    //alert("Footer javascript content successful.");
});
HTML;
    }

    public function cssContent()
    {
        return <<<HTML
.test {
    color: tomato;
}
HTML;
    }

}
WebDispatch::conditionalExec();
