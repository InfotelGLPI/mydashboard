--
-- Structure de la table 'glpi_plugin_mydashboard_profiles'
-- gestion des droits pour le plugin
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_profiles`;
CREATE TABLE `glpi_plugin_mydashboard_profiles` (
  `id`          INT(11) NOT NULL        AUTO_INCREMENT, -- id du profil
  `profiles_id` INT(11) NOT NULL        DEFAULT '0'
  COMMENT 'RELATION to glpi_profiles (id)', -- lien avec profiles de glpi
  `mydashboard` CHAR(1)
                COLLATE utf8_unicode_ci DEFAULT NULL, -- $right:  r (can view), w (can update)
  `config`      CHAR(1)
                COLLATE utf8_unicode_ci DEFAULT NULL, -- $right:  r (can view), w (can update)
  PRIMARY KEY (`id`),
  KEY `profiles_id` (`profiles_id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

--
-- Structure de la table 'glpi_plugin_mydashboard_widgets'
-- 
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_widgets`;
CREATE TABLE `glpi_plugin_mydashboard_widgets` (
  `id`   INT(11)      NOT NULL AUTO_INCREMENT, -- id du widget
  `name` VARCHAR(255) NOT NULL, -- nom du widget
  PRIMARY KEY (`id`),
  UNIQUE (`name`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

--
-- Structure de la table 'glpi_plugin_mydashboard_userwidgets'
-- 
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_userwidgets`;
CREATE TABLE `glpi_plugin_mydashboard_userwidgets` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT, -- id
  `users_id`   INT(11) NOT NULL
  COMMENT 'RELATION to glpi_users(id)',
  `widgets_id` INT(11) NOT NULL, -- id du widget
  `place`      INT(11) NOT NULL, -- placement du widget
  PRIMARY KEY (`id`),
  FOREIGN KEY (`users_id`) REFERENCES glpi_users (id)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

--
-- Structure de la table 'glpi_plugin_mydashboard_configs'
-- 
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_configs`;
CREATE TABLE `glpi_plugin_mydashboard_configs` (
  `id`                    INT(11)    NOT NULL AUTO_INCREMENT, -- id
  `enable_fullscreen`     TINYINT(1) NOT NULL DEFAULT '1',
  `display_menu`          TINYINT(1) NOT NULL DEFAULT '1',
  `display_plugin_widget` TINYINT(1) NOT NULL DEFAULT '1',
  `replace_central`       TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

--
-- Structure de la table 'glpi_plugin_mydashboard_preferences'
-- 
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_preferences`;
CREATE TABLE `glpi_plugin_mydashboard_preferences` (
  `id`                      INT(11)    NOT NULL
  COMMENT 'RELATION to glpi_users(id)',
  `automatic_refresh`       TINYINT(1) NOT NULL DEFAULT '0',
  `automatic_refresh_delay` INT(11)    NOT NULL DEFAULT '10',
  `nb_widgets_width`        INT(11)    NOT NULL DEFAULT '3',
  PRIMARY KEY (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

--
-- Structure de la table 'glpi_plugin_mydashboard_preferenceuserblacklists'
-- 
--
DROP TABLE IF EXISTS `glpi_plugin_mydashboard_preferenceuserblacklists`;
CREATE TABLE `glpi_plugin_mydashboard_preferenceuserblacklists` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `users_id`    INT(11)      NOT NULL
  COMMENT 'RELATION to glpi_users(id)',
  `plugin_name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;