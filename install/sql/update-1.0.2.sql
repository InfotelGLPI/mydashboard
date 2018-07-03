DROP TABLE IF EXISTS `glpi_plugin_mydashboard_alerts`;
CREATE TABLE `glpi_plugin_mydashboard_alerts` (
  `id`           INT(11)    NOT NULL AUTO_INCREMENT,
  `reminders_id` INT(11)    NOT NULL,
  `impact`       TINYINT(1) NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;