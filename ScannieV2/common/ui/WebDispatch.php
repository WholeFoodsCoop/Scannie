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
    protected $deviceType = '';
    protected $starttime = NULL;
    protected $loadtime = NULL;

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
        $obj->starttime = microtime(true);
        $obj->draw_page();
    }

    private function draw_page()
    {
        $this->deviceType = $this->getDeviceType();
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
        if ($this->ui === true) 
            echo coreNav::run();
        echo $this->body_content();
        echo $this->footer();
        $diff = microtime(true) - $this->starttime;
        $sec = intval($diff);
        $micro = $diff - $sec;
        $this->loadtime = strftime('%T', mktime(0, 0, $sec)) . str_replace('0.', '.', sprintf('%.3f', $micro));
        echo $this->getHelpContent();
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
        //$this->addScript(__DIR__.'/../lib/javascript/scannie.js');
        if ($this->enable_linea) {
            $this->addScript("http://{$MY_ROOTDIR}/common/lib/javascript/linea/cordova-2.2.0.js");
            $this->addScript("http://{$MY_ROOTDIR}/common/lib/javascript/linea/ScannerLib-Linea-2.0.0.js");
            $this->addScript("http://{$MY_ROOTDIR}/common/lib/javascript/linea/WebHub.js");
            $this->addScript("http://{$MY_ROOTDIR}/common/lib/javascript/linea/core.js");
        }
        if ($this->ui == true) {
            $this->addScript("http://{$MY_ROOTDIR}/common/ui/search.js");
        }
        //if ($this->helpContent()) {
        //    $this->addScript("http://{$MY_ROOTDIR}/common/javascript/helpContent.js");
        //}

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
    <script src="http://{$MY_ROOTDIR}/common/javascript/webDispatch.js"></script>
    <script src="http://{$MY_ROOTDIR}/common/lib/javascript/scannie.js"></script>
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
</html>
HTML;
    }

    public function getHelpContent()
    {
        return <<<HTML
<div class="help-contents" id="help-contents">
    <div class="help-body" id="help-body">
        {$this->helpContent()}
        <div class="row">
            <div class="col-md-8">
            </div>
            <div class="col-md-4">
                <div class="pre">
                    Page drawn in: {$this->loadtime}
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
    }

    protected function helpContent()
    {
        return <<<HTML
Oops! No help contents exist for this page. 
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
        foreach ($_GET as $name => $get) {
            foreach ($this->__routes as $route) {
                if (strpos($route, $name)) {
                    $name = ucfirst($name);
                    if (method_exists($this, 'get'.$name.'Handler')) {
                        $this->{'get'.$name.'Handler'}();
                        die();
                    } elseif (method_exists($this, 'get'.$name.'View')) {
                        $this->displayFunction = $this->{'get'.$name.'View'}();
                    }
                }
            }
        }
        foreach ($_POST as $name => $post) {
            foreach ($this->__routes as $route) {
                if (strpos($route, $name)) {
                    $name = ucfirst($name);
                    if (method_exists($this, 'post'.$name.'Handler')) {
                        $this->{'post'.$name.'Handler'}();
                        die();
                    } elseif (method_exists($this, 'post'.$name.'View')) {
                        $this->displayFunction = $this->{'post'.$name.'View'}();
                    }
                }
            }
        }

        return false;
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

    public function getDeviceType()
    {
        require_once(__DIR__.'/../../common/Mobile-Detect/Mobile_Detect.php');
        $detect = new Mobile_Detect;
        $device = '';
        if ( $detect->isMobile() ) {
        }

        if( $detect->isTablet() ){
        }

        if( $detect->isMobile() && !$detect->isTablet() ){
            $device = 'mobile';
        }

        if( $detect->isiOS() ){
        }

        if( $detect->isAndroidOS() ){
        }

        return $device;
    }

}



