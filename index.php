<?php

    require_once 'lib.php';

    $dbh = mysql_connect('localhost', 'time', '');
    mysql_select_db('timetracking', $dbh);
    
    $client_list = client_list($dbh);
    
    mysql_close($dbh);
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Stamen Clients</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <script src="protovis-r3.2.js" type="text/javascript"></script>
    <script src="client.js" type="text/javascript"></script>
    <style type="text/css" title="text/css">
    <!--
        h1 { font: 18px Georgia; }
        body { font: 18px Georgia; }
    -->
    </style>
</head>
<body>
    <ul>
    <? foreach($client_list as $info) { ?>
        <li>
            <a href="client.php?name=<?= urlencode($info['name']) ?>"><?= $info['name'] ?></a>
            <br>
            $<?= nice_int($info['budget']) ?>,
            ends <?= $info['date'] ?>.
        </li>
    <? } ?>
    </ul>
</body>
</html>
