<?php

    require_once 'lib.php';

    $dbh = mysql_connect('localhost', 'time', '');
    mysql_select_db('timetracking', $dbh);
    
    $clients = recent_clients($dbh);
    $people = recent_people($dbh);
    
    // pad some
    $people[] = '';
    $people[] = '';
    $people[] = '';
    
    $rows = 20;
    
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
        input { border: none; }

        table tr td
        {
            border-top: 1px solid #ddd;
            border-left: 1px solid #ddd;
        }

        table { border-collapse: collapse; }
        table tr td:first-child { border-left: none; }
        
        .yui3-aclist-list .yui3-aclist-item
        {
        	color: #999;
        	background-color: white;
        	border-top: 0 #bbb none;
        	border-right: 1px #bbb solid;
        	border-bottom: 1px #bbb solid;
        	border-left: 1px #bbb solid;
        }

        .yui3-aclist-list .yui3-aclist-item:first-child,
        .yui3-aclist-list .yui3-aclist-item-active { color: black; }

        .yui3-aclist-list .yui3-aclist-item-active { background-color: yellow; }
    -->
    </style>
</head>
<body>
    <h1></h1>
    
    <table>
        <tr>
            <th> </th>
            <? foreach($people as $person => $name) { ?>
                <th><input type="text" size="3" tabindex="<?= 1 + ($person + 1) * ($rows + 1) ?>" value="<?= htmlspecialchars($name) ?>"></th>
            <? } ?>
        </tr>
        <? for($row = 0; $row < $rows; $row++) { ?>
            <tr>
                <td><input type="text" size="20" tabindex="<?= 1 + $row ?>" class="client"></td>
                <? foreach($people as $person => $name) { ?>
                    <td><input type="text" size="3" tabindex="<?= 2 + $row + ($person + 1) * ($rows + 1) ?>"></td>
                <? } ?>
            </tr>
        <? } ?>
    </table>

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
    
    //-->
    </script>

</body>
</html>
