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
class AuditScannerReportAjax
{
    private function deleteRow()
    {
        $store_id = $_POST['store_id'];
        $username = $_POST['username'];
        $upc = substr($_POST['upc'], 3);
        $rowclicked = $_POST['rowclicked'];

        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $args = array($upc,$store_id,$username);
        $prep = $dbc->prepare('DELETE FROM woodshed_no_replicate.AuditScanner WHERE upc = ? AND store_id = ? AND username = ?');
        $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            echo '<div class="alert alert-danger">'.$er.'</div>';
        }
        //echo 'affected rows: '.$dbc->affectedRows(); return 0 rows when a row is deleted...

        return false;
    }

    public function run()
    {
        if ($_POST['deleteRow'] == true) {
            return $this->deleteRow();
        }

        return true;
    }
}
$obj = new AuditScannerReportAjax();
$obj->run();