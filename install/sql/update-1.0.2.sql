DROP TABLE IF EXISTS `glpi_plugin_mydashboard_alerts`;
CREATE TABLE `glpi_plugin_mydashboard_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reminders_id` int(11) NOT NULL,
  `impact` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;