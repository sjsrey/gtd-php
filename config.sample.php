<?php
//Configuration settings
$config = array(

    //connection information
        "host"                      => 'localhost', //the hostname of your database server
        "db"                        => '', //the name of your database
        "user"                      => '', //username for database access
        "pass"                      => '', //database password

    //database information
        "dbtype"                    => 'mysql',  //database type: currently only mysql is valid.  DO NOT CHANGE!

    //user preferences : MOVE TO DATABASE
        "title"			    => 'GTD-PHP', // site name (appears at the top of each page)
        "debug"                     => 'false',  // false | true |  developer
        "theme"                     => 'default', //default | menu_sidebar
        "contextsummary"            => 'all',  //all | nextaction (Show all actions on context report, or nextactions only?)
        "nextaction"                => 'multiple', //single | multiple (Allow single or multiple nextactions per project)
        );


//Default sort order for each query (can be easily overridden within each page...)
//Once all built, can be either (a) simplified for user-editing, (b) create an options page that alters the config file, or (c) placed in the database and options page employed [best option?]
//need to alter once sqlabstraction is done to turn $sort into a simple variable string in mysql.inc.php, and pass the correct sort via the php code-- default is in config file/database/admin page (defined by report --or sort order--, not query), and can be modified on the page as needed.
//simplify all options down to a few...

$sort = array(
    "projectssummary"       => "`projects`.`name` ASC",
    "spacecontextselectbox" => "`context`.`name` ASC",
    "categoryselectbox"     => "`categories`.`category` ASC",
    "checklistselectbox"    => "`checklist`.`title` ASC",
    "listselectbox"         => "`list`.`title` ASC",
    "projectselectbox"      => "`projects`.`name` ASC",
    "timecontextselectbox"  => "`timeitems`.`timeframe` DESC",
    "getprojects"           => "`categories`.`category`, `projectattributes`.`deadline`, `projects`.`name` ASC",
    "getlistitems"          => "`listItems`.`item` ASC",
    "getcompleteditems"     => "`itemstatus`.`dateCompleted` DESC, `projects`.`name`, `items`.`title` ASC",
    "getitems"              => "`projects`.`name`, `itemattributes`.`deadline`, `items`.`title` ASC",
    "selectchecklist"       => "`checklist`.`title` ASC",
    "getchecklists"         => "`categories`.`category` ASC",
    "getlists"              => "`categories`.`category` ASC",
    "getchecklistitems"     => "`checklistItems`.`checked` DESC, `checklistItems`.`item` ASC",
    );
?>