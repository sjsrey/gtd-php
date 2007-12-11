INSERT INTO `gtdsample_categories` VALUES ('1','Professional','Work related.');
INSERT INTO `gtdsample_categories` VALUES ('2','Personal','Outside of work.');
-- *******************************
INSERT INTO `gtdsample_checklist` VALUES ('1','Weekly Review Checklist','2','Use this checklist every week to make sure that you have done everything you need to do to keep GTD-PHP up-to-date.\r\n\r\nChecklists are re-usable lists.  Use them for processes you need to repeat over and over again.  When you need to do the list over from the beginning use the clear checklist option and all the checkmarks will go away.');
-- *******************************
INSERT INTO `gtdsample_checklistitems` VALUES ('4','Gather all loose papers','','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('5','Process all notes','','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('6','Check all voice mail','','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('7','Review Email Inbox','Move each item to an action, waiting, or reference folder.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('8','Review Email action box','Put next action reminder into system for each one, delete completed emails or move to waiting on or reference boxes as approriate.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('9','Review Email waiting on box','Put waiting on reminder into system for each one, delete completed emails or move to reference box as approriate.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('10','Review previous calendar','Transfer any missed actions to system','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('11','Purge agendas','','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('12','Review upcoming calendar','Capture actions about arrangements and preparations for any upcoming events','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('13','Empty your head','Put in writing any <a href=\"item.php?type=p\" target=\"_blank\">new projects</a>, <a href=\"item.php?type=a\" target=\"_blank\">actions</a>, <a href=\"item.php?type=w\" target=\"_blank\">things you are waiting for</a>, <a href=\"item.php?type=r\" target=\"_blank\">references</a>, and <a href=\"item.php?type=p&someday=true\" target=\"_blank\">someday/maybes</a> that are not yet in the system.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('14','Empty your gtd-php inbox','See your <a href=\"listItems.php?type=i\" target=\"_blank\">gtd-php inbox</a>.  Use the \'Set Type\' button to convert each one into a project, action, reference or waiting-on','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('15','Review Projects list','See <a href=\"listItems.php?type=p\" target=\"_blank\">your projects list.</a> Make sure that all projects have next actions defined.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('16','Review Actions list','See <a href=\"listItems.php?type=a\" target=\"_blank\">your actions list.</a>  Mark off any completed actions, review for reminders of further actions to capture.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('17','Review WaitingOn list','See <a href=\"listItems.php?type=w\" target=\"_blank\">your waiting on list.</a>  Mark off any items which have now happened; for each such item\'s parent project, decide what the new next action is.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('18','Review Lists','See <a href=\"listLists.php?type=L\" target=\"_blank\">your lists</a>.  Review relevant lists for actionable items or projects.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('19','Review Checklists','See <a href=\"listLists.php?type=C\" target=\"_blank\">your checklists.</a> Review relevant Checklists for actionable items or projects.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('20','Review Someday/Maybe List','See <a href=\"listItems.php?type=p&someday=true\" target=\"_blank\">your someday/maybe list.</a>  Add new fun things, move any existing items into Projects if they are ready to go.','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('21','Review support files','','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('22','Review Goals','See <a href=\"listItems.php?type=g\" target=\"_blank\">your goals.</a>  Check off goals from this week. Define goals for upcoming week','1','n');
INSERT INTO `gtdsample_checklistitems` VALUES ('23','Brainstorm','Get creative with any new <a href=\"item.php?type=p\" target=\"_blank\">projects</a> or <a href=\"item.php?type=p&someday=true\" target=\"_blank\">someday/maybes</a> that may further your values, visions, goals, or areas of responsibility.','1','n');
-- *******************************
INSERT INTO `gtdsample_context` VALUES ('1','Computer','Sitting at a keyboard.');
INSERT INTO `gtdsample_context` VALUES ('2','Office','At the office');
INSERT INTO `gtdsample_context` VALUES ('3','Phone','Calls');
INSERT INTO `gtdsample_context` VALUES ('4','Home','Something you can only do from home.\r\n');
-- *******************************
INSERT INTO `gtdsample_itemattributes` VALUES ('1','p','n','1','0','0',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('3','a','n','1','1','1',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('4','r','n','2','1','0',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('5','a','n','2','1','1',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('6','g','n','0','0','0',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('8','a','n','1','1','1',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('9','w','n','1','1','0',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('10','i','n','1','1','0',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('11','p','y','2','0','3',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('12','r','n','2','4','0',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('13','r','n','2','4','0',NULL,'0','n','0');
INSERT INTO `gtdsample_itemattributes` VALUES ('14','r','n','2','1','0',NULL,'0','n','0');
-- *******************************
INSERT INTO `gtdsample_items` VALUES ('1','Set up your new gtd-php system','Getting Things Done','Mind like water.');
INSERT INTO `gtdsample_items` VALUES ('3','Do a weekly review to help populate this system','Click on <a href=\'weekly.php\'>this link</a> to see the Weekly Review: you can use this as a guide to help set up the system - there\'s an accompanying <a href=\'reportLists.php?type=C&id=1\'>Weekly Review checklist</a> for you to tick off the tasks, each week.','');
INSERT INTO `gtdsample_items` VALUES ('4','gtd-php user forums','the gtd-php <a href=\'http://toae.org/boards/\'>user forums</a> are the place to go to ask questions and meet others  in the gtd-php community','');
INSERT INTO `gtdsample_items` VALUES ('5','delete this action, which is deliberately an orphan','and can safely be deleted, by editing this item, then checking the \"delete\" box below, and then pressing the \"update\" button','');
INSERT INTO `gtdsample_items` VALUES ('6','Stay on top of things, and don\'t lose track of what needs doing','','Having one place where you trust yourself to list all of the things you need to do, which means you can keep your mind clear for whatever it is you\'re doing at any one moment, without being distracted by all the things you\'re not doing at that moment ');
INSERT INTO `gtdsample_items` VALUES ('8','review the checklist of things to do to start','We\'ve created a <a href=\'reportLists.php?type=L&id=1\'>\"gtd-php startup things-to-do\" list</a> for you to start with.  Once you\'ve done these things, then you\'re ready to start adding your own actions: which means you can now make the <b>\"Do a weekly review to help populate this system\"</b> action into a <i>Next</i> Action.  To do that, in the <a href=\'itemReport.php?itemId=1\'>project</a> screen, check the \"Next Action\" box and then press the \"update\" button immediately below it.','');
INSERT INTO `gtdsample_items` VALUES ('9','Update GTD-PHP','Whenever a new release comes out, you should upgrade your GTD-PHP application.  This is a sample of a waiting-on event.  This is something outside of your control, but you can make it the \"next action\" so that a project doesn\'t ask you for a next action.  This is helpful when your next action on a project is for someone else to contact you or send you something.','');
INSERT INTO `gtdsample_items` VALUES ('10','Drop items into your inbox','You have an \"inbox\" for quickly adding items to you GTD-PHP system.  After you use <strong>Capture-><a href=\"item.php?type=i\">Inbox</a></strong>, you can then <a href=\"listItems.php?type=i\">view all your inbox items</a> so they may be added to <a href=\"listItems.php?type=p\">projects</a>.','');
INSERT INTO `gtdsample_items` VALUES ('11','Read Additional References','The someday/maybe area is for projects you may not get to in the very near future, but you need a place to store the gist of the project, maybe a few notes/references, and this makes it easy to take the idea \"live\" by translating it into a project.\r\n\r\nIf you decide to take this project on, and think you\'ll be working on it in the next month or so, edit this item and <strong>un</strong>check the \"Someday\" box at the bottom.  Then it will be a normal project.\r\n\r\nYou may want to consider reading additional books about time management and prioritization, which  can help you organize your projects and priorities.  See the references for this someday/maybe item for ideas of other materials and books that you might want to read or look into.','');
INSERT INTO `gtdsample_items` VALUES ('12','Getting Things Done','by David Allen\r\n\r\nThis application is based on his project/action management philosophies.\r\n\r\nISBN-10: 0142000280\r\nISBN-13: 978-0142000281\r\n','');
INSERT INTO `gtdsample_items` VALUES ('13','The 7 Habits of Highly Effective People','by Stephen R. Covey\r\n\r\nThere are many books in the genre, including Sean Covey\'s 7 Habits of Highly Effective Teens.  For the first groundbreaking book on managing yourself see:\r\n\r\nISBN-10: 0743269519\r\nISBN-13: 978-0743269513\r\n\r\nThis book influenced some of the developers of this application.  Covey doesn\'t go into project management to the level of detail of David Allen, and David Allen doesn\'t go into goal and vision management to the degree of Stephen Covey.  Since they can be viewed in a complimentary manner, one can read both and come out with a good map and a way to manage getting there on a day-to-day basis.','');
INSERT INTO `gtdsample_items` VALUES ('14','43 Folders Website','You may want to look into the resources at <a href=\"http://www.43folders.com/\">43 Folders</a> which are usually based on David Allen\'s work. There are many resources there that are helpful for time management and project management.','');
-- *******************************
INSERT INTO `gtdsample_itemstatus` VALUES ('1','2007-10-17','2007-10-17 20:09:19',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('3','2007-10-17','2007-11-20 20:30:34',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('4','2007-10-17','2007-10-17 21:03:00',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('5','2007-10-17','2007-10-20 08:18:50',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('6','2007-10-17','2007-10-17 20:31:49',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('8','2007-10-19','2007-11-20 20:30:53',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('9','2007-10-22','2007-10-22 13:02:19',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('10','2007-10-22','2007-10-22 13:06:58',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('11','2007-10-22','2007-10-22 13:21:15',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('12','2007-10-22','2007-10-22 13:12:40',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('13','2007-10-22','2007-10-22 13:23:06',NULL);
INSERT INTO `gtdsample_itemstatus` VALUES ('14','2007-10-22','2007-10-22 13:22:49',NULL);
-- *******************************
INSERT INTO `gtdsample_list` VALUES ('1','gtd-php startup things-to-do','2','A list of things you should check out in the new sample data generated for you.\r\n\r\nLists are for one-time simple lists that once done you\'ll no longer need.  For reusable lists see checklists.');
-- *******************************
INSERT INTO `gtdsample_listitems` VALUES ('1','categories','Check the <a href=\'editCat.php?field=category\'>categories</a> meet your needs','1',NULL);
INSERT INTO `gtdsample_listitems` VALUES ('2','space contexts','Check the <a href=\'editCat.php?field=context\'>space contexts</a> meet your needs','1',NULL);
INSERT INTO `gtdsample_listitems` VALUES ('3','time contexts','Check the <a href=\'editCat.php?field=time-context\'>time contexts</a> meet your needs','1',NULL);
-- *******************************
INSERT INTO `gtdsample_lookup` VALUES ('1','3');
INSERT INTO `gtdsample_lookup` VALUES ('1','4');
INSERT INTO `gtdsample_lookup` VALUES ('1','8');
INSERT INTO `gtdsample_lookup` VALUES ('1','9');
INSERT INTO `gtdsample_lookup` VALUES ('11','12');
INSERT INTO `gtdsample_lookup` VALUES ('11','13');
INSERT INTO `gtdsample_lookup` VALUES ('11','14');
-- *******************************
INSERT INTO `gtdsample_nextactions` VALUES ('0','5');
INSERT INTO `gtdsample_nextactions` VALUES ('1','8');
INSERT INTO `gtdsample_nextactions` VALUES ('1','9');
-- *******************************
-- *******************************
INSERT INTO `gtdsample_timeitems` VALUES ('1','Short','< 10 Minutes','a');
INSERT INTO `gtdsample_timeitems` VALUES ('2','Medium','10-30 Minutes','a');
INSERT INTO `gtdsample_timeitems` VALUES ('3','Long','> 30 Minutes','a');
-- *******************************
