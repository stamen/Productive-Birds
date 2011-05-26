<?php

    $dbh = mysql_connect('localhost', 'time', '');
    mysql_select_db('timetracking', $dbh);
    
    function client_synonyms(&$dbh, $name)
    {
        $q = sprintf("SELECT ca.client1 AS ca1, ca.client2 AS ca2,
                             cb.client1 AS cb1, cb.client2 AS cb2, 
                             cc.client1 AS cc1, cc.client2 AS cc2
                      
                      FROM same_clients AS ca
                      
                      LEFT JOIN same_clients AS cb
                        ON cb.client1 = ca.client1 OR cb.client1 = ca.client2
                        OR cb.client2 = ca.client1 OR cb.client2 = ca.client2
                      
                      LEFT JOIN same_clients AS cc
                        ON cc.client1 = cb.client1 OR cc.client1 = cb.client2
                        OR cc.client2 = cb.client1 OR cc.client2 = cb.client2
                      
                      WHERE ca.client1 = '%s' 
                         OR ca.client2 = '%s'",
                      
                      mysql_real_escape_string($name, $dbh),
                      mysql_real_escape_string($name, $dbh));
        
        $res = mysql_query($q, $dbh);
        $names = array(strtolower($name));
        
        while($row = mysql_fetch_array($res, MYSQL_NUM))
            foreach($row as $other)
                if(!is_null($other))
                    $names[] = strtolower($other);
        
        $names = array_unique($names);
        return array_values($names);
    }
    
    function client_synonyms_literal(&$dbh, $name)
    {
        $names = client_synonyms($dbh, $name);
        
        foreach($names as $i => $name)
            $names[$i] = sprintf("'%s'", mysql_real_escape_string($name, $dbh));
        
        return join(', ', $names);
    }
    
    function client_days(&$dbh, $name)
    {
        $names = client_synonyms_literal($dbh, $name);
        
        $q = sprintf("SELECT week, SUM(days) AS days
                      FROM utilization
                      WHERE client IN (%s)
                      GROUP BY week
                      ORDER BY week",
                      $names);

        $res = mysql_query($q, $dbh);
        $rows = array();
        
        while($row = mysql_fetch_array($res, MYSQL_ASSOC))
        {
            $row['days']= floatval($row['days']);
            $row['time'] = strtotime("{$row['week']}-5 12:00:00");
            $row['date'] = date('M j', $row['time']);
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    function client_people(&$dbh, $name)
    {
        $names = client_synonyms_literal($dbh, $name);
        
        $q = sprintf("SELECT person, SUM(days) AS days
                      FROM utilization
                      WHERE client IN (%s)
                      GROUP BY person
                      ORDER BY days DESC",
                      $names);

        $res = mysql_query($q, $dbh);
        $rows = array();
        
        while($row = mysql_fetch_array($res, MYSQL_ASSOC))
        {
            $row['days']= floatval($row['days']);
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    $client_days = client_days($dbh, $_GET['name']);
    $client_people = client_people($dbh, $_GET['name']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>blah</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <script src="protovis-r3.2.js" type="text/javascript"></script>
</head>
<body>

    <script type="text/javascript">
    <!--
    
        var data = <?=json_encode($client_days)?>;
        
        console.log(data);
        
        var weeks = [],
            times = [],
            dates = [],
            days = [],
            total = 0,
            cumulative = [],
            first = data[0],
            last = null;
        
        while(data.length)
        {
            weeks.push(data[0].week);
            times.push(data[0].time);
            dates.push(data[0].date);
            days.push(data[0].days);
            
            total += data[0].days;
            last = data.shift();
            
            cumulative.push({time: last.time, total: total});
        }
        
        console.log([weeks, times, dates, days, cumulative]);
        
        var w = 800,
            h = 400,
            x = pv.Scale.linear(first.time - 7*86400, last.time).range(0, w),
            y = pv.Scale.linear(0, total).range(0, h);
        
        var vis = new pv.Panel()
            .width(w)
            .height(h);
        
        vis.add(pv.Line)
            .data(cumulative)
            .left(function(d) { return x(d.time) })
            .bottom(function(d) { return y(d.total) })
            .lineWidth(1)
          .add(pv.Dot);
        
        vis.render();
    
    //-->
    </script>
    <tt><?= json_encode($client_people) ?></tt>
</body>
</html>
