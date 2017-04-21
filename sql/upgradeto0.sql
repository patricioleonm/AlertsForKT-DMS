
CREATE TABLE IF NOT EXISTS `PatoLeonAlerts` (
  `AlertID` int(10) unsigned NOT NULL auto_increment,
  `DocumentID` int(10) unsigned NOT NULL,
  `Users` varchar(250) NOT NULL,
  `Date` datetime NOT NULL,
  `Message` varchar(250) NOT NULL,
  `Sent` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`AlertID`),
  KEY `DocumentID` (`DocumentID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;