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
                "contextsummary"            => 'all',  //all | nextaction
                );


//Default sort order for each query (can be easily overridden within each page...)
//Once all built, can be either (a) simplified for user-editing, (b) create an options page that alters the config file, or (c) placed in the database and options page employed [best option?]

//simplify all options down to a few...

        $sort = array(
            "projectssummary"       => "`projects`.`name` ASC",
            "spacecontextselectbox" => "`context`.`name` ASC",
            "categoryselectbox"     => "`categories`.`category` ASC",
            "checklistselectbox"    => "`checklist`.`title` ASC",
            "listselectbox"         => "`list`.`title` ASC",
            "projectselectbox"      => "`projects`.`name` ASC",
            "timecontextselectbox"  => "`timeitems`.`timeframe` DESC",
            "selectproject"         => "`projects`.`name` ASC",
            "getprojects"           => "`categories`.`category`, `projectattributes`.`deadline`, `projects`.`name` ASC",
            "getlistitems"          => "`listItems`.`item` ASC",
            "getcompleteditems"     => "`itemstatus`.`dateCompleted` DESC, `projects`.`name`, `items`.`title` ASC",
            "getitems"              => "`projects`.`name`, `itemattributes`.`deadline`, `items`.`title` ASC",
            "selectchecklist"       => "`checklist`.`title` ASC",
            "getchecklists"         => "`categories`.`category` ASC",
            "getlists"              => "`categories`.`category` ASC",
            "getchecklistitems"     => "checklistItems.checked DESC, checklistItems.item ASC",
                        );
?>