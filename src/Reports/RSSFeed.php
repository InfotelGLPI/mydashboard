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
use Entity_RSSFeed;
use Glpi\DBAL\QueryExpression;
use Glpi\RichText\RichText;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use GlpiPlugin\Mydashboard\Html;
use Group_RSSFeed;
use Profile_RSSFeed;
use RSSFeed_User;
use Session;

/**
 * This class extends GLPI class rssfeed to add the functions to display a widget on Dashboard
 */
class RSSFeed extends CommonGLPI
{
    /**
     * @param int $nb
     *
     * @return string
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
            $widgets[Menu::$TOOLS]["rssfeedpersonalwidget"] = [
                "title" => _n('Personal RSS feed', 'Personal RSS feeds', 2),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
        }
        if (Session::haveRight("rssfeed_public", READ)) {
            $widgets[Menu::$TOOLS]["rssfeedpublicwidget"] = [
                "title" => _n('Public RSS feed', 'Public RSS feeds', 2),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
        }

        return $widgets;
    }

    /**
     * @param $widgetId
     * @return Datatable|false
     */
    public function getWidgetContentForItem($widgetId)
    {
        switch ($widgetId) {
            case "rssfeedpersonalwidget":
                return RSSFeed::showListForCentral();
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

            $criteria = [
                'SELECT' => '*',
                'FROM' => 'glpi_rssfeeds',
                'WHERE' => [
                    'glpi_rssfeeds.users_id' => $users_id,
                    'glpi_rssfeeds.is_active' => 1,
                ],
                'ORDERBY' => 'glpi_rssfeeds.name',
            ];

            $titre = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/rssfeed.php\">" . _n(
                    'Personal RSS feed',
                    'Personal RSS feeds',
                    2
                ) . "</a>";
        } else {
            // Show public rssfeeds / not mines : need to have access to public rssfeeds
            if (!Session::haveRight('rssfeed_public', READ)) {
                return false;
            }


            $criteria = [
                'SELECT' => 'glpi_rssfeeds.*',
                'FROM' => 'glpi_rssfeeds',
                'LEFT JOIN' => self::getVisibilityCriteriaCommonJoin(true),
                'WHERE' => [
                    'OR' => [
                        ['NOT'       => ['glpi_entities_rssfeeds.entities_id' => null]],
                        ['NOT'       => ['glpi_profiles_rssfeeds.profiles_id' => null]],
                        ['NOT'       => ['glpi_groups_rssfeeds.groups_id' => null]],
                        ['NOT'       => ['glpi_rssfeeds_users.users_id' => null]],
                    ],
                ],
                'ORDERBY' => 'glpi_rssfeeds.name',
            ];


            if (Session::getLoginUserID()) {
                $criteria['WHERE'] = self::getVisibilityCriteria();
            } else {
                // Anonymous access
                if (Session::isMultiEntitiesMode()) {
                    $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                            'glpi_rssfeeds'
                        );
                }
            }

            if (Session::getCurrentInterface() != 'helpdesk') {
                $titre = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/rssfeed.php\">" . _n(
                        'Public RSS feed',
                        'Public RSS feeds',
                        2
                    ) . "</a>";
            } else {
                $titre = _n('Public RSS feed', 'Public RSS feeds', 2);
            }
        }

        $iterator = $DB->request($criteria);
        $items = [];
        $rssfeed = new \RSSFeed();
        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
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
            $output['title'] .= "<i class='ti ti-plus'></i><span class='sr-only'>" . __s('Add') . "</span></a></span>";
        }

        $count = 0;
        $output['header'][0] = __('Date');
        $output['header'][1] = __('Title');

        $output['body'] = [];

        if (count($iterator) > 0) {
            usort($items, ['SimplePie', 'sort_items']);
            foreach ($items as $item) {
                $output['body'][$count][0] = \Html::convDateTime($item->get_date('Y-m-d H:i:s'));
                $link = $item->feed->get_permalink();
                if (empty($link)) {
                    $output['body'][$count][1] = $item->feed->get_title();
                } else {
                    $output['body'][$count][1] = "<a target=\"_blank'\" href=\"$link\">" . $item->feed->get_title(
                        ) . '</a>';
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
                    [
                        'applyto' => "rssitem$rand",
                        'display' => false
                    ]
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



    private static function getVisibilityCriteriaCommonJoin(bool $forceall = false)
    {

        $join = [];

        // Context checks - avoid doing unnecessary join if possible
        $has_session_groups = count(($_SESSION["glpigroups"] ?? []));
        $has_active_profile = isset($_SESSION["glpiactiveprofile"]['id']);
        $has_active_entity = count(($_SESSION["glpiactiveentities"] ?? []));

        // Add user restriction data
        if ($forceall || Session::getLoginUserID()) {
            $join['glpi_rssfeeds_users'] = [
                'ON' => [
                    'glpi_rssfeeds_users' => 'rssfeeds_id',
                    'glpi_RssFeeds'       => 'id',
                ],
            ];
        }

        // Add group restriction data
        if ($forceall || $has_session_groups) {
            $join['glpi_groups_rssfeeds'] = [
                'ON' => [
                    'glpi_groups_rssfeeds' => 'rssfeeds_id',
                    'glpi_RssFeeds'       => 'id',
                ],
            ];
        }

        // Add profile restriction data
        if ($forceall || $has_active_profile) {
            $join['glpi_profiles_rssfeeds'] = [
                'ON' => [
                    'glpi_profiles_rssfeeds' => 'rssfeeds_id',
                    'glpi_RssFeeds'       => 'id',
                ],
            ];
        }

        // Add entity restriction data
        if ($forceall || $has_active_entity) {
            $join['glpi_entities_rssfeeds'] = [
                'ON' => [
                    'glpi_entities_rssfeeds' => 'rssfeeds_id',
                    'glpi_RssFeeds'       => 'id',
                ],
            ];
        }

        return $join;
    }


    /**
     * Get visibility criteria for articles displayed in the knowledge base
     * (seen by central users)
     * This mean any KB article with valid visibility criteria for the current
     * user should be displayed
     *
     * @return array WHERE clause
     */
    private static function getVisibilityCriteria(): array
    {

        // Prepare criteria, which will use an OR statement (the user can read
        // the article if any of the user/group/profile/entity criteria are
        // validated)
        $where = ['OR' => []];

        // Special case: the user may be the article's author
        $user = Session::getLoginUserID();
        $author_check = [\RSSFeed::getTableField('users_id') => $user];
        $where['OR'][] = $author_check;

        // Filter on users
        $where['OR'][] = self::getVisibilityCriteria_User();

        // Filter on groups (if the current user have any)
        $groups = $_SESSION["glpigroups"] ?? [];
        if (count($groups)) {
            $where['OR'][] = self::getVisibilityCriteria_Group();
        }

        // Filter on profiles
        $where['OR'][] = self::getVisibilityCriteria_Profile();

        // Filter on entities
        $where['OR'][] = self::getVisibilityCriteria_Entity();

        return $where;
    }
    /**
     * Get criteria used to filter knowledge base articles on users
     *
     * @return array
     */
    private static function getVisibilityCriteria_User(): array
    {
        $user = Session::getLoginUserID();
        return [
            RssFeed_User::getTableField('users_id') => $user,
        ];
    }

    /**
     * Get criteria used to filter knowledge base articles on groups
     *
     * @return array
     */
    private static function getVisibilityCriteria_Group(): array
    {
        $groups = $_SESSION["glpigroups"] ?? [-1];
        $entity_restriction = getEntitiesRestrictCriteria(
            Group_RssFeed::getTable(),
            '',
            '',
            true,
            true
        );

        return [
            Group_RssFeed::getTableField('groups_id') => $groups,
            'OR' => [
                    Group_RssFeed::getTableField('no_entity_restriction') => 1,
                ] + $entity_restriction,
        ];
    }

    /**
     * Get criteria used to filter knowledge base articles on profiles
     *
     * @return array
     */
    private static function getVisibilityCriteria_Profile(): array
    {
        $profile = $_SESSION["glpiactiveprofile"]['id'] ?? -1;
        $entity_restriction = getEntitiesRestrictCriteria(
            Profile_RssFeed::getTable(),
            '',
            '',
            true,
            true
        );

        return [
            Profile_RssFeed::getTableField('profiles_id') => $profile,
            'OR' => [
                    Profile_RssFeed::getTableField('no_entity_restriction') => 1,
                ] + $entity_restriction,
        ];
    }

    /**
     * Get criteria used to filter knowledge base articles on entity
     *
     * @return array
     */
    private static function getVisibilityCriteria_Entity(): array
    {
        $entity_restriction = getEntitiesRestrictCriteria(
            Entity_RssFeed::getTable(),
            '',
            '',
            true,
            true
        );

        // All entities
        if (!count($entity_restriction)) {
            $entity_restriction = [
                Entity_RssFeed::getTableField('entities_id') => null,
            ];
        }

        return $entity_restriction;
    }
}
