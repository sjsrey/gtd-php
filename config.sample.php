<?php
include_once('gtd_constants.inc.php');

/******************************************/
/**********   REQUIRED SETTINGS    ********/
/******************************************/


/*********  Database Settings ************/

// Database settings are NOT optional.

$config = array(

    //connection information
        "host"                      => 'localhost', //the hostname of your database server
        "db"                        => '', //the name of your database
        "prefix"					=> 'gtd_', // the GTD table prefix for your installation (optional)
        "user"                      => '', //username for database access
        "pass"                      => '', //database password
    //database information
        "dbtype"                    => 'mysql',  //database type: currently only mysql is valid.  DO NOT CHANGE!
);



/******************************************/
/**********   OPTIONAL SETTINGS    ********/
/******************************************/


/*********  Interface Settings ************/

// The following settings change settings for the user interface.  
// These can be left at their default values, or changed if you have a different preference.

$config["title"]= 'GTD-PHP'; // site name (appears at the top of each page)
$config["datemask"] = 'Y-m-d D'; // date format - required
$config["theme"] = 'default'; //default | menu_sidebar
$config["title_suffix"]	= false; // true | false - add filename to title tag
$config["trimLength"] = 72;     // max visible length of descriptions when listing items
$config["firstDayOfWeek"] = 0; // 0=Sunday, 1=Monday, ... 6=Saturday
        

// These are the shortcut settings for menu options.  Add a key for any page or page view in the main menus.
// Note IE only allows 26 access keys (a-z).

$acckey = array(
	"about.php"								=> "", // License
	"achievements.php"						=> "", // Achievements
	"credits.php"							=> "", // Credits
	"donate.php"							=> "", // Donate
	"item.php?type=a"						=> "", // add Action
	"item.php?type=a&nextonly=true"			=> "", // add Next Action
	"item.php?type=g"						=> "", // add Goal
	"item.php?type=i"						=> "i", // add Inbox item
	"item.php?type=m"						=> "", // add Value
	"item.php?type=o"						=> "", // add Role
	"item.php?type=p"						=> "p", // add Project
	"item.php?type=p&someday=true"			=> "", // add Someday/Maybe
	"item.php?type=r"						=> "", // add Reference
	"item.php?type=v"						=> "", // add Vision
	"item.php?type=w"						=> "", // add Waiting On
	"leadership.php"						=> "", // Leadership
	"listChecklist.php"						=> "c", // Checklists
	"listItems.php?type=a"					=> "a", // Actions
	"listItems.php?type=a&nextonly=true"	=> "n", // Next Actions
	"listItems.php?type=a&tickler=true"		=> "", // Tickler File
	"listItems.php?type=g"					=> "", // Goals
	"listItems.php?type=i"					=> "", // Inbox
	"listItems.php?type=m"					=> "", // Values
	"listItems.php?type=o"					=> "", // Roles
	"listItems.php?type=p"					=> "v", // Projects
	"listItems.php?type=p&someday=true"		=> "m", // Someday/Maybes
	"listItems.php?type=r"					=> "", // References
	"listItems.php?type=v"					=> "", // Visions
	"listItems.php?type=w"					=> "w", // Waiting On
	"listList.php"							=> "l", // Lists
	"management.php"						=> "", // Management
	"newCategory.php"						=> "", // new Categories
	"newChecklist.php"						=> "", // new Checklist
	"newContext.php"						=> "", // new Space Contexts
	"newList.php"							=> "", // new List
	"newTimeContext.php"					=> "", // new Time Contexts
	"note.php"								=> "o", // Note
	"orphans.php"							=> "", // Orphaned Items
	"preferences.php"						=> "", // User Preferences
	"reportCategory.php"					=> "g", // Categories
	"reportContext.php"						=> "x", // Space Contexts
	"reportTimeContext.php"					=> "t", // Time Contexts
	"summaryAlone.php"						=> "s", // Summary
	"weekly.php"							=> "r" // Weekly Review
);


/*********  Behavior Settings ************/

// The following settings change how the interface behaves.  
// These can be left at their default values, or changed if you have a different preference.

$config["contextsummary"] = 'all';  //all | nextaction (Show all actions on context report, or nextactions only?)
$config["nextaction"] = 'multiple'; //single | multiple (Allow single or multiple nextactions per project)
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




/*********  Customize Weekly Review  ************/

// Entirely optional: add custom items to the weekly review.  
// Uncomment to use, add more fields to the array for more lines.

/*
$custom_review = array(
	"Play the Lottery" => "Before Saturday's drawing!",
	"Pay Allowances" => "I want the kids to let me move in after I retire.",
	"Check my Oil" => "Check the oil in the car.",
	"Send Update" => "Send Weekly Update to Tom"
);
*/


/*********  Advanced Settings  ************/

//A bit too complicated for the average admin.  Will be simplified in a later release.

//Default sort order for each query.  The sort order can be overridden within each page.

$sort = array(
    "spacecontextselectbox" => "cn.`name` ASC",
    "categoryselectbox"     => "c.`category` ASC",
    "checklistselectbox"    => "cl.`title` ASC",
    "listselectbox"         => "l.`title` ASC",
    "parentselectbox"       => "i.`title` ASC",
    "timecontextselectbox"  => "ti.`timeframe` DESC",
    "getlistitems"          => "li.`item` ASC",
    "getitemsandparent"     => "ptitle ASC, pcatname ASC, type ASC, deadline ASC, title ASC, dateCreated DESC",
    "getorphaneditems"      => "ia.`type` ASC, i.`title` ASC",
    "selectchecklist"       => "cl.`title` ASC",
    "getchecklists"         => "c.`category` ASC",
    "getlists"              => "c.`category` ASC",
    "getchecklistitems"     => "cli.`checked` DESC, cli.`item` ASC",
    "getchildren"           => "ia.`type` ASC",
    "getitems"              => "i.`title` ASC ",
    "getnotes"              => "tk.`date` DESC ",
);

$config["storeRecurrences"] = true; // false | true - when recurring items are completed, store each occurrence as a completed item

/*********  Developer Settings ************/

/* The debug value is generally for the developers of the application.  You will probably want this to remain 0
Values: (use "|" to combine, "&" to test)
            0 - no debugging output
_GTD_ERRORS   - display errors
_GTD_DEBUG    - display debugging text (there will be lots of it - use debugKey to toggle its display)
_GTD_FREEZEDB - do not execute commands which would otherwise update the items table: use in conjunction with _GTD_DEBUG to display sql commands without running them
*/
$config["debug"] = 0;  // integer (actually a set of boolean flags)
$config["debugKey"] = 'H'; // the key that will toggle the display of debug text - a letter here must typed in upper case.
