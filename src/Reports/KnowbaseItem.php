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
use Entity_KnowbaseItem;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Group_KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_User;
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
                "knowbaseitempopular" => [
                    "title" => __('FAQ') . " - " . __('Most popular questions'),
                    "type" => Widget::$TABLE,
                    "comment" => ""
                ],
                "knowbaseitemrecent" => [
                    "title" => __('FAQ') . " - " . __('Recent entries'),
                    "type" => Widget::$TABLE,
                    "comment" => ""
                ],
                "knowbaseitemlastupdate" => [
                    "title" => __('FAQ') . " - " . __('Last updated entries'),
                    "type" => Widget::$TABLE,
                    "comment" => ""
                ],
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
            $orderby = "`date_creation` DESC";
            $title = __('FAQ') . " - " . __('Recent entries');
        } elseif ($widgetId == 'knowbaseitemlastupdate') {
            $orderby = "`date_mod` DESC";
            $title = __('FAQ') . " - " . __('Last updated entries');
        } else {
            $orderby = "`view` DESC";
            $title = __('FAQ') . " - " . __('Most popular questions');
        }

        // Force all joins for not published to verify no visibility set

        $criteria = [
            'SELECT' => ['glpi_knowbaseitems.id',
                'glpi_knowbaseitems.name',
                'glpi_knowbaseitems.is_faq',
                'glpi_knowbaseitems.date_creation',
                'glpi_knowbaseitems.date_mod'],
            'FROM' => 'glpi_knowbaseitems',
            'LEFT JOIN' => self::getVisibilityCriteriaCommonJoin(true),
            'WHERE' => [
                'OR' => [
                    ['NOT'       => ['glpi_entities_knowbaseitems.entities_id' => null]],
                    ['NOT'       => ['glpi_knowbaseitems_profiles.profiles_id' => null]],
                    ['NOT'       => ['glpi_groups_knowbaseitems.groups_id' => null]],
                    ['NOT'       => ['glpi_knowbaseitems_users.users_id' => null]],
                ],
                ],
            'ORDERBY' => $orderby,
            'LIMIT' => 10,
        ];

        if (Session::getLoginUserID()) {
            $criteria['WHERE'] = self::getVisibilityCriteriaKB();
        } else {
            // Anonymous access
            if (Session::isMultiEntitiesMode()) {
                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_entities_knowbaseitems'
                    );
            }
        }

        if ($faq) { // FAQ
            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_knowbaseitems.is_faq' => 1];
        }


        $iterator = $DB->request($criteria);

        $tab = [];
        $nb = count($iterator);
        foreach ($iterator as $row) {
            if ($widgetId == "knowbaseitemrecent") {
                $date = $row["date_creation"];
            } else {
                $date = $row["date_mod"];
            }
            $tab[] = [
                "<a href=\"" .
                $CFG_GLPI["root_doc"] . "/front/knowbaseitem.form.php?id=" . $row["id"] . "\">" .
                \Html::resume_text($row["name"], 80) . "</a>",
                \Html::convDateTime($date)
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
     * Get criteria used to filter knowledge base articles on users
     *
     * @return array
     */
    private static function getVisibilityCriteriaKB_User(): array
    {
        $user = Session::getLoginUserID();
        return [
            KnowbaseItem_User::getTableField('users_id') => $user,
        ];
    }

    /**
     * Get criteria used to filter knowledge base articles on groups
     *
     * @return array
     */
    private static function getVisibilityCriteriaKB_Group(): array
    {
        $groups = $_SESSION["glpigroups"] ?? [-1];
        $entity_restriction = getEntitiesRestrictCriteria(
            Group_KnowbaseItem::getTable(),
            '',
            '',
            true,
            true
        );

        return [
            Group_KnowbaseItem::getTableField('groups_id') => $groups,
            'OR' => [
                    Group_KnowbaseItem::getTableField('no_entity_restriction') => 1,
                ] + $entity_restriction,
        ];
    }

    /**
     * Get criteria used to filter knowledge base articles on profiles
     *
     * @return array
     */
    private static function getVisibilityCriteriaKB_Profile(): array
    {
        $profile = $_SESSION["glpiactiveprofile"]['id'] ?? -1;
        $entity_restriction = getEntitiesRestrictCriteria(
            KnowbaseItem_Profile::getTable(),
            '',
            '',
            true,
            true
        );

        return [
            KnowbaseItem_Profile::getTableField('profiles_id') => $profile,
            'OR' => [
                    KnowbaseItem_Profile::getTableField('no_entity_restriction') => 1,
                ] + $entity_restriction,
        ];
    }

    /**
     * Get criteria used to filter knowledge base articles on entity
     *
     * @return array
     */
    private static function getVisibilityCriteriaKB_Entity(): array
    {
        $entity_restriction = getEntitiesRestrictCriteria(
            Entity_KnowbaseItem::getTable(),
            '',
            '',
            true,
            true
        );

        // All entities
        if (!count($entity_restriction)) {
            $entity_restriction = [
                Entity_KnowbaseItem::getTableField('entities_id') => null,
            ];
        }

        return $entity_restriction;
    }

    /**
     * Get visibility criteria for articles displayed in the knowledge base
     * (seen by central users)
     * This mean any KB article with valid visibility criteria for the current
     * user should be displayed
     *
     * @return array WHERE clause
     */
    private static function getVisibilityCriteriaKB(): array
    {
        // Special case for KB Admins
        if (Session::haveRight(self::$rightname, \KnowbaseItem::KNOWBASEADMIN)) {
            // See all articles
            return [new QueryExpression('1')];
        }

        // Prepare criteria, which will use an OR statement (the user can read
        // the article if any of the user/group/profile/entity criteria are
        // validated)
        $where = ['OR' => []];

        // Special case: the user may be the article's author
        $user = Session::getLoginUserID();
        $author_check = [\KnowbaseItem::getTableField('users_id') => $user];
        $where['OR'][] = $author_check;

        // Filter on users
        $where['OR'][] = self::getVisibilityCriteriaKB_User();

        // Filter on groups (if the current user have any)
        $groups = $_SESSION["glpigroups"] ?? [];
        if (count($groups)) {
            $where['OR'][] = self::getVisibilityCriteriaKB_Group();
        }

        // Filter on profiles
        $where['OR'][] = self::getVisibilityCriteriaKB_Profile();

        // Filter on entities
        $where['OR'][] = self::getVisibilityCriteriaKB_Entity();

        return $where;
    }

    private static function getVisibilityCriteriaCommonJoin(bool $forceall = false)
    {
        global $CFG_GLPI;

        $join = [];

        // Context checks - avoid doing unnecessary join if possible
        $is_public_faq_context = !Session::getLoginUserID() && $CFG_GLPI["use_public_faq"];
        $has_session_groups = count(($_SESSION["glpigroups"] ?? []));
        $has_active_profile = isset($_SESSION["glpiactiveprofile"]['id']);
        $has_active_entity = count(($_SESSION["glpiactiveentities"] ?? []));

        // Add user restriction data
        if ($forceall || Session::getLoginUserID()) {
            $join['glpi_knowbaseitems_users'] = [
                'ON' => [
                    'glpi_knowbaseitems_users' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id',
                ],
            ];
        }

        // Add group restriction data
        if ($forceall || $has_session_groups) {
            $join['glpi_groups_knowbaseitems'] = [
                'ON' => [
                    'glpi_groups_knowbaseitems' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id',
                ],
            ];
        }

        // Add profile restriction data
        if ($forceall || $has_active_profile) {
            $join['glpi_knowbaseitems_profiles'] = [
                'ON' => [
                    'glpi_knowbaseitems_profiles' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id',
                ],
            ];
        }

        // Add entity restriction data
        if ($forceall || $has_active_entity || $is_public_faq_context) {
            $join['glpi_entities_knowbaseitems'] = [
                'ON' => [
                    'glpi_entities_knowbaseitems' => 'knowbaseitems_id',
                    'glpi_knowbaseitems'       => 'id',
                ],
            ];
        }

        return $join;
    }
}
