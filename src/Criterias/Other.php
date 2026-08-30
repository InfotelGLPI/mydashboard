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

namespace GlpiPlugin\Mydashboard\Criterias;

use Glpi\Application\View\TemplateRenderer;
use Ajax;
use CommonITILObject;
use DbUtils;
use Dropdown;
use Group_User;
use Html;
use PluginTagTag;
use Ticket;
use User;

/**
 * Class Other
 */
class Other
{
    //    public static $criteria_name = '...';

    public static $criterias_list = [
        'status',
        'multiple_technicians_id',
        'end',
        'begin',
        'multiple_time',
        'multiple_year_time',
        'itilcategorielvl1',
        'tag',
    ];

    public static function getDefaultValue()
    {
        // BEGIN DATE
        //        if (in_array("begin", $criterias)) {
        $default['begin'] = date("Y-m-d H:i:s");
        //        }


        // END DATE
        //        if (in_array("end", $criterias)) {
        $default['end'] = date("Y-m-d H:i:s");
        //        }


        // TECHNICIAN MULTIPLE
        //        $opt['multiple_technicians_id'] = [];
        //        //        $crit['crit']['multiple_technicians_id'] = " AND 1 = 1 ";
        //        if (in_array("multiple_technicians_id", $criterias)) {
        //            if (isset($params['opt']['multiple_technicians_id'])) {
        //                $opt['multiple_technicians_id'] = is_array(
        //                    $params['opt']['multiple_technicians_id']
        //                ) ? $params['opt']['multiple_technicians_id'] : [];
        //            } else {
        //                $crit['crit']['multiple_technicians_id'] = [];
        //            }
        //            $params['opt']['multiple_technicians_id'] = $opt['multiple_technicians_id'];
        //
        //            if (isset($params['opt']['multiple_technicians_id'])
        //                && is_array($params['opt']['multiple_technicians_id'])
        //                && count($params['opt']['multiple_technicians_id']) > 0) {
        //                $crit['crit']['multiple_technicians_id'] = $params['opt']['multiple_technicians_id'];
        //            }
        //        }


        // MULTIPLE TIME
        //        if (in_array("multiple_time", $criterias)) {
        //            if (isset($params['opt']['multiple_time'])) {
        //                $opt["multiple_time"] = $params['opt']['multiple_time'];
        //                $crit['crit']['multiple_time'] = $params['opt']['multiple_time'];
        //            } else {
        //                $opt["multiple_time"] = "MONTH";
        //                $crit['crit']['multiple_time'] = "MONTH";
        //            }
        //        }

        // MULTIPLE YEAR TIME
        //        if (in_array("multiple_year_time", $criterias)) {
        //            if (isset($params['opt']['multiple_year_time'])) {
        //                $opt["multiple_year_time"] = $params['opt']['multiple_year_time'];
        //                $crit['crit']['multiple_year_time'] = $params['opt']['multiple_year_time'];
        //            } else {
        //                $opt["multiple_year_time"] = "LASTMONTH";
        //                $crit['crit']['multiple_year_time'] = "LASTMONTH";
        //            }
        //            if (isset($params['opt']['month_year'])) {
        //                $opt["month_year"] = $params['opt']['month_year'];
        //                $crit['crit']['month_year'] = $params['opt']['month_year'];
        //            }
        //        }


        // STATUS
        //        $default = [
        //            CommonITILObject::INCOMING,
        //            CommonITILObject::ASSIGNED,
        //            CommonITILObject::PLANNED,
        //            CommonITILObject::WAITING,
        //        ];
        //        $crit['crit']['status'] = $default;
        //        $opt['status'] = $default;
        //        if (in_array("status", $criterias)) {
        //            $status = [];
        //
        //            if (isset($params['opt']["status_1"])
        //                && $params['opt']["status_1"] > 0) {
        //                $status[] = CommonITILObject::INCOMING;
        //            }
        //            if (isset($params['opt']["status_2"])
        //                && $params['opt']["status_2"] > 0) {
        //                $status[] = CommonITILObject::ASSIGNED;
        //            }
        //            if (isset($params['opt']["status_3"])
        //                && $params['opt']["status_3"] > 0) {
        //                $status[] = CommonITILObject::PLANNED;
        //            }
        //            if (isset($params['opt']["status_4"])
        //                && $params['opt']["status_4"] > 0) {
        //                $status[] = CommonITILObject::WAITING;
        //            }
        //            if (isset($params['opt']["status_5"])
        //                && $params['opt']["status_5"] > 0) {
        //                $status[] = CommonITILObject::SOLVED;
        //            }
        //            if (isset($params['opt']["status_6"])
        //                && $params['opt']["status_6"] > 0) {
        //                $status[] = CommonITILObject::CLOSED;
        //            }
        //
        //            if (count($status) > 0) {
        //                $opt['status'] = $status;
        //                $crit['crit']['status'] = $status;
        //            }
        //        }
        //ITILCATEGORY_LVL1
        //        $opt['itilcategorielvl1'] = 0;
        //        //        $crit['crit']['itilcategorielvl1'] = " AND 1 = 1 ";
        //        if (in_array("itilcategorielvl1", $criterias)) {
        //            if (isset($params['preferences']['prefered_category'])
        //                && $params['preferences']['prefered_category'] > 0 && !isset($params['opt']['itilcategorielvl1'])) {
        //                $opt['itilcategorielvl1'] = $params['preferences']['prefered_category'];
        //            } elseif (isset($params['opt']["itilcategorielvl1"])
        //                && $params['opt']["itilcategorielvl1"] > 0) {
        //                $opt['itilcategorielvl1'] = $params['opt']['itilcategorielvl1'];
        //            }
        //            $category = new ITILCategory();
        //            $catlvl2 = $category->find(
        //                ['itilcategories_id' => $opt['itilcategorielvl1'], 'is_request' => 1, 'is_incident' => 1]
        //            );
        //            $listcat = [];
        //            $listcat[] = $opt['itilcategorielvl1'];
        //            foreach ($catlvl2 as $cat) {
        //                $listcat[] = $cat['id'];
        //            }
        //            $categories = implode(",", $listcat);
        //            if (empty($listcat)) {
        //                $listcat = "0";
        //            }
        //            $crit['crit']['itilcategorielvl1'] = ['glpi_tickets.itilcategories_id' => $categories];
        //        }


        //TAG
        //        $opt['tag'] = 0;
        //        //        $crit['crit']['tag'] = "AND 1 = 1";
        //        if (in_array("tag", $criterias)) {
        //            if (isset($params['opt']["tag"])
        //                && $params['opt']["tag"] > 0) {
        //                $opt['tag'] = $params['opt']['tag'];
        //
        //                $crit['crit']['tag'] = ['glpi_plugin_tag_tagitems.plugin_tag_tags_id' => $opt['tag']];
        //            }
        //        }
        //
        //        $crit['opt'] = $opt;
    }

    public static function getDisplayValue($opt)
    {
        $form = "";

        // TECHNICIAN MULTIPLE
        if (isset($opt['multiple_technicians_id'])) {
            $opt['multiple_technicians_id'] = is_array(
                $opt['multiple_technicians_id'],
            ) ? $opt['multiple_technicians_id'] : [];

            $opt['multiple_technicians_id'] = array_filter($opt['multiple_technicians_id']);

            if (count($opt['multiple_technicians_id']) > 0) {
                $form .= "&nbsp;/&nbsp;" . _n(
                    'Technician',
                    'Technicians',
                    count($opt['multiple_technicians_id']),
                    'mydashboard',
                ) . "&nbsp;:&nbsp;";
                foreach ($opt['multiple_technicians_id'] as $k => $v) {
                    $form .= getUserName($v);
                    if (count($opt['multiple_technicians_id']) > 1) {
                        $form .= "&nbsp;-&nbsp;";
                    }
                }
            }
        }

        if (isset($opt['tag']) && $opt['tag'] > 0) {
            $form .= "&nbsp;/&nbsp;" . PluginTagTag::getTypeName() . "&nbsp;:&nbsp;" . Dropdown::getDropdownName(
                'glpi_plugin_tag_tags',
                $opt['tag'],
            );
        }
        if (isset($opt['multiple_year_time'])) {
            switch ($opt['multiple_year_time']) {
                case "LASTMONTH":
                    $form .= "&nbsp;/&nbsp;" . __('Time display', 'mydashboard') . "&nbsp;/&nbsp;" . __(
                        "Last month",
                        'mydashboard',
                    );
                    break;
                case "LASTYEAR":
                    $form .= "&nbsp;/&nbsp;" . __('Time display', 'mydashboard') . "&nbsp;/&nbsp;" . __(
                        "Last year",
                        'mydashboard',
                    );
                    break;
                case "YEARTODATE":
                    $form .= "&nbsp;/&nbsp;" . __('Time display', 'mydashboard') . "&nbsp;/&nbsp;" . __(
                        "Year to date",
                        'mydashboard',
                    );
                    break;
                case "MONTH":
                    $form .= "&nbsp;/&nbsp;" . __('Time display', 'mydashboard') . "&nbsp;/&nbsp;" . __(
                        "Month",
                        'mydashboard',
                    );
                    break;
            }
        }


        if (isset($opt['itilcategorielvl1']) && $opt['itilcategorielvl1'] > 0) {
            $form .= "&nbsp;/&nbsp;" . __("Category", 'mydashboard') . "&nbsp;:&nbsp;" . Dropdown::getDropdownName(
                'glpi_itilcategories',
                $opt['itilcategorielvl1'],
            );
        }

        return $form;
    }

    /**
     * Render one criterion of this class.
     *
     * @param string $label
     * @param string $input_html
     * @param int    $count
     * @param string $break      trailing spacing: 'double', 'single' or 'none'
     * @param string $extra_html appended inside the span, after the widget
     *
     * @return string
     */
    private static function renderField($label, $input_html, $count, $break = 'double', $extra_html = '')
    {
        return TemplateRenderer::getInstance()->render('@mydashboard/criteria_other_field.html.twig', [
            'label' => $label,
            'input_html' => $input_html,
            'extra_html' => $extra_html,
            'break' => $count > 1 ? $break : 'none',
        ]);
    }

    public static function getDisplayForm($default, $opt, $count)
    {
        global $CFG_GLPI;

        $criterias = $opt['criterias'] ?? $default['criterias'];

        // Beware: each branch below ASSIGNS $form instead of appending to it, so when
        // several criteria are active only the last one is rendered. Behaviour kept as is.
        $form = '';

        // START DATE
        if (in_array("begin", $criterias)) {
            $form = self::renderField(
                __('Start'),
                Html::showDateTimeField(
                    "begin",
                    ['value' => $opt['begin'] ?? $default['begin'], 'maybeempty' => false, 'display' => false],
                ),
                $count,
                in_array("end", $criterias) ? 'single' : 'double',
            );
        }

        // END DATE
        if (in_array("end", $criterias)) {
            $form = self::renderField(
                __('End'),
                Html::showDateTimeField(
                    "end",
                    ['value' => $opt['end'] ?? $default['end'], 'maybeempty' => false, 'display' => false],
                ),
                $count,
            );
        }

        // TECHNICIAN MULTIPLE
        if (in_array("multiple_technicians_id", $criterias)) {
            $params = [
                'entity' => $_SESSION['glpiactive_entity'],
                'right' => ['groups'],
                'groups_id' => 0,
                'values' => [],
                'multiple' => true,
                'display' => false,
            ];

            $list = [];
            foreach (User::getSqlSearchResult(false, $params['right'], $params['entity']) as $data) {
                $list[] = $data['id'];
            }
            $restrict = [];
            if (count($list) > 0) {
                $restrict = ['glpi_users.id' => $list];
            }
            $restrict["glpi_users.is_deleted"] = 0;
            $restrict["glpi_users.is_active"] = 1;

            $users = [];
            foreach (Group_User::getGroupUsers(1, $restrict) as $data) {
                $users[$data['id']] = formatUserName(
                    $data['id'],
                    $data['name'],
                    $data['realname'],
                    $data['firstname'],
                );
                $params['values'][] = $data['id'];
            }
            $params['size'] = count($users);

            $form = self::renderField(
                _n('Technician', 'Technicians', 2, 'mydashboard'),
                Dropdown::showFromArray(
                    "multiple_technicians_id",
                    $users ?: $default['multiple_technicians_id'],
                    $params,
                ),
                $count,
            );
        }

        //STATUS
        if (in_array("status", $criterias)) {
            // Local list renamed: it used to shadow the $default parameter, which made the
            // "no selection" fallback below read $default['status'] off a plain list.
            $default_status = [
                CommonITILObject::INCOMING,
                CommonITILObject::ASSIGNED,
                CommonITILObject::PLANNED,
                CommonITILObject::WAITING,
            ];

            $statuses = [];
            foreach (Ticket::getAllStatusArray() as $svalue => $sname) {
                $statuses[] = [
                    'value' => $svalue,
                    'label' => $sname,
                    'checked' => in_array($svalue, $opt['status'])
                        || (count($opt['status']) < 1 && in_array($svalue, $default_status)),
                ];
            }

            $form = TemplateRenderer::getInstance()->render('@mydashboard/criteria_other_status.html.twig', [
                'statuses' => $statuses,
                'count' => $count,
            ]);
        }

        if (in_array("multiple_time", $criterias)) {
            $temp = [
                "DAY" => __("Day", 'mydashboard'),
                "WEEK" => __("Week", 'mydashboard'),
                "MONTH" => __("Month", 'mydashboard'),
            ];

            $form = self::renderField(
                __('Time display', 'mydashboard'),
                Dropdown::showFromArray("multiple_time", $temp, [
                    "name" => 'multiple_time',
                    "display" => false,
                    "multiple" => false,
                    "width" => '200px',
                    'value' => $opt['multiple_time'] ?? $default['multiple_time'],
                    'display_emptychoice' => false,
                ]),
                $count,
            );
        }

        if (in_array("multiple_year_time", $criterias)) {
            $temp = [
                "YEARTODATE" => __("Year to date", 'mydashboard'),
                "LASTYEAR" => __("year", 'mydashboard'),
                "LASTMONTH" => __("Last month", 'mydashboard'),
                "MONTH" => __("Month", 'mydashboard'),
            ];

            $rand = mt_rand();
            $dropdown = Dropdown::showFromArray("multiple_year_time", $temp, [
                "name" => 'multiple_year_time',
                "display" => false,
                "multiple" => false,
                "width" => '200px',
                "rand" => $rand,
                'value' => $opt['multiple_year_time'] ?? $default['multiple_year_time'],
                'display_emptychoice' => false,
            ]);

            // The month picker lives in its own span, refreshed in place by the AJAX call
            $month_html = TemplateRenderer::getInstance()->render(
                '@mydashboard/criteria_other_month.html.twig',
                [
                    'rand' => $rand,
                    'show_month' => isset($opt['multiple_year_time']) && $opt['multiple_year_time'] == 'MONTH',
                    'month_html' => Month::monthDropdown(
                        "month_year",
                        $opt['month_year'] ?? $default['multiple_year_time'],
                    ),
                ],
            );

            $root = $CFG_GLPI['root_doc'] . '/plugins/mydashboard';
            $ajax_html = Ajax::updateItemOnSelectEvent(
                'dropdown_multiple_year_time' . $rand,
                "month_crit$rand",
                $root . "/ajax/dropdownMonth.php",
                ['value' => '__VALUE__'],
                false,
            );

            $form = self::renderField(
                __('Time display', 'mydashboard'),
                $dropdown,
                $count,
                'double',
            ) . $month_html . $ajax_html;
        }

        //ITILCATEGORY LVL1
        if (in_array("itilcategorielvl1", $criterias)) {
            $dbu = new DbUtils();
            if (isset($_POST["params"]['entities_id'])) {
                $restrict = $dbu->getEntitiesRestrictCriteria(
                    'glpi_entities',
                    '',
                    $_POST["params"]['entities_id'],
                    $_POST["params"]['sons'],
                );
            } else {
                $restrict = $dbu->getEntitiesRestrictCriteria('glpi_entities', '', $opt['entities_id'], $opt['sons']);
            }

            $form = self::renderField(
                __('Category', 'mydashboard'),
                \ITILCategory::dropdown(
                    [
                        'name' => 'itilcategorielvl1',
                        'value' => $opt['itilcategorielvl1'] ?? $default['itilcategorielvl1'],
                        'display' => false,
                        'condition' => ['level' => 1, ['OR' => ['is_request' => 1, 'is_incident' => 1]]],
                    ] + $restrict,
                ),
                $count,
            );
        }

        if (in_array("tag", $criterias)) {
            $dbu = new DbUtils();
            if (isset($_POST["params"]['entities_id'])) {
                $restrict = $dbu->getEntitiesRestrictCriteria(
                    'glpi_plugin_tag_tags',
                    '',
                    $_POST["params"]['entities_id'],
                    $_POST["params"]['is_recursive_entities'],
                );
            } else {
                $restrict = $dbu->getEntitiesRestrictCriteria(
                    'glpi_plugin_tag_tags',
                    '',
                    $opt['entities_id'],
                    $opt['is_recursive_entities'],
                );
            }

            $tags = [];
            $tag = new PluginTagTag();
            foreach ($tag->find([$restrict]) as $data) {
                $types = json_decode($data['type_menu']);
                if (in_array('Ticket', $types)) {
                    $tags[$data['id']] = $data['name'];
                }
            }

            $form = self::renderField(
                __('Tag', 'mydashboard'),
                Dropdown::showFromArray("tag", $tags, [
                    'multiple' => false,
                    'display' => false,
                    'value' => $opt['tag'] ?? $default['tag'],
                    'size' => count($tags),
                ]),
                $count,
            );
        }

        return $form;
    }

}
