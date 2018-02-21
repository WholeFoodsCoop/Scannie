<?php
session_start();

class DataCollect 
{
    public function run()
    {
        $obj = new DataCollect;
        $obj->getData();
        
        return false;
    }
    
    private function getData()
    {
        $browser = $_SERVER['HTTP_USER_AGENT'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $date = date('Y-m-d');
        $page = basename($_SERVER['PHP_SELF']);
        $from = $_SERVER['HTTP_REFERER'];
        include('../config.php');
        $dbc = new SQLManager($MYHOST, 'pdo_mysql', $MYDB, $MYUSER, $MYPASS);
        
        $args = array($browser,$ip,$date,$page);
        $prepA = $dbc->prepare("SELECT * FROM statTracker 
            WHERE browser = ? AND ip = ? AND date_visited = ? AND page = ?");
        $resA = $dbc->execute($prepA,$args);
        
        if ($dbc->numRows($resA) == 0) {
            $prep = $dbc->prepare("INSERT INTO statTracker (browser,ip,date_visited,page) values ( ?, ?, ?, ?)");
            $dbc->execute($prep,$args);
            $ret .= $dbc->error();
        }
        
        return false;
    }
}


