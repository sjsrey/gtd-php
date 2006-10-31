<?php
//Configuration settings
        $config = array(

            //connection information
                "host"                      => 'localhost', //the hostname of your database server
                "db"                         => 'gtd6', //the name of your database
                "user"                      => 'serge', //username for database access
                "pass"                      => 'some_pass', //database password

            //database information
                "dbtype"                    => 'mysql',  //database type: currently only mysql is valid.  DO NOT CHANGE!

            //user preferences
                "debug"                     => 'true',  // false | true |  developer
                "theme"                     => 'default', //default | menu_sidebar
                "contextsummary"     => 'all',  //all | nextaction
                );


//Default sort order for each query (can be easily overridden within each page...)
//Once all built, can be either (a) simplified for user-editing, (b) create an options page that alters the config file, or (c) placed in the database and options page employed [best option?]

//simplify all options down to a few...

        $sort = array(
            "projectssummary"       => "`projects`.`name` ASC",
            "spacecontextselectbox" => "`context`.`name` ASC",
            "categoryselectbox"     => "`categories`.`category` ASC",
            "projectselectbox"      => "`projects`.`name` ASC",
            "timecontextselectbox"  => "`timeitems`.`timeframe` DESC",
            "selectactiveprojects"  => "`categories`.`category`, `projectattributes`.`deadline`, `projects`.`name` ASC",
            "getlistitems"          => "`listItems`.`item` ASC",
            "getcompletedlistitems" => "`listItems`.`dateCompleted` ASC",
            "getitems"              => "`projects`.`name`, `itemattributes`.`deadline`, `items`.`title` ASC",
                );
?>
