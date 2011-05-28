<?php

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
    
    function client_list(&$dbh, $order)
    {
        if($order == 'by-date') {
            $order = 'ends ASC';
        
        } else if($order == 'by-size') {
            $order = 'days DESC';
        
        } else {
            $order = 'client ASC';
        }
    
        $q = sprintf("SELECT client AS name, ends, days, budget
                      FROM client_info
                      ORDER BY {$order}");

        $res = mysql_query($q, $dbh);
        $rows = array();
        
        while($row = mysql_fetch_array($res, MYSQL_ASSOC))
        {
            $row['days']= floatval($row['days']);
            $row['time'] = strtotime("{$row['ends']} 12:00:00");
            $row['date'] = date('F jS', $row['time']);
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    function client_info(&$dbh, $name)
    {
        $q = sprintf("SELECT client AS name, ends, days, budget
                      FROM client_info
                      WHERE client = '%s'",
                      mysql_real_escape_string($name, $dbh));
        
        $res = mysql_query($q, $dbh);
        
        if($row = mysql_fetch_array($res, MYSQL_ASSOC))
        {
            $row['days']= floatval($row['days']);
            $row['time'] = strtotime("{$row['ends']} 12:00:00");
            $row['date'] = date('F jS', $row['time']);
            return $row;
        }
        
        return null;
    }
    
    function nice_int($int)
    {
        $str = sprintf('%d', $int);
        
        while(preg_match('/\B(\d\d\d)\b/', $str))
            $str = preg_replace('/\B(\d\d\d)\b/', ',\1', $str);
        
        return $str;
    }
    
    function nice_days($val)
    {
        $str = sprintf('%.1f', $val);
        $str = preg_replace('/\.0$/', '', $str);
        $str = preg_replace('/\.5$/', '½', $str);
        $str = preg_replace('/^0/', '', $str);
        
        return $str;
    }
    
    function nice_relative_date($time)
    {
        $diff = abs($time - time());
        
        if($diff > 45 * 86400) {
            $val = round($diff / (30 * 86400));
            $unit = 'month';
        
        } elseif($diff > 12 * 86400) {
            $val = round($diff / (7 * 86400));
            $unit = 'week';
        
        } elseif($diff > 36 * 3600) {
            $val = round($diff / 86400);
            $unit = 'day';
        
        } else {
            return 'Now';
        }
        
        $str = sprintf('%d %s%s %s',
                       $val, $unit,
                       ($val > 1 ? 's' : ''),
                       ($time < time() ? 'ago' : 'from now'));
        
        return $str;
    }

?>
