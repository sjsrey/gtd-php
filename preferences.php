<?php
/*
TOFIX - variables still to do:
$config['events']=$config['addons']=array();
$config['addons']['achievements']=array(
        "link"=>"addons/achievements/achievements.php", 'title'=>"Notable Achievements", 'label' => "Achievements",
        'where'=>'listItems.php?type=*&amp;tickler=true&amp;liveparents=*','when'=>'after','options'=>array('jpgraphdir'=>'../jpgraph/'));
$config['events'][_GTD_ON_HEADER]['*'][]="addons/ajax/insertjs.inc.php";
   ======================================================================================
*/
function makeOptionsTab($array,$values,$tabname,$varprefix='',$textsize=10) {
    global $checkboxes;
    echo "<h2 class='tab' id='$tabname'>$tabname</h2>
          <div class='tabsheet' id='sheet-$tabname'>";
    //echo '<pre>values=',print_r($values,true),' array=',print_r($array,true),'</pre>'; // debugging line
    foreach ($array as $option) {
        $name=$varprefix.$option[0];
        echo "<div class='formrow'>\n";
        switch ($option[1]) {
            case 'checkbox':
                echo "<input type='checkbox' name='$name' "
                    ,($values[$option[0]]) ? " checked='checked' ": ''
                    ,"/>";
                $checkboxes.="$name,";
                break;
            case 'heading':
                echo '<hr />';
                break;
            case 'text': // deliberately flows through
                echo "<input type='text' name='$name' size='$textsize' value='"
                    ,(empty($values[$option[0]])) ? '' : makeclean($values[$option[0]])
                    ,"' />\n";
                break;
            default: // it's a select
                echo "<select name='$name'>";
                foreach ($option[1] as $optval=>$opttext)
                    echo "<option value='$optval'"
                        ,($values[$option[0]]==$optval) ? " selected='selected' ": ''
                        ,">",makeclean($opttext),"</option>\n";
                echo "</select>";
                break;
        }
        echo "<label>{$option[2]}</label></div>\n";
    }
    ?>
        <div class='formbuttons'>
            <input type='submit' value='Apply' name='submit' />
            <input type='reset'  value='Reset' name='reset'  />
        </div>
    </div>
    <?php
}
/*
   ======================================================================================
*/
$menu='';
include_once 'header.inc.php';

// get a list of theme sub-directories, to go into the dropdown selector
$themes=array();
$checkboxes='';
$themedir = "./themes";
if ($handle = opendir($themedir)) {
	while (false !== ($file = readdir($handle))) {
		if ($file[0] !== "." && is_dir($themedir. "/" . $file)) {
			$themes[$file] = $file;
		}
	}
	closedir($handle);
}
$config=$_SESSION['config'];
$cookievars=array('theme','useLiveEnhancements');
foreach ($cookievars as $key)
    $config[$key]=$_SESSION[$key];

?>
<form action="processPreferences.php" method="post" id='optionsform'>
<div id='tabrow' class='hidden'></div>
<div>
<?php

/* ------------------------------------------------------------------------
    basic options
*/
$array=array(
    array('title','text','Title'),
    array('trimLength','text','Maximum length of descriptions in lists of items (0=display all)'),
    array('trimLengthInReport','text','Maximum length of descriptions of children in item reports (0=display all)'),
    array('ReportMaxCompleteChildren','text','Maximum number of children of each type to display in item reports (0=display all)'),
    array('theme',$themes,'Theme'),
    array('firstDayOfWeek',array(0=>'Sunday',1=>'Monday',2=>'Tuesday',
            3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday'
            ),'First day of week'),
    array('title_suffix','checkbox','Add filename to page title'),
    array('contextsummary','checkbox','In the space contexts report, show only <b>next</b> actions'),
    array('useLiveEnhancements','checkbox','Use Live enhancements'),
    array('reviewProjectsWithoutOutcomes','checkbox','In the weekly review, identify projects without outcomes'),
    array('storeRecurrences','checkbox','When recurring items are completed, store each occurrence as a completed item')
);
makeOptionsTab($array,$config,'Options');
/* ------------------------------------------------------------------------
    Addons: TOFIX scan for add-ons, grab auto-config file, and offer option of enabling / disabling
$array=array();
makeOptionsTab($array,$config,'Add-ons');
*/
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
    array('charset','text','PHP name of character set (codepage) to use'),
    array('radioButtonsForNextPage','checkbox','When editing an item, display choice of next page as a radio group rather than as submit buttons'),
    array('useTypesForTimeContexts','checkbox','Bind each time-context to a particular item type (action, project, etc'),
    array('forceAllFields','checkbox','Display all possible fields, when editing any item'),
    array('allowChangingTypes','checkbox','Enable option of changing the types of <b>any</b> item (rather than just inbox items)'),
    array('withholdVersionInfo','checkbox','When you file a bug report, withhold information about the versions of PHP and database software you are using'),
    array('showAdmin','checkbox','Show administrator options in menus'),
    array('allowCustomRecurrences','checkbox','Enable entry of rfc2445 text directly to specify item recurrence patterns'),
    array('show7','checkbox','Show the Seven Habits of Highly Effective People and Sharpening the Saw in Weekly Review'),
    array('separator','text','Separator string for MySQL GROUP queries'),
);
makeOptionsTab($array,$config,'Advanced');
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
    shortcut keys TOFIX: cycle through all options in $menu, for shortcut keys
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
    echo "<input type='hidden' name='lkeys$keyentry' value='",makeclean($link),"' />\n";
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
    <input type='hidden' name='checkboxes' value='<?php echo $checkboxes; ?>' />
</div>
</form>
<?php include_once 'footer.inc.php'; ?>
