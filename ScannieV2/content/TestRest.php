<?php
include(__DIR__.'/../config.php');
if (!class_exists('BaseRESTfulPage')) {
    include(__DIR__.'/../common/ui/BaseRESTfulPage.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../common/sqlconnect/SQLManager.php');
}

class TestRest extends BaseRESTfulPage
{

    public function body_content()
    {
        $ret = '';
        include(__DIR__.'/../config.php');

        return <<<HTML
<p>Hi!</p>
{$this->form_content()}
HTML;
    }

    public function get_id_view()
    {
        return <<<HTML
<p>RESTful routing works!</p>
HTML;
    }

    public function form_content()
    {
        return <<<HTML
<form method="get">
    <input type="text" name="id" autofocus>
    <input type="submit" value="submit">
</form>
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
$(document).ready(function(){
    //alert("This is the default alert. Welcome to the home page.")
});
HTML;
    }

    public function cssContent()
    {
        return <<<HTML
HTML;
    }

}
WebDispatch::conditionalExec();
