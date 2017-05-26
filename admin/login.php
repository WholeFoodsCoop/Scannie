<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>


<?php
if (!class_exists('SQLManager')) {
    include('../common/sqlconnect/SQLManager.php');
} 

class admin
{
    
    public function run()
    {
        echo $this->header();
        echo $this->view();
    }
    
    private function view()
    {
        
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANALTDB, $SCANUSER, $SCANPASS);
        
        $cur_from_page = basename($_SERVER['HTTP_REFERER']);
        if ($cur_from_page != 'login.php') {
            $_SESSION['from_page'] = basename($_SERVER['HTTP_REFERER']);
            $_SESSION['from_path'] = $_SERVER['HTTP_REFERER'];
        }
        $from_page = $_SESSION['from_page'];
        $from_path = $_SESSION['from_path'];
        
        
        if(!function_exists('hash_equals')) {
            function hash_equals($str1, $str2) {
                if(strlen($str1) != strlen($str2)) {
                    return false;
                } else {
                      $res = $str1 ^ $str2;
                      $ret = 0;
                      for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
                      return !$ret;
                }
            }
        }
        $curUser = $_SESSION['user_name'];
        
        $ret = '';
        $ret .= $this->form_content();
        echo $ret;
        
        $user = array();

        if (isset($_SESSION['notadmin'])) echo 'You must be logged in as admin to access the previous page.';
        if ($_POST['username']) {
            //check if password is correct
            $curUser = $_POST['username'];
            $query = $dbc->prepare("
                SELECT hash
                FROM ScannieAuth
                WHERE name = ?
                LIMIT 1;
            ");
            $result = $dbc->execute($query,$curUser);
            $hash = $dbc->fetchRow($result);
            if ( hash_equals($hash[0], crypt($_POST['pw'], $hash[0])) ) {
                $queryB = $dbc->prepare("SELECT name, type FROM ScannieAuth WHERE name = ? ;");
                $resultB = $dbc->execute($queryB,$curUser);
                while ($row = $dbc->fetchRow($resultB)) {
                    $_SESSION['user_type'] = $row[1];
                    $_SESSION['user_name'] = $row[0];
                }
                if ($dbc->error()) echo $dbc->error();
                echo "<br /><br /><div align='center'>
                    <div class='alert alert-success login-resp'>
                        <strong>".$curUser."</strong> successfully logged in.";
                echo '</div>';
                echo $this->jsRedirect();
            } else {
                echo "<br /><br /><div align='center'><div class='alert alert-danger login-resp' >Username or Password is incorrect</div></div>";
            }
            /*
            if ($dbc->numRows($resultB) == 0) {
                echo '<div class="alert alert-danger">User does not exist.</div>';
            } else {

            }*/
            // echo '<h1>'.$_SESSION['user_name'].'</h1>';
            // echo '<h1>'.$_SESSION['user_type'].'</h1>';
            // echo '<h1>'.basename($_SERVER['HTTP_REFERER']).'</h1>';
        }

    }
    
    private function form_content()
    {
        include('../config.php');
        include('../common/lib/scanLib.php');
        $ret = '';
        if ($ipod = scanLib::isDeviceIpod()) {
            $width = 'width: 90vw;';
        } else {
            $width = '';
        }
        $ret .= '
            <style>
                h2.login {
                    text-shadow: 1px 1px grey;
                }
            </style>
        ';
        $ret .= '
            <div class="login-form" align="center" style="'.$width.'">
                <form method="post" class="form-inline">
                    <h2 class="login">Scannie Login</h2><br /><br />
                    <label style="width:120px">Username:</label>
                        <input type="text" name="username" class="form-control" style="max-width: 200px;" autofocus><br><br>
                    <label style="width:120px">Password:</label>
                        <input type="password" name="pw" class="form-control" style="max-width: 200px;"><br><br><br><br>
                        <input type="submit" value="LOG IN" class="btn btn-default btn-login" style="width: 150px; "><br /><br /><br /> 
                    <a class="" href="'.$SCANWEBPATH.'/misc/mobile.php" style="background: rgba(155,155,155,.9); border: 1px solid grey; padding: 5px;">Mobile Menu</a><br />
                </form>
                
            </div>
        ';
        
        return $ret;
    }
    
    private function header()
    {
        ob_start()?>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../common/bootstrap/bootstrap.min.css">
  <!-- <link rel="stylesheet" href="../common/css/Scannie_css.css"> -->
  <script src="../common/bootstrap/jquery.min.js"></script>
  <script src="../common/bootstrap/bootstrap.min.js"></script>
  <script src="../common/javascript/jquery.cookie.js"></script>
<style media="screen">
html,body {
    display:table;
    width:100%;
    height:100%;
    margin:0;
 }
body {
    display:table-cell;
    vertical-align:middle;
    background-image: url('../common/src/img/bricks.png');
 }
.login-form {
    display:block;
    width: 400px;
    border-radius: 5px;
    margin:auto;
    box-shadow:0.7vw 0.7vw 0.7vw #272822;
    background: linear-gradient(lightgrey,white);
    opacity: 0.9;
    padding: 20px;
    color: black;
}
.login-resp {
    width:400px;
}
.btn-login {
    border: 2px solid lightblue;
    width: 170px;
}

</style>
</head>
        <?php
        return ob_get_clean();
    }
    
    private function jsRedirect()
    {
        $prevUrl = $_SESSION['prevUrl'];
        return '
<script type="text/javascript">
$(document).ready( function () {
    window.location.replace( "'.$prevUrl.'" );
});
</script>
        ';
        
    }
    
}

$obj = new admin();
$obj->run();