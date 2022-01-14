ALTER TABLE `glpi_plugin_mydashboard_dashboards` ADD `grid_statesave` LONGTEXT NULL DEFAULT NULL;
ALTER TABLE `glpi_plugin_mydashboard_configs` ADD `levelCat` int unsigned NOT NULL DEFAULT '2';

CREATE TABLE `glpi_plugin_mydashboard_groupprofiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `groups_id` int unsigned NOT NULL default '0',
  `profiles_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
