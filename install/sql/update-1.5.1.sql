ALTER TABLE `glpi_plugin_mydashboard_configs`
  ADD `replace_central` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_mydashboard_configs`
  ADD `google_api_key` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `glpi_plugin_mydashboard_preferences`
  ADD `drag_mode` TINYINT(1) NOT NULL DEFAULT '0';