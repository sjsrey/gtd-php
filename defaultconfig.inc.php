<?php
/*********  Interface Settings ************/

// The following settings change settings for the user interface.  
// These can be left at their default values, or changed if you have a different preference.
$config=array();
$config["title"]= 'GTD-PHP'; // site name (appears at the top of each page)
$config["datemask"] = 'Y-m-d D'; // date format - required
$config["theme"] = 'default'; //default | menu_sidebar
$config["title_suffix"]	= false; // true | false - add filename to title tag
$config["trimLength"] = 72;     // max visible length of descriptions when listing items
$config["trimLengthInReport"] = 0;     // max visible length of descriptions when reporting children
$config["firstDayOfWeek"] = 0; // 0=Sunday, 1=Monday, ... 6=Saturday
$config['ReportMaxCompleteChildren']=0;  // maximum number of child items of any one type shown in itemReport
$config['useLiveEnhancements']=true; // javascript productivity aids: tested on PC/IE7, PC/Firefox2, Linux/Firefox2, Linux/Epiphany, Linux/Konqueror3
$config['showRelativeDeadlines']=false; // Show deadlines as relative days (e.g. "due in 5 days") rather than as dates

// These are the shortcut settings for menu options.  Add a key for any page or page view in the main menus.
// Note IE only allows 26 access keys (a-z).

$acckey = array(
	"item.php?type=i"						=> "i", // add Inbox item
	"item.php?type=p"						=> "p", // add Project
	"listItems.php?quickfind"				=> "f", // quick find
	"listItems.php?type=a"					=> "a", // Actions
	"listItems.php?type=a&amp;nextonly=true"=> "n", // Next Actions
	"listItems.php?type=p"					=> "v", // Projects
	"listItems.php?type=p&someday=true"		=> "m", // Someday/Maybes
	"listItems.php?type=w"					=> "w", // Waiting On
	"listItems.php?type=C"					=> "c", // Checklists
	"listItems.php?type=L"					=> "l", // Lists
	"reportContext.php"						=> "x", // Space Contexts
	"index.php"        						=> "s", // Summary
	"weekly.php"							=> "r" // Weekly Review
);


/*********  Behavior Settings ************/

// The following settings change how the interface behaves.  
// These can be left at their default values, or changed if you have a different preference.

$config["contextsummary"] = 'all';  //all | nextaction (Show all actions on context report, or nextactions only?)
$config["afterCreate"]	= array (  // parent | item | list | another - default view after creating an item
			'i'		=>	'another', // inbox preference
			'a'		=>	'parent', // action preference
			'w'		=>	'parent', // waiting-on preference
			'r'		=>	'parent', // reference preference
			'p'		=>	'list', // project preference
			'm'		=>	'item', // value preference
			'v'		=>	'item', // vision preference
			'o'		=>	'item', // role preference
			'g'		=>	'list' // goal preference
	    );

// uses initials as above; so o=role, m=value, etc., each in single quotes, separated by commas
$config['suppressAsOrphans']="'i','m','v','o','g','p'"; 

/*********  Customize Weekly Review  ************/
$config['reviewProjectsWithoutOutcomes']=true; // false | true - list projects which have no outcome
$config['show7']=false; // false | true - show the Seven Habits of Highly Effective People and Sharpening the Saw in Weekly Review

/*********  Advanced Settings  ************/

//A bit too complicated for the average admin.  Will be simplified in a later release.

//Default sort order for each query.  The sort order can be overridden within each page.

$sort = array(
    "categoryselectbox"      => "`category` ASC"
    ,"getchildren"           => "`dateCompleted` DESC, `deadline` DESC, `title` ASC"
    ,"getitems"              => "`title` ASC"
    ,"getitemsandparent"     => "`type` ASC, `ptitle` ASC, `title` ASC, `deadline` ASC, `dateCreated` DESC"
    ,"getorphaneditems"      => "`type` ASC, `title` ASC"
    ,"spacecontextselectbox" => "`name` ASC"
    ,"timecontextselectbox"  => "`timeframe` DESC"
);

$config["storeRecurrences"] = true; // false | true - when recurring items are completed, store each occurrence as a completed item
$config['useTypesForTimeContexts'] = false; // false | true - Time Contexts will be bound to a particular type
$config['separator'] = '^&*#@#%&*%^@$^*$$&%#@#@^^'; // should be an arbitrary string that you'll never use in titles of items; used to separate titles in mysql queries
$config['forceAllFields'] = false; // false | true - all fields will always be displayed on item.php
$config['allowChangingTypes'] = false; // false | true - allows the user to change the types of any item (false=change only inbox items)
$config['suppressAdmin'] = false; // false | true - suppresses the Admin option from the menu
$config['suppressCustomRecurrences']=true; // false | true - prevents user entering recurrence patterns as plain text
$config['charset'] = 'ISO8859-15'; // character-encoding for pages: utf-8 IS EXPERIMENTAL; other multi-byte character sets are not supported
$config['withholdVersionInfo']=false; // true | false - if false, will send the version numbers of your installations of gtd-php, PHP and MySQL when you report a bug
$config['showTreeInSummary']=false; // false | true -if true, show the tree of projects on the front page
$config['debugKey']='h';
$config['debug']=0;
// php closing tag has been omitted deliberately, to avoid unwanted blank lines being sent to the browser
