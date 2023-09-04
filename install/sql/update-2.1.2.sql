ALTER TABLE `glpi_plugin_mydashboard_configs` DROP `display_plugin_widget`;
ALTER TABLE `glpi_plugin_mydashboard_configs` DROP `google_api_key`;
ALTER TABLE `glpi_plugin_mydashboard_preferences` ADD `prefered_category` INT unsigned NOT NULL DEFAULT '0';
