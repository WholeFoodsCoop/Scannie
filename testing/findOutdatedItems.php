<html>
<head>
  <title>Discrepancies</title>
  <link rel="stylesheet" href="../common/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="../common/css/darkpages.css">
  <script src="../common/boostrap/jquery.min.js"></script>
  <script src="../common/boostrap/bootstrap.min.js"></script>
<style>
table, th, tr, td {
  border: none;
}
</style>
</head>
<body>

<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Community Co-op.
    
    This file is a part of Scannie.
    
    Scannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Scannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file LICENSE along with Scannie; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    
*********************************************************************************/
session_start();
include('/var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    //include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
    include('/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}

class multiStoreDiscrepanciesPage
{
    
    protected $title = '';
    protected $description = 'This page finds products that are in use 
        for at least one store but have not sold in a specified amount of time. 
        There is no UI for entering date range.';
    
    private function blank()
    {
        
    }
    
    public function view()
    {        
    
        echo '<div style="position:relative;left:25;">';
        $obj = new multiStoreDiscrepanciesPage();
        echo $obj->description . '<br>';
    
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $prep = $dbc->prepare("
            SELECT p.upc, p.last_sold 
            FROM products AS p 
            WHERE p.last_sold < '2016-01-01' 
                AND store_id = 1 
                AND (SELECT last_sold FROM products WHERE upc=p.upc AND store_id = 2 LIMIT 1) < '2016-01-01' 
                AND (
                    inUse = 1
                    OR (SELECT inUse FROM products WHERE upc=p.upc AND store_id = 2 LIMIT 1) = 1
                );
        ");
        $result = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($result)) {
            $upc = $row[0];
            $link = '<a href="http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc=' . $upc . '&ntype=UPC&searchBtn=" target="_blank">' . $upc . '</a><br>';
            echo $link;
        }
        
        return false;
    }
    
    
}
multiStoreDiscrepanciesPage::view();

