CREATE TABLE `glpi_plugin_mydashboard_dashboards` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `users_id` int unsigned NOT NULL default '0',
  `grid` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profiles_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `glpi_plugin_mydashboard_preferences`
  ADD `prefered_group` int unsigned NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_mydashboard_preferences`
  ADD `prefered_entity` int unsigned NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_mydashboard_preferences`
  ADD `edit_mode` tinyint NOT NULL DEFAULT '0';

CREATE TABLE `glpi_plugin_mydashboard_problemalerts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reminders_id` int unsigned NOT NULL default '0',
  `problems_id` int unsigned NOT NULL default '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `glpi_plugin_mydashboard_userwidgets` ADD `profiles_id` int unsigned NOT NULL default '0' AFTER `users_id`;
