<?php

    require_once 'lib.php';

    $dbh = mysql_connect('localhost', 'time', '');
    mysql_select_db('timetracking', $dbh);
    
    $clients = recent_clients($dbh);
    
    mysql_close($dbh);
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Record Week</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?3.3.0/build/widget/assets/skins/sam/widget.css&3.3.0/build/widget/assets/skins/sam/widget-stack.css&3.3.0/build/autocomplete/assets/skins/sam/autocomplete.css">
    <script type="text/javascript" src="http://yui.yahooapis.com/3.3.0/build/yui/yui-min.js"></script>
</head>
<body>
    <h1></h1>

    <p>
    <input id="client-name" type="text" size="32">
    <script type="text/javascript">
    <!--
    
        var clients = <?=json_encode($clients)?>;
        
        YUI().use('autocomplete', 'autocomplete-filters', 'autocomplete-highlighters', function(Y)
          {
            Y.one('#client-name').plug(Y.Plugin.AutoComplete, {
                resultFilters: 'phraseMatch',
                resultHighlighter: 'phraseMatch',
                source: clients
              });
          });
    
    //-->
    </script>
    </p>
</body>
</html>
