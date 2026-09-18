<?php

/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Mydashboard\Reports;

use GlpiPlugin\Mydashboard\Helper;
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
     * @return MydashboardHtml
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
        $widgetTitle = Helper::getWidgetTitleHtml(
            $CFG_GLPI["root_doc"] . "/front/reminder.php?reset=reset",
            $title,
            \Reminder::canCreate() ? $CFG_GLPI["root_doc"] . "/front/reminder.form.php" : null,
        );

        $widget->setWidgetTitle(
            $icon . " " . $widgetTitle,
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
                    htmlescape($name),
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
                    ],
                );
                $name = sprintf(__s('%1$s %2$s'), $link, $tooltip);

                if ($data["is_planned"]) {
                    $tab      = explode(" ", $data["begin"]);
                    $date_url = $tab[0];
                    $planning_text = sprintf(
                        __('From %1$s to %2$s'),
                        Html::convDateTime($data["begin"]),
                        Html::convDateTime($data["end"]),
                    );
                    $planning = sprintf(
                        '<a href="%s" class="pointer float-end" title="%s"><i class="ti ti-bell"></i><span class="sr-only">%s</span></a>',
                        htmlescape(sprintf('%s/front/planning.php?date=%s&type=day', $CFG_GLPI['root_doc'], $date_url)),
                        htmlescape($planning_text),
                        __s('Planning'),
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
     * Merge both halves of the core reminder visibility criteria into a query.
     *
     * \Reminder::getVisibilityCriteria() answers with a 'LEFT JOIN' block and the 'WHERE'
     * block that goes with it, and the two are indissociable: the joins on their own only
     * add nullable columns and filter nothing at all. The alert builders used to take the
     * joins only, through getVisibilityCriteriaCommonJoin(), so every reminder of the
     * instance was listed whatever entity, group, profile or user it targets. Route every
     * builder through here so the two halves can no longer be separated.
     *
     * The core helper degrades safely on its own: without the READ right on reminders it
     * restricts on the session user id alone, and with no session at all on a falsy one.
     * The entity axis of a reminder is its targeting table glpi_entities_reminders, which
     * the WHERE block already restricts — glpi_reminders.entities_id only records where the
     * reminder was written, and the core never filters on it either.
     *
     * @param array $criteria Query criteria reading FROM glpi_reminders.
     *
     * @return array
     */
    public static function applyVisibilityCriteria(array $criteria): array
    {
        $visibility = \Reminder::getVisibilityCriteria(true);

        // Join keys are table names, so the union operator keeps the join the caller has
        // already declared when both name the same table — the outcome wanted here.
        $criteria['LEFT JOIN'] = ($criteria['LEFT JOIN'] ?? []) + ($visibility['LEFT JOIN'] ?? []);

        // array_merge() and not "+": these WHERE blocks open on integer keys, which the union
        // operator would silently drop instead of AND-ing them. The visibility block travels
        // wrapped in an array of its own so its top-level 'OR' stays a single AND-ed group.
        if (isset($visibility['WHERE'])) {
            $criteria['WHERE'] = array_merge($criteria['WHERE'] ?? [], [$visibility['WHERE']]);
        }

        return $criteria;
    }

    /**
     * @deprecated Use applyVisibilityCriteria() instead: these joins filter nothing on their
     *             own and must never be applied without the matching WHERE block.
     */
    public static function getVisibilityCriteriaCommonJoin(bool $forceall = false)
    {

        $join = [];

        // Context checks - avoid doing unnecessary join if possible
        if (!Session::haveRight(\Reminder::$rightname, READ)) {
            return '';
        }
        $has_session_groups = count(($_SESSION["glpigroups"] ?? []));
        $has_active_profile = isset($_SESSION["glpiactiveprofile"]['id']);
        $has_active_entity = count(($_SESSION["glpiactiveentities"] ?? []));

        // Add user restriction data
        if ($forceall || Session::getLoginUserID()) {
            $join['glpi_reminders_users'] = [
                'ON' => [
                    'glpi_reminders_users' => 'reminders_id',
                    'glpi_reminders'       => 'id',
                ],
            ];
        }

        // Add group restriction data
        if ($forceall || $has_session_groups) {
            $join['glpi_groups_reminders'] = [
                'ON' => [
                    'glpi_groups_reminders' => 'reminders_id',
                    'glpi_reminders'       => 'id',
                ],
            ];
        }

        // Add profile restriction data
        if ($forceall || $has_active_profile) {
            $join['glpi_profiles_reminders'] = [
                'ON' => [
                    'glpi_profiles_reminders' => 'reminders_id',
                    'glpi_reminders'       => 'id',
                ],
            ];
        }

        // Add entity restriction data
        if ($forceall || $has_active_entity) {
            $join['glpi_entities_reminders'] = [
                'ON' => [
                    'glpi_entities_reminders' => 'reminders_id',
                    'glpi_reminders'       => 'id',
                ],
            ];
        }

        return $join;
    }

}
