<?php 
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}

class Template extends PageLayoutA 
{
    
    protected $title = "Find Purchase Orders";
    protected $ui = TRUE;

    public function __construct()
    {
    }

    public function preprocess()
    {
        $this->displayFunction = $this->view();

        return false;
    }

    private function formContent()
    {

        return <<<HTML
HTML;
    }

    private function view()
    {

        return <<<HTML
hi
HTML;
    }

    public function cssContent()
    {
        return <<<HTML
HTML;
    }

}
WebDispatch::conditionalExec();
