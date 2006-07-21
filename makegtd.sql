-- MySQL dump 10.9
--
-- Host: localhost    Database: gtd
-- ------------------------------------------------------
-- Server version	4.1.12-standard

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `gtd`
--
CREATE DATABASE /*!32312 IF NOT EXISTS*/ `gtd` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `gtd`;

--
-- Table structure for table `context`
--

DROP TABLE IF EXISTS `context`;
CREATE TABLE `context` (
  `contextId` int(10) unsigned NOT NULL auto_increment,
  `name` char(50) NOT NULL default '',
  `description` char(100) NOT NULL default '',
  PRIMARY KEY  (`contextId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `goals`
--

DROP TABLE IF EXISTS `goals`;
CREATE TABLE `goals` (
  `id` int(11) NOT NULL auto_increment,
  `goal` longtext,
  `description` longtext,
  `created` date default NULL,
  `deadline` date default NULL,
  `completed` date default NULL,
  `type` enum('weekly','quarterly') default NULL,
  `projectId` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `maybe`
--

DROP TABLE IF EXISTS `maybe`;
CREATE TABLE `maybe` (
  `maybeId` int(11) NOT NULL auto_increment,
  `title` longtext,
  `description` longtext,
  `dateCreated` date default NULL,
  `completed` int(11) default NULL,
  PRIMARY KEY  (`maybeId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `maybeSomeday`
--

DROP TABLE IF EXISTS `maybeSomeday`;
CREATE TABLE `maybeSomeday` (
  `wishId` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `category` varchar(50) NOT NULL default '',
  `description` longtext NOT NULL,
  `dateCreated` date default NULL,
  PRIMARY KEY  (`wishId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `nextActions`
--

DROP TABLE IF EXISTS `nextActions`;
CREATE TABLE `nextActions` (
  `nextActionId` int(10) unsigned NOT NULL auto_increment,
  `projectId` int(11) NOT NULL default '0',
  `contextId` int(11) NOT NULL default '0',
  `timeId` int(11) default NULL,
  `title` longtext,
  `description` longtext,
  `dateCreated` date default NULL,
  `dateCompleted` date default NULL,
  `completed` int(11) default NULL,
  PRIMARY KEY  (`nextActionId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `projectId` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `description` longtext NOT NULL,
  `desiredOutcome` longtext NOT NULL,
  `dateCreated` date default NULL,
  `dateCompleted` date default NULL,
  PRIMARY KEY  (`projectId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `reference`
--

DROP TABLE IF EXISTS `reference`;
CREATE TABLE `reference` (
  `referenceId` int(11) NOT NULL auto_increment,
  `projectId` int(11) default NULL,
  `title` varchar(100) default NULL,
  `description` longtext,
  `dateCreated` date default NULL,
  PRIMARY KEY  (`referenceId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `waitingOn`
--

DROP TABLE IF EXISTS `waitingOn`;
CREATE TABLE `waitingOn` (
  `waitingOnId` int(11) NOT NULL auto_increment,
  `projectId` int(11) default NULL,
  `title` longtext,
  `description` longtext,
  `dateCreated` date default NULL,
  `dateCompleted` date default NULL,
  PRIMARY KEY  (`waitingOnId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

