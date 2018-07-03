DROP TABLE IF EXISTS glpi_plugin_mydashboard_stocktickets;

CREATE TABLE IF NOT EXISTS glpi_plugin_mydashboard_stocktickets (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  date           DATE         NOT NULL,
  nbstocktickets INT UNSIGNED NOT NULL,
  entities_id    INT UNSIGNED NOT NULL
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;
