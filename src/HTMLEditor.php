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

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Session;
use Toolbox;

class HTMLEditor extends CommonDBTM
{
    public $itemtype = Customswidget::class;
    public $items_id = 'id';

    public static $types = [Customswidget::class];

    // The tab is attached to Customswidget, which is gated by
    // 'plugin_mydashboard_config': declaring the weaker 'plugin_mydashboard' here let a
    // plain dashboard user reach the editor through the tab.
    public static string $rightname = 'plugin_mydashboard_config';

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'            => '66',
            'table'         => $this->getTable(),
            'field'         => 'content',
            'name'          => __('Content'),
            'datatype'      => 'text',
            'itemlink_type' => $this->getType(),
        ];
    }

    public static function getIcon()
    {
        return Menu::getIcon();
    }

    /**
     * Display tab for each users
     *
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $dbu = new DbUtils();
        if (!$withtemplate) {
            if ($item->getType() == Customswidget::class) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    return self::createTabEntry(
                        Customswidget::getTypeName(),
                        $dbu->countElementsInTable(
                            Customswidget::getTable(),
                            ["`id`" => $item->getID()],
                        ),
                    );
                }
                return self::createTabEntry(Customswidget::getTypeName());
            }
        }
        return '';
    }

    /**
     * Display content for each users
     *
     * @static
     *
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // CommonGLPI::displayStandardTab() checks no right on a plugin tab, and
        // ajax/common.tabs.php reaches this method without ever calling getTabNameForItem():
        // the guard placed on the declaration is not replayed here.
        Session::checkRight(self::$rightname, UPDATE);

        $field = new self();

        $field->showForm($item);

        return true;
    }

    public function showForm($item, $openform = true, $closeform = true)
    {
        $rand = mt_rand();

        TemplateRenderer::getInstance()->display('@mydashboard/htmleditor_form.html.twig', [
            'openform' => $openform,
            'closeform' => $closeform,
            'form_action' => Toolbox::getItemTypeFormURL(HTMLEditor::class),
            'rand' => $rand,
            'id' => $item->fields['id'],
            'name' => $item->fields['name'],
            // Encoded once more as before: the editor shows the stored markup, which
            // front/htmleditor.form.php decodes then sanitizes on save.
            'content' => htmlspecialchars($item->fields['content']),
        ]);
    }
}
