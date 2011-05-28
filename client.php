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
                        AND `count` = 1
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
    
    function client_limits(&$dbh, $name)
    {
        $q = sprintf("SELECT client AS name, ends, days, budget
                      FROM client_limits
                      WHERE client = '%s'",
                      mysql_real_escape_string($name, $dbh));
        
        $res = mysql_query($q, $dbh);
        
        if($row = mysql_fetch_array($res, MYSQL_ASSOC))
        {
            $row['days']= floatval($row['days']);
            $row['time'] = strtotime("{$row['ends']} 12:00:00");
            $row['date'] = date('M j', $row['time']);
            return $row;
        }
        
        return null;
    }
    
    $client_days = client_days($dbh, $_GET['name']);
    $client_people = client_people($dbh, $_GET['name']);
    $client_limits = client_limits($dbh, $_GET['name']);
    
    function nice_int($int)
    {
        $str = sprintf('%d', $int);
        
        while(preg_match('/\B(\d\d\d)\b/', $str))
            $str = preg_replace('/\B(\d\d\d)\b/', ',\1', $str);
        
        return $str;
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>blah</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <script src="protovis-r3.2.js" type="text/javascript"></script>
    <style type="text/css" title="text/css">
    <!--
        h1 { font: 18px Georgia; }
    -->
    </style>
</head>
<body>
    <h1><?= $client_limits['name'] ?> ($<?= nice_int($client_limits['budget']) ?>)</h1>

    <p>
    <script type="text/javascript">
    <!--
    
        function nice_days(days)
        {
            return days.toFixed(1)
                .replace(/\.0$/, '')
                .replace(/\.5$/, '½')
                .replace(/^0/, '');
        }
    
        var data = <?=json_encode($client_days)?>;
        var limit = <?=json_encode($client_limits)?>;
        
        console.log(data);
        
        var total = 0,
            last = null;
        
        var start = {'days': 0, 'time': data[0].time - 7*86400, 'week': ''};
        data.unshift(start);
        
        for(var i = 0; i < data.length; i++)
        {
            total += data[i].days;
            data[i].total = total;
            last = data[i];
        }
        
        var w = 960,
            h = 400,
            x = pv.Scale.linear(start.time, Math.max(last.time, limit.time)).range(0, w),
            y = pv.Scale.linear(0, Math.max(total, limit.days)).range(0, h),
            small = '13px Georgia',
            large = '18px Georgia';
        
        var vis = new pv.Panel()
            .width(w)
            .height(h)
            .left(40)
            .right(25)
            .bottom(30)
            .top(30);
        
        // area of profitability
        vis.add(pv.Area)
            .data([{time: start.time, total: 0}, {time: Math.max(last.time, limit.time), total: limit.days}])
            .left(function(d) { return x(d.time) })
            .height(function(d) { return y(d.total) })
            .bottom(0)
            .fillStyle('#eee');
        
        // weekly vertical rules
        vis.add(pv.Rule)
            .data(data)
            .strokeStyle('#ccc')
            .left(function(d) { return x(d.time) })
            .height(function(d) { return y(d.total) })
            .bottom(y(0))
          .anchor('bottom').add(pv.Label)
            .text(function(d) { return d.date })
            .textAlign('right')
            .font(small);
        
        // bottom rule
        vis.add(pv.Rule)
            .bottom(y(0))
            .strokeStyle('#ccc')
            .left(0)
            .right(0);
        
        // top rule
        vis.add(pv.Rule)
            .bottom(y((limit.days)))
            .strokeStyle('#f90')
            .lineWidth(2)
            .left(0)
            .right(0);
        
        // left-hand rule
        vis.add(pv.Rule)
            .left(x(start.time))
            .strokeStyle('#ccc')
            .bottom(0)
            .top(0);
        
        // left hand ticks
        vis.add(pv.Rule)
            .data(y.ticks())
            .visible(function() { return this.index > 0 })
            .strokeStyle('#ccc')
            .bottom(y)
            .left(-5)
            .width(5)
          .anchor('left').add(pv.Label)
            .text(y.tickFormat)
            .font(small);
        
        // right-hand rule and label
        vis.add(pv.Rule)
            .left(x(limit.time))
            .strokeStyle('#f90')
            .lineWidth(2)
            .bottom(0)
            .height(y(limit.days))
          .add(pv.Label)
            .top(h - 6)
            .text(function(d) { return limit.date })
            .textAlign('right')
            .font(large);
        
        // weekly time
        vis.add(pv.Line)
            /*
            .data(data)
            .left(function(d) { return x(d.time) })
            .bottom(function(d) { return y(d.total) })
            .strokeStyle('white')
            .lineWidth(8)
            */
          .add(pv.Line)
            .data(data)
            .left(function(d) { return x(d.time) })
            .bottom(function(d) { return y(d.total) })
            .strokeStyle('#666')
            .lineWidth(4)
          .add(pv.Dot)
            .size(function(d) { return (this.index > 0) ? 40 : 20 })
            .fillStyle(function(d) { return (this.index > 0) ? 'white' : '#666' })
          .anchor('top').add(pv.Label)
            .text(function(d) { return nice_days(d.total); })
            .visible(function() { return this.index > 0 })
            .textAlign('right')
            .font(large);
        
        // pig
        vis.add(pv.Panel)
            .width(46)
            .height(46)
            .left(x(Math.max(last.time, limit.time)) - 23)
            .bottom(y(limit.days) - 23)
          .add(pv.Image)
            .url('pig.png')
        
        // bird
        vis.add(pv.Panel)
            .width(41)
            .height(35)
            .left(x(last.time) - 20)
            .bottom(y(last.total) - 20)
          .add(pv.Image)
            .url('bird.png')
        
        vis.render();
    
    //-->
    </script>
    </p>
</body>
</html>
