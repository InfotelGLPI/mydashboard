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

use CommonGLPI;
use Glpi\RichText\RichText;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use GlpiPlugin\Mydashboard\Customswidget;
use GlpiPlugin\Mydashboard\Html;

/**
 * Class Reports_Custom
 */
class Reports_Custom extends CommonGLPI
{
    private $options;
    private $pref;
    public static $reports = [];

    /**
     * Reports_Custom constructor.
     *
     * @param array $_options
     */
    public function __construct($_options = [])
    {
        $this->options = $_options;
    }

    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        //      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        $widgets = [];
        $customsWidgets = Customswidget::listCustomsWidgets();
        if (!empty($customsWidgets)) {
            foreach ($customsWidgets as $customWidget) {
                $addwidgets[$this->getType() . "cw" . $customWidget['id']] = [
                    // The widget name is user-controlled free text stored raw and later
                    // emitted as a menu label (and into a data-classname attribute) in the
                    // widget picker. Escape it here at the source so the shared render path
                    // keeps working for report widgets whose titles are legitimate HTML.
                    "title" => htmlspecialchars($customWidget['name'], ENT_QUOTES, 'UTF-8'),
                    "type" => Widget::$OTHERS,
                    "icon" => "ti ti-edit",
                    "comment" => "",
                ];
            }
            $widgets[Menu::$OTHERS] = $addwidgets;
        }

        return $widgets;
    }

    /**
     * @param       $widgetId
     * @param array $opt
     *
     * @return string
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
        switch ($widgetId) {
            default:
                {
                    // It's a custom widget
                    if (strpos($widgetId, "cw")) {
                        // Last letter of widgetId is customWidget index in database
                        $id = intval(substr($widgetId, -1));

                        $content = Customswidget::getCustomWidget($id);

                        $widget = new Html(true);

                        // Escape the user-controlled custom widget name at the source.
                        // setWidgetTitle() is also fed trusted HTML titles (e.g. <a> links)
                        // by report widgets, so escaping must happen here, not in the shared
                        // render path where the title is emitted raw.
                        $widget->setWidgetTitle(htmlspecialchars($content['name'], ENT_QUOTES, 'UTF-8'));

                        // Sanitize the stored free-HTML widget content before rendering it
                        // as raw HTML on every user's dashboard. This keeps the allowed
                        // formatting (headings, styles, images, links) but strips scripts
                        // and event handlers, closing the stored-XSS path where a config
                        // admin could inject a payload executed in another user's browser.
                        $htmlContent = RichText::getSafeHtml(
                            html_entity_decode($content['content']),
                        );

                        // Edit style to avoid padding, margin, and limited width

                        //               $htmlContent .= "<script>
                        //                $( document ).ready(function() {
                        //                    let $widgetId = document.getElementById('$widgetId');
                        //                    " . $widgetId . ".children[0].style.marginTop = '-5px';
                        //                    " . $widgetId . ".children[0].children[0].classList.remove('bt-col-md-11');
                        //                    " . $widgetId . ".children[0].children[0].classList.add('bt-col-md-12');
                        //                    " . $widgetId . ".children[0].children[0].children[0].style = 'padding-left : 0% !important; margin-right : 28px;margin-bottom: -10px;';
                        //                });
                        //                </script>";

                        if (isset($opt["is_widget"]) && $opt["is_widget"] == false) {
                            return $htmlContent;
                        }
                        $widget->setWidgetHtmlContent($htmlContent);
                        return $widget;
                    }
                }
        }
    }
}
