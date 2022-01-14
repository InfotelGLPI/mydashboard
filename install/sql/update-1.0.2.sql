DROP TABLE IF EXISTS `glpi_plugin_mydashboard_alerts`;
CREATE TABLE `glpi_plugin_mydashboard_alerts` (
  `id`           int unsigned    NOT NULL AUTO_INCREMENT,
  `reminders_id` int unsigned    NOT NULL,
  `impact`       tinyint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
