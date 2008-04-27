<?php include_once('header.inc.php'); ?>
<h2>GTD-PHP Version Information</h2>
<table summary='version information'>
    <tbody>
        <tr><th>GTD-PHP revision</th>
            <td><?php echo _GTD_REVISION; ?></td>
        </tr>
        <tr><th>GTD-PHP version</th>
            <td><?php echo _GTDPHP_VERSION; ?></td>
        </tr>
        <tr><th>GTD-PHP database</th>
            <td><?php echo array_pop(array_pop(query('getgtdphpversion',$config))); ?></td>
        </tr>
        <tr><th>GTD-PHP theme</th>
            <td><?php echo $config['theme']; ?></td>
        </tr>
        <tr><th>PHP</th>
            <td><?php echo PHP_VERSION; ?></td>
        </tr>
        <tr><th>Database</th>
            <td><?php echo getDBVersion(); ?></td>
        </tr>
    </tbody>
</table>
<?php include_once('footer.inc.php'); ?>
