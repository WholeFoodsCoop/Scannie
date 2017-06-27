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
include('../config.php');
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}
class getProcesslist
{
    
    public function getResponse()
    {           
        
        //Id,User,Host,db,Command,Time,State,Info
        
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $prep = $dbc->prepare('show processlist');
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            foreach ($row as $v) {
                if ($_GET['liveList'] == TRUE) {
                    $ret .= $v . '<span style="color: #cacaca; padding: 1px;"> | </span> ';
                } elseif ($_GET['logList'] == TRUE) {
                    if ($row['Command'] == 'Query' && $row['Info'] != 'show processlist') {
                        $ret .=  $v . '<span style="color: #cacaca; padding: 1px;"> | </span> ';
                    }
                }
            }
            
            if ($_GET['liveList'] == TRUE) {
                $ret .= '<div style="border: 1px solid lightgrey"></div>';
            } elseif ($_GET['logList'] == TRUE && $ret != '') {
                $ret .= '<div style="border: 1px solid #d8e2ed"></div>';
            }
        }
        
        return $ret;
        
    }
    
}
$obj = new getProcesslist;
echo $obj->getResponse();













