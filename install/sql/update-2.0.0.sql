UPDATE `glpi_plugin_mydashboard_profileauthorizedwidgets` SET `widgets_id` = '0' WHERE `widgets_id` = '-1';
UPDATE `glpi_plugin_mydashboard_stocktickets` SET `groups_id` = '0' WHERE `groups_id` = '-1';
UPDATE `glpi_plugin_mydashboard_alerts` SET `itilcategories_id` = '0' WHERE `itilcategories_id` = '-1';
ALTER TABLE `glpi_plugin_mydashboard_profileauthorizedwidgets` CHANGE `widgets_id` `widgets_id` INT unsigned NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_mydashboard_widgets (id)';
ALTER TABLE `glpi_plugin_mydashboard_stocktickets` CHANGE `groups_id` `groups_id` INT unsigned NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_mydashboard_userwidgets` DROP CONSTRAINT `glpi_plugin_mydashboard_userwidgets_ibfk_1`;
