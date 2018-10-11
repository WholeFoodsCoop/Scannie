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
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}
class ProdUserChangeReport extends ScancoordDispatch
{
    
    protected $title = "Product Changes by Date & User";
    protected $description = "[Product Changes by D&U] Find all products changed 
        by user for a given range of time.";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $ret .= $this->description."<br /><br />";
        $ret .= $this->form_content($dbc);
        $fromdate = $_POST['fromdate'];
        $todate = $_POST['todate'];
        $userid = $_POST['user']; 
        
        $args = array($fromdate,$todate,$userid);
        $query = $dbc->prepare("
            select pu.* 
            from prodUpdate as pu
                left join products as p on pu.upc=p.upc
            where pu.modified between ? and ?
                and pu.user = ?
            group by pu.upc 
            order by pu.modified
        ");
        $result = $dbc->execute($query,$args);
        $upcs = array();
        $uids = array();
        while ($row = $dbc->fetchRow($result)) {
            $upcs[] = $row['upc'];
            /*
            if (!in_array($row['user'],$uids) && $row['user'] != 0) {
                $uids[] = $row['user'];
            }
            */
        }
        if ($dbc->error()) echo $dbc->error();
        
        $ret .= '<strong>'.count($upcs).'</strong> products were updated from 
            '.$fromdate.' to '.$todate.' by this user. <br />';
        $ret .= '<textarea rows=25 columns=10>';
        foreach ($upcs as $upc) {
            $ret .= $upc . "\r\n";
        }
        $ret .= '</textarea><br />';
        $ret .= '<a href="http://'.$HTTP_HOST.'/git/fannie/item/AdvancedItemSearch.php" target="_BLANK">
            Advanced Search</a><br />';
        
        foreach ($uids as $uid) {
            $ret .= $uid . '<br />';
        }
        
        return $ret;
    }
    
    private function form_content($dbc) 
    {
        $query = $dbc->prepare("select * from Users");
        $result = $dbc->execute($query);
        while ($row = $dbc->fetchRow($result)) {
            if (!in_array($row['uid'],$uids) && $row['real_name'] != '') {
                $uids[$row['uid']] = $row['real_name'];
            }
        }
        if ($dbc->error()) echo $dbc->error();
        
        $ret = '';
        $ret .= '<form method="post" class="form-inline">';
        $ret .= '<select name="user" class="form-control">';
        $user_names = array('Corey Sather','Corrina Rouleau','Sam Hise','Janice Matthews',
            'Ellen Turner','Jim Richardson','Pauline Veatch','Lisa Anderson','Tim Wilson',
            'Erika Osterman','Dean Walczynski','Peter Schulz','Marlene Weikle','Andy Theuninck');
        foreach ($uids as $uid => $name) {
            if (in_array($name,$user_names)) {
                $ret .= '<option value="'.$uid.'"';
                if ($_POST['user'] == $uid) $ret .= 'selected';
                $ret.= '>'.$name.'</option>';
            }
        }
        $ret .= '</select>&nbsp;';
        $ret .= '<input type="text" class="form-control" name="fromdate" value="'.$_POST['fromdate'].'" placeholder="date from">&nbsp;';
        $ret .= '<input type="text" class="form-control" name="todate" value="'.$_POST['todate'].'" placeholder="date to ">&nbsp;';
        $ret .= '<button class="btn btn-default" type="submit">Submit</button>';
        $ret .= '</form>';
        $ret .= '<br />';
        
        return $ret;
        
    }
    
}
ScancoordDispatch::conditionalExec();
