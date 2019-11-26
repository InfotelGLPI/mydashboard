RENAME TABLE `glpi_plugin_mydashboard_problemalerts` TO `glpi_plugin_mydashboard_itilalerts`;
ALTER TABLE `glpi_plugin_mydashboard_itilalerts` ADD `itemtype` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'see .class.php file';
ALTER TABLE `glpi_plugin_mydashboard_itilalerts` CHANGE `problems_id` `items_id` int(11) NOT NULL default '0';
UPDATE `glpi_plugin_mydashboard_itilalerts` SET `itemtype` = 'Problem';