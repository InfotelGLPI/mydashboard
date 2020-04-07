ALTER TABLE `glpi_plugin_mydashboard_configs` ADD `title_alerts_widget` VARCHAR(255) COLLATE utf8_unicode_ci;
ALTER TABLE `glpi_plugin_mydashboard_configs` ADD `title_maintenances_widget` VARCHAR(255) COLLATE utf8_unicode_ci;
ALTER TABLE `glpi_plugin_mydashboard_configs` ADD `title_informations_widget` VARCHAR(255) COLLATE utf8_unicode_ci;
ALTER TABLE `glpi_plugin_mydashboard_preferences` ADD `color_palette` INT(11) NOT NULL DEFAULT '1';
ALTER TABLE `glpi_plugin_mydashboard_alerts` ADD `itilcategories_id` INT(11) NOT NULL DEFAULT '0';

CREATE TABLE `glpi_plugin_mydashboard_configtranslations`
(
    `id`       int(11) NOT NULL AUTO_INCREMENT,
    `items_id` int(11) NOT NULL                     DEFAULT '0',
    `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `language` varchar(5) COLLATE utf8_unicode_ci   DEFAULT NULL,
    `field`    varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `value`    text COLLATE utf8_unicode_ci         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;
