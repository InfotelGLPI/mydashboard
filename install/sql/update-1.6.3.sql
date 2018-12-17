ALTER TABLE `glpi_plugin_mydashboard_dashboards` ADD `grid_statesave` LONGTEXT NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_mydashboard_configs` ADD `levelCat` int(11) NOT NULL DEFAULT '2';

CREATE TABLE `glpi_plugin_mydashboard_groupprofiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groups_id` int(11) NOT NULL default '0',
  `profiles_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;