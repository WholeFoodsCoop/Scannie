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
<div align="center">Scannie Version 2.0 Home</div>
<div align="center"><p>Go to <a href="../Scanning/BatchCheck/BatchCheckMenu.php">Batch Check Tools</a></p></div>

<a class="click-confirm" 
    data-confirmation="Are you sure you want an alert to appear?" 
    href="#"
    onclick="alert('hi'); return false;">
    Alert Hi? 
</a>
<button onclick="alert('hello'); return false;" class="click-confirm" data-confirmation="Allow onclick to happen?">Alert Hello</button>
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
p {
    margin-top: 50px;
    font-size: 38px;
    color: white;
    color: rgba(0,0,0,0.2);
}
a {
    color: lightgreen;
}
div {
    font-size: 42px;
    color: rgba(0,0,0,0.2);
}
HTML;
    }

}
WebDispatch::conditionalExec();
