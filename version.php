<?php
$title='GTD-PHP Version Information';
include_once 'header.inc.php';
$addons=array();
if (array_key_exists('addons',$_SESSION)) {
    foreach ($_SESSION['addons'] as $key=>$val) if ($val===true) {
        $ver=@file_get_contents($_SESSION['addonsdir'].$key.'/version');
        $addons[makeclean($key)]=makeClean($ver);
    }
}
?>
<table summary='version information'>
    <tbody>
        <tr><th>GTD-PHP revision</th>
            <td><?php echo _GTD_REVISION; ?></td>
        </tr>
        <tr><th>GTD-PHP version</th>
            <td><?php echo _GTDPHP_VERSION; ?></td>
        </tr>
        <tr><th>GTD-PHP database</th>
            <td><?php echo array_pop(array_pop(query('getgtdphpversion'))); ?></td>
        </tr>
        <tr><th>GTD-PHP theme</th>
            <td><?php echo $_SESSION['theme']; ?></td>
        </tr>
        <tr><th>PHP</th>
            <td><?php echo PHP_VERSION; ?></td>
        </tr>
        <tr><th>Database</th>
            <td><?php echo getDBVersion(); ?></td>
        </tr>
        <?php if (count($addons)) { ?>
            <tr><th colspan='2'>Addons</th></tr>
            <?php foreach ($addons as $name=>$ver) { ?>
                <tr><th><?php echo $name; ?></th>
                    <td><?php echo $ver; ?></td>
                </tr>
            <?php
            }
        } ?>
    </tbody>
</table>
<?php include_once 'footer.inc.php'; ?>
