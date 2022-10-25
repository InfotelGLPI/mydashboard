<?php
header("Content-type: text/css; charset: UTF-8");
include('../../../inc/includes.php');
$config = new PluginMydashboardConfig();
$gtheme = $config->getGridTheme();
$wtheme = $config->getWidgetTheme();
$stheme = $config->getSlidePanelTheme();
$sltheme = $config->getSlideLinkTheme();

?>

.mygrid {
background-color: <?php echo $gtheme; ?>;
}

.md-grid-stack-item-content {
background-color: <?php echo $wtheme; ?>;
}

.slidepanel {
background: <?php echo $stheme; ?>;
}

.slidelink {
color: <?php echo $sltheme; ?>;
}
.plugin_mydashboard_menuDashboardListTitle1,
.plugin_mydashboard_menuDashboardListContainer,
.plugin_mydashboard_menuDashboardList,
.plugin_mydashboard_menuDashboardListTitle1:hover,
.plugin_mydashboard_menuDashboardListTitle1Opened,
.plugin_mydashboard_menuDashboardListItem,
.plugin_mydashboard_menuDashboardListTitle2 {
background: <?php echo $stheme; ?>;
}
