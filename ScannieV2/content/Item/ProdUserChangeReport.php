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
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
class ProdUserChangeReport extends WebDispatch 
{
    
    protected $title = "Product Changes by Date & User";
    protected $description = "[Product Changes by D&U] Find products 
        modified by user for a given timespan.";
    
    public function body_content()
    {           
        $ret = '';
        include(__DIR__.'/../../config.php');
        $dbc = ScanLib::getConObj();
        
        $ret .= "<h4>".$this->title."</h4>";
        $ret .= $this->form_content($dbc);
        $fromdate = FormLib::get('fromdate');
        $todate = FormLib::get('todate');
        $userid = FormLib::get('user');
        
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
        while ($row = $dbc->fetchRow($result)) {
            $upcs[] = $row['upc'];
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
        
        
        return "<div class=\"container-fluid\" style=\"margin-top: 25px;\">".$ret."</div>";
    }
    
    private function form_content($dbc) 
    {
        $query = $dbc->prepare("select * from Users");
        $result = $dbc->execute($query);
        $user_names = array();
        while ($row = $dbc->fetchRow($result)) {
            if (!in_array($row['uid'],$uids) && $row['name'] != '') {
                $uids[$row['uid']] = $row['name'];
            }
        }
        if ($dbc->error()) echo $dbc->error();
        
        $ret = '';
        $ret .= '<form method="post" class="form-inline">';
        $ret .= '<select name="user" class="form-control">';
        foreach ($uids as $uid => $name) {
            $ret .= '<option value="'.$uid.'"';
            if (FormLib::get('user') == $uid) $ret .= 'selected';
            $ret.= '>'.$name.'</option>';
        }
        $ret .= '</select>&nbsp;';
        $ret .= '<input type="text" class="form-control" name="fromdate" id="fromdate" value="'.FormLib::get('fromdate').'" placeholder="date from">&nbsp;';
        $ret .= '<input type="text" class="form-control" name="todate" id="todate" value="'.FormLib::get('todate').'" placeholder="date to ">&nbsp;';
        $ret .= '<button class="btn btn-default" type="submit">Submit</button>';
        $ret .= '</form>';
        $ret .= '<br />';

        $this->addOnloadCommand("
            $('#fromdate').datepicker({dateFormat: 'yy-mm-dd'});
            $('#todate').datepicker({dateFormat: 'yy-mm-dd'});
        ");
        
        return $ret;
        
    }

    public function helpContent()
    {
        return <<<HTML
<h4>{$this->title}</h4>
<p>Find products edited within a specified range of time by user.</p>
HTML;
    }
    
}
WebDispatch::conditionalExec();
