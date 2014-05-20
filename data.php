<?php

    require_once 'lib.php';

    $dbh = connect_mysql();
    
    $q = "SELECT week, person, days, client
          FROM utilization
          WHERE count > 0
          ORDER BY week, person, client";

    $res = mysql_query($q, $dbh);
    $rows = array();
    
    header('Content-Type: text/plain');
    echo join("\t", array('week', 'person', 'days', 'client'))."\n";
    
    while($row = mysql_fetch_array($res, MYSQL_ASSOC))
        echo join("\t", $row)."\n";

    mysql_close($dbh);
    
?>
