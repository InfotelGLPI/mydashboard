CREATE TABLE `glpi_plugin_mydashboard_stockwidgets` (
  `id`              INT(11)                              NOT NULL            AUTO_INCREMENT,
  `entities_id`     INT(11)                              NOT NULL            DEFAULT '0',
  `is_recursive`    TINYINT(1)                           NOT NULL            DEFAULT '0',
  `name`            VARCHAR(255)                         NOT NULL,
  `states`          longtext COLLATE utf8_unicode_ci                         DEFAULT NULL,
  `itemtype`        VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL
  COMMENT 'see .class.php file',
  `icon`            VARCHAR(255)                         NOT NULL,
  `types`           longtext COLLATE utf8_unicode_ci                         DEFAULT NULL,
  `alarm_threshold` int(11)                              NOT NULL            DEFAULT '5',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;