<?php

    require_once 'lib.php';
    
    $people = $_POST['people'];
    $clients = $_POST['clients'];
    $client_days = $_POST['client_days'];
    
    $dbh = connect_mysql();
    
    if($_POST['week'] && is_array($people) && is_array($clients) && is_array($client_days))
    {
        header('content-type: text/plain');
    
        $q = sprintf("DELETE FROM utilization WHERE `week` = '%s'",
                     mysql_real_escape_string($_POST['week'], $dbh));
        
        //echo "{$q}\n\n";
        $res = mysql_query($q, $dbh);
        
        foreach($client_days as $client => $person_days)
        {
            if(empty($clients[$client]))
                continue;
            
            foreach($person_days as $person => $days)
            {
                if(empty($people[$person]))
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
                
                //echo "{$q}\n\n";
                $res = mysql_query($q, $dbh);
            }
        }
        
        header('HTTP/1.1 303 See Other');
        header("Location: {$_SERVER['SCRIPT_NAME']}?week={$_POST['week']}");
        exit();
    }
    
    $current_clients = week_clients(&$dbh, $_GET['week']);

    $recent_clients = recent_clients($dbh);
    $recent_clients = array_merge($current_clients, $recent_clients);
    $recent_clients = array_values(array_unique($recent_clients));
    
    if(isset($_GET['week'])) {
        $explicit_week = true;

        $t = strtotime($_GET['week']);
        $this_week = nice_week($t);
        $prev_week = nice_week($t - 604800);
        $next_week = nice_week($t + 604800);

        $people = week_people($dbh, $this_week);
        $days = week_utilization(&$dbh, $this_week, $current_clients, $people);
    
    } else {
        $explicit_week = false;

        $this_week = nice_week(time());
        $prev_week = nice_week(time() - 604800);

        $people = recent_people($dbh);
        $days = array();
        
        header('HTTP/1.1 303 See Other');
        header("Location: {$_SERVER['SCRIPT_NAME']}?week={$this_week}");
        exit();
    }
    
    $week_text = sprintf('Week ending %s', date('M j', strtotime("{$this_week}-5")));
    
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
        <input type="submit" style="margin-right: 1em;">
        <input type="hidden" name="week" value="<?= $this_week ?>">

        <? if($explicit_week) { ?>
            <a href="<?= $_SERVER['SCRIPT_NAME'] ?>?week=<?= $prev_week ?>">«</a>
            <?= $week_text ?>
            <a href="<?= $_SERVER['SCRIPT_NAME'] ?>?week=<?= $next_week ?>">»</a>
        
        <? } else { ?>
            <a href="<?= $_SERVER['SCRIPT_NAME'] ?>?week=<?= $prev_week ?>">«</a>
            <?= $week_text ?>
        <? } ?>

        <table>
            <tr>
                <th>
                </th>
                <? foreach($people as $person => $name) { ?>
                    <? $tab = 1 + ($person + 1) * ($rows + 1); ?>
                    <th><input name="people[<?=$person?>]" value="<?= htmlspecialchars($name) ?>" tabindex="<?=$tab?>" type="text" size="3"></th>
                <? } ?>
            </tr>
            <? for($client = 0; $client < $rows; $client++) { ?>
                <tr>
                    <? $tab = 1 + $client; ?>
                    <td><input name="clients[<?=$client?>]" value="<?= htmlspecialchars($current_clients[$client]) ?>" tabindex="<?=$tab?>" class="client" type="text" size="32"></td>
                    <? foreach($people as $person => $name) { ?>
                        <? $tab = 2 + $client + ($person + 1) * ($rows + 1); ?>
                        <? $value = is_array($days[$client]) && isset($days[$client][$person]) ? $days[$client][$person] : '' ?>
                        <td><input name="client_days[<?=$client?>][<?=$person?>]" value="<?= htmlspecialchars($value) ?>" tabindex="<?=$tab?>" type="text" size="3"></td>
                    <? } ?>
                </tr>
            <? } ?>
        </table>
        <input type="submit">
    </form>

    <script type="text/javascript">
    <!--
    
        var clients = <?=json_encode($recent_clients)?>;
        
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
