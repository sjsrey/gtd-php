<?php
   require_once("ses.php")
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
		  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>
<?php
$config = array();
$options = array();
$sort = array();
require_once("config.php");
if ($config['debug']!=='false') {
/*	if (version_compare(PHP_VERSION, "5.0.0", ">="))
		error_reporting( E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_STRICT);
	else
*/		error_reporting( E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);
}
//CONNECT TO DATABASE: this will need modification to connect to other dtabases (use SWITCH)
$connection = mysql_connect($config['host'], $config['user'], $config['pass']) or die ("Unable to connect!");
mysql_select_db($config['db']) or die ("Unable to select database!");

require_once("gtdfuncs.php");
require_once("query.inc.php");
$thisurl=parse_url($_SERVER[PHP_SELF]);

$title = '	<title>'.$config['title'];
if ($config['title_suffix']) { $title .= '-'.basename($thisurl['path'],".php");}
$title .= "</title>\n";

echo $title;

if ($config['debug']==='developer')
	echo '<style type="text/css">pre,.debug {}</style>';

$config['theme']=$_SESSION['theme'];
?>

	<!-- theme main stylesheet -->
	<link rel="stylesheet" href="themes/<?php echo $config['theme']; ?>/style.css" type="text/css"/>

	<!-- theme screen stylesheet (should check to see if this actually exists) -->
	<link rel="stylesheet" href="themes/<?php echo $config['theme']; ?>/style_screen.css" type="text/css" media="screen" />

	<!-- theme script (should check to see if this actually exists) -->
	<script type="text/javascript" src="themes/<?php echo $config['theme']; ?>/theme.js"></script>
	
	<!-- printing stylesheet -->
	<link rel="stylesheet" href="print.css" type="text/css" media="print" />

	<link rel="shortcut icon" href="./favicon.ico" />
	
	<!-- calendar stylesheet -->
	<link rel="stylesheet" type="text/css" media="all" href="calendar-win2k-cold-1.css" title="win2k-cold-1" />

	<!-- main calendar program -->
	<script type="text/javascript" src="calendar.js"></script>

	<!-- language for the calendar -->
	<script type="text/javascript" src="lang/calendar-en.js"></script>

	<!-- the following script defines the Calendar.setup helper function, which makes
		  adding a calendar a matter of 1 or 2 lines of code. -->
	<script type="text/javascript" src="calendar-setup.js"></script>

	<!-- sort tables -->
	<script type="text/javascript" src="gtdfuncs.js"></script>
    
    <?php if ($config['debug']==='developer')
		echo '<script type="text/javascript">aps_debugInit("',$config['debugKey'],'");</script>'; ?>
    
</head>
<body>

<div id="container">
<div id="header">
	<h1 id='sitename'><a href='index.php'><?php echo $config['title'];?></a></h1>
</div>

<?php
function makemenu($list) {
	global $acckey;
	foreach ($list as $info) {
		$thislink = $info['link'];
		$output = "<li><a href='".$info['link']."' title='".$info['title']."'";
		if ((isset($acckey[$thislink])) && ($acckey[$thislink] !== '')) $output .= " accesskey='".$acckey[$thislink]."'";
		$output .= ">".$info['label']."";
		if ((isset($acckey[$thislink])) && ($acckey[$thislink] !== ''))  $output .= " (<u>".$acckey[$thislink]."</u>)";
		$output .= "</a></li>\r\n";
		echo $output;
	}
}
?>

<div id="menudiv">
	<ul id="menulist">

	 	<li>Capture
			<ul>
				<?php 
				$thismenu[] = array("link"=>"item.php?type=i", 'title'=>"Drop an item into the inbox", 'label' => "Inbox item");
				$thismenu[] = array('link'=>"note.php", 'title'=>"Create a reminder note", 'label'=>'Note'); 
				$thismenu[] = array("link"=>"item.php?type=a&amp;nextonly=true", 'title'=>"Create a new next action", 'label' => "Next Action");
				$thismenu[] = array("link"=>"item.php?type=a", 'title'=>"Create a new action", 'label' => "Action");
				$thismenu[] = array("link"=>"item.php?type=w", 'title'=>"Create a new waiting on item", 'label' => "Waiting On");
				$thismenu[] = array("link"=>"item.php?type=r", 'title'=>"Create a reference", 'label' => "Reference");
				$thismenu[] = array("link"=>"item.php?type=p", 'title'=>"Create a new project", 'label' => "Project");
				$thismenu[] = array("link"=>"item.php?type=p&amp;someday=true", 'title'=>"Create a future project", 'label' => "Someday/Maybe");
				$thismenu[] = array("link"=>"item.php?type=o", 'title'=>"Define a new role", 'label' => "Role");
				$thismenu[] = array("link"=>"item.php?type=g", 'title'=>"Define a new goal", 'label' => "Goal");
				$thismenu[] = array("link"=>"item.php?type=v", 'title'=>"Define a new vision", 'label' => "Vision");
				$thismenu[] = array("link"=>"item.php?type=m", 'title'=>"Define a new value", 'label' => "Value");
				$thismenu[] = array("link"=>"newList.php", 'title'=>"Create a general purpose list", 'label' => "List");
				$thismenu[] = array("link"=>"newChecklist.php", 'title'=>"Create a reusable list", 'label' => "Checklist");
				$thismenu[] = array("link"=>"newContext.php", 'title'=>"Define a geographical context", 'label' => "Space Context");
				$thismenu[] = array("link"=>"newTimeContext.php", 'title'=>"Define a time window for items", 'label' => "Time Context");
				$thismenu[] = array("link"=>"newCategory.php", 'title'=>"Define an new meta-category", 'label' => "Category");
				makemenu($thismenu);
				unset($thismenu);
				?>
			</ul>

		<li>Process
			<ul>
				<?php 
				$thismenu[] = array("link"=>"listItems.php?type=i", 'title'=>"Inbox", 'label' => "Inbox");
				$thismenu[] = array("link"=>"reportCategory.php", 'title'=>"Active items sorted by category", 'label' => "Categories");
				$thismenu[] = array("link"=>"reportContext.php", 'title'=>"Active items sorted by space context", 'label' => "Space Contexts");
				$thismenu[] = array("link"=>"reportTimeContext.php", 'title'=>"Active items sorted by time context", 'label' => "Time Contexts");
				$thismenu[] = array("link"=>"listItems.php?type=p", 'title'=>"Projects", 'label' => "Projects");
				$thismenu[] = array("link"=>"listItems.php?type=a&amp;nextonly=true", 'title'=>"Next actions", 'label' => "Next Actions");
				$thismenu[] = array("link"=>"listItems.php?type=a", 'title'=>"Actions", 'label' => "Actions");
				$thismenu[] = array("link"=>"listItems.php?type=w", 'title'=>"Waiting On", 'label' => "Waiting On");
				$thismenu[] = array("link"=>"listItems.php?type=r", 'title'=>"References", 'label' => "References");
				$thismenu[] = array("link"=>"listItems.php?type=p&amp;someday=true", 'title'=>"Someday projects", 'label' => "Someday/Maybes");
				$thismenu[] = array("link"=>"listList.php", 'title'=>"General-purpose lists", 'label' => "Lists");
				$thismenu[] = array("link"=>"listChecklist.php", 'title'=>"Reusable checklists", 'label' => "Checklists");
				$thismenu[] = array("link"=>"summaryAlone.php", 'title'=>"Summary view", 'label' => "Summary");
				makemenu($thismenu);
				unset($thismenu);
				?>

			</ul>

		<li>Review
			<ul>
				<?php
				$thismenu[] = array("link"=>"weekly.php", 'title'=>"Steps in the Weekly Review", 'label' => "Weekly Review");
				$thismenu[] = array("link"=>"orphans.php", 'title'=>"List items without a parent item", 'label' => "Orphaned Items");
				$thismenu[] = array("link"=>"leadership.php", 'title'=>"Leadership view", 'label' => "Leadership");
				$thismenu[] = array("link"=>"management.php", 'title'=>"Management view", 'label' => "Management");
				$thismenu[] = array("link"=>"listItems.php?type=m", 'title'=>"Values / Mission", 'label' => "Values");
				$thismenu[] = array("link"=>"listItems.php?type=v", 'title'=>"Visions", 'label' => "Visions");
				$thismenu[] = array("link"=>"listItems.php?type=o", 'title'=>"Roles / Areas of Responsibility", 'label' => "Roles");
				$thismenu[] = array("link"=>"listItems.php?type=g", 'title'=>"Goals", 'label' => "Goals");
				$thismenu[] = array("link"=>"achievements.php", 'title'=>"Notable Achievements", 'label' => "Achievements");
				$thismenu[] = array("link"=>"listItems.php?type=a&amp;tickler=true", 'title'=>"Hidden items and reminders", 'label' => "Tickler File");

				makemenu($thismenu);
				unset($thismenu);
				?>

			</ul>

                <li>Lists
			<ul>
				<?php
				$thismenu[] = array("link"=>"listList.php", 'title'=>"General-purpose lists", 'label' => "Lists");
				$thismenu[] = array("link"=>"listChecklist.php", 'title'=>"Reusable checklists", 'label' => "Checklists");

				makemenu($thismenu);
				unset($thismenu);
				?>
			</ul>

                <li>Configure

			<ul><!-- need to change from capture to management view  //-->
				<?php
				$thismenu[] = array("link"=>"newCategory.php", 'title'=>"Meta-categories", 'label' => "Categories");
				$thismenu[] = array("link"=>"newContext.php", 'title'=>"Spatial contexts", 'label' => "Space Contexts");
				$thismenu[] = array("link"=>"newTimeContext.php", 'title'=>"Time contexts", 'label' => "Time Contexts");
				$thismenu[] = array("link"=>"preferences.php", 'title'=>"User preferences", 'label' => "User Preferences");

				makemenu($thismenu);
				unset($thismenu);
				?>
			</ul>

		<li>About
			<ul>
				<?php
				$thismenu[] = array("link"=>"about.php", 'title'=>"About the GTD-PHP license", 'label' => "License");
				$thismenu[] = array("link"=>"credits.php", 'title'=>"The GTD-PHP development team", 'label' => "Credits");
				$thismenu[] = array("link"=>"http://toae.org/boards", 'title'=>"Help and development discussions", 'label' => "Forum");
				$thismenu[] = array("link"=>"http://www.gtd-php.com", 'title'=>"Documentation", 'label' => "Wiki");
				$thismenu[] = array("link"=>"https://www.hosted-projects.com/trac/toae/gtdphp", 'title'=>"Bug tracking and project development", 'label' => "Trac Page");
				$thismenu[] = array("link"=>"http://www.frappr.com/gtdphp", 'title'=>"Tell us where you are", 'label' => "Frappr Map");
				$thismenu[] = array("link"=>"donate.php", 'title'=>"Help us defray our costs", 'label' => "Donate");

				makemenu($thismenu);
				unset($thismenu);
				?>
			</ul>
	</ul>
</div>

<div id="main">
