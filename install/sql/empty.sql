--
-- Structure de la table 'glpi_plugin_mydashboard_widgets'
--
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_widgets`;
CREATE TABLE `glpi_plugin_mydashboard_widgets`
(
    `id`   int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `class` varchar(255) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE (`name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;


--
-- Structure de la table 'glpi_plugin_mydashboard_configs'
--
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_configs`;
CREATE TABLE `glpi_plugin_mydashboard_configs`
(
    `id`                        int unsigned NOT NULL AUTO_INCREMENT,
    `enable_fullscreen`         tinyint      NOT NULL DEFAULT '1',
    `display_menu`              tinyint      NOT NULL DEFAULT '1',
    `replace_central`           int unsigned NOT NULL DEFAULT '0',
    `impact_1`                  varchar(200) NOT NULL DEFAULT '#228b22',
    `impact_2`                  varchar(200) NOT NULL DEFAULT '#fff03a',
    `impact_3`                  varchar(200) NOT NULL DEFAULT '#ffa500',
    `impact_4`                  varchar(200) NOT NULL DEFAULT '#cd5c5c',
    `impact_5`                  varchar(200) NOT NULL DEFAULT '#8b0000',
    `levelCat`                  int unsigned NOT NULL DEFAULT '2',
    `title_alerts_widget`       varchar(255) COLLATE utf8mb4_unicode_ci,
    `title_maintenances_widget` varchar(255) COLLATE utf8mb4_unicode_ci,
    `title_informations_widget` varchar(255) COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_preferences'
--
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_preferences`;
CREATE TABLE `glpi_plugin_mydashboard_preferences`
(
    `id`                       int unsigned NOT NULL AUTO_INCREMENT COMMENT 'RELATION to glpi_users(id)',
    `automatic_refresh`        tinyint      NOT NULL DEFAULT '0',
    `automatic_refresh_delay`  int unsigned NOT NULL DEFAULT '10',
    `replace_central`          tinyint      NOT NULL DEFAULT 0,
    `nb_widgets_width`         int unsigned NOT NULL DEFAULT '3',
    `prefered_group`           varchar(255) NOT NULL DEFAULT '[]',
    `requester_prefered_group` varchar(255) NOT NULL DEFAULT '[]',
    `prefered_entity`          int unsigned NOT NULL DEFAULT '0',
    `edit_mode`                tinyint      NOT NULL DEFAULT '0',
    `drag_mode`                tinyint      NOT NULL DEFAULT '0',
    `color_palette`            varchar(50)  NOT NULL DEFAULT '',
    `prefered_type`            int unsigned NOT NULL DEFAULT '0',
    `prefered_category`        int unsigned NOT NULL DEFAULT '0',
    `prefered_year`            tinyint      NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_preferenceuserblacklists'
--
--
DROP TABLE IF EXISTS `glpi_plugin_mydashboard_preferenceuserblacklists`;
CREATE TABLE `glpi_plugin_mydashboard_preferenceuserblacklists`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT,
    `users_id`    int unsigned NOT NULL COMMENT 'RELATION to glpi_users(id)',
    `plugin_name` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_stockwidgets'
--
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_stockwidgets`;
CREATE TABLE `glpi_plugin_mydashboard_stockwidgets`
(
    `id`              int unsigned                            NOT NULL AUTO_INCREMENT,
    `entities_id`     int unsigned                            NOT NULL DEFAULT '0',
    `is_recursive`    tinyint                                 NOT NULL DEFAULT '0',
    `name`            varchar(255)                            NOT NULL,
    `states`          longtext COLLATE utf8mb4_unicode_ci              DEFAULT NULL,
    `itemtype`        varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file',
    `icon`            varchar(255)                            NOT NULL,
    `types`           longtext COLLATE utf8mb4_unicode_ci              DEFAULT NULL,
    `alarm_threshold` int unsigned                            NOT NULL DEFAULT '5',
    PRIMARY KEY (`id`),
    KEY `name` (`name`),
    KEY `entities_id` (`entities_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_customswidgets'
--
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_customswidgets`;
CREATE TABLE `glpi_plugin_mydashboard_customswidgets`
(
    `id`      int unsigned NOT NULL AUTO_INCREMENT,
    `name`    varchar(255) NOT NULL,
    `comment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `content` text         NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_profileauthorizedwidgets'
--
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_profileauthorizedwidgets`;
CREATE TABLE `glpi_plugin_mydashboard_profileauthorizedwidgets`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT,
    `profiles_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_profiles (id)',
    `widgets_id`  int unsigned NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_mydashboard_widgets (id)',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_alerts'
--
--

DROP TABLE IF EXISTS `glpi_plugin_mydashboard_alerts`;
CREATE TABLE `glpi_plugin_mydashboard_alerts`
(
    `id`                int unsigned NOT NULL AUTO_INCREMENT,
    `reminders_id`      int unsigned NOT NULL,
    `impact`            tinyint      NOT NULL,
    `type`              tinyint      NOT NULL,
    `is_public`         tinyint      NOT NULL,
    `itilcategories_id` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;


--
-- Structure de la table 'glpi_plugin_mydashboard_dashboards'
--
--
DROP TABLE IF EXISTS glpi_plugin_mydashboard_dashboards;
CREATE TABLE `glpi_plugin_mydashboard_dashboards`
(
    `id`             int unsigned NOT NULL AUTO_INCREMENT,
    `users_id`       int unsigned NOT NULL               DEFAULT '0',
    `grid`           longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `profiles_id`    int unsigned NOT NULL               DEFAULT '0',
    `grid_statesave` LONGTEXT     NULL                   DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_itilalerts'
--
--
DROP TABLE IF EXISTS glpi_plugin_mydashboard_itilalerts;
CREATE TABLE `glpi_plugin_mydashboard_itilalerts`
(
    `id`           int unsigned                            NOT NULL AUTO_INCREMENT,
    `reminders_id` int unsigned                            NOT NULL DEFAULT '0',
    `items_id`     int unsigned                            NOT NULL DEFAULT '0',
    `itemtype`     varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_groupprofiles'
--
--
DROP TABLE IF EXISTS glpi_plugin_mydashboard_groupprofiles;
CREATE TABLE `glpi_plugin_mydashboard_groupprofiles`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT,
    `groups_id`   varchar(255) NOT NULL DEFAULT '[]',
    `profiles_id` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;


--
-- Structure de la table 'glpi_plugin_mydashboard_configtranslations'
--
--
DROP TABLE IF EXISTS glpi_plugin_mydashboard_configtranslations;
CREATE TABLE `glpi_plugin_mydashboard_configtranslations`
(
    `id`       int unsigned NOT NULL AUTO_INCREMENT,
    `items_id` int unsigned NOT NULL                   DEFAULT '0',
    `itemtype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `language` varchar(5) COLLATE utf8mb4_unicode_ci   DEFAULT NULL,
    `field`    varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `value`    text COLLATE utf8mb4_unicode_ci         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_stocktickets'
--
--
DROP TABLE IF EXISTS glpi_plugin_mydashboard_stocktickets;
CREATE TABLE `glpi_plugin_mydashboard_stocktickets`
(
    `id`             int          NOT NULL AUTO_INCREMENT,
    `date`           date         NOT NULL,
    `entities_id`    int          NOT NULL,
    `groups_id`      int          NOT NULL DEFAULT 0,
    `nbstocktickets` int unsigned NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;

--
-- Structure de la table 'glpi_plugin_mydashboard_stockticketindicators'
--
--
DROP TABLE IF EXISTS glpi_plugin_mydashboard_stockticketindicators;
CREATE TABLE `glpi_plugin_mydashboard_stockticketindicators`
(
    `id`           int          NOT NULL AUTO_INCREMENT,
    `entities_id`  int          NOT NULL,
    `groups_id`    int          NOT NULL DEFAULT 0,
    `indicator_id` int          NOT NULL,
    `nbTickets`    int unsigned NOT NULL,
    `week`         int unsigned NOT NULL,
    `year`         int unsigned NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
  ROW_FORMAT = DYNAMIC;
