-- DROP TABLE IF EXISTS glpi_plugin_mydashboard_stocktickets_group;
--
-- CREATE TABLE IF NOT EXISTS glpi_plugin_mydashboard_stocktickets_group (
--   id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--   groups_id      INT,
--   date           DATE         NOT NULL,
--   nbstocktickets INT UNSIGNED NOT NULL,
--   entities_id    INT UNSIGNED NOT NULL
-- )
--   ENGINE = InnoDB
--   DEFAULT CHARSET = latin1;
ALTER TABLE `glpi_plugin_mydashboard_preferences` CHANGE `prefered_group` `prefered_group` VARCHAR(255) NOT NULL DEFAULT '[]';
ALTER TABLE `glpi_plugin_mydashboard_preferences` ADD `requester_prefered_group` VARCHAR(255) NOT NULL DEFAULT '[]';
ALTER TABLE `glpi_plugin_mydashboard_groupprofiles` CHANGE `groups_id` `groups_id` VARCHAR(255) NOT NULL DEFAULT '[]';
ALTER TABLE `glpi_plugin_mydashboard_stocktickets` ADD `groups_id` int unsigned NOT NULL DEFAULT '0' AFTER `entities_id`;
