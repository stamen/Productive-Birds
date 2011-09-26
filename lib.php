<?php

    define('PEOPLE', 'ER SA MM ASC GS JE SC RB NK');

    function &connect_mysql()
    {
        $dbh = mysql_connect('localhost', 'time', '');
        mysql_select_db('timetracking', $dbh);
        return $dbh;
    }

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
            $row['days'] = floatval($row['days']);
            $row['time'] = strtotime("{$row['week']}-5 12:00:00");
            $row['date'] = date('M j', $row['time']);
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    function client_people(&$dbh, $name)
    {
        $names = client_synonyms_literal($dbh, $name);
        
        $q = sprintf("SELECT w.week, p.person, u.days
                      FROM (
                          SELECT DISTINCT week
                          FROM utilization
                          WHERE client IN (%s)
                            AND `count` = 1
                        ) AS w
                      CROSS JOIN (
                          SELECT DISTINCT person
                          FROM utilization
                          WHERE client IN (%s)
                            AND `count` = 1
                        ) AS p
                      LEFT JOIN utilization AS u
                        ON u.week = w.week
                       AND u.person = p.person
                       AND u.client IN (%s)
                       AND u.count = 1
                      ORDER BY w.week, p.person",
                      $names,
                      $names,
                      $names);

        $res = mysql_query($q, $dbh);
        $rows = array();
        
        while($row = mysql_fetch_array($res, MYSQL_ASSOC))
        {
            $row['days'] = is_null($row['days']) ? null : floatval($row['days']);
            $row['time'] = strtotime("{$row['week']}-5 12:00:00");
            $row['date'] = date('M j', $row['time']);
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    function client_list(&$dbh, $order, $ended)
    {
        if($order == 'by-date') {
            $order = ($ended == 'no') ? 'ends ASC' : 'ends DESC';
        
        } else if($order == 'by-size') {
            $order = 'days DESC';
        
        } else {
            $order = 'client ASC';
        }
    
        $q = sprintf("SELECT client AS name, ends, days, budget
                      FROM client_info
                      WHERE ended = '{$ended}'
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
    
    function week_clients(&$dbh, $week)
    {
        $q = sprintf("SELECT DISTINCT `client`
                      FROM utilization
                      WHERE week = '%s'
                      ORDER BY `order`",
                     mysql_real_escape_string($week, $dbh));
        
        $res = mysql_query($q, $dbh);
        $clients = array();
        
        while($row = mysql_fetch_array($res, MYSQL_NUM))
            $clients[] = $row[0];
        
        return $clients;
    }
    
    function week_people(&$dbh, $week)
    {
        $people = explode(' ', PEOPLE);

        $q = sprintf("SELECT DISTINCT `person`
                      FROM utilization
                      WHERE week = '%s'",
                     mysql_real_escape_string($week, $dbh));
        
        $res = mysql_query($q, $dbh);
        
        while($row = mysql_fetch_array($res, MYSQL_NUM))
            if(!in_array($row[0], $people))
                $people[] = $row[0];
        
        return $people;
    }
    
   /**
    * Return a nested array of clients and people, using numeric indexes.
    */
    function week_utilization(&$dbh, $week, $clients, $people)
    {
        $q = sprintf("SELECT DISTINCT `client`
                      FROM utilization
                      WHERE week = '%s'",
                     mysql_real_escape_string($week, $dbh));
        
        $res = mysql_query($q, $dbh);

        $client_days = array();
        
        while($row = mysql_fetch_array($res, MYSQL_ASSOC))
        {
            $client = array_search($row['client'], $clients);
            $client_days[$client] = array();
        }
        
        $q = sprintf("SELECT `client`, `person`, `days`
                      FROM utilization
                      WHERE week = '%s'",
                     mysql_real_escape_string($week, $dbh));
        
        $res = mysql_query($q, $dbh);

        while($row = mysql_fetch_array($res, MYSQL_ASSOC))
        {
            $client = array_search($row['client'], $clients);
            $person = array_search($row['person'], $people);
            $client_days[$client][$person] = $row['days'];
        }
        
        return $client_days;
    }
    
    function recent_clients(&$dbh)
    {
        $q = "SELECT client
              FROM client_info
              ORDER BY ends DESC";

        $res = mysql_query($q, $dbh);
        $names = array();
        $seen = array();
        
        while($row = mysql_fetch_array($res, MYSQL_NUM))
        {
            $names[] = $row[0];
            $seen[] = strtolower($row[0]);
        }
        
        $q = "SELECT DISTINCT client
              FROM utilization
              ORDER BY week DESC
              LIMIT 50";
        
        $res = mysql_query($q, $dbh);
        
        while($row = mysql_fetch_array($res, MYSQL_NUM))
            if(!in_array(strtolower($row[0]), $seen))
                $names[] = $row[0];
        
        return $names;
    }
    
    function recent_people(&$dbh)
    {
        $people = explode(' ', PEOPLE);
        $time = time() - 6 * 7 * 86400;
        $week = date('Y-', $time).'W'.date('W', $time);
    
        $q = "SELECT DISTINCT person
              FROM utilization
              WHERE week >= '{$week}'";

        $res = mysql_query($q, $dbh);
        
        while($row = mysql_fetch_array($res, MYSQL_NUM))
            if(!in_array($row[0], $people))
                $people[] = $row[0];
        
        return $people;
    }
    
    function nice_int($int)
    {
        if(is_null($int))
            return '?';
        
        $str = sprintf('%d', $int);
        
        while(preg_match('/\B(\d\d\d)\b/', $str))
            $str = preg_replace('/\B(\d\d\d)\b/', ',\1', $str);
        
        return $str;
    }
    
    function nice_week($time)
    {
        return sprintf('%s-W%s', date('Y', $time), date('W', $time));
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
