CREATE TABLE `glpi_plugin_mydashboard_stockwidgets` (
  `id`              int unsigned                              NOT NULL            AUTO_INCREMENT,
  `entities_id`     int unsigned                              NOT NULL            DEFAULT '0',
  `is_recursive`    tinyint                             NOT NULL            DEFAULT '0',
  `name`            VARCHAR(255)                         NOT NULL,
  `states`          longtext COLLATE utf8mb4_unicode_ci                         DEFAULT NULL,
  `itemtype`        VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL
  COMMENT 'see .class.php file',
  `icon`            VARCHAR(255)                         NOT NULL,
  `types`           longtext COLLATE utf8mb4_unicode_ci                         DEFAULT NULL,
  `alarm_threshold` int unsigned                              NOT NULL            DEFAULT '5',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
