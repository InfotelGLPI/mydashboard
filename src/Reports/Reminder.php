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
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Html;
use Session;

/**
 *
 */

/**
 * This class extends GLPI class reminder to add the functions to display widgets on Dashboard
 */
class Reminder extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        return __('Reminder');
    }

    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        $widgets = [];
        if (Session::getCurrentInterface() != 'helpdesk') {
            $widgets[Menu::$TOOLS]["reminderpersonalwidget"] = [
                "title" => _n('Personal reminder', 'Personal reminders', 2),
                "type" => Widget::$TABLE,
                "comment" => "",
            ];
        }
        if (Session::haveRight("reminder_public", READ)) {
            $widgets[Menu::$TOOLS]["reminderpublicwidget"] = [
                "title" => _n('Public reminder', 'Public reminders', 2),
                "type" => Widget::$TABLE,
                "comment" => "",
            ];
        }

        return $widgets;
    }


    /**
     * @param $widgetId
     *
     * @return Nothing
     */
    public function getWidgetContentForItem($widgetId)
    {
        switch ($widgetId) {
            case "reminderpersonalwidget":
                return self::showListForCentral($widgetId);

            case "reminderpublicwidget":
                if (Session::haveRight("reminder_public", READ)) {
                    return self::showListForCentral($widgetId, false);
                }
                break;
        }
    }


    /**
     * Show list for central view
     *
     * @param $personal boolean : display reminders created by me ? (true by default)
     *
     * @return MydashboardHtml (display function)
     **/
    public static function showListForCentral($widgetId, $personal = true)
    {
        global $DB, $CFG_GLPI;

        $criteria = \Reminder::getListCriteria();
        $personal_criteria = $criteria['personal'];
        $public_criteria = $criteria['public'];

        // Only standard interface users have personal reminders
        $can_see_personal = Session::getCurrentInterface() === 'central';
        $can_see_public = (bool) Session::haveRight(\Reminder::$rightname, READ);

        $personal_reminders = [];
        $public_reminders = [];

        if ($personal && $can_see_personal) {
            $iterator = $DB->request($personal_criteria);
            foreach ($iterator as $data) {
                $personal_reminders[] = $data;
            }
        }
        if ($can_see_public) {
            $iterator = $DB->request($public_criteria);
            foreach ($iterator as $data) {
                $public_reminders[] = $data;
            }

            // Remove all reminders from the personal list that are already in the public list (Check by id)
            foreach ($public_reminders as $key => $public_reminder) {
                foreach ($personal_reminders as $key2 => $personal_reminder) {
                    if ($personal_reminder['id'] === $public_reminder['id']) {
                        unset($personal_reminders[$key2]);
                    }
                }
            }
        }

        if ($personal) {
            $title = '<a href="' . htmlescape($CFG_GLPI["root_doc"]) . '/front/reminder.php">'
                . _sn('Personal reminder', 'Personal reminders', Session::getPluralNumber())
                . '</a>';
        } else {
            if (Session::getCurrentInterface() !== 'helpdesk') {
                $title = '<a href="' . htmlescape($CFG_GLPI["root_doc"]) . '/front/reminder.php">'
                    . _sn('Public reminder', 'Public reminders', Session::getPluralNumber())
                    . '</a>';
            } else {
                $title = _sn('Public reminder', 'Public reminders', Session::getPluralNumber());
            }
        }

        $reminders = $personal ? $personal_reminders : $public_reminders;
        $nb = count($reminders);

        $widget = new MydashboardHtml();
        $widget->setWidgetId($widgetId);

        $icon = "<i class='" . \Reminder::getIcon() . "'></i>";
        $widgetTitle = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.php?reset=reset\">"
            . $title . "</a>";
        if (\Reminder::canCreate()) {
            $widgetTitle .= "&nbsp;<span>";
            $widgetTitle .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.form.php\">";
            $widgetTitle .= "<i class='ti ti-plus'></i><span class='sr-only'>" . __s('Add') . "</span></a>";
        }

        $widget->setWidgetTitle(
            $icon . " " . $widgetTitle
        );

        $entries = [];
        if ($nb) {
            $rand = mt_rand();

            foreach ($reminders as $data) {

                $name = $data['name'];

                if (!empty($data['transname'])) {
                    $name = $data['transname'];
                }
                $link = sprintf(
                    '<a id="content_reminder_%s" href="%s">%s</a>',
                    htmlescape($data["id"] . $rand),
                    htmlescape(\Reminder::getFormURLWithID($data["id"])),
                    htmlescape($name)
                );
                $text = $data["text"];
                if (!empty($data['transtext'])) {
                    $text = $data['transtext'];
                }
                $tooltip = Html::showToolTip(
                    RichText::getEnhancedHtml($text),
                    [
                        'applyto' => "content_reminder_" . $data["id"] . $rand,
                        'display' => false,
                    ]
                );
                $name = sprintf(__s('%1$s %2$s'), $link, $tooltip);

                if ($data["is_planned"]) {
                    $tab      = explode(" ", $data["begin"]);
                    $date_url = $tab[0];
                    $planning_text = sprintf(
                        __('From %1$s to %2$s'),
                        Html::convDateTime($data["begin"]),
                        Html::convDateTime($data["end"])
                    );
                    $planning = sprintf(
                        '<a href="%s" class="pointer float-end" title="%s"><i class="ti ti-bell"></i><span class="sr-only">%s</span></a>',
                        htmlescape(sprintf('%s/front/planning.php?date=%s&type=day', $CFG_GLPI['root_doc'], $date_url)),
                        htmlescape($planning_text),
                        __s('Planning')
                    );
                } else {
                    $planning = '';
                }
                $entries[] = [
                    'itemtype' => \Reminder::class,
                    'name' => $name,
                    'planning' => $planning,
                ];
            }
        }

        $add_link = '';

        $columns = [
            'name' => __('Name'),
            'planning' => '',
        ];
        $formatters = [
            'name' => 'raw_html',
            'planning' => 'raw_html',
        ];
        $footers = [];
        //        if (
        //            ($personal && \Reminder::canCreate())
        //            || (!$personal && Session::haveRight(\Reminder::$rightname, CREATE))
        //        ) {
        //            $add_link = \Reminder::getFormURL();
        //        }

        $output = TemplateRenderer::getInstance()->render('@mydashboard/table.html.twig', [
            'title' => __('Name'),
            'add_link' => $add_link,
            'datatable_params' => [
                'is_tab' => true,
                'nofilter' => true,
                'nosort' => true,
                'columns' => $columns,
                'formatters' => $formatters,
                'entries' => $entries,
                'footers' => $footers,
                'total_number' => count($entries),
                'filtered_number' => count($entries),
                'showmassiveactions' => false,
            ],
        ]);

        $widget->toggleWidgetRefresh();
        $widget->setWidgetHtmlContent($output);

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
        $criteria = self::getVisibilityCriteria();
        unset($criteria['LEFT JOIN']);
        $criteria['FROM'] = \Reminder::getTable();

        $it = new \DBmysqlIterator(null);
        $it->buildQuery($criteria);
        $sql = $it->getSql();
        $sql = preg_replace('/.*WHERE /', '', $sql);

        return $sql;
    }


    /**
     * Return visibility joins to add to DBIterator parameters
     *
     * @param boolean $forceall force all joins (false by default)
     *
     * @return array
     * @since 9.4
     *
     */
    public static function getVisibilityCriteria(bool $forceall = false): array
    {
        if (!Session::haveRight(\Reminder::$rightname, READ)) {
            return [
                'WHERE' => ['glpi_reminders.users_id' => Session::getLoginUserID()],
            ];
        }

        $join = [];
        $where = [];

        // Users
        $join['glpi_reminders_users'] = [
            'FKEY' => [
                'glpi_reminders_users' => 'reminders_id',
                'glpi_reminders' => 'id',
            ],
        ];
        //disabled for plugin

        //      if (Session::getLoginUserID()) {
        //         $where['OR'] = [
        //               'glpi_reminders.users_id'        => Session::getLoginUserID(),
        //               'glpi_reminders_users.users_id'  => Session::getLoginUserID(),
        //         ];
        //      } else {
        //         $where = [
        //            0
        //         ];
        //      }

        // Groups
        if ($forceall
            || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))) {
            $join['glpi_groups_reminders'] = [
                'FKEY' => [
                    'glpi_groups_reminders' => 'reminders_id',
                    'glpi_reminders' => 'id',
                ],
            ];

            $or = ['glpi_groups_reminders.entities_id' => ['<', 0]];
            $restrict = getEntitiesRestrictCriteria('glpi_groups_reminders', '', '', true);
            if (count($restrict)) {
                $or = $or + $restrict;
            }
            $where['OR'][] = [
                'glpi_groups_reminders.groups_id' => count($_SESSION["glpigroups"])
                    ? $_SESSION["glpigroups"]
                    : [-1],
                'OR' => $or,
            ];
        }

        // Profiles
        if ($forceall
            || (isset($_SESSION["glpiactiveprofile"])
                && isset($_SESSION["glpiactiveprofile"]['id']))) {
            $join['glpi_profiles_reminders'] = [
                'FKEY' => [
                    'glpi_profiles_reminders' => 'reminders_id',
                    'glpi_reminders' => 'id',
                ],
            ];

            $or = ['glpi_profiles_reminders.entities_id' => ['<', 0]];
            $restrict = getEntitiesRestrictCriteria('glpi_profiles_reminders', '', '', true);
            if (count($restrict)) {
                $or = $or + $restrict;
            }
            $where['OR'][] = [
                'glpi_profiles_reminders.profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
                'OR' => $or,
            ];
        }

        // Entities
        if ($forceall
            || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))) {
            $join['glpi_entities_reminders'] = [
                'FKEY' => [
                    'glpi_entities_reminders' => 'reminders_id',
                    'glpi_reminders' => 'id',
                ],
            ];
        }
        if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
            $restrict = getEntitiesRestrictCriteria('glpi_entities_reminders', '', '', true, true);
            if (count($restrict)) {
                $where['OR'] = $where['OR'] + $restrict;
            }
        }

        $criteria = [
            'LEFT JOIN' => $join,
            'WHERE' => $where,
        ];

        return $criteria;
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
        if (!Session::haveRight(\Reminder::$rightname, READ)) {
            return '';
        }
        // Users
        $join = " LEFT JOIN `glpi_reminders_users`
                     ON (`glpi_reminders_users`.`reminders_id` = `glpi_reminders`.`id`) ";

        // Groups
        if ($forceall
            || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))) {
            $join .= " LEFT JOIN `glpi_groups_reminders`
                        ON (`glpi_groups_reminders`.`reminders_id` = `glpi_reminders`.`id`) ";
        }

        // Profiles
        if ($forceall
            || (isset($_SESSION["glpiactiveprofile"])
                && isset($_SESSION["glpiactiveprofile"]['id']))) {
            $join .= " LEFT JOIN `glpi_profiles_reminders`
                        ON (`glpi_profiles_reminders`.`reminders_id` = `glpi_reminders`.`id`) ";
        }

        // Entities
        if ($forceall
            || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))) {
            $join .= " LEFT JOIN `glpi_entities_reminders`
                        ON (`glpi_entities_reminders`.`reminders_id` = `glpi_reminders`.`id`) ";
        }

        return $join;
    }

}
