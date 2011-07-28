<?php

    require_once 'lib.php';
    
    $people = $_POST['people'];
    $clients = $_POST['clients'];
    $person_days = $_POST['person_days'];
    
    $dbh = mysql_connect('localhost', 'time', '');
    mysql_select_db('timetracking', $dbh);
    
    if($_POST['week'] && is_array($people) && is_array($clients) && is_array($person_days))
    {
        $q = sprintf("DELETE * FROM utilization WHERE `week` = '%s'",
                     mysql_real_escape_string($_POST['week'], $dbh));
        
        $res = mysql_query($q, $dbh);
        
        foreach($person_days as $person => $client_days)
        {
            if(empty($people[$person]))
                continue;
            
            foreach($client_days as $client => $days)
            {
                if(empty($clients[$client]))
                    continue;

                if(empty($days) && $days != '0') {
                    continue;
                
                } elseif(preg_match('#^\d*\.?\d*$#', trim($days))) {
                    // integer or floating point number
                    $days = floatval($days);
                
                } elseif(preg_match('#^(\d+)\s*/\s*(\d+)$#', trim($days), $m)) {
                    // fraction such as "1/2"
                    $days = floatval($m[1]) / floatval($m[2]);
                
                } elseif(trim($days) == '*') {
                    // splat means checkmark means meeting
                    $days = 0;
                
                } else {
                    continue;
                }
                
                $q = sprintf("INSERT INTO utilization
                              (`week`, `client`, `person`, `days`)
                              VALUES('%s', '%s', '%s', %f)",

                             mysql_real_escape_string($_POST['week'], $dbh),
                             mysql_real_escape_string($clients[$client], $dbh),
                             mysql_real_escape_string($people[$person], $dbh),
                             $days);
                
                $res = mysql_query($q, $dbh);
            }
        }
    }
    
    $weeks = array();
    $this_week = null;
    
    foreach(range(0, 4) as $i)
    {
        $t = time() - 7 * $i * 86400;
        $week = sprintf('%s-W%s', date('Y', $t), date('W', $t));
        $monday = date('M jS', strtotime("{$week}-1"));
        $friday = date('M jS', strtotime("{$week}-5"));
        
        switch($i)
        {
            case 0:
                $name = 'This week';
                $this_week = $week;
                break;
            
            case 1:
                $name = 'Last week';
                break;
            
            default:
                $name = sprintf('%d weeks ago', $i);
                break;
        }
        
        $weeks[$week] = "{$name} ({$monday} - {$friday})";
    }

    $clients = recent_clients($dbh);
    $people = recent_people($dbh);
    
    // pad some
    $people[] = '';
    $people[] = '';
    $people[] = '';
    
    $rows = 25;
    
    mysql_close($dbh);
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Record Week</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?3.3.0/build/widget/assets/skins/sam/widget.css&3.3.0/build/widget/assets/skins/sam/widget-stack.css&3.3.0/build/autocomplete/assets/skins/sam/autocomplete.css">
    <script type="text/javascript" src="http://yui.yahooapis.com/3.3.0/build/yui/yui-min.js"></script>
    <style type="text/css" title="text/css">
    <!--
        input[type=text] { border: none; }
        
        table tr td
        {
            border-top: 1px solid #ddd;
            border-left: 1px solid #ddd;
        }

        table tr:first-child th { border-bottom: 1px solid #999; }
        table tr td:first-child { border-left: none; }

        table { border-collapse: collapse; }
        
        .yui3-aclist-list .yui3-aclist-item
        {
        	color: #999;
        	background-color: white;
        	border-top: 0 #bbb none;
        	border-right: 1px #bbb solid;
        	border-bottom: 1px #bbb solid;
        	border-left: 1px #bbb solid;
        }

        .yui3-aclist-list .yui3-aclist-item:first-child { color: black; }
        .yui3-aclist-list .yui3-aclist-item-active { color: white !important; background-color: #666; }
    -->
    </style>
</head>
<body>
    <h1></h1>
    
    <form method="post" action="record.php">
        <!--<select name="week" tabindex="1">
            <? foreach($weeks as $value => $label) { ?>
                <option label="<?= $label ?>" value="<?= $value ?>"><?= $label ?></option>
            <? } ?>
        </select>-->
        <input type="hidden" name="week" value="<?= $this_week ?>">
        <table>
            <tr>
                <th>
                </th>
                <? foreach($people as $person => $name) { ?>
                    <th><input name="people[<?=$person?>]" value="<?= htmlspecialchars($name) ?>" tabindex="<?= 1 + ($person + 1) * ($rows + 1) ?>" type="text" size="3"></th>
                <? } ?>
            </tr>
            <? for($row = 0; $row < $rows; $row++) { ?>
                <tr>
                    <td><input name="clients[<?=$row?>]" tabindex="<?= 1 + $row ?>" class="client" type="text" size="32"></td>
                    <? foreach($people as $person => $name) { ?>
                        <td><input name="person_days[<?=$person?>][<?=$row?>]" tabindex="<?= 2 + $row + ($person + 1) * ($rows + 1) ?>" type="text" size="3"></td>
                    <? } ?>
                </tr>
            <? } ?>
        </table>
        <input type="submit">
    </form>

    <script type="text/javascript">
    <!--
    
        var clients = <?=json_encode($clients)?>;
        
        YUI().use('autocomplete', 'autocomplete-filters', 'autocomplete-highlighters', function(Y)
          {
            Y.all('input.client').plug(Y.Plugin.AutoComplete, {
                resultFilters: 'phraseMatch',
                resultHighlighter: 'phraseMatch',
                source: clients
              });
          });
        
        YUI().use('event-key', function(Y)
          {
            var handle = Y.on('key', function(e)
              {
                e.halt();
              },
              'input[type=text]', 'down:13', Y);
          });
    
    //-->
    </script>

</body>
</html>
