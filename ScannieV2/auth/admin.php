<?php
/*
*
*   @class admin: a class to manage/administer user accounts. 
*
*
*/

class admin
{
    public function addUser($username,$pw,$email,$fn,$ln,$ad1,$ad2,$ct,$st,$zip,$ph,$dbc)
    
    {
        
        //include('../../config.php');
        //$dbc = new SQLManager($MYHOST, 'pdo_mysql', $MYDB, $MYUSER, $MYPASS);
        
        $cost = 10;
        $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
        $salt = sprintf("$2a$%02d$", $cost) . $salt;
        $hash = crypt($pw, $salt);
        
        $prep = $dbc->prepare("SELECT name FROM users WHERE name = ?");
        $res = $dbc->execute($prep,$username);
        $accountExists = $dbc->fetch_row($res);
        if ($dbc->error()) $ret .=  $dbc->error();
        
        $ret = '';
        if (isset($accountExists[0])) {
            $ret .= "An account with that username already exists.";
        } else {
            $args = array($username,$email,$hash,$fn,$ln,$ad1,$ad2,$ct,$st,$zip,$ph);
            $prep = $dbc->prepare("INSERT INTO users (name, email, type, hash, 
                firstName, lastName, address, address2, city, state, zip, phone) 
                VALUES (?, ?, '1', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $dbc->execute($prep,$args);
            if ($dbc->error()) {
                $ret .=  "Error:" . $dbc->error();
            } else {
                $ret .= "<div class='alert alert-success'>Account successfully created for <strong>".$username."<strong></div>";
                $ret .= '<a href="http://localhost/local_html/website(1)_store/accounts/accounts.php?login">Go to login page</a>';
            }
            
        }
        
        return $ret;
    }
    
    public function hash_equals($str1, $str2) {
        if(strlen($str1) != strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;
            for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
            
            return !$ret;
        }
    }
    
    public function login($username, $pw, $dbc) {
        
        /*
        *   Remove echoing/
        */
        
        $ret = '';
        $prep = $dbc->prepare("
            SELECT hash
            FROM users
            WHERE name = ?
            LIMIT 1;
        ");
        $res = $dbc->execute($prep,$username);
        $hash = $dbc->fetch_row($res);
        if (self::hash_equals($hash[0], crypt($pw, $hash[0]))) {
            $query = $dbc->prepare("SELECT name, type FROM users WHERE name = ?;");
            $result = $dbc->execute($query,$username);
            while ($row = $dbc->fetch_row($result)) {
                $_SESSION['user_type'] = $row['type'];
                $_SESSION['user_name'] = $row['name'];
            }
            if ($dbc->error()) $ret .=  "Error:" . $dbc->error();
            echo '<div class="container"><div class="alert alert-success">'.$username.' successfully logged in.</div></div>';
        } else {
            $ret .= "Username or Password is incorrect";
        }
        
        return $ret;
        
    }
    
    public function redirect()
    {
        
    }

    public function createHash($pw)
    {
        $cost = 10;
        $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
        $salt = sprintf("$2a$%02d$", $cost) . $salt;
        return $hash = crypt($pw, $salt);
    }
}
