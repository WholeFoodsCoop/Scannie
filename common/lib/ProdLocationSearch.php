<?php

class prodLocationSearch
{
    
    /**
    *   getLocation()
    *   
    *   Returns a floor section ID (isle) based on department and storeID.
    */
    
    public function getLocation($dept,$storeID)
        {   
            //  Hillside
            if ($storeID == 1) {
                //  Suggest physical location of product based on department.
                if ($dept>=1 && $dept<=17) {
                    //return 
                    return 16;
                } elseif ($dept==245) {
                    //bulk 1
                    return 16;
                } elseif ($dept==39 || $dept==40 || $dept==44 || $dept==45 ||
                $dept==260 || $dept==261) {
                    // Cool 1
                    return 11;
                } elseif ($dept==38 || $dept==41 || $dept==42){
                    // Cool 2
                    return 13;
                } elseif ($dept==32 || $dept==35 || $dept==37 || $dept==30) {
                    // Cool 3
                    return 14;
                } elseif ( ($dept>=26 && $dept<=31 && $dept!=30)
                    || ($dept==34 ) ) {
                    // Cool 4
                    return 15;
                } elseif( ($dept>=170 && $dept<=173)
                    || ($dept==160 ) || ($dept==169) ) {
                    // Grocery 1
                    return 1;
                } elseif( ($dept==156) || ($dept==161) || ($dept==163)
                    || ($dept==166) || ($dept==172) || ($dept==174)
                    || ($dept==175) || ($dept==177) ) {
                    // Grocery 2
                    return 2;
                } elseif( ($dept==153) || ($dept==157)
                    || ($dept==164) || ($dept==167) || ($dept==168)
                    || ($dept==176) ) {
                    // Grocery 3
                    return 3;
                } elseif( ($dept==151) || ($dept==152) || $dept==182) {
                    // Grocery 4
                    return 4;
                } elseif( ($dept==159) || ($dept==155) ) {
                    // Grocery 5
                    return 5;
                } elseif($dept==158) {
                    // Grocery 6
                    return 6;
                } elseif($dept==165) {
                    // Grocery 7
                    return 7;
                } elseif($dept==159 || $dept==162 || $dept==179 
                    || $dept==154 || $dept==242) {
                    // Grocery 8
                    return 8;
                } elseif($dept==88 || $dept==90 || $dept==95 ||
                    $dept==96 || $dept==98 || $dept==99 ||
                    $dept==100) {
                    // Wellness 1
                    return 9;
                } elseif($dept==86 ||$dept==87 || $dept==89 ||
                    $dept==90 || $dept==90 || $dept==91 ||
                    $dept==94 || $dept==97 || $dept==102 ||
                    $dept==93 || $dept==104) {
                    // Wellness 2
                    return 10;
                } elseif($dept==101 || ($dept>=105 && $dept<=109) ) {
                    // Wellness 3
                    return 12;
                } else {
                    return 'none';
                }
            } else {
                //  Denfeld does not have product locations assigned yet.
                return false;
            }
        }
        
}