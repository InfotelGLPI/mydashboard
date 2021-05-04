<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015 by the MyDashboard Development Team.
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

/**
 * This class is the "mother" class of every widget class,
 * it contains all basic properties needed for a widget
 */
abstract class PluginMydashboardModule extends CommonGLPI {

   private $widgetId;
   private $widgetType = "chart";
   private $widgetTitle;
   private $widgetHeader;
   private $widgetHeaderType;
   private $widgetComment;
   private $widgetListTitle;
   private $widgetScripts = [];
   private $widgetHtmlContent = "";
   private $widgetEnableRefresh = false;
   private $widgetEnableMaximize = true;
   private $widgetIsOnlyHTML = false;
   private $widgetDebug = [];
   private $widgetColorTab;
   protected $titleVisibility = true;

   static $rightname = "plugin_mydashboard";

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return __('Dashboard', 'mydashboard');
   }

   function getTitleVisibility(){
      return $this->titleVisibility;
   }


   /**
    * Return the widgetId of the widget, default 'iddefault'
    * @return string
    */
   function getWidgetId() {
      if (!isset($this->widgetId)) {
         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            Toolbox::logDebug("PluginMydashboardModule::getWidgetId() You probably want to get a widgetId, but this one is not set, by default its value is 'iddefault'");
         }
         $this->widgetId = "iddefault";
      }
      return $this->widgetId;
   }

   /**
    * Return the widget type ('table','chart' ...)
    * @return string
    */
   function getWidgetType() {
      return $this->widgetType;
   }

   /**
    * Return the widget title, if not set it returns 'Default Title'
    * @return string of the title
    */
   function getWidgetTitle() {
      if (!isset($this->widgetTitle)) {
         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            Toolbox::logDebug("PluginMydashboardModule::getWidgetTitle() You probably want to get a widgetTitle, but this one is not set, by default its value is 'Default Title'");
         }
         $this->widgetTitle = "Default Title";
      }
      return $this->widgetTitle;
   }

   function getWidgetHeader(){
      return !isset($this->widgetHeader) ? "" : $this->widgetHeader;
   }

   function getWidgetHeaderType(){
      return !isset($this->widgetHeaderType) ? "" : $this->widgetHeaderType;
   }

   /**
    * @return string
    */
   function getWidgetComment() {
      if (!isset($this->widgetComment)) {
         $this->widgetComment = "";
      }
      return $this->widgetComment;
   }

   /**
    * Return the widget title which will be displayed in the menu list, by default it is basically the text part of widgetTitle (strip_tag)
    * @return string of the widget list title
    */
   function getWidgetListTitle() {
      if (!isset($this->widgetListTitle)) {
         $this->widgetListTitle = strip_tags($this->getWidgetTitle());
      }
      $this->widgetListTitle = stripslashes($this->widgetListTitle);
      return $this->widgetListTitle;
   }

   /**
    * Return the array of scripts of the widget
    * Adds some unremovable scripts executed after all
    * @return array of string
    */
   function getWidgetScripts() {
      foreach ($this->widgetScripts as &$script) {
         $script = str_replace([/*"\r\n","\n","\r",*/
            "'"], [/*"","","",*/
            "\""], $script);
      }
      //If the lateral menu is not displayed, we hide the remove button
      if (!PluginMydashboardHelper::getDisplayMenu()) {
         $this->appendWidgetScripts(["$('#" . $this->getWidgetId() . "').find('.sDashboard-circle-remove-icon').remove();"]);
      }
      if (!$this->widgetEnableMaximize) {
         $this->appendWidgetScripts(["$('#" . $this->getWidgetId() . "').find('.sDashboard-circle-plus-icon').remove();"]);
      }
      return $this->widgetScripts;
   }

   /**
    * Get the HTML content of the widget
    * @return type
    */
   function getWidgetHtmlContent() {
      //Internal debug is append to the widget as HTML content
      //Different colors depending on type of debug from white, information to red , error
      $bgcolors = [__('notice', 'mydashboard') => 'white',
                   __('warning', 'mydashboard') => 'orange',
                   __('error', 'mydashboard') => 'red'];
      //Internal debug is only shown when GLPI is in debug mode
      if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
          && !empty($this->widgetDebug)) {
         foreach ($this->widgetDebug as $type => $msg) {
            if (strlen($msg) > 0) {
               $this->appendWidgetHtmlContent("<span style='background-color:" . $bgcolors[$type] . ";'><b>" . ucfirst($type) . " : </b>" . $msg . "</span><br>");
            }
         }
         //We empty debug stack in case getHtmlContent is called few times
         $this->widgetDebug = "";
      }
      return $this->widgetHtmlContent;
   }

   /**
    * Return the value of widgetEnableRefresh
    * @return string, 'true' if refresh is enabled, else 'false'
    */
   function getWidgetEnableRefresh() {
      $ret = $this->widgetEnableRefresh ? "true" : "false";
      return $ret;
   }

   /**
    * Check if the widget is declared as only HTML (meaning no charts by dashboard)
    * @return boolean TRUE if only Html, FALSE otherwise
    */
   function getWidgetIsOnlyHTML() {
      return $this->widgetIsOnlyHTML;
   }

   /**
    * Set a new widget title
    *
    * @param string $nTitle
    *
    * @return mixed
    */
   function setWidgetTitle($nTitle) {
      //        $this->widgetTitle = addslashes($nTitle);
      return $this->widgetTitle = str_replace(["\""], ["'"], $nTitle);
   }

   /**
    * @param $header
    *
    * @return mixed
    */
   function setWidgetHeader($header){
      return $this->widgetHeader = $header;
   }

   /**
    * @param $header
    *
    * @return mixed
    */
   function setWidgetHeaderType($header){
      return $this->widgetHeaderType = $header;
   }
   /**
    * @param $nComment
    *
    * @return mixed
    */
   function setWidgetComment($nComment) {
      return $this->widgetComment = str_replace(["\""], ["'"], $nComment);
   }

   /**
    * Toggles only HTML value
    */
   function toggleOnlyHTML() {
      $this->widgetIsOnlyHTML = !$this->widgetIsOnlyHTML;
   }


   /**
    * Set a new HTML content for the widget
    * @param string $_html
    */
   function setWidgetHtmlContent($_html) {
      $this->widgetScripts = array_merge(PluginMydashboardHelper::extractScriptsFromString($_html), $this->widgetScripts);

      $this->widgetHtmlContent = PluginMydashboardHelper::removeScriptsFromString($_html);
   }

   /**
    * Append new HTML content to the widget
    * @param string $_html
    */
   function appendWidgetHtmlContent($_html) {
      $this->widgetScripts = array_merge(PluginMydashboardHelper::extractScriptsFromString($_html), $this->widgetScripts);
      $this->widgetHtmlContent = $this->widgetHtmlContent . PluginMydashboardHelper::removeScriptsFromString($_html);
   }


   /**
    * Set a new widget list title
    * @param string $_widgetListTitle
    */
   function setWidgetListTitle($_widgetListTitle) {
      $this->widgetListTitle = strip_tags($_widgetListTitle);
   }


   /**
    * Set a new widgetId
    * @param string $_widgetId
    */
   function setWidgetId($_widgetId) {
      $this->widgetId = $_widgetId;
   }


   /**
    * Set the new widget type
    * @param string $_Type
    */
   function setWidgetType($_Type) {
      $this->widgetType = $_Type;
   }


   /**
    * Set the new array of scripts of the widget
    * @param array of string $_scripts
    */
   function setWidgetScripts($_scripts) {
      $this->widgetScripts = $_scripts;
   }

   /**
    * Set the new array of scripts of the widget
    * @param array of string $_scripts
    */
   function appendWidgetScripts($_scripts) {
      $this->widgetScripts = array_merge($this->widgetScripts, $_scripts);
   }

   /**
    * Toggle refresh button on the widget, useless if there are no callbacks
    * @param int $value
    */
   function toggleWidgetRefresh($value = -1) {
      if ($value == -1) {
         $this->widgetEnableRefresh = !$this->widgetEnableRefresh;
      } else {
         $this->widgetEnableRefresh = $value;
      }
   }

   /**
    * Toggle maximize button on the widget
    */
   function toggleWidgetMaximize() {
      $this->widgetEnableMaximize = !$this->widgetEnableMaximize;
   }

   /**
    * @param $msg
    */
   function debugNotice($msg) {
      $this->widgetDebug[__("notice", 'mydashboard')] = $msg;
   }

   /**
    * @param $msg
    */
   function debugWarning($msg) {
      $this->widgetDebug[__("warning", 'mydashboard')] = $msg;
   }

   /**
    * @param $msg
    */
   function debugError($msg) {
      $this->widgetDebug[__("error", 'mydashboard')] = $msg;
   }

   /**
    * Get the color array
    * @return array like ['label1'=>color1,'label2'=>color2 ...]
    */
   function getColorTab() {
      return $this->widgetColorTab;
   }

   /**
    * Set a new color array
    * @param array $_widgetColorTab
    *        [
    *          'label1'=>'<hexa_color>',
    *          ...
    *        ]
    */
   function setColorTab($_widgetColorTab) {
      $this->widgetColorTab = $_widgetColorTab;
   }

   /**
    * To be overwrited by children
    * Must set $optionvalue to $optionname
    * @param $optionname
    * @param $optionvalue
    * @param bool $force
    */
   function setOption($optionname, $optionvalue, $force = false) {
   }

   /**
    * To be overwrited by children
    * Must give an array of datas
    */
   function getTabDatas() {
   }

   /**
    * To be overwrited by children
    * Must set an array of datas
    * @param $_tabdatas
    */
   function setTabDatas($_tabdatas) {
   }
}
