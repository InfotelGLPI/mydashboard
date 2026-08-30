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
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class HTMLEditor extends CommonDBTM
{
    public $itemtype = Customswidget::class;
    public $items_id = 'id';

    public static $types = [Customswidget::class];

    public static $rightname = 'plugin_mydashboard';

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

        $field = new self();

        $field->showForm($item);

        return true;
    }

    public function showForm($item, $openform = true, $closeform = true)
    {
        $rand = mt_rand();

        //        $editor_options = [
        //            'mode'         => 'text/css',
        //            'lineNumbers'  => true,
        //            //         'lineWrapping' => true,
        //            // Autocomplete with CTRL+SPACE
        //            'extraKeys'    => [
        //                'Ctrl-Space' => 'autocomplete',
        //            ],
        //
        //            // Code folding configuration
        //            'foldGutter'   => true,
        //            'gutters'      => [
        //                'CodeMirror-linenumbers',
        //                'CodeMirror-foldgutter',
        //            ],
        //        ];
        //
        //        echo \Html::scriptBlock('
        //              $(function() {
        //                 var textarea = document.getElementById("custom_css_code_' . $rand . '");
        //                 var editor = CodeMirror.fromTextArea(textarea, ' . json_encode($editor_options) . ');
        //
        //                 // Fix bad display of gutter (see https://github.com/codemirror/CodeMirror/issues/3098 )
        //                 setTimeout(function () {editor.refresh();}, 10);
        //
        //              });
        //           ');

        echo TemplateRenderer::getInstance()->render('@mydashboard/htmleditor_form.html.twig', [
            'openform' => $openform,
            'closeform' => $closeform,
            'form_action' => Toolbox::getItemTypeFormURL(HTMLEditor::class),
            'name' => $item->fields['name'],
            'textarea_html' => \Html::textarea([
                'name' => 'content',
                'value' => htmlspecialchars($item->fields['content']),
                'editor_id' => 'custom_css_code_' . $rand,
                'enable_richtext' => true,
                'display' => false,
            ]),
            'hidden_html' => \Html::hidden('id', ['value' => $item->fields['id']]),
            'submit_html' => \Html::submit(_sx('button', 'Save'), ['name' => 'update',
                'class' => 'btn btn-primary',
            ]),
            'close_form_html' => \Html::closeForm(false),
        ]);
    }
}
