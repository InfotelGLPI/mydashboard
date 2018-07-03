--
-- Structure de la table 'glpi_plugin_mydashboard_profileauthorizedwidget'
-- gestion des droits pour le plugin
--
DROP TABLE IF EXISTS `glpi_plugin_mydashboard_profileauthorizedwidgets`;
CREATE TABLE `glpi_plugin_mydashboard_profileauthorizedwidgets` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT, -- id du profil
  `profiles_id` INT(11) NOT NULL DEFAULT '0'
  COMMENT 'RELATION to glpi_profiles (id)', -- lien avec profiles de glpi
  `widgets_id`  INT(11) NOT NULL DEFAULT '-1'
  COMMENT 'RELATION to glpi_mydashboard_widgets (id)',
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;