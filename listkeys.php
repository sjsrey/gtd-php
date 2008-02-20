<?php
$title='gtd-php shortcut keys';
require_once('headerHtml.inc.php');
require_once('config.php');
$menufile=file_get_contents('headerMenu.inc.php');
$filetext=explode('$thismenu[]',$menufile);
$menulines=preg_grep("/array/",$filetext);
$links=$titles=$labels=array();
foreach ($menulines as $line) {
    $matches=array();
    if (preg_match('/[\'"]link.*=>.*[\'"](.*)[\'"].*[\'"]title[\'"].*=>.*[\'"](.*)[\'"].*[\'"]label[\'"].*=>.*[\'"](.*)[\'"]/',$line,$matches)) {
        $links[] =$matches[1];
        $titles[]=$matches[2];
        $labels[]=$matches[3];
    }
}
?>
</head><body>
<p class='noprint'>These keys can be changed in config.php</p>
<table summary='Shortcut keys'>
<caption>Shortcut keys for gtd-php</caption>
<thead><tr><th>key</th><th>title</th><th>description</th></tr></thead>
<tbody>
<?php
foreach ($acckey as $linktotest=>$keypress) {
    if (!empty($keypress) && false!==$i=array_search($linktotest,$links)) {
        echo "<tr><td>$keypress</td><td>{$labels[$i]}</td><td>{$titles[$i]}</td></tr>";
    }
}
?>
</tbody>
</table>
</body></html>
