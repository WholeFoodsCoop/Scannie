<?php
//$f = open('file.txt');
$out = "data\r\n";
file_put_contents('file.txt',$out,FILE_APPEND);
