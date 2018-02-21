<?php
include('../../config.php');
if (!class_exists('PageLayoutA')) {
    include(dirname(__FILE__).'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(__FILE__).'/../../../common/sqlconnect/SQLManager.php');
}

class Home extends PageLayoutA
{
    public function preprocess()
    {
        $this->displayFunction = $this->pageContent();

        return false;
    }

    public function pageContent()
    {
        $ret = '';
        include(__DIR__.'/../../config.php');
        $this->addScript('Home.js');
        return <<<HTML
<div>Page Content.</div>
<table class="table table-condensed table-bordered small alert-success table-striped draggable">
    <thead><tr><th>Table Header</th></tr></thead>
    <tbody>
        <tr><td>Is this table</td></tr>
        <tr><td>Bootstrapped?</td></tr>
    </tbody>
</table>
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
HTML;
    }

    public function cssContent()
    {
        return <<<HTML
HTML;
    }

}
WebDispatch::conditionalExec();
