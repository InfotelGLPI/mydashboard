DROP TABLE IF EXISTS glpi_plugin_mydashboard_stockticketindicators;

CREATE TABLE IF NOT EXISTS glpi_plugin_mydashboard_stockticketindicators (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year           INT UNSIGNED NOT NULL,
  week           INT UNSIGNED NOT NULL,
  nbTickets INT UNSIGNED NOT NULL,
  indicator_id   INT UNSIGNED NOT NULL,
  groups_id      INT UNSIGNED NOT NULL,
  entities_id    INT UNSIGNED NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;
