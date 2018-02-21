<?php
include('../../config.php');
if (!class_exists('PageLayoutA')) {
    include(dirname(__FILE__).'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(__FILE__).'/../../../common/sqlconnect/SQLManager.php');
}

class Checklist extends PageLayoutA
{
    public function preprocess()
    {
        //$this->addOnloadCommand('alert("hi");');
        $dbc = $this->createConObj();
        if (FormLib::get('newTableName', false)) {
            $this->newTableName_handler($dbc);
        } elseif (FormLib::get('addTableRow', false)) {
            $this->addTableRow_handler($dbc);
        } 
        if (FormLib::get('checkbox', false)) {
            $this->checkbox_handler($dbc);
            die();
        } elseif (FormLib::get('comments', false)) {
            $this->comments_handler($dbc);
            die();
        } elseif (FormLib::get('notes', false)) {
            $this->notes_handler($dbc);
            die();
        }

        $this->displayFunction = $this->pageContent($dbc);
        return false;
    }

    private function notes_handler($dbc)
    {
        $text = FormLib::get('text');
        $json = array();
        $args = array($text);
        $prep = $dbc->prepare("UPDATE checklistText SET text = ?");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            $json['error'] = $er;
        }

        echo json_encode($json);
        return false; 
    }

    private function comments_handler($dbc)
    {
        $id = FormLib::get('id');
        $id = ltrim($id, 'p');
        $text = FormLib::get('text');
        $json = array();
        $args = array($text,$id);
        $prep = $dbc->prepare("UPDATE checklists SET comments = ? WHERE id = ?");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            $json['error'] = $er;
        }
        $json['id'] = ($id) ? $id : '';

        echo json_encode($json);
        return false; 
    }

    private function checkbox_handler($dbc)
    {
        $id = FormLib::get('id');
        $id = ltrim($id, 'c');
        $checked = FormLib::get('checked', false);
        $date = FormLib::get('date');
        $json = array();
        $json['date'] = $date;

        if ($checked == 'true') {
            $args = array($date,$id);
            $prep = $dbc->prepare("UPDATE checklists SET Date = ? WHERE id = ?");
        } else {
            $args = array($id);
            $prep = $dbc->prepare("UPDATE checklists SET Date = NULL where id = ?");
        }
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            $json['error'] = $er;
        }
        $json['id'] = ($id) ? $id : '';

        echo json_encode($json);
        return false; 
    }

    private function newTableName_handler($dbc)
    {
        $tableName = FormLib::get('newTableName');
        $args = array($tableName);
        $prep = $dbc->prepare("INSERT INTO checklistTables (tableName) values (?)");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            //echo $er;
        }
        return false;
    }

    private function addTableRow_handler($dbc)
    {
        $tableID = FormLib::get('tableName');
        $description = FormLib::get('description');
        $location = FormLib::get('location');
        
        $args = array($tableID);
        $prep = $dbc->prepare("SELECT MAX(row)+1 AS newRow FROM checklists WHERE tableID = ?;");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $newRow = ($row['newRow']) ? $row['newRow'] : 1;
        }

        $args = array($tableID, $location, $description, $newRow);
        $prep = $dbc->prepare("INSERT INTO checklists (tableID, location, description, row) values (?, ?, ?, ?)");
        $res = $dbc->execute($prep,$args);
        if ($er = $dbc->error()) {
            //echo $er;
        }
        return false;
    }

    public function pageContent($dbc)
    {
        $ret = '';
        include(__DIR__.'/../../config.php');
        $this->addScript('checklist.js');


        $addTable = "
        <div class='container'>
            <h4>Form <button class='btn-default easycopy' data-toggle='collapse' data-target='#forms'> +/- </button></h4>
        </div>
        <div class='container collapse in' id='forms'>
                <label>Add New Table</label>
                <form name='createTable' method='post' class='form-inline'>
                    <input type='text' class='form-control' name='newTableName' id='newTableName' placeholder='Table Name'>
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <button class='btn btn-info' id='addNewTableName'> + </button>
                </form> 
                <label>Add Row to Table</label>
                <form name='addTableRow' method='post' class='form-inline'>
                    <input type='text' class='form-control' name='tableName' id='tableName' placeholder='Table Name'>
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <input type='text' class='form-control' name='description' id='description' placeholder='Description'>
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <input type='text' class='form-control' name='location' id='location' placeholder='Store ID'>
                    <div class='spacer hidden-sm hidden-md hidden-xs'></div>
                    <button class='btn btn-primary' id='addTableRow' name='addTableRow' value='1'> + </button>
            </form> 
        </div>";

        $ret .= $this->getTables($dbc);
        $ret .= $this->getNotes($dbc);

        return <<<HTML
<div id="ajaxResp"></div>
$addTable
$ret
HTML;
    }

    private function getNotes($dbc) 
    {
        $prep = $dbc->prepare("SELECT text from checklistText");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $notes = $row['text'];
        }
        return <<<HTML
<div align="center">
    <div class="notesContainer">
        <form name="notes" id="notesForm" method="post" class="form-inline">
            <textarea id='notes' value='{$notes}' class=''>$notes</textarea> 
        </form>
    </div>
</div>
HTML;
    }

    private function getTables($dbc)
    {
        $ret = '';
        $tables = array();
        $prep = $dbc->prepare("SELECT * from checklistTables ORDER BY id ASC");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $tables[$row['id']] = $row['tableName'];
        }

        $tableData = array();
        $prep = $dbc->prepare("SELECT * FROM checklists");
        $res = $dbc->execute($prep);
        $fields = array('Date','location','description','comments','inUse','tableID','id');
        while ($row = $dbc->fetchRow($res)) {
            foreach ($fields as $field) {
                        $tableData[$row['tableID']][$row['row']][$field] = $row[$field];
            }
        }

        foreach ($tables as $id => $table) {
            $ret .= "<div align='center' class='tableContainer'>
                <table class='table table-condensed small'>
                <th class='text-center' colspan='5'><input class='easycopy' value='$table' readonly><a data-toggle='collapse' data-target='#table$id'>+/-</a></th>
                <tbody id='table$id' class='collapse-in'>";

            foreach ($tableData as $tablename => $row) {
                foreach ($row as $rowNum) {
                    $tableID = $rowNum['tableID'];
                    if ($tableID === $table) {
                        $description = $rowNum['description'];
                        $location = $rowNum['location'];
                        if ($location == 0) {
                            $location = 'Both';
                        } elseif ($location == '1') {
                            $location = 'Hillside';
                        } else {
                            $location = 'Denfeld';
                        }
                        $Date = $rowNum['Date'];
                        $id = $rowNum['id'];
                        $comments = $rowNum['comments'];
                        $comments = "<input type='text' value='$comments' class='comments' id='p$id'>";
                        $checked = (is_null($Date)) ? '' : 'checked'; 
                        $ret .= "<tr><td><input type='checkbox' class='check' id='c$id' $checked></td>";
                        $ret .= "<td>$description</td>";
                        $ret .= "<td>$location</td>";
                        $ret .= "<td id='t$id'>$Date</td>";
                        $ret .= "<td>$comments</td>";
                        $ret .= "</tr>";
                    }
                } 
            }
            $ret .= "</tbody></table></div>";
        }
        
        return $ret;
    }

    private function createConObj()
    {
        $dbc = new SQLManager('127.0.0.1', 'pdo_mysql', 'wfc_op', 'phpmyadmin','wfc');
        return $dbc;
    }

    public function javascriptContent()
    {
        return <<<HTML
HTML;
    }

    public function cssContent()
    {
        return <<<HTML
#ajaxResp {
    position: fixed;
    top: 20px;
    right : 20px;
}
.btn {
    font-weight: bold;
}
.notesContainer {
    max-width: 800px;
}
.notesForm {
   width: 400px; 
}
#notes {
    min-width: 800px;
    min-height: 250px;
    font-size: 14px;
    border: 1px solid rgba(0,0,0,0);
}
.easycopy {
    border: none;
    background-color: rgba(0,0,0,0);
    text-align: center;
    font-weight: bold;
    font-size: 16px;
    color: rgba(255,255,255,0.5);
}
.spacer {
    width: 5px;
    float: left;
}
.comments {
    width: 100%;
    border: 1px solid transparent;
    background-color: rgba(0,0,0,0.05);
}
.table, .container {
    max-width: 800px;
}
table, th, tr, td, tbody, thead {
    background-color: rgba(0,0,0,0);
    //border: 1px solid rgba(0,0,0,0.2);
}
th {
    background-color: rgba(0,0,0,0.3);
    color: rgba(255,255,255,0.5);
    border-top-right-radius: 5px;
    border-top-left-radius: 5px;
}
.table {
        border-bottom:0px !important;
}
.table th, .table td {
        border: 1px !important;
}
.fixed-table-container {
        border:0px !important;
}
body {
    background-color: rgba(255,255,255,0.9);
    //background: linear-gradient(135deg, orange 50%, tomato);
    background: linear-gradient(135deg, #42a7f4, #0a1528);
    background-repeat: no-repeat;
    background-attachment: fixed;

}
a {
    cursor: pointer;
}
input > .form-control {
    background-color: rgba(255,255,255,0.3);
}
textarea {
    background-color: rgba(255,255,255,0.2);
}
HTML;
    }

}
WebDispatch::conditionalExec();
