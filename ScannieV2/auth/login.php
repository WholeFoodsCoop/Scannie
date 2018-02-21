<?php session_start(); ?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doodle Cloud Developers</title>
  <link rel="stylesheet" href="../common/bootstrap/bootstrap.min.css">
  <script src="../common/bootstrap/jquery.min.js"></script>
  <script src="../common/bootstrap/bootstrap.min.js"></script>
  <link rel="stylesheet" href="//securendn.a.ssl.fastly.net/newpanel/css/singlepage.css" />
</head>
<body>
    <div class="page page-comingsoon">
        <h1 style="color: #0095ff">Doodle Cloud Developer<span>s</span></h2>
    </div>
    <div class="login-form" align="center">
        <form method="post" class="form-inline">
                <input type="text" id="un" name="username" class="form-control" style="display: none; max-width: 60vw;"><br><br>
                <input type="password" id="pw" name="pw" class="form-control" style="display: none; max-width: 60vw;"><br><br>
                <input type="submit" id="sm" value="LOG IN" class="btn btn-default btn-login" style="display: none">
        </form>    
    </div>
</body>
</html>
<?php
include('../common/sqlconnect/SQLManager.php');
class admin
{
    public function view()
    {
        if ($username = $_SESSION['user_name']) {
            echo "Hi " . $username . " #" . $_SESSION['user_type'];
        }
        include('../config.php');
        $dbc = new SQLManager($MYHOST, 'pdo_mysql', $MYDB, $MYUSER, $MYPASS);
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
        /*
        $host = "";
        $username = "";
        $password = "";
        $database_name = "";
        */
        $user = array();

        if ($_POST['username']) {
            //check if password is correct
            $curUser = $_POST['username'];
            $query = $dbc->prepare("
                SELECT hash
                FROM users
                WHERE name = ?
                LIMIT 1;
            ");
            $result = $dbc->execute($query,$curUser);
            $hash = $dbc->fetchRow($result);
            if ( hash_equals($hash[0], crypt($_POST['pw'], $hash[0])) ) {
                $queryB = $dbc->prepare("SELECT name, type FROM users WHERE name = ? ;");
                $resultB = $dbc->execute($queryB,$curUser);
                while ($row = $dbc->fetchRow($resultB)) {
                    $_SESSION['user_type'] = $row[1];
                    $_SESSION['user_name'] = $row[0];
                }
                if ($dbc->error()) echo $dbc->error();
                echo "<div align='center'>";
                echo "<br /><br /><div style='max-width: 60vw;'><div class='alert alert-success' 
                    style='width:30vw; min-width:400px; '>You have successfully logged in.
                    <a href='http://www.".$MYROOT."/content/Home/Home.php'>View Website</a></div></div>";
            } else {
                echo "<br /><br /><div align='center'><div class='alert alert-danger' 
                    style='width:30vw; min-width:400px; '>Username or Password is incorrect</div></div>";
            }
            echo "</div>";
        }
        
    }
}

$obj = new admin;
echo $obj->view();
?>
<script type="text/javascript">
$(document).ready(function() {
    function KeyDown(evt) {
        switch (evt.keyCode) {
            case 192:  /* Tilde */
                c = confirm("open p3sb preview?");
                break;
        }
        if (c == true) {
            showForm();
        }
    }
    window.addEventListener('keydown', KeyDown);
    clickS();
});

function clickS()
{
    $('span').click(function() {
        showForm();
    });
}

function showForm() {
    $('#pw').show();
    $('#un').show();
    $('#sm').show();
}

</script>
