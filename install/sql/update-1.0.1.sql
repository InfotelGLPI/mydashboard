--
-- Structure de la table 'glpi_plugin_mydashboard_profileauthorizedwidget'
-- gestion des droits pour le plugin
--
DROP TABLE IF EXISTS `glpi_plugin_mydashboard_profileauthorizedwidgets`;
CREATE TABLE `glpi_plugin_mydashboard_profileauthorizedwidgets` (
  `id` int(11) NOT NULL auto_increment, -- id du profil
  `profiles_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)', -- lien avec profiles de glpi
  `widgets_id` int(11) NOT NULL default '-1' COMMENT 'RELATION to glpi_mydashboard_widgets (id)',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;