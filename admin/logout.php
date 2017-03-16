<?php session_start(); ?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../common/bootstrap/bootstrap.min.css">
  <script src="../common/bootstrap/jquery.min.js"></script>
  <script src="../common/bootstrap/bootstrap.min.js"></script>
<style media="screen">

<?php
include('../common/sqlconnect/SQLManager.php');

class logout
{
    public function run()
    {
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        
        return header('location: http://192.168.1.2/scancoord/admin/login.php');
    }
}

$obj = new logout;
echo $obj->run();