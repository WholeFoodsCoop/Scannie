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

include('/var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include('/var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}
include('/var/www/html/git/fannie/admin/labels/pdf_layouts/WFC_New.php');
include('/var/www/html/scancoord/WFC_New_Denfeld_Init_Gen.php');

echo "--beginning of page drawn--<br><br>";

$description = 'Create sets of tags separated by physical location. This page was 
    originally used to create all the shelftags when opening our Denfeld location. 
    There is no UI for this page, all parameters must be manually set.';

    
include('../config.php');    

$dbc = mysql_connect($SCANHOST, $SCANUSER, $SCANPASS);
mysql_select_db($SCANDB, $dbc);

$tags = array(
    'description' => array(),
    'department' => array(),
    'isle' => array(),
    'subIsle' => array(),
    'upc' => array(),
    'pricePerUnit' => array(),
    'brand' => array(),
    'units' => array(),
    'sku' => array(),
    'vendor' => array(),
    'normal_price' => array(),
    'size' => array(),
    'scale' => array()
);

$query = "SELECT 
        p.upc, 
        p.description, 
        p.department,
        p.normal_price,
        p.brand,
        p.unitofmeasure,
        CASE WHEN vi.size is null then p.size ELSE vi.size END as size,
        vi.sku,
        v.vendorName,
        vi.units,
        p.scale
    FROM products AS p
        LEFT JOIN vendors AS v ON v.vendorID=p.default_vendor_id
        LEFT JOIN vendorItems AS vi ON vi.upc=p.upc AND vi.vendorID=p.default_vendor_id
    WHERE p.upc=0009511381911
		AND p.store_id=2
    ;";


/*
 WHERE store_id=2
        AND inUse=1
        AND department NOT BETWEEN 508 AND 998
        AND department NOT BETWEEN 250 AND 259
        AND department NOT BETWEEN 225 AND 234
        AND department NOT BETWEEN 1 AND 25
        AND department NOT BETWEEN 61 AND 78
        AND department != 46
        AND department != 150
        AND department != 208
        AND department != 235
        AND department != 240
        AND department != 500
*/    

$result = mysql_query($query, $dbc);
$isleEndMark = 0;
$isleEndCheck = 0;
while ($row = mysql_fetch_assoc($result)) {

        $tags[$row['upc']]['description'] = $row['description'];
        $tags[$row['upc']]['department'] = $row['department'];
        $tags[$row['upc']]['pricePerUnit'] = COREPOS\Fannie\API\lib\PriceLib::pricePerUnit($row['normal_price'], 
            $row['size']);
        $tags[$row['upc']]['normal_price'] = $row['normal_price'];
        $tags[$row['upc']]['brand'] = $row['brand'];
        $tags[$row['upc']]['units'] = $row['units'];
        $tags[$row['upc']]['size'] = $row['size'];
        $tags[$row['upc']]['sku'] = $row['sku'];
        $tags[$row['upc']]['upc'] = $row['upc'];
        $tags[$row['upc']]['vendor'] = $row['vendorName'];
        $tags[$row['upc']]['scale'] = $row['scale'];
  
        if ($tags[$row['upc']]['department'] == 154) {
            $tags[$row['upc']]['isle'] = 1;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 180) {
            $tags[$row['upc']]['isle'] = 1;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 242) {
            $tags[$row['upc']]['isle'] = 1;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 246 ) {
            $tags[$row['upc']]['isle'] = 1;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 14) {
            $tags[$row['upc']]['isle'] = 1;
            $tags[$row['upc']]['subIsle'] = 5;
        } elseif ($tags[$row['upc']]['department'] == 188) {
            $tags[$row['upc']]['isle'] = 1;
            $tags[$row['upc']]['subIsle'] = 6;
        } elseif ($tags[$row['upc']]['department'] == 189) {
            $tags[$row['upc']]['isle'] = 1;
            $tags[$row['upc']]['subIsle'] = 7;
        }
        
        if ($tags[$row['upc']]['department'] == 168) {
            $tags[$row['upc']]['isle'] = 2;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 164 ) {
            $tags[$row['upc']]['isle'] = 2;
            $tags[$row['upc']]['subIsle'] = 2 ;
        } elseif ($tags[$row['upc']]['department'] == 153) {
            $tags[$row['upc']]['isle'] = 2;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 155) {
            $tags[$row['upc']]['isle'] = 2;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 176) {
            $tags[$row['upc']]['isle'] = 2;
            $tags[$row['upc']]['subIsle'] = 5;
        } elseif ($tags[$row['upc']]['department'] == 151) {
            $tags[$row['upc']]['isle'] = 2;
            $tags[$row['upc']]['subIsle'] = 6;
        } elseif ($tags[$row['upc']]['department'] == 245) {
            $tags[$row['upc']]['isle'] = 2;
            $tags[$row['upc']]['subIsle'] = 7;
        } elseif ($tags[$row['upc']]['department'] == 190) {
            $tags[$row['upc']]['isle'] = 2;
            $tags[$row['upc']]['subIsle'] = 8;
        }
        
        if ($tags[$row['upc']]['department'] == 167) {
            $tags[$row['upc']]['isle'] = 3;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 161) {
            $tags[$row['upc']]['isle'] = 3;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 156) {
            $tags[$row['upc']]['isle'] = 3;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 159) {
            $tags[$row['upc']]['isle'] = 3;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 172) {
            $tags[$row['upc']]['isle'] = 3;
            $tags[$row['upc']]['subIsle'] = 5;
        }
        
        if ($tags[$row['upc']]['department'] == 177) {
            $tags[$row['upc']]['isle'] = 4;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 170) {
            $tags[$row['upc']]['isle'] = 4;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 173) {
            $tags[$row['upc']]['isle'] = 4;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 175) {
            $tags[$row['upc']]['isle'] = 4;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 174) {
            $tags[$row['upc']]['isle'] = 4;
            $tags[$row['upc']]['subIsle'] = 5;
        } elseif ($tags[$row['upc']]['department'] == 171) {
            $tags[$row['upc']]['isle'] = 4;
            $tags[$row['upc']]['subIsle'] = 6;
        } elseif ($tags[$row['upc']]['department'] == 160) {
            $tags[$row['upc']]['isle'] = 4;
            $tags[$row['upc']]['subIsle'] = 7;
        } elseif ($tags[$row['upc']]['department'] == 169) {
            $tags[$row['upc']]['isle'] = 4;
            $tags[$row['upc']]['subIsle'] = 8;
        } elseif ($tags[$row['upc']]['department'] == 173) {
            $tags[$row['upc']]['isle'] = 4;
            $tags[$row['upc']]['subIsle'] = 9;
        }
        
        if ($tags[$row['upc']]['department'] == 179) {
            $tags[$row['upc']]['isle'] = 5;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 155) {
            $tags[$row['upc']]['isle'] = 5;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 158) {
            $tags[$row['upc']]['isle'] = 5;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 162) {
            $tags[$row['upc']]['isle'] = 5;
            $tags[$row['upc']]['subIsle'] = 4;
        }
        
        if ($tags[$row['upc']]['department'] == 157) {
            $tags[$row['upc']]['isle'] = 6;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 163) {
            $tags[$row['upc']]['isle'] = 6;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 166) {
            $tags[$row['upc']]['isle'] = 6;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 165) {
            $tags[$row['upc']]['isle'] = 6;
            $tags[$row['upc']]['subIsle'] = 4;
        }
        
        if ($tags[$row['upc']]['department'] == 182) {
            $tags[$row['upc']]['isle'] = 7;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 181) {
            $tags[$row['upc']]['isle'] = 7;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 183) {
            $tags[$row['upc']]['isle'] = 7;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 152) {
            $tags[$row['upc']]['isle'] = 7;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 187) {
            $tags[$row['upc']]['isle'] = 7;
            $tags[$row['upc']]['subIsle'] = 5;
        } elseif ($tags[$row['upc']]['department'] == 239) {
            $tags[$row['upc']]['isle'] = 7;
            $tags[$row['upc']]['subIsle'] = 6;
        }
        
        /* Wellness tags are much less easy to 
         * categorize. There are several items
         * in the same departmnet but located
         * on different shelves */
        if ($tags[$row['upc']]['department'] == 88) {
            $tags[$row['upc']]['isle'] = 8;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 96) {
            $tags[$row['upc']]['isle'] = 8;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 98) {
            $tags[$row['upc']]['isle'] = 8;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 97) {
            $tags[$row['upc']]['isle'] = 8;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 100) {
            $tags[$row['upc']]['isle'] = 8;
            $tags[$row['upc']]['subIsle'] = 5;
        } elseif ($tags[$row['upc']]['department'] == 91) {
            $tags[$row['upc']]['isle'] = 8;
            $tags[$row['upc']]['subIsle'] = 6;
        } elseif ($tags[$row['upc']]['department'] == 95) {
            $tags[$row['upc']]['isle'] = 8;
            $tags[$row['upc']]['subIsle'] = 7;
        } elseif ($tags[$row['upc']]['department'] == 99) {
            $tags[$row['upc']]['isle'] = 8;
            $tags[$row['upc']]['subIsle'] = 8;
        }
        
        if ($tags[$row['upc']]['department'] == 101) {
            $tags[$row['upc']]['isle'] = 9;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 104) {
            $tags[$row['upc']]['isle'] = 9;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 102) {
            $tags[$row['upc']]['isle'] = 9;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 94) {
            $tags[$row['upc']]['isle'] = 9;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 90) {
            $tags[$row['upc']]['isle'] = 9;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 87) {
            $tags[$row['upc']]['isle'] = 9;
            $tags[$row['upc']]['subIsle'] = 5;
        } elseif ($tags[$row['upc']]['department'] == 86) {
            $tags[$row['upc']]['isle'] = 9;
            $tags[$row['upc']]['subIsle'] = 6;
        } elseif ($tags[$row['upc']]['department'] == 103) {
            $tags[$row['upc']]['isle'] = 9;
            $tags[$row['upc']]['subIsle'] = 7;
        }
        
        /*  Isle 10 also includes several items from 
         *  department #101 */
        if ($tags[$row['upc']]['department'] == 93) {
            $tags[$row['upc']]['isle'] = 10;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 89) {
            $tags[$row['upc']]['isle'] = 10;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 105) {
            $tags[$row['upc']]['isle'] = 10;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 106) {
            $tags[$row['upc']]['isle'] = 10;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 108) {
            $tags[$row['upc']]['isle'] = 10;
            $tags[$row['upc']]['subIsle'] = 5;
        } elseif ($tags[$row['upc']]['department'] == 109) {
            $tags[$row['upc']]['isle'] = 10;
            $tags[$row['upc']]['subIsle'] = 6;
        }
        
        if ($tags[$row['upc']]['department'] == 39) {
            $tags[$row['upc']]['isle'] = 11;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 41) {
            $tags[$row['upc']]['isle'] = 11;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 42) {
            $tags[$row['upc']]['isle'] = 11;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 38) {
            $tags[$row['upc']]['isle'] = 11;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 40) {
            $tags[$row['upc']]['isle'] = 11;
            $tags[$row['upc']]['subIsle'] = 5;
        } elseif ($tags[$row['upc']]['department'] == 44) {
            $tags[$row['upc']]['isle'] = 11;
            $tags[$row['upc']]['subIsle'] = 6;
        } elseif ($tags[$row['upc']]['department'] == 45) {
            $tags[$row['upc']]['isle'] = 11;
            $tags[$row['upc']]['subIsle'] = 7;
        }
        
        if ($tags[$row['upc']]['department'] == 260) {
            $tags[$row['upc']]['isle'] = 12;
            $tags[$row['upc']]['subIsle'] =1 ;
        } elseif ($tags[$row['upc']]['department'] == 261) {
            $tags[$row['upc']]['isle'] = 12;
            $tags[$row['upc']]['subIsle'] = 2;
        }
        
        if ($tags[$row['upc']]['department'] == 30) {
            $tags[$row['upc']]['isle'] = 13;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 32) {
            $tags[$row['upc']]['isle'] = 13;
            $tags[$row['upc']]['subIsle'] = 2;
        } elseif ($tags[$row['upc']]['department'] == 29) {
            $tags[$row['upc']]['isle'] = 13;
            $tags[$row['upc']]['subIsle'] = 3;
        } elseif ($tags[$row['upc']]['department'] == 27) {
            $tags[$row['upc']]['isle'] = 13;
            $tags[$row['upc']]['subIsle'] = 4;
        } elseif ($tags[$row['upc']]['department'] == 37) {
            $tags[$row['upc']]['isle'] = 13;
            $tags[$row['upc']]['subIsle'] = 5;
        } elseif ($tags[$row['upc']]['department'] == 26) {
            $tags[$row['upc']]['isle'] = 13;
            $tags[$row['upc']]['subIsle'] = 6;
        } elseif ($tags[$row['upc']]['department'] == 28) {
            $tags[$row['upc']]['isle'] = 13;
            $tags[$row['upc']]['subIsle'] = 7;
        } elseif ($tags[$row['upc']]['department'] == 35) {
            $tags[$row['upc']]['isle'] = 13;
            $tags[$row['upc']]['subIsle'] = 8;
        }
        
        if ($tags[$row['upc']]['department'] == 31) {
            $tags[$row['upc']]['isle'] = 14;
            $tags[$row['upc']]['subIsle'] = 1;
        } elseif ($tags[$row['upc']]['department'] == 34) {
            $tags[$row['upc']]['isle'] = 14;
            $tags[$row['upc']]['subIsle'] = 2;
        }
        
        if ($tags[$row['upc']]['department'] == 186 
            || $tags[$row['upc']]['department'] == 178) {
            $tags[$row['upc']]['isle'] = 99;
            $tags[$row['upc']]['subIsle'] = 1;
        }
        
}
        
        /* Template
        
        if ($tags[$row['upc']]['department'] == ) {
            $tags[$row['upc']]['isle'] = ;
            $tags[$row['upc']]['subIsle'] = ;
        } elseif ($tags[$row['upc']]['department'] == ) {
            $tags[$row['upc']]['isle'] = ;
            $tags[$row['upc']]['subIsle'] = ;
        } elseif ($tags[$row['upc']]['department'] == ) {
            $tags[$row['upc']]['isle'] = ;
            $tags[$row['upc']]['subIsle'] = ;
        } elseif ($tags[$row['upc']]['department'] == ) {
            $tags[$row['upc']]['isle'] = ;
            $tags[$row['upc']]['subIsle'] = ;
        } elseif ($tags[$row['upc']]['department'] == ) {
            $tags[$row['upc']]['isle'] = ;
            $tags[$row['upc']]['subIsle'] = ;
        } elseif ($tags[$row['upc']]['department'] == ) {
            $tags[$row['upc']]['isle'] = ;
            $tags[$row['upc']]['subIsle'] = ;
        }
        */
        
//$sortedTags = array();

$sortedTags = array(
    'normal_price' => array(),
    'description' => array(),
    'brand' => array(),
    'units' => array(),
    'size' => array(),
    'sku' => array(),
    'pricePerUnit' => array(),
    'upc' => array(),
    'vendor' => array(), 
    'scale' => array(),
    'isle' => array(),
    'subIsle' => array()
);



$sortedTags = array_msort($tags, array('isle'=>SORT_ASC, 'subIsle'=>SORT_ASC));

$sortedTagsMarked = array();

$i = 0;
$isleCheck = 0;
$subIsleCheck = 0;
foreach ($sortedTags as $key => $row) {
    
    /*  This chunk commented out to prevent sorting tags from being generated.
     *  un-comment if you would like to create Isle / Shelf location tags.
     *
    if($isleCheck != $row['isle']) {
        $i++;
        $sortedTagsMarked[$i]['upc'] = 1234567890123;
        $sortedTagsMarked[$i]['description'] = "TAGS FOR ISLE " . $row['isle'];
    }
    $isleCheck = $row['isle'];
    $i++;
    
    if($subIsleCheck != $row['subIsle']) {
        $i++;
        $sortedTagsMarked[$i]['upc'] = 1234567890123;
        $sortedTagsMarked[$i]['description'] = "New Section of Isle " . $row['isle'] . " : " . $row['subIsle'];
    }
    $subIsleCheck = $row['subIsle'];
    */
    $i++;
    
    $sortedTagsMarked[$i]['normal_price'] = $row['normal_price'];
    $sortedTagsMarked[$i]['description'] = $row['description'];
    $sortedTagsMarked[$i]['brand'] = $row['brand'];
    $sortedTagsMarked[$i]['units'] = $row['units'];
    $sortedTagsMarked[$i]['size'] = $row['size'];
    $sortedTagsMarked[$i]['sku'] = $row['sku'];
    $sortedTagsMarked[$i]['pricePerUnit'] = $row['pricePerUnit'];
    $sortedTagsMarked[$i]['upc'] = $row['upc'];
    $sortedTagsMarked[$i]['vendor'] = $row['vendor'];
    $sortedTagsMarked[$i]['scale'] = $row['scale'];
    $sortedTagsMarked[$i]['isle'] = $row['isle'];
    $sortedTagsMarked[$i]['subIsle'] = $row['subIsle'];
}


/*
foreach ($sortedTags as $key => $row) {
    $data[$key]['normal_price'] = $row['price'];
    $data[$key]['description'] = $row['description'];
    $data[$key]['brand'] = 
    
    
}
*/

/*
echo "<table>
    <th></th>
    ";
foreach ($sortedTags as $key => $value) {
        echo "<tr><td>" . $key . "</td>";
        echo "<td>" . $tags[$key]['description'] . "</td>";
        echo "<td>" . $tags[$key]['department'] . "</td>";
        echo "<td>" . $tags[$key]['isle'] . "</td>";
        echo "<td>" . $tags[$key]['subIsle'] . "</tr>";
        $count++;   
}
echo "</table>";
echo $count;
*/

WFC_New($sortedTagsMarked, $offset=0);

echo "<br>--end of page drawn--";

function array_msort($array, $cols)
{
    
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
    }
    $eval = 'array_multisort(';
    foreach ($cols as $col => $order) {
        $eval .= '$colarr[\''.$col.'\'],'.$order.',';
    }
    $eval = substr($eval,0,-1).');';
    eval($eval);
    $ret = array();
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            $k = substr($k,1);
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
    }
    return $ret;

}
