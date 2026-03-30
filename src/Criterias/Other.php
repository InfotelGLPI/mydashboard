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

namespace GlpiPlugin\Mydashboard\Criterias;

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
        'week',
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
//        if (in_array("week", $criterias)) {
        $default['week'] = intval(date('W', time()) - 1);
//        }

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
        if (isset($opt['week'])) {
            $form .= "&nbsp;/&nbsp;" . __('Week', 'mydashboard') . "&nbsp;:&nbsp;" . $opt['week'];
        }


        // TECHNICIAN MULTIPLE
        if (isset($opt['multiple_technicians_id'])) {
            $opt['multiple_technicians_id'] = is_array(
                $opt['multiple_technicians_id']
            ) ? $opt['multiple_technicians_id'] : [];

            $opt['multiple_technicians_id'] = array_filter($opt['multiple_technicians_id']);

            if (count($opt['multiple_technicians_id']) > 0) {
                $form .= "&nbsp;/&nbsp;" . _n(
                        'Technician',
                        'Technicians',
                        count($opt['multiple_technicians_id']),
                        'mydashboard'
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
                    $opt['tag']
                );
        }
        if (isset($opt['multiple_year_time'])) {
            switch ($opt['multiple_year_time']) {
                case "LASTMONTH":
                    $form .= "&nbsp;/&nbsp;" . __('Time display', 'mydashboard') . "&nbsp;/&nbsp;" . __(
                            "Last month",
                            'mydashboard'
                        );
                    break;
                case "LASTYEAR":
                    $form .= "&nbsp;/&nbsp;" . __('Time display', 'mydashboard') . "&nbsp;/&nbsp;" . __(
                            "Last year",
                            'mydashboard'
                        );
                    break;
                case "YEARTODATE":
                    $form .= "&nbsp;/&nbsp;" . __('Time display', 'mydashboard') . "&nbsp;/&nbsp;" . __(
                            "Year to date",
                            'mydashboard'
                        );
                    break;
                case "MONTH":
                    $form .= "&nbsp;/&nbsp;" . __('Time display', 'mydashboard') . "&nbsp;/&nbsp;" . __(
                            "Month",
                            'mydashboard'
                        );
                    break;
            }
        }


        if (isset($opt['itilcategorielvl1']) && $opt['itilcategorielvl1'] > 0) {
            $form .= "&nbsp;/&nbsp;" . __("Category", 'mydashboard') . "&nbsp;:&nbsp;" . Dropdown::getDropdownName(
                    'glpi_itilcategories',
                    $opt['itilcategorielvl1']
                );
        }

        return $form;
    }

    public static function getDisplayForm($default, $opt, $count)
    {
        global $CFG_GLPI;

        $criterias = $opt['criterias'] ?? $default['criterias'];
        // DATE
        // YEAR

        if (in_array("week", $criterias)) {
            $form = "<span class='md-widgetcrit'>";
            //            $semaine_courante = date('W', time());
            $semaine_courante = $default['week'];
            if (isset($opt["week"])
                && $opt["week"] > 0) {
                $semaine_courante = $opt["week"];
            }
            $form .= __('Week', 'mydashboard');
            $form .= "&nbsp;";
            $form .= self::WeekDropdown($semaine_courante);
            $form .= "</span>";
            if ($count > 1) {
                $form .= "</br></br>";
            }
        }

        // START DATE
        if (in_array("begin", $criterias)) {
            $form = "<span class='md-widgetcrit'>";
            $form .= __('Start');
            $form .= "&nbsp;";
            $form .= Html::showDateTimeField(
                "begin",
                ['value' => $opt['begin'] ?? $default['begin'], 'maybeempty' => false, 'display' => false]
            );
            $form .= "</span>";
            if ($count > 1 && !in_array("end", $criterias)) {
                $form .= "</br></br>";
            } elseif ($count > 1 && in_array("end", $criterias)) {
                $form .= "</br>";
            }
        }
        // END DATE
        if (in_array("end", $criterias)) {
            $form = "<span class='md-widgetcrit'>";
            $form .= __('End');
            $form .= "&nbsp;";
            $form .= Html::showDateTimeField(
                "end",
                ['value' => $opt['end'] ?? $default['end'], 'maybeempty' => false, 'display' => false]
            );
            $form .= "</span>";
            if ($count > 1) {
                $form .= "</br></br>";
            }
        }

        // TECHNICIAN MULTIPLE
        if (in_array("multiple_technicians_id", $criterias)) {
            $form = "<span class='md-widgetcrit'>";

            $params['entity'] = $_SESSION['glpiactive_entity'];
            $params['right'] = ['groups'];
            $data_users = [];
            $users = [];
            $param['values'] = [];
            $params['groups_id'] = 0;
            if (isset($opt['technicians_groups_id'])) {
                $technicians_groups_id = (is_array(
                    $opt['technicians_groups_id']
                ) ? $opt['technicians_groups_id'] : []);
            } else {
                $technicians_groups_id = [];
            }
            $technicians_groups_id = 1;
            $list = [];
            $restrict = [];
            $res = User::getSqlSearchResult(false, $params['right'], $params['entity']);
            foreach ($res as $data) {
                $list[] = $data['id'];
            }
            if (count($list) > 0) {
                $restrict = ['glpi_users.id' => $list];
            }
            $restrict["glpi_users.is_deleted"] = 0;
            $restrict["glpi_users.is_active"] = 1;

            $data_users = Group_User::getGroupUsers($technicians_groups_id, $restrict);

            foreach ($data_users as $data) {
                $users[$data['id']] = formatUserName(
                    $data['id'],
                    $data['name'],
                    $data['realname'],
                    $data['firstname']
                );
                $params['values'][] = $data['id'];
            }

            $params['multiple'] = true;
            $params['display'] = false;
            $params['size'] = count($users);

            $form .= _n('Technician', 'Technicians', 2, 'mydashboard');
            $form .= "&nbsp;";

            $dropdownusers = Dropdown::showFromArray(
                "multiple_technicians_id",
                $users ?? $default['multiple_technicians_id'],
                $params
            );

            $form .= $dropdownusers;

            $form .= "</span>";
            if ($count > 1) {
                $form .= "</br></br>";
            }
        }

        //STATUS
        if (in_array("status", $criterias)) {
            $form = "<span class='md-widgetcrit'>";
            $form .= _n('Status', 'Statuses', 2) . "&nbsp;";
            $default = [
                CommonITILObject::INCOMING,
                CommonITILObject::ASSIGNED,
                CommonITILObject::PLANNED,
                CommonITILObject::WAITING,
            ];


            $i = 1;
            foreach (Ticket::getAllStatusArray() as $svalue => $sname) {
                $form .= '<input type="hidden" name="status_' . $svalue . '" value="0" /> ';
                $form .= '<input type="checkbox" name="status_' . $svalue . '" value="1"';

                if (in_array($svalue, $opt['status'])) {
                    $form .= ' checked="checked"';
                }
                if (count($opt['status']) < 1 && in_array($svalue, $default['status'])) {
                    $form .= ' checked="checked"';
                }

                $form .= ' /> ';
                $form .= $sname;
                if ($i % 2 == 0) {
                    $form .= "<br>";
                } else {
                    $form .= "&nbsp;";
                }
                $i++;
            }
            $form .= "</span>";
            if ($count > 1) {
                $form .= "</br></br>";
            }
        }

        if (in_array("multiple_time", $criterias)) {
            $form = "<span class='md-widgetcrit'>";


            $temp = [];
            $temp["DAY"] = __("Day", 'mydashboard');
            $temp["WEEK"] = __("Week", 'mydashboard');
            $temp["MONTH"] = __("Month", 'mydashboard');

            $params = [
                "name" => 'multiple_time',
                "display" => false,
                "multiple" => false,
                "width" => '200px',
                'value' => $opt['multiple_time'] ?? $default['multiple_time'],
                'display_emptychoice' => false,
            ];

            $form .= __('Time display', 'mydashboard');
            $form .= "&nbsp;";

            $dropdown = Dropdown::showFromArray("multiple_time", $temp, $params);

            $form .= $dropdown;

            $form .= "</span>";


            if ($count > 1) {
                $form .= "</br></br>";
            }
        }

        if (in_array("multiple_year_time", $criterias)) {
            $form = "<span class='md-widgetcrit'>";


            $temp = [];
            $temp["YEARTODATE"] = __("Year to date", 'mydashboard');
            $temp["LASTYEAR"] = __("year", 'mydashboard');
            $temp["LASTMONTH"] = __("Last month", 'mydashboard');
            $temp["MONTH"] = __("Month", 'mydashboard');

            $rand = mt_rand();
            $params = [
                "name" => 'multiple_year_time',
                "display" => false,
                "multiple" => false,
                "width" => '200px',
                "rand" => $rand,
                'value' => $opt['multiple_year_time'] ?? $default['multiple_year_time'],
                'display_emptychoice' => false,
            ];

            $form .= __('Time display', 'mydashboard');
            $form .= "&nbsp;";

            $dropdown = Dropdown::showFromArray("multiple_year_time", $temp, $params);

            $form .= $dropdown;


            $form .= "</span>";
            if (isset($opt['multiple_year_time']) && $opt['multiple_year_time'] == 'MONTH') {
                $form .= "<span id='month_crit$rand' name= 'month_crit$rand' class='md-widgetcrit'>";
                $form .= "</br></br>";
                $form .= __('Month', 'mydashboard');
                $form .= "&nbsp;";
                $form .= Month::monthDropdown(
                    "month_year",
                    ($opt['month_year'] ?? $default['multiple_year_time'])
                );
                $form .= "</span>";
            } else {
                $form .= "<span id='month_crit$rand' name= 'month_crit$rand' class='md-widgetcrit'></span>";
            }

            $params2 = [
                'value' => '__VALUE__',

            ];
            $root = $CFG_GLPI['root_doc'] . '/plugins/mydashboard';
            $form .= Ajax::updateItemOnSelectEvent(
                'dropdown_multiple_year_time' . $rand,
                "month_crit$rand",
                $root . "/ajax/dropdownMonth.php",
                $params2,
                false
            );

            if ($count > 1) {
                $form .= "</br></br>";
            }
        }

        //ITILCATEGORY LVL1
        if (in_array("itilcategorielvl1", $criterias)) {
            $form = "<span class='md-widgetcrit'>";

            $form .= __('Category', 'mydashboard');
            $form .= "&nbsp;";
            $dbu = new DbUtils();
            if (isset($_POST["params"]['entities_id'])) {
                $restrict = $dbu->getEntitiesRestrictCriteria(
                    'glpi_entities',
                    '',
                    $_POST["params"]['entities_id'],
                    $_POST["params"]['sons']
                );
            } else {
                $restrict = $dbu->getEntitiesRestrictCriteria('glpi_entities', '', $opt['entities_id'], $opt['sons']);
            }

            $dropdown = \ITILCategory::dropdown(
                [
                    'name' => 'itilcategorielvl1',
                    'value' => $opt['itilcategorielvl1'] ?? $default['itilcategorielvl1'],
                    'display' => false,
                    'condition' => ['level' => 1, ['OR' => ['is_request' => 1, 'is_incident' => 1]]],
                ] + $restrict
            );

            $form .= $dropdown;

            $form .= "</span>";


            if ($count > 1) {
                $form .= "</br></br>";
            }
        }


        if (in_array("tag", $criterias)) {
            $form = "<span class='md-widgetcrit'>";

            $form .= __('Tag', 'mydashboard');
            $form .= "&nbsp;";
            $dbu = new DbUtils();
            if (isset($_POST["params"]['entities_id'])) {
                $restrict = $dbu->getEntitiesRestrictCriteria(
                    'glpi_plugin_tag_tags',
                    '',
                    $_POST["params"]['entities_id'],
                    $_POST["params"]['is_recursive_entities']
                );
            } else {
                $restrict = $dbu->getEntitiesRestrictCriteria(
                    'glpi_plugin_tag_tags',
                    '',
                    $opt['entities_id'],
                    $opt['is_recursive_entities']
                );
            }
            $tag = new PluginTagTag();
            $data_tags = $tag->find([$restrict]);
            foreach ($data_tags as $data) {
                $types = json_decode($data['type_menu']);
                if (in_array('Ticket', $types)) {
                    $tags[$data['id']] = $data['name'];
                }
            }
            $params['multiple'] = false;
            $params['display'] = false;
            $params['value'] = $opt['tag'] ?? $default['tag'];
            $params['size'] = count($tags);


            $dropdown = Dropdown::showFromArray("tag", $tags, $params);


            $form .= $dropdown;

            $form .= "</span>";


            if ($count > 1) {
                $form .= "</br></br>";
            }
        }


        return $form;
    }

    /**
     * @param null $selected
     *
     * @return int|string
     */
    public static function WeekDropdown($selected = null)
    {
        $opt = [
            'value' => $selected,
            'min' => 1,
            'max' => 53,
            'display' => false,
        ];

        return Dropdown::showNumber("week", $opt);
    }
}
