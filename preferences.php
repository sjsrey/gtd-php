<?php
/* 
   ======================================================================================
*/
function makeOptionsTab($array,$values,$tabname,$varprefix='',$textsize=10) {
    global $checkboxes;
    ?>
    <h2><?php echo $tabname; ?></h2>
    <div class='tabsheet' id='<?php echo $tabname; ?>'>
    <a id='opt<?php echo $tabname; ?>' name='opt<?php echo $tabname; ?>'></a>
    <?php
    log_array(array('values'=>$values,'array'=>$array));
    foreach ($array as $option) {
        $name=$varprefix.$option[0];
        $val=(isset($values[$option[0]])) ? $values[$option[0]] : null;
        echo "<div class='formrow'>\n";
        $for=" for='opt$name'";
        switch ($option[1]) {
            case 'checkbox':
                echo "<input type='checkbox' name='$name' id='opt$name' "
                    ,($val) ? " checked='checked' ": ''
                    ,"/>";
                $checkboxes.="$name,";
                break;
            case 'heading':
                echo '<hr />';
                $for='';
                break;
            case 'text': // deliberately flows through
                echo "<input type='text' name='$name' size='$textsize' id='opt$name' value='"
                    ,makeclean($val)
                    ,"' />\n";
                break;
            default: // it's a select
                echo "<select name='$name' id='opt$name'>";
                foreach ($option[1] as $optval=>$opttext)
                    echo "<option value='$optval'"
                        ,($val==$optval) ? " selected='selected' ": ''
                        ,">",makeclean($opttext),"</option>\n";
                echo "</select>";
                break;
        }
        echo "<label$for>{$option[2]}</label></div>\n";
    }
    ?>
        <div class='formbuttons'>
            <input type='submit' value='Apply all sections' name='submit' />
            <input type='reset'  value='Reset all' name='reset'  />
        </div>
    </div>
    <?php
}
/*
   ======================================================================================
*/
$menu='';
require_once 'headerHtml.inc.php';
if ($_SESSION['useLiveEnhancements']) {
    ?>
    <script type='text/javascript'>
        /* <![CDATA[ */
        $(document).ready(GTD.setTabs);
        /* ]]> */
    </script>
    <?php
}
include_once 'header.inc.php';
retrieveConfig(); // force retrieval of preferences from db upon entering this screen, to avoid inter-session contamination
$checkboxes='';
// get a list of theme sub-directories, to go into the dropdown selector
$themes=array();
$dir = "./themes";
if ($handle = opendir($dir)) {
	while (false !== ($file = readdir($handle))) {
		if ($file[0] !== "." && is_dir($dir. "/" . $file)) {
            $file=makeclean($file);
			$themes[$file] = $file;
		}
	}
	closedir($handle);
}
// get a list of addons present
$addons=array();
if ($handle = opendir($_SESSION['addonsdir'])) {
	while (false !== ($file = readdir($handle))) {
		if ($file[0] !== "." && is_dir($_SESSION['addonsdir'] . $file)) {
            $file=makeclean($file);
			$addons[$file] = $file;
		}
	}
	closedir($handle);
}
$config=$_SESSION['config'];
$hidden='';
?>
<form action="processPreferences.php" method="post" id='optionsform'>
<?php

/* ------------------------------------------------------------------------
    basic options
*/
$array=array(
    array('title','text','Title'),
    array('trimLength','text','Maximum length of descriptions in lists of items (0=display all)'),
    array('trimLengthInReport','text','Maximum length of descriptions of children in item reports (0=display all)'),
    array('ReportMaxCompleteChildren','text','Maximum number of completed children of each type to display in item reports (0=display all)'),
    array('theme',$themes,'Theme'),
    array('firstDayOfWeek',array(0=>'Sunday',1=>'Monday',2=>'Tuesday',
            3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday'
            ),'First day of week'),
    array('contextsummary','checkbox','In the space contexts report, show only <b>next</b> actions'),
    array('useLiveEnhancements','checkbox','Use Live enhancements'),
    array('reviewProjectsWithoutOutcomes','checkbox','In the weekly review, identify projects without outcomes'),
    array('storeRecurrences','checkbox','When recurring items are completed, store each occurrence as a completed item')
);
makeOptionsTab($array,$config,'Options');
/* ------------------------------------------------------------------------
    Addons: scan for add-ons, grab auto-config file, and offer option of enabling / disabling
*/
$array=array();
foreach ($addons as $addon) {
    $desc=makeClean(@file_get_contents($_SESSION['addonsdir'].$addon.'/description'));
    if (!empty($desc)) $desc="($desc)";
    if ($optHandle=@fopen($_SESSION['addonsdir'].$addon.'/options.inc.php','r',true)) {
        /* TOFIX - we definitely don't want the options link to be part of the checkbox label
           Also, probably better to put each options tab in a DIV at the bottom of the page
           and convert it into a tab.
           In summary, this is not yet production-ready, but is here as a basic interface 
           to enable addon developers to start work on viable options pages
        */
        $opts = "<a href='addon.php?addonid=$addon&amp;url=options.inc.php'>Options</a>";
        fclose($optHandle);
    } else $opts ='';
    $array[]=array($addon,'checkbox',"<b>$addon</b> $opts $desc");
}
$live=array();
if ($_SESSION['addons']) foreach ($_SESSION['addons'] as $where)
    if (is_array($where)) foreach ($where as $page)
        if ($page) foreach ($page as $addonname=>$how)
            $live[$addonname]=true;
makeOptionsTab($array,$live,'Addons','addons','50');
/* ------------------------------------------------------------------------
    Default actions after creating items
*/
$array=array();
$buttonselect=array('another'=>'Create another','child'=>'Create a child','item'=>'View item','list'=>'List all','parent'=>'View parent');
foreach (getTypes() as $type=>$typename)
    $array[]=array("afterCreate$type",$buttonselect,"After creating $typename");
makeOptionsTab($array,$config,'Workflow');
/* ------------------------------------------------------------------------
     advanced settings
*/
$array=array(
    array('datemask','text','PHP mask for date format'),
    array('charset','text','PHP name of character set (codepage) to use (WARNING: UTF-8 support is experimental)'),
    array('title_suffix','checkbox','Add filename to page title'),
    array('radioButtonsForNextPage','checkbox','When editing an item, display choice of next page as a radio group rather than as submit buttons'),
    array('useTypesForTimeContexts','checkbox','Bind each time-context to a particular item type (action, project, etc'),
    array('forceAllFields','checkbox','Display all possible fields, when editing any item'),
    array('allowChangingTypes','checkbox','Enable option of changing the types of <b>any</b> item (rather than just inbox items)'),
    array('withholdVersionInfo','checkbox','When you file a bug report, withhold information about the versions of PHP and database software you are using'),
    array('suppressAdmin','checkbox','Suppress administrator options in menus'),
    array('suppressCustomRecurrences','checkbox','Suppress entry of rfc2445 text directly to specify item recurrence patterns'),
    array('show7','checkbox','Show the Seven Habits of Highly Effective People and Sharpening the Saw in Weekly Review'),
    array('showRelativeDeadlines','checkbox','Show deadlines as relative days (e.g. "due in 5 days") rather than as dates'),
    array('separator','text','Separator string for MySQL GROUP queries'),
    array('basepath','text','Base path for installation (default is: '.getAbsolutePath().')')
);
makeOptionsTab($array,$config,'Advanced','',25);
/* ------------------------------------------------------------------------
    sort options
*/
$array=array(
    array('categoryselectbox','text','Categories'),
    array('getchildren','text','Children in item view'),
    array('getitems','text','Projects and someday/maybes on the summary page'),
    array('getitemsandparent','text','Lists of items and their parents'),
    array('getorphaneditems','text','Orphans'),
    array('spacecontextselectbox','text','Space contexts'),
    array('timecontextselectbox','text','Time contexts')
);
makeOptionsTab($array,$_SESSION['sort'],'Sort','sort',50);
/* ------------------------------------------------------------------------
    shortcut keys: cycle through all options in $menu, for shortcut keys
*/
$keylist=$array=array();
$mainmenu='';
$keyentry=0;
foreach ($menu as $menuitem) { // Store keyprefs against link in db.
    if (empty($menuitem['link'])) { // if link is blank, then label is a main menu
        if($menuitem['label']==='separator') continue;
        $mainmenu=$menuitem['label'];
        $array[]=array('','heading',$menuitem['label']);
        continue;
    }
    // keys are link, title, label
    $array[]=array($keyentry,'text',$menuitem['title']);
    $link=$menuitem['link'];
    $hidden.="<input type='hidden' name='lkeys$keyentry' value='".makeclean($link)."' />\n";
    $keylist[$keyentry]=(isset($_SESSION['keys'][$link])) ? $_SESSION['keys'][$link] : null;
    $keyentry++;
}
makeOptionsTab($array,$keylist,'Shortcuts','keys',1);
/* ------------------------------------------------------------------------
    debug options
*/
$array=array(
    array('key','text','Key to press to toggle display of detailed debugging information'),
    array('notice','checkbox','Report PHP notices'),
    array('debug','checkbox','Show detailed debugging information'),
    array('wait','checkbox','Wait after updating an item, rather than progressing to the next screen automatically'),
    array('freeze','checkbox','Suppress any changes to the gtd-php database')
);
makeOptionsTab($array,$_SESSION['debug'],'Debugging','debug',1);
/* ------------------------------------------------------------------------
    finish form
*/
?>
<div class='formbuttons'>
    <input type='submit' name='restoredefaults' value='Restore default preferences' />
    <?php
    $hidden.="<input type='hidden' name='checkboxes' value='$checkboxes' />\n";
    echo $hidden;
    ?>
</div>
</form>
<?php include_once 'footer.inc.php'; ?>
