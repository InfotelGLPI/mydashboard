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

namespace GlpiPlugin\Mydashboard;

/**
 * This widget class is meant to display some html in a widget
 */
class Html extends Module
{
    static $rightname = "plugin_mydashboard";

   /**
    * Html constructor.
    */
    function __construct($titleVisibility = true)
    {
        $this->setWidgetType("html");
        $this->toggleOnlyHTML();

        $this->titleVisibility = $titleVisibility;
    }

   /**
    * @param int $nb
    * @return string
    */
    static function getTypeName($nb = 0)
    {

        return __('Dashboard', 'mydashboard');
    }

   /**
    * @return string
    */
    public function getJSonDatas()
    {
        return json_encode($this->getWidgetHtmlContent());
    }
}
