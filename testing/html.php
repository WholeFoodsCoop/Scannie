<!--/*******************************************************************************

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
    
*********************************************************************************/-->

<html>
<head>
<title>Batch Review</title>
<?php include('BatchReviewPage.html');?>
<style>
    .redText {
        color: red;
    }
</style>
</head>
<br><br><br>
<body>
    <div class="container">
    <?php include('BatchReviewLinks.html'); ?>
    <h4>
        <b>UNFI Batch Review</b>
        <?php if ($_GET['id']) echo ' Batch ID # ' . $_GET['id'];?>
    </h4>
    <form method="get" class="form-inline">
        <input type="text" class="form-control" name="id" placeholder="Enter Batch  ID" autofocus>
        <input type="submit" class="form-control">
    </form>