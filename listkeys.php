<?php
$title='shortcut keys';
$menu='';
require_once 'headerHtml.inc.php';
ob_start();
require_once 'headerMenu.inc.php';
$menutext=ob_get_contents();
ob_end_clean();
?>
</head><body>
<div class='noprint'>
    <?php echo $menutext; ?>
    <p id='main'>These keys can be changed in config.inc.php</p>
</div>
<h2>Shortcut keys for gtd-php</h2>
<table summary='Shortcut keys'>
<thead><tr><th>key</th><th>title</th><th>description</th></tr></thead>
<tbody>
<?php
foreach ($menu as $line)
    if (!empty($line['key']))
        echo "<tr>"
            ,"<td>{$line['key']}</td>"
            ,"<td>{$line['label']}</td>"
            ,"<td>{$line['title']}</td>"
            ,"</tr>";
?>
</tbody>
</table>
</body>
</html>
