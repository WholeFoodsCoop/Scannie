<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op.
    
    This file is a part of CORE-POS.
    
    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file LICENSE along with CORE-POS; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    
*********************************************************************************/?>
<html>
<head>
  <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
  <script src="../bootstrap/jquery.min.js"></script>
  <script src="../bootstrap/bootstrap.min.js"></script>
<?php
if (!class_exists('MenuClass')) {
    include('MenuClass.php');
}
print menu::nav_menu();