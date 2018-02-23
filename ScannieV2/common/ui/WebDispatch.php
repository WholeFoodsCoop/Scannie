<?php
class WebDispatch
{
    protected $title = 'ScannieV2';
    protected $authByIp;
    protected $onload_commands = array();
    protected $scripts = array();
    protected $start_timestamp = NULL;
    protected $must_authenticate = false;
    protected $current_user = false;
    protected $auth_classes = array();
    protected $enable_linea = false;
    protected $ui = true;

    function __construct() {}

    private function runPage($class)
    {
        if (!class_exists('FormLib')) {
            include(__DIR__.'/../lib/FormLib.php');
        }
        if(!class_exists('scanLib')) {
            include(__DIR__.'/../lib/scanLib.php');
        }
        $obj = new $class();
        $obj->draw_page();
    }

    private function draw_page()
    {

        if (!class_exists('coreNav')) {
            include(__DIR__.'/CoreNav.php');
        }
        if(!class_exists('Footer')) {
            //don't include the footer in scannieV2
            //include(__DIR__.'/Footer.php');
        }

        $this->preflight();
        $this->preprocess();
        echo $this->header();
        echo coreNav::run();
        echo $this->body_content();
        echo $this->footer();
        echo $this->writeJS();
    }

    static public function conditionalExec($custom_errors=true)
    {
        $frames = debug_backtrace();
        if (count($frames) == 1) {
            $page = basename(filter_input(INPUT_SERVER, 'PHP_SELF'));
            $class = substr($page,0,strlen($page)-4);
            if ($class != 'index' && class_exists($class)) {
                self::runPage($class);
            } else {
                trigger_error('Missing class '.$class, E_USER_NOTICE);
            }
        }
    }

    private function preflight()
    {
        // re-rout client to loggin page if user not logged in. 
        if ( WebDispatch::is_session_started() === FALSE ) session_start();
        if (!isset($_SESSION['user_type'])) $_SESSION['user_type'] = null;
        $userType = $_SESSION['user_type'];
        if ($userType != 1) {
            //header('Location: http://www.doodleclouddeveopers.com/auth/login.php');
        }
    }

    private function redirect()
    {
        //header('Location: http://');
    }

    private function header()
    {
        include(__DIR__.'/../../config.php');
        /*
         *  Include DataCollect to collect user data to through.
        include('../common/ui/DataCollect.php');
        $DataCollect = new DataCollect;
        $DataCollect::run();
        */
        if ($this->enable_linea) {
            $this->addScript("http://{$MY_ROOTDIR}/common/lib/javascript/linea/cordova-2.2.0.js");
            $this->addScript("http://{$MY_ROOTDIR}/common/lib/javascript/linea/ScannerLib-Linea-2.0.0.js");
            $this->addScript("http://{$MY_ROOTDIR}/common/lib/javascript/linea/WebHub.js");
            $this->addScript("http://{$MY_ROOTDIR}/common/lib/javascript/linea/core.js");
        }

        return <<<HTML
<html>
<head>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="http://{$MY_ROOTDIR}/common/bootstrap4/css/bootstrap.min.css">
    <link rel="stylesheet" href="http://{$MY_ROOTDIR}/common/css/commonInterface.css">
    <script src="http://{$MY_ROOTDIR}/common/bootstrap/jquery.min.js"></script>
    <script src="http://{$MY_ROOTDIR}/common/javascript/popper.min.js"></script>
    <script src="http://{$MY_ROOTDIR}/common/bootstrap4/js/bootstrap.min.js"></script>
    <script src="http://{$MY_ROOTDIR}/common/ui/CoreNav.js"></script>
    <title>{$this->title}</title>
    <link rel="icon" href="http://{$MY_ROOTDIR}/common/src/icons/scannie_favicon.ico">
<style>
{$this->cssContent()}
</style>
</head>
<body>
HTML;
    }

    private function footer()
    {
       return <<<HTML
</body>
<script type="text/javascript">
{$this->javascriptContent()}
</script>
</html>
HTML;
    }

    protected function cssContent()
    {
    }

    protected function javascriptContent()
    {
    }

    protected function preprocess()
    {
    }
    
    protected function add_script($file_url,$type="text/javascript")
    {
        $this->addScript($file_url, $type);
    }
    
    protected function addScript($file_url, $type='text/javascript')
    {
        $this->scripts[$file_url] = $type;
    }
    
    protected function add_onload_command($str)
    {
        $this->onload_commands[] = $str;    
    }
    protected function addOnloadCommand($str)
    {
        $this->add_onload_command($str);
    }
    
    protected function writeJS()
    {
        foreach($this->scripts as $s_url => $s_type) {
            printf('<script type="%s" src="%s"></script>',
                $s_type, $s_url);
            echo "\n";
        }
        $js_content = $this->javascriptContent();
        if (!empty($js_content) || !empty($this->onload_commands)) {
            echo '<script type="text/javascript">';
            echo $js_content;
            echo "\n\$(document).ready(function(){\n";
            echo array_reduce($this->onload_commands, function($carry, $oc) { return $carry . $oc . "\n"; }, '');
            echo "});\n";
            echo '</script>';
        }
    }

    public function is_session_started()
    {
        if ( php_sapi_name() !== 'cli' ) {
            if ( version_compare(phpversion(), '5.4.0', '>=') ) {
                return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
            } else {
                return session_id() === '' ? FALSE : TRUE;
            }
        }
        return FALSE;
    }

}



