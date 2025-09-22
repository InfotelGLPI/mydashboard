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
use Glpi\RichText\RichText;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use GlpiPlugin\Mydashboard\Html;
use Session;

/**
 * This class extends GLPI class rssfeed to add the functions to display a widget on Dashboard
 */
class RSSFeed extends CommonGLPI
{
    /**
     * @param int $nb
     *
     * @return string|\translated
     */
    public static function getTypeName($nb = 0)
    {
        return __('RSS');
    }
   /**
    * @return array
    */
    public function getWidgetsForItem()
    {
        $widgets = [];
        if (Session::getCurrentInterface() != 'helpdesk') {
            $widgets[Menu::$TOOLS]["rssfeedpersonalwidget"] = ["title"   =>  _n('Personal RSS feed', 'Personal RSS feeds', 2),
                                                                                   "type"    => Widget::$TABLE,
                                                                                "comment" => ""];
        }
        if (Session::haveRight("rssfeed_public", READ)) {
            $widgets[Menu::$TOOLS]["rssfeedpublicwidget"] = ["title"   => _n('Public RSS feed', 'Public RSS feeds', 2),
                                                                                 "type"    => Widget::$TABLE,
                                                                              "comment" => ""];
        }

        return $widgets;
    }

   /**
    * @param $widgetId
    * @return Nothing
    */
    public function getWidgetContentForItem($widgetId)
    {
        switch ($widgetId) {
            case "rssfeedpersonalwidget":
                return RSSFeed::showListForCentral();
                break;
            case "rssfeedpublicwidget":
                if (Session::haveRight("rssfeed_public", READ)) {
                    return RSSFeed::showListForCentral(false);
                }
                break;
        }
    }

   /**
    * Show list for central view
    *
    * @param $personal boolean   display rssfeeds created by me ? (true by default)
    *
    * @return Datatable (display function)
    */
    public static function showListForCentral($personal = true)
    {
        global $DB, $CFG_GLPI;

        $output = [];

        $users_id = Session::getLoginUserID();

        if ($personal) {
            /// Personal notes only for central view
            if (Session::getCurrentInterface() == 'helpdesk') {
                return false;
            }

            $query = "SELECT `glpi_rssfeeds`.*
                   FROM `glpi_rssfeeds`
                   WHERE `glpi_rssfeeds`.`users_id` = '$users_id'
                         AND `glpi_rssfeeds`.`is_active` = '1'
                   ORDER BY `glpi_rssfeeds`.`name`";

            $titre = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/rssfeed.php\">" . _n('Personal RSS feed', 'Personal RSS feeds', 2) . "</a>";
        } else {
            // Show public rssfeeds / not mines : need to have access to public rssfeeds
            if (!Session::haveRight('rssfeed_public', READ)) {
                return false;
            }

            $restrict_user = '1';
            // Only personal on central so do not keep it
            if (Session::getCurrentInterface() == 'central') {
                $restrict_user = "`glpi_rssfeeds`.`users_id` <> '$users_id'";
            }

            $query = "SELECT `glpi_rssfeeds`.*
                   FROM `glpi_rssfeeds` " .
            RSSFeed::addVisibilityJoins() . "
                   WHERE $restrict_user
                         AND " . RSSFeed::addVisibilityRestrict() . "
                   ORDER BY `glpi_rssfeeds`.`name`";

            if (Session::getCurrentInterface() != 'helpdesk') {
                $titre = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/rssfeed.php\">" . _n('Public RSS feed', 'Public RSS feeds', 2) . "</a>";
            } else {
                $titre = _n('Public RSS feed', 'Public RSS feeds', 2);
            }
        }

        $result = $DB->doQuery($query);
        $items = [];
        $rssfeed = new \RSSFeed();
        if ($nb = $DB->numrows($result)) {
            while ($data = $DB->fetchAssoc($result)) {
                if ($rssfeed->getFromDB($data['id'])) {
                    // Force fetching feeds
                    if ($feed = \RSSFeed::getRSSFeed($data['url'], $data['refresh_rate'])) {
                        // Store feeds in array of feeds
                        $items = array_merge($items, $feed->get_items(0, $data['max_items']));
                        $rssfeed->setError(false);
                    } else {
                        $rssfeed->setError(true);
                    }
                }
            }
        }

        $output['title'] = "<span>$titre</span>";

        if (\RSSFeed::canCreate()) {
            $output['title'] .= "<span class=\"rssfeed_right\">";
            $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/rssfeed.form.php\">";
            $output['title'] .= "<i class='ti ti-plus'></i><span class='sr-only'>". __s('Add')."</span></a></span>";
        }

        $count = 0;
        $output['header'][0] = __('Date');
        $output['header'][1] = __('Title');

        $output['body'] = [];

        if ($nb) {
            usort($items, ['SimplePie', 'sort_items']);
            foreach ($items as $item) {
                $output['body'][$count][0] = \Html::convDateTime($item->get_date('Y-m-d H:i:s'));
                $link = $item->feed->get_permalink();
                if (empty($link)) {
                    $output['body'][$count][1] = $item->feed->get_title();
                } else {
                    $output['body'][$count][1] = "<a target=\"_blank'\" href=\"$link\">" . $item->feed->get_title() . '</a>';
                }
                $link = $item->get_permalink();
                $rand = mt_rand();
                $output['body'][$count][1] .= "<div id=\"rssitem$rand\" class=\"pointer rss\">";
                if (!is_null($link)) {
                    $output['body'][$count][1] .= "<a target=\"_blank\" href=\"$link\">";
                }
                $output['body'][$count][1] .= $item->get_title();
                if (!is_null($link)) {
                    $output['body'][$count][1] .= "</a>";
                }
                $output['body'][$count][1] .= "</div>";
                $output['body'][$count][1] .= \Html::showToolTip(
                    RichText::getSafeHtml($item->get_content()),
                    ['applyto' => "rssitem$rand",
                    'display' => false]
                );
                $count++;
            }
        }

        $publique = $personal ? "personal" : "public";

        //First we create a new Widget of Datatable kind
        $widget = new Datatable();
        //We set the widget title and the id
        $widget->setWidgetTitle($output['title']);
        $widget->setWidgetId("rssfeed" . $publique . "widget");
        //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of Datatable)
        $widget->setTabNames($output['header']);
        $widget->setTabDatas($output['body']);

        //Here we set few otions concerning the jquery library Datatable, bSort for sorting, bPaginate for paginating ...
//        if (count($output['body']) > 0) {
//            $widget->setOption("bSort", false);
//        }
        $widget->setOption("bPaginate", false);
        $widget->setOption("bFilter", false);
        $widget->setOption("bInfo", false);

        return $widget;
    }

    /**
     * Return visibility joins to add to DBIterator parameters
     *
     * @since 9.4
     *
     * @param boolean $forceall force all joins (false by default)
     *
     * @return array
     */
    public static function getVisibilityCriteria(bool $forceall = false): array
    {
        $where = [\RSSFeed::getTable() . '.users_id' => Session::getLoginUserID()];
        $join = [];

        if (!\RSSFeed::canView()) {
            return [
                'LEFT JOIN' => $join,
                'WHERE'     => $where
            ];
        }

        //JOINs
        // Users
        $join['glpi_rssfeeds_users'] = [
            'ON' => [
                'glpi_rssfeeds_users'   => 'rssfeeds_id',
                'glpi_rssfeeds'         => 'id'
            ]
        ];

        $where = [
            'OR' => [
                \RSSFeed::getTable() . '.users_id'   => Session::getLoginUserID(),
                'glpi_rssfeeds_users.users_id'   => Session::getLoginUserID()
            ]
        ];
        $orwhere = [];

        // Groups
        if (
            $forceall
            || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))
        ) {
            $join['glpi_groups_rssfeeds'] = [
                'ON' => [
                    'glpi_groups_rssfeeds'  => 'rssfeeds_id',
                    'glpi_rssfeeds'         => 'id'
                ]
            ];
        }

        if (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
            $restrict = getEntitiesRestrictCriteria('glpi_groups_rssfeeds', '', '', true);
            $orwhere[] = [
                'glpi_groups_rssfeeds.groups_id' => count($_SESSION["glpigroups"])
                    ? $_SESSION["glpigroups"]
                    : [-1],
                'OR' => [
                        'glpi_groups_rssfeeds.no_entity_restriction' => 1,
                    ] + $restrict
            ];
        }

        // Profiles
        if (
            $forceall
            || (isset($_SESSION["glpiactiveprofile"])
                && isset($_SESSION["glpiactiveprofile"]['id']))
        ) {
            $join['glpi_profiles_rssfeeds'] = [
                'ON' => [
                    'glpi_profiles_rssfeeds'   => 'rssfeeds_id',
                    'glpi_rssfeeds'            => 'id'
                ]
            ];
        }

        if (isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id'])) {
            $restrict = getEntitiesRestrictCriteria('glpi_entities_rssfeeds', '', '', true);
            if (!count($restrict)) {
                $restrict = [true];
            }
            $ors = [
                'glpi_profiles_rssfeeds.no_entity_restriction' => 1,
                $restrict
            ];

            $orwhere[] = [
                'glpi_profiles_rssfeeds.profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
                'OR' => $ors
            ];
        }

        // Entities
        if (
            $forceall
            || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))
        ) {
            $join['glpi_entities_rssfeeds'] = [
                'ON' => [
                    'glpi_entities_rssfeeds'   => 'rssfeeds_id',
                    'glpi_rssfeeds'            => 'id'
                ]
            ];
        }

        if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
            // Force complete SQL not summary when access to all entities
            $restrict = getEntitiesRestrictCriteria('glpi_entities_rssfeeds', '', '', true, true);
            if (count($restrict)) {
                $orwhere[] = $restrict;
            }
        }

        $where['OR'] = array_merge($where['OR'], $orwhere);
        $criteria = ['LEFT JOIN' => $join];
        if (count($where)) {
            $criteria['WHERE'] = $where;
        }

        return $criteria;
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
        $criteria = self::getVisibilityCriteria();
        unset($criteria['LEFT JOIN']);
        $criteria['FROM'] = \RSSFeed::getTable();

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
        $criteria = self::getVisibilityCriteria();
        unset($criteria['WHERE']);
        $criteria['FROM'] = \RSSFeed::getTable();

        $it = new \DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = trim(str_replace(
            'SELECT * FROM ' . $DB->quoteName(\RSSFeed::getTable()),
            '',
            $sql
        ));
        return $sql;
    }
}
