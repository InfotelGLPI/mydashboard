<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015-2022 by the MyDashboard Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of MyDashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with MyDashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Mydashboard\Reports;

use CommonGLPI;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Session;

/**
 * Class KnowbaseItem
 */
class KnowbaseItem extends CommonGLPI
{
    public static $rightname = 'knowbase';

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('Knowledge base');
    }

    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        $widgets = [
            Menu::$TOOLS => [
              "knowbaseitempopular"    => ["title"   => __('FAQ') . " - " . __('Most popular questions'),
                                           "type"    => Widget::$TABLE,
                                           "comment" => ""],
              "knowbaseitemrecent"     => ["title"   => __('FAQ') . " - " . __('Recent entries'),
                                           "type"    => Widget::$TABLE,
                                           "comment" => ""],
              "knowbaseitemlastupdate" => ["title"   => __('FAQ') . " - " . __('Last updated entries'),
                                           "type"    => Widget::$TABLE,
                                           "comment" => ""],
           ]
        ];

        return $widgets;
    }


    /**
     * @param $widgetId
     *
     * @return Datatable
     */
    public function getWidgetContentForItem($widgetId)
    {
        global $DB, $CFG_GLPI;

        $faq = !Session::haveRight(self::$rightname, READ);

        if ($widgetId == "knowbaseitemrecent") {
            $orderby = "ORDER BY `date_creation` DESC";
            $title   = __('FAQ') . " - " . __('Recent entries');
        } elseif ($widgetId == 'knowbaseitemlastupdate') {
            $orderby = "ORDER BY `date_mod` DESC";
            $title   = __('FAQ') . " - " . __('Last updated entries');
        } else {
            $orderby = "ORDER BY `view` DESC";
            $title   = __('FAQ') . " - " . __('Most popular questions');
        }

        $faq_limit = "";
        // Force all joins for not published to verify no visibility set
        $join = KnowbaseItem::addVisibilityJoins(true);

        if (Session::getLoginUserID()) {
            $faq_limit .= "WHERE " . KnowbaseItem::addVisibilityRestrict();
        } else {
            // Anonymous access
            if (Session::isMultiEntitiesMode()) {
                $faq_limit .= " WHERE (`glpi_entities_knowbaseitems`.`entities_id` = '0'
                                   AND `glpi_entities_knowbaseitems`.`is_recursive` = '1')";
            } else {
                $faq_limit .= " WHERE 1";
            }
        }

        // Only published
        $faq_limit .= " AND (`glpi_entities_knowbaseitems`.`entities_id` IS NOT NULL
                           OR `glpi_knowbaseitems_profiles`.`profiles_id` IS NOT NULL
                           OR `glpi_groups_knowbaseitems`.`groups_id` IS NOT NULL
                           OR `glpi_knowbaseitems_users`.`users_id` IS NOT NULL)";

        if ($faq) { // FAQ
            $faq_limit .= " AND (`glpi_knowbaseitems`.`is_faq` = '1')";
        }

        $query = "SELECT DISTINCT `glpi_knowbaseitems`.`id`,
                `glpi_knowbaseitems`.`name`,
                `glpi_knowbaseitems`.`is_faq`,
                `glpi_knowbaseitems`.`date_creation`,
                `glpi_knowbaseitems`.`date_mod`
                FROM `glpi_knowbaseitems`
                $join
                $faq_limit
                $orderby
                LIMIT 10";

        $result = $DB->doQuery($query);
        $tab    = [];
        $nb     = $DB->numrows($result);
        while ($row = $DB->fetchAssoc($result)) {
            if ($widgetId == "knowbaseitemrecent") {
                $date = $row["date_creation"];
            } else {
                $date = $row["date_mod"];
            }
            $tab[] = [
               "<a " . ($row['is_faq'] ? " class='pubfaq' " : " class='knowbase' ") . " href=\"" .
               $CFG_GLPI["root_doc"] . "/front/knowbaseitem.form.php?id=" . $row["id"] . "\">" .
               \Html::resume_text($row["name"], 80) . "</a>", \Html::convDateTime($date)
            ];
        }
        if ($widgetId == "knowbaseitemrecent") {
            $headers = [__('Name'), __('Publication date', 'mydashboard')];
        } else {
            $headers = [__('Name'), __('Modification date', 'mydashboard')];
        }

        $widget = new Datatable();
        $widget->setTabNames($headers);
        $widget->setTabDatas($tab);
        $widget->setWidgetTitle($title);
        $widget->setOption("bDate", ["DH"]);
        if ($nb) {
            $widget->setOption("bSort", [1, 'desc']);
        }

        return $widget;
    }

    /**
     * Return visibility SQL restriction to add
     *
     * @return string restrict to add
     **/
    public static function addVisibilityRestrict()
    {
        //not deprecated because used in Search

        //get and clean criteria
        $criteria = \KnowbaseItem::getVisibilityCriteria();
        unset($criteria['LEFT JOIN']);
        $criteria['FROM'] = \KnowbaseItem::getTable();

        $it = new \DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = preg_replace('/.*WHERE /', '', $sql);

        return $sql;
    }

    /**
     * Return visibility joins to add to SQL
     *
     * @param $forceall force all joins (false by default)
     *
     * @return string joins to add
     **/
    public static function addVisibilityJoins($forceall = false)
    {
        //not deprecated because used in Search
        /** @var \DBmysql $DB */
        global $DB;

        //get and clean criteria
        $criteria = \KnowbaseItem::getVisibilityCriteria();
        unset($criteria['WHERE']);
        $criteria['FROM'] = \KnowbaseItem::getTable();

        $it = new \DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = trim(str_replace(
            'SELECT * FROM ' . $DB->quoteName(\KnowbaseItem::getTable()),
            '',
            $sql
        ));
        return $sql;
    }
}
