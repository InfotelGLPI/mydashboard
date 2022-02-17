--
-- Structure de la table 'glpi_plugin_mydashboard_profileauthorizedwidget'
-- gestion des droits pour le plugin
--
DROP TABLE IF EXISTS `glpi_plugin_mydashboard_profileauthorizedwidgets`;
CREATE TABLE `glpi_plugin_mydashboard_profileauthorizedwidgets` (
  `id`          int unsigned NOT NULL AUTO_INCREMENT, -- id du profil
  `profiles_id` int unsigned NOT NULL DEFAULT '0'
  COMMENT 'RELATION to glpi_profiles (id)', -- lien avec profiles de glpi
  `widgets_id` int unsigned NOT NULL DEFAULT '0'
  COMMENT 'RELATION to glpi_mydashboard_widgets (id)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
