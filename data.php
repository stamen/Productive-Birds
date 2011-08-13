<?php

    require_once 'lib.php';

    $dbh = mysql_connect('localhost', 'time', '');
    mysql_select_db('timetracking', $dbh);
    
    $q = "SELECT week, person, client, days
          FROM utilization
          WHERE count > 0
          ORDER BY week, person, client";

    $res = mysql_query($q, $dbh);
    $rows = array();
    
    header('Content-Type: text/plain');
    echo join("\t", array('week', 'person', 'client', 'days'))."\n";
    
    while($row = mysql_fetch_array($res, MYSQL_ASSOC))
        echo join("\t", $row)."\n";

    mysql_close($dbh);
    
?>
