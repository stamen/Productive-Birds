<?php

    require_once 'lib.php';

    $dbh = mysql_connect('localhost', 'time', '');
    mysql_select_db('timetracking', $dbh);
    
    $client_days = client_days($dbh, $_GET['name']);
    $client_people = client_people($dbh, $_GET['name']);
    $client_info = client_info($dbh, $_GET['name']);
    $client_name = $client_info ? $client_info['name'] : $_GET['name'];
    
    mysql_close($dbh);
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $client_name ?> Client Info</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <script src="protovis-r3.2.js" type="text/javascript"></script>
    <link rel="stylesheet" href="style.css" type="text/css" media="all">
    <script src="client.js" type="text/javascript"></script>
</head>
<body>
    <h1><?= $client_name ?> ($<?= nice_int($client_info['budget']) ?>)</h1>

    <p>
        <script type="text/javascript">
        <!--
            var days = <?=json_encode($client_days)?>;
            var people = <?=json_encode($client_people)?>;
            var info = <?=json_encode($client_info)?>;
    
            render_client(days, info);
        //-->
        </script>
    </p>
    <p>
        <script type="text/javascript">
        <!--
            render_people(people, info);
        //-->
        </script>
    </p>
</body>
</html>
