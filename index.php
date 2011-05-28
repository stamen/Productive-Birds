<?php

    require_once 'lib.php';

    $dbh = mysql_connect('localhost', 'time', '');
    mysql_select_db('timetracking', $dbh);
    
    $clients_byname = client_list($dbh, 'by-name');
    $clients_bydate = client_list($dbh, 'by-date');
    $clients_bysize = client_list($dbh, 'by-size');
    
    mysql_close($dbh);
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Stamen Clients</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="style.css" type="text/css" media="all">
    <script src="protovis-r3.2.js" type="text/javascript"></script>
    <script src="client.js" type="text/javascript"></script>
    <style type="text/css" title="text/css">
    <!--
        div.listing
        {
            float: left;
            width: 16em;
        }
    -->
    </style>
</head>
<body>
    <h2>Current Projects</h2>

    <div class="listing">
        <h3>By Name:</h3>
        <ul>
        <? foreach($clients_byname as $info) { ?>
            <li>
                <a href="client.php?name=<?= urlencode($info['name']) ?>"><?= $info['name'] ?></a>
            </li>
        <? } ?>
        </ul>
    </div>

    <div class="listing">
        <h3>By End Date:</h3>
        <ul>
        <? foreach($clients_bydate as $info) { ?>
            <li>
                <a href="client.php?name=<?= urlencode($info['name']) ?>"><?= $info['name'] ?></a>
                <br>
                <?= nice_relative_date($info['time']) ?>.
            </li>
        <? } ?>
        </ul>
    </div>

    <div class="listing">
        <h3>By Size:</h3>
        <ul>
        <? foreach($clients_bysize as $info) { ?>
            <li>
                <a href="client.php?name=<?= urlencode($info['name']) ?>"><?= $info['name'] ?></a>
                <br>
                $<?= nice_int($info['budget']) ?>,
                <?= nice_days($info['days']) ?> days.
            </li>
        <? } ?>
        </ul>
    </div>
</body>
</html>
