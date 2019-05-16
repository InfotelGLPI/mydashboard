CREATE TABLE `glpi_plugin_mydashboard_dashboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL default '0',
  `grid` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `profiles_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

ALTER TABLE `glpi_plugin_mydashboard_preferences`
  ADD `prefered_group` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_mydashboard_preferences`
  ADD `prefered_entity` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_mydashboard_preferences`
  ADD `edit_mode` TINYINT(1) NOT NULL DEFAULT '0';

CREATE TABLE `glpi_plugin_mydashboard_problemalerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reminders_id` int(11) NOT NULL default '0',
  `problems_id` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

ALTER TABLE `glpi_plugin_mydashboard_userwidgets` ADD `profiles_id` int(11) NOT NULL default '0' AFTER `users_id`;