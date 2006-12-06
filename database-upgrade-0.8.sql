 -- DUPLICATE CURRENT TABLE --

CREATE DATABASE `newgtd`;

CREATE TABLE `newgtd`.`categories` ( `categoryId` int( 10 ) unsigned NOT NULL auto_increment , `category` text NOT NULL , `description` text, PRIMARY KEY ( `categoryId` ) );
INSERT INTO `newgtd`.`categories` SELECT * FROM `gtd`.`categories`;

CREATE TABLE `newgtd`.`checklist` ( `checklistId` int( 10 ) unsigned NOT NULL auto_increment , `title` text NOT NULL , `categoryId` int( 10 ) unsigned NOT NULL default '0', `description` text, PRIMARY KEY ( `checklistId` ) ) COMMENT = 'Reusable Checklists';
INSERT INTO `newgtd`.`checklist` SELECT * FROM `gtd`.`checklist`;

CREATE TABLE `newgtd`.`checklistItems` ( `checklistItemId` int( 10 ) unsigned NOT NULL auto_increment , `item` text NOT NULL , `notes` text, `checklistId` int( 10 ) unsigned NOT NULL default '0', `checked` enum( 'y', 'n' ) NOT NULL default 'n', PRIMARY KEY ( `checklistItemId` ) , KEY `checklistId` ( `checklistId` ) , FULLTEXT KEY `notes` ( `notes` ) , FULLTEXT KEY `item` ( `item` ) ) COMMENT = 'Individual Checklist items';
INSERT INTO `newgtd`.`checklistItems` SELECT * FROM `gtd`.`checklistItems`;

CREATE TABLE `newgtd`.`context` ( `contextId` int( 10 ) unsigned NOT NULL auto_increment , `name` text NOT NULL , `description` text, PRIMARY KEY ( `contextId` ) ) COMMENT = 'Item Contexts' ;
INSERT INTO `newgtd`.`context` SELECT * FROM `gtd`.`context`;

CREATE TABLE `newgtd`.`goals` ( `id` int( 11 ) NOT NULL auto_increment , `goal` longtext, `description` longtext, `created` date default NULL , `deadline` date default NULL , `completed` date default NULL , `type` enum( 'weekly', 'quarterly' ) default NULL , `projectId` int( 11 ) default NULL , PRIMARY KEY ( `id` ) ) ;
INSERT INTO `newgtd`.`goals` SELECT * FROM `gtd`.`goals`;

CREATE TABLE `newgtd`.`itemattributes` ( `itemId` int( 10 ) unsigned NOT NULL auto_increment , `type` enum( 'a', 'r', 'w' ) NOT NULL default 'a', `projectId` int( 10 ) unsigned NOT NULL default '0', `contextId` int( 10 ) unsigned NOT NULL default '0', `timeframeId` int( 10 ) unsigned NOT NULL default '0', `deadline` date default NULL , `repeat` int( 10 ) unsigned NOT NULL default '0', `suppress` enum( 'y', 'n' ) NOT NULL default 'n', `suppressUntil` int( 10 ) unsigned default NULL , PRIMARY KEY ( `itemId` ) , KEY `projectId` ( `projectId` ) , KEY `contextId` ( `contextId` ) , KEY `suppress` ( `suppress` ) , KEY `type` ( `type` ) , KEY `timeframeId` ( `timeframeId` ) ) COMMENT = 'Characteristics of items (action, waiting, reference, etc)' ;
INSERT INTO `newgtd`.`itemattributes` SELECT * FROM `gtd`.`itemattributes`;

CREATE TABLE `newgtd`.`items` ( `itemId` int( 10 ) unsigned NOT NULL auto_increment , `title` text NOT NULL , `description` longtext, PRIMARY KEY ( `itemId` ) , FULLTEXT KEY `title` ( `title` ) , FULLTEXT KEY `description` ( `description` ) ) COMMENT = 'All individual items (runway) --  actions, references, waiting' ;
INSERT INTO `newgtd`.`items` SELECT * FROM `gtd`.`items`;

CREATE TABLE `newgtd`.`itemstatus` ( `itemId` int( 10 ) unsigned NOT NULL auto_increment , `dateCreated` date NOT NULL default '0000-00-00', `lastModified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP , `dateCompleted` date default NULL , `completed` int( 10 ) unsigned default NULL , PRIMARY KEY ( `itemId` ) ) COMMENT = 'Status of items';
INSERT INTO `newgtd`.`itemstatus` SELECT * FROM `gtd`.`itemstatus`;

CREATE TABLE `newgtd`.`list` ( `listId` int( 10 ) unsigned NOT NULL auto_increment , `title` text NOT NULL , `categoryId` int( 10 ) unsigned NOT NULL default '0', `description` text, PRIMARY KEY ( `listId` ) , KEY `categoryId` ( `categoryId` ) , FULLTEXT KEY `description` ( `description` ) , FULLTEXT KEY `title` ( `title` ) ) COMMENT = 'Unordered lists' ;
INSERT INTO `newgtd`.`list` SELECT * FROM `gtd`.`list`;

CREATE TABLE `newgtd`.`listItems` ( `listItemId` int( 10 ) unsigned NOT NULL auto_increment , `item` text NOT NULL , `notes` text, `listId` int( 10 ) unsigned NOT NULL default '0', `dateCompleted` date default '0000-00-00', PRIMARY KEY ( `listItemId` ) , KEY `listId` ( `listId` ) , FULLTEXT KEY `notes` ( `notes` ) , FULLTEXT KEY `item` ( `item` ) ) COMMENT = 'Individual list items';
INSERT INTO `newgtd`.`listItems` SELECT * FROM `gtd`.`listItems`;

CREATE TABLE `newgtd`.`nextactions` ( `projectId` int( 10 ) unsigned NOT NULL default '0', `nextaction` int( 10 ) unsigned NOT NULL default '0', PRIMARY KEY ( `projectId` , `nextaction` ) ) COMMENT = 'Identifies an item as a next action for a project';
INSERT INTO `newgtd`.`nextactions` SELECT * FROM `gtd`.`nextactions`;

CREATE TABLE `newgtd`.`projectattributes` ( `projectId` int( 10 ) unsigned NOT NULL auto_increment , `categoryId` int( 10 ) unsigned NOT NULL default '1', `isSomeday` enum( 'y', 'n' ) NOT NULL default 'n', `deadline` date default NULL , `repeat` int( 11 ) unsigned NOT NULL default '0', `suppress` enum( 'y', 'n' ) NOT NULL default 'n', `suppressUntil` int( 10 ) unsigned default NULL , PRIMARY KEY ( `projectId` ) , KEY `categoryId` ( `categoryId` ) , KEY `isSomeday` ( `isSomeday` ) , KEY `suppress` ( `suppress` ) ) COMMENT = 'Characteristics of projects' ;
INSERT INTO `newgtd`.`projectattributes` SELECT * FROM `gtd`.`projectattributes`;

CREATE TABLE `newgtd`.`projects` ( `projectId` int( 10 ) unsigned NOT NULL auto_increment , `name` text NOT NULL , `description` text, `desiredOutcome` text, PRIMARY KEY ( `projectId` ) , FULLTEXT KEY `desiredOutcome` ( `desiredOutcome` ) , FULLTEXT KEY `name` ( `name` ) , FULLTEXT KEY `description` ( `description` ) ) COMMENT = 'Projects (10,000ft view)';
INSERT INTO `newgtd`.`projects` SELECT * FROM `gtd`.`projects`;

CREATE TABLE `newgtd`.`projectstatus` ( `projectId` int( 10 ) unsigned NOT NULL auto_increment , `dateCreated` date NOT NULL default '0000-00-00', `lastModified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP , `dateCompleted` date default NULL , PRIMARY KEY ( `projectId` ) ) COMMENT = 'Status of projects' ;
INSERT INTO `newgtd`.`projectstatus` SELECT * FROM `gtd`.`projectstatus`;

CREATE TABLE `newgtd`.`tickler` ( `ticklerId` int( 10 ) unsigned NOT NULL auto_increment , `date` date NOT NULL default '0000-00-00', `title` text NOT NULL , `note` longtext, PRIMARY KEY ( `ticklerId` ) , KEY `date` ( `date` ) , FULLTEXT KEY `notes` ( `note` ) ) COMMENT = 'Tickler file' ;
INSERT INTO `newgtd`.`tickler` SELECT * FROM `gtd`.`tickler`;

CREATE TABLE `newgtd`.`timeitems` ( `timeframeId` int( 10 ) unsigned NOT NULL auto_increment , `timeframe` text NOT NULL , `description` text, PRIMARY KEY ( `timeframeId` ) ) COMMENT = 'Item timeframes';
INSERT INTO `newgtd`.`timeitems` SELECT * FROM `gtd`.`timeitems`;

 -- SWITCH TO NEW DATABASE -- 

USE `newgtd`;
 -- Update note tickler file for repeating notes
ALTER TABLE `tickler` ADD `repeat` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `tickler` ADD `suppressUntil` INT UNSIGNED NOT NULL DEFAULT '0';

 -- Create new lookup table -- 
CREATE TABLE `lookup` (`parentId` int(11) NOT NULL default '0', `itemId` int(11) NOT NULL default '0', PRIMARY  KEY (`parentId`,`itemId`));

 -- Copy over projectId and itemId pairs to projectLookup -- 
INSERT INTO `lookup` (`parentId`,`itemId`) SELECT `projectId`,`itemId` FROM `itemattributes`;

 -- Drop projectId from itemattributes -- 
ALTER TABLE `itemattributes` DROP `projectId`;

 -- Add category Id and is Someday to itemattributes table -- 
ALTER TABLE `itemattributes` ADD `isSomeday` ENUM( 'y', 'n' ) NOT NULL DEFAULT 'n' AFTER `type`, ADD `categoryId` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `isSomeday` ;
ALTER TABLE `itemattributes` ADD INDEX ( `isSomeday` );
ALTER TABLE `itemattributes` ADD INDEX ( `categoryId`);

 -- Add desiredOutcome to items table -- 
ALTER TABLE `items` ADD `desiredOutcome` TEXT NULL;
ALTER TABLE `items` ADD FULLTEXT (`desiredOutcome`);

 -- Drop completed from itemstatus table -- 
ALTER TABLE `itemstatus` DROP `completed`;

 -- Alter type enum to be m,v,o,g,p,a,r,w,i; default i -- 
ALTER TABLE `itemattributes` CHANGE `type` `type` ENUM( 'm', 'v', 'o', 'g', 'p', 'a', 'r', 'w', 'i' ) NOT NULL DEFAULT 'i';

 -- Add type to projectattributes table and default all to 'p' -- 
ALTER TABLE `projectattributes` ADD `type` ENUM( 'p' ) NOT NULL DEFAULT 'p' AFTER `projectId`;

 -- Fix nextactions primary key -- 
ALTER TABLE `nextactions` DROP PRIMARY KEY, ADD PRIMARY KEY ( `projectId` , `nextaction`);

 -- Rename projectId to parentId in nextactions -- 
ALTER TABLE `nextactions` CHANGE `projectId` `parentId` INT( 10 ) UNSIGNED NOT NULL DEFAULT'0';

 -- Increment all itemIds by max (projectId+goalId) in items, itemsstatus, itemattributes, nextactions, and lookup -- 
ALTER TABLE `items` ADD `prikey` INT UNSIGNED NOT NULL FIRST;
ALTER TABLE `itemattributes` ADD `prikey` INT UNSIGNED NOT NULL FIRST;
ALTER TABLE `itemstatus` ADD `prikey` INT UNSIGNED NOT NULL FIRST;

ALTER TABLE `items` CHANGE `itemId` `itemId` INT( 10 ) UNSIGNED NOT NULL;
ALTER TABLE `itemattributes` CHANGE `itemId` `itemId` INT( 10 ) UNSIGNED NOT NULL;
ALTER TABLE `itemstatus` CHANGE `itemId` `itemId` INT( 10 ) UNSIGNED NOT NULL;

UPDATE `items` SET `prikey`=`itemId` +( 
	CASE  WHEN (SELECT MAX(`id`) FROM `goals`) IS NULL THEN 0
		ELSE (SELECT MAX(`id`) FROM `goals`)
	END
	)+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	);

UPDATE `itemattributes` SET `prikey`=`itemId`+(
	CASE  WHEN (SELECT MAX(`id`) FROM `goals`) IS NULL THEN 0
		ELSE (SELECT MAX(`id`) FROM `goals`)
	END
	)+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	);


UPDATE `itemstatus` SET `prikey`=`itemId`+(
	CASE  WHEN (SELECT MAX(`id`) FROM `goals`) IS NULL THEN 0
		ELSE (SELECT MAX(`id`) FROM `goals`)
	END
	)+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	);

ALTER TABLE `items` DROP PRIMARY KEY, ADD PRIMARY KEY (`prikey`);
ALTER TABLE `itemattributes` DROP PRIMARY KEY, ADD PRIMARY KEY (`prikey`);
ALTER TABLE `itemstatus` DROP PRIMARY KEY, ADD PRIMARY KEY (`prikey`);

ALTER TABLE `items` DROP `itemId`;
ALTER TABLE `itemattributes` DROP `itemId`;
ALTER TABLE `itemstatus` DROP `itemId`;

ALTER TABLE `items` CHANGE `prikey` `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `itemattributes` CHANGE `prikey` `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `itemstatus` CHANGE `prikey` `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';

DELETE FROM `nextactions` WHERE `nextaction` ='0';
UPDATE `nextactions` SET `nextaction`=`nextaction`+(
	CASE  WHEN (SELECT MAX(`id`) FROM `goals`) IS NULL THEN 0
		ELSE (SELECT MAX(`id`) FROM `goals`)
	END
	)+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	);

ALTER TABLE `lookup` ADD `prikey` INT UNSIGNED NOT NULL;
UPDATE `lookup` SET `prikey` =`itemId`+(
	CASE  WHEN (SELECT MAX(`id`) FROM `goals`) IS NULL THEN 0
		ELSE (SELECT MAX(`id`) FROM `goals`)
	END
	)+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	);
ALTER TABLE `lookup` DROP PRIMARY KEY, ADD PRIMARY KEY (`parentId` , `prikey`);
ALTER TABLE `lookup` DROP `itemId`;
ALTER TABLE `lookup` CHANGE `prikey` `itemId` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';

 -- Copy over data from projects, projectattributes, projectstatus -- 
INSERT INTO `items` (`itemId`,`title`,`description`,`desiredOutcome`) SELECT `projectId`,`name`,`description`,`desiredOutcome` FROM `projects`;
INSERT INTO `itemattributes`(`itemId`,`type`,`categoryId`,`isSomeday`,`deadline`,`repeat`,`suppress`,`suppressUntil`) SELECT `projectId`,`type`,`categoryId`,`isSomeday`,`deadline`,`repeat`,`suppress`,`suppressUntil` FROM `projectattributes`;
INSERT INTO `itemstatus` (`itemId`,`dateCreated`, `lastModified`, `dateCompleted`) SELECT `projectId`,`dateCreated`, `lastModified`, `dateCompleted` FROM `projectstatus`;

 -- Increment goals table by max projectId -- 
ALTER TABLE `goals` ADD `prikey` INT UNSIGNED NOT NULL FIRST;
ALTER TABLE `goals` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL;
UPDATE `goals` SET `prikey`=`id`+(
	CASE  WHEN (SELECT MAX(`projectId`) FROM `projects`) IS NULL THEN 0
		ELSE (SELECT MAX(`projectId`) FROM `projects`)
	END
	);
ALTER TABLE `goals` DROP PRIMARY KEY, ADD PRIMARY KEY (`prikey`);
ALTER TABLE `goals` DROP `id`;
ALTER TABLE `goals` CHANGE `prikey` `id` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';

 -- Move goal type data to timeitems -- 
ALTER TABLE `timeitems` ADD `type` ENUM( 'v', 'o', 'g', 'p', 'a' ) NOT NULL DEFAULT 'a';
ALTER TABLE `timeitems` ADD INDEX ( `type` );
ALTER TABLE `goals` ADD `timeframeId` INT UNSIGNED NOT NULL;
UPDATE `goals` SET `timeframeId`= (1 + (
	CASE  WHEN (SELECT MAX(`timeframeId`) FROM `timeitems`) IS NULL THEN 0
		ELSE (SELECT MAX(`timeframeId`) FROM `timeitems`)
	END
	)) WHERE `type`='weekly';
UPDATE `goals` SET `timeframeId`= (2 + (
	CASE  WHEN (SELECT MAX(`timeframeId`) FROM `timeitems`) IS NULL THEN 0
		ELSE (SELECT MAX(`timeframeId`) FROM `timeitems`)
	END
	)) WHERE `type`='quarterly';
ALTER TABLE `goals` CHANGE `type` `type` ENUM('g') NOT NULL DEFAULT 'g';

 -- Copy over data from goals -- 
INSERT INTO `items` (`itemId`,`title`,`description`) SELECT `id`,`goal`,`description` FROM `goals`;
INSERT INTO `itemattributes` (`itemId`,`type`,`timeframeId`,`deadline`) SELECT `id`,`type`,`timeframeId`, `deadline` FROM `goals`;
INSERT INTO `itemstatus` (`itemId`,`dateCreated`, `dateCompleted`) SELECT `id`, `created`, `completed` FROM `goals`;
INSERT INTO `lookup` (`parentId`,`itemId`) SELECT `projectId`,`id` FROM `goals`;
INSERT INTO `timeitems` ( `timeframeId` , `timeframe` , `description` , `type` ) VALUES (NULL , 'Weekly', NULL, 'g'), (NULL , 'Quarterly', NULL , 'g');

 -- Drop projectattributes, projectstatus, projects, and goals tables -- 
DROP TABLE `projectattributes`,`projects`,`projectstatus`,`goals` ;

 -- Reorder tables by itemId -- 
ALTER TABLE `items`  ORDER BY `itemId`;
ALTER TABLE `itemattributes`  ORDER BY `itemId`;
ALTER TABLE `itemstatus`  ORDER BY `itemId`;
ALTER TABLE `itemattributes` ADD INDEX ( `isSomeday`);

 -- Replace Autoincrement on primary keys -- 
ALTER TABLE `items` CHANGE `itemId` `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `itemattributes` CHANGE `itemId` `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT;
 ALTER TABLE `itemstatus` CHANGE `itemId` `itemId` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT;

 -- Update indexes and fulltext searches -- 
ALTER TABLE `timeitems` ADD FULLTEXT (`timeframe`);
ALTER TABLE `timeitems` ADD FULLTEXT (`description`);
ALTER TABLE `tickler` ADD FULLTEXT (`title`);
ALTER TABLE `context` ADD FULLTEXT (`name`);
ALTER TABLE `context` ADD FULLTEXT (`description`);
ALTER TABLE `checklist` ADD FULLTEXT (`description`);
ALTER TABLE `checklist` ADD FULLTEXT (`title`);
ALTER TABLE `categories` ADD FULLTEXT (`category`);
ALTER TABLE `categories` ADD FULLTEXT (`description`);

 -- create version table -- 
 CREATE TABLE `version` (`version` FLOAT UNSIGNED NOT NULL,`updated` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP);

 -- rename old database to name-old --
 -- rename new database to preferred name -- 
 -- User may need to adjust privileges --