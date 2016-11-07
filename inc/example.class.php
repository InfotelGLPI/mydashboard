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
 * Class PluginMydashboardExample
 */
class PluginMydashboardExample extends CommonGLPI
{
   public $widgets = array();
   private $options;

   /**
    * PluginMydashboardExample constructor.
    * @param array $options
    */
   function __construct($options = array())
   {

      $this->options = $options;
   }

   /**
    * @return array
    */
   function getWidgetsForItem()
   {
      return array(
         "PluginMydashboardExamplewidget1" => __("My first widget (HTML)", 'mydashboard'),
         "PluginMydashboardExamplewidget2" => __("My second widget (VBarChart)", 'mydashboard'),
         "PluginMydashboardExamplewidget3" => __("My third widget (HBarChart)", 'mydashboard'),
         "PluginMydashboardExamplewidget4" => __("My fourth widget (PieChart)", 'mydashboard'),
         "PluginMydashboardExamplewidget5" => __("My fifth widget (LineChart)", 'mydashboard'),
         "PluginMydashboardExamplewidget6" => __("My sixth widget (Datatable)", 'mydashboard'),
         __("Others") => array(
            "PluginMydashboardExamplewidget7" => __("My seventh widget (Clock)", 'mydashboard'),
            "PluginMydashboardExamplewidget8" => __("My eighth widget (RadarChart)", 'mydashboard'),
            "PluginMydashboardExamplewidget9" => __("My nineth widget (Fake one)", 'mydashboard')
         )
      );
   }

   /**
    * @param $widgetId
    * @return PluginMydashboardHtml|PluginMydashboardPieChart|PluginMydashboardRadarChart|PluginMydashboardVBarChart
    */
   function getWidgetContentForItem($widgetId)
   {
      //set advanced to true if you want to see advanced widgets
      $advanced = true;
      //The data is the same for every sample widgets
      $sampleDatas = array(1 => 0, 2 => 4, 3 => 1, 5 => 4);
      switch ($widgetId) {
         case "PluginMydashboardExamplewidget1" :
            return $this->getWidgetHtml($widgetId);
            break;
         case "PluginMydashboardExamplewidget2" : //widget2 is a Vertical Bar Chart
            $widget2 = new PluginMydashboardVBarChart(); //On déclare un widget (V(ertical)BarChart)
            //We set its id, <name_of_plugin>.<whateveryouwant>
//                    $widget2->setWidgetId("PluginMydashboardExample"."widget2");
            //We set a title for this widget, don't forget internationalization
            $widget2->setWidgetTitle(__("My second widget (VBarChart)", 'mydashboard'));
            //We set the data
            $widget2->setTabDatas($sampleDatas);
            //If you want to see some sample options set $advanced to true
            if ($advanced) {
               //Here we want a bar chart with two series of data (a stacked barchart)
               //array(Serie1,Serie2,...) each Serie = array('label'=>'value','x'=>'y'...) or with PHP 5.4 Serie = ['label'=>'value' ...]
               $widget2->setTabDatas(array(array("x" => 0, "y" => 4, "z" => 1, "v" => 0, "w" => 4), array("x" => 3, "y" => 1, "z" => 6, "v" => 1.5, "w" => 2)));
               $widget2->setOption("xaxis", array("ticks" => PluginMydashboardBarChart::getTicksFromLabels($widget2->getTabDatas())));
               //This options can be useful if you want to display the data table
               //Attention, this option lead to an unattended comportement when maximized,
               //maybe due to css property position of the spreadsheet
               $widget2->setOption('spreadsheet', array("show" => true));
            }

            return $widget2;
            break;
         case "PluginMydashboardExamplewidget3" :
            $widget3 = new PluginMydashboardHBarChart(); //On déclare un widget (H(orizontal)BarChart)
//                    $widget3->setWidgetId("PluginMydashboardExample"."widget3");
            $widget3->setWidgetTitle(__("My third widget (HBarChart)", 'mydashboard'));
            $widget3->setTabDatas($sampleDatas);

            if ($advanced) {
               //He we want a bar chart but with string labels
               $widget3->setTabDatas(array("Un" => 0, "Deux" => 4, "Trois" => 1, "Cinq" => 4));
               //We need to declare specific ticks for the yaxis (we are horizontal)
               //getTicksFromLabels is able to get ticks from an array of datas
               $widget3->setOption("yaxis", array("ticks" => PluginMydashboardBarChart::getTicksFromLabels($widget3->getTabDatas())));
               //Of course you can manually associate value with a label :
               //$widget3->setOption("yaxis", array("ticks" => array([1,"Un"],[2,"Deux"],[3,"Trois"],[4,"Quatre"],[5,"Cinq"])));
               //We want to display the X value on the bar, markers is the Flotr2 way to do this,
               //it needs to be formatted, dashboard provides a formatter (getLabelFormatter),parameter = 1 is to display the x value
               $widget3->setOption("markers", array("show" => true, "position" => "lm", "labelFormatter" => PluginMydashboardBarChart::getLabelFormatter(1)));
            }

            return $widget3;
            break;
         case "PluginMydashboardExamplewidget4" : //widget4 is a Pie Chart
            $widget4 = new PluginMydashboardPieChart(); //On déclare un widget (H(orizontal)BarChart)
//                    $widget4->setWidgetId("PluginMydashboardExample"."widget4");
            $widget4->setWidgetTitle(__("My fourth widget (PieChart)", 'mydashboard'));
            $widget4->setTabDatas($sampleDatas);

            if ($advanced) {
               //For this pie we want a custom format for label,
               //Value = X units
               $widget4->setOption('pie', array('labelFormatter' => PluginMydashboardPieChart::getLabelFormatter(2, __("Value", 'mydashboard') . " = ", " " . __("units", 'mydashboard'))));
            }
            return $widget4;
            break;
         case "PluginMydashboardExamplewidget5" : //widget5 is a Line Chart
            $widget5 = new PluginMydashboardLineChart(); //On déclare un widget (LineChart)
//                    $widget5->setWidgetId("PluginMydashboardExample"."widget5");
            $widget5->setWidgetTitle(__("My fifth widget (LineChart)", 'mydashboard'));
            $widget5->setTabDatas(array("f(x)" => $sampleDatas));
            return $widget5;
            break;
         case "PluginMydashboardExamplewidget6" : //widget6 is a Datatable
            $widget6 = new PluginMydashboardDatatable(); //On déclare un widget (Datatable)
//                    $widget6->setWidgetId("PluginMydashboardExample"."widget6");
            $widget6->setWidgetTitle(__("My sixth widget (Datatable)", 'mydashboard'));


//                    $testName = array();
//                    $testData = array();
//                    for($i=0;$i<50;$i++) $testName[] = "x".$i;
//                    for($i=0;$i<1000;$i++) for($j=0;$j<50;$j++) $testData[$i]["x".$j] = $i*$j;

            $widget6->setTabNames(array("x", "y"));
            $widget6->setTabDatas(array(array(1, 0), array("y" => 2, "x" => 4), array(3, 1), array(5, 4)));
//                    $widget6->setTabNames($testName);
//                    $widget6->setTabDatas($testData);
            return $widget6;
            break;
         case "PluginMydashboardExamplewidget7" : //The seventh widget is a widget that displays the hour, it's to test/show the automatic refreshing of widgets
            $widget7 = new PluginMydashboardPieChart();
//                    $widget7->setWidgetId("PluginMydashboardExample"."widget7");
            $widget7->setWidgetTitle(__("My seventh widget (Clock)", 'mydashboard'));
            //We toggle the refresh, of course it's the main goal of this widget
            $widget7->toggleWidgetRefresh();
            //Two data, time elapsed int his our and time left
            $widget7->setTabDatas(array(__("Minutes elapsed", 'mydashboard') => date('i'), __("Minutes left", 'mydashboard') => 60 - date('i')));
            //It's a clock, we start from noon, by default we start on the left 0.75 means 'noon', and we choose the labelFormatter 3 no display
            $widget7->setOption('pie', array('fillOpacity' => '1', 'startAngle' => '0.75', 'labelFormatter' => PluginMydashboardPieChart::getLabelFormatter(3)));
            //Choosing the color of a slice
            //$widget7->setColorTab(array(__("Minutes left",'mydashboard')=>"#FFF"));
            //We check if the MyDashboard is automatically refreshed or not
            if (PluginMydashboardHelper::getAutomaticRefresh()) {
               $script = "<script type='text/javascript'>"
                  . "time = " . (PluginMydashboardHelper::getAutomaticRefreshDelay() * 60) . ";"
                  . "if(typeof timer !== 'undefined') clearInterval(timer);"
                  . "timer = setInterval(function(){"
                  . "$('#secondes').html(time-1);"
                  . "time--;"
                  . "},1000);"
                  . "</script>";
               $delay = PluginMydashboardHelper::getAutomaticRefreshDelay();
               $widget7->setWidgetHtmlContent("<h1>" . date('H') . ":" . date('i') . "</h1>"
                  . "<p>" . __("This example widget display the time, it refreshes every ", 'mydashboard')
                  . $delay . " " . _n("minute", "minutes", $delay)
                  . " (<span id='secondes'>0</span>)"
                  . $script);
            } else {
               $widget7->setWidgetHtmlContent("<h1>" . date('H') . ":" . date('i') . "</h1><p>" . __("This example widget display the time, it can be refreshed manually", 'mydashboard'));
            }
            return $widget7;
            break;
         case "PluginMydashboardExamplewidget8" :
            $widget8 = new PluginMydashboardRadarChart(); //On déclare un widget (RadarChart)
//                    $widget8->setWidgetId("PluginMydashboardExample"."widget8");
            $widget8->setWidgetTitle(__("My eighth widget (RadarChart)", 'mydashboard'));
            $widget8->setTabDatas(array(
                  "real " => array(1 => 2, 2 => 4, 3 => 1, 4 => 1, 5 => 4),
                  "expected " => array(1 => 2, 2 => 2, 3 => 2, 4 => 2, 5 => 2)
               )
            );
            $widget8->setOption("mouse", array("track" => true));
            return $widget8;
            break;

      }
   }

   /**
    * Get an example of HTML widget, this example has javascript in it,
    * it also has a form to parameterize the widget
    * @param $widgetId
    * @return PluginMydashboardHtml , an example widget
    */
   function getWidgetHtml($widgetId)
   {
      $widget1 = new PluginMydashboardHtml(); //On déclare un widget (Html)
//        $widget1->setWidgetId("PluginMydashboardExample"."widget1");
      $widget1->setWidgetTitle(__("My first widget (HTML)", 'mydashboard'));

      $formHeader = PluginMydashboardHelper::getFormHeader($widgetId);
      $formContent = "<label>" . __("Answer", 'mydashboard') . " :<select name='number' value='1'>";
      $formContent .= "<option value='1'>1</option>";
      $formContent .= "<option value='2'>2</option>";
      $formContent .= "<option value='3'>3</option>";
      $formContent .= "<option value='4'>4</option>";
      $formContent .= "</select></label>";
      $formfooter = "</form>";
      $form = $formHeader . $formContent . $formfooter;

      $text = "<h1 id='titrewidget1'>" . __("Examples widgets", "mydashboard") . "</h1>";
      $text .= "2+2 = ";
      if (isset($this->options['number'])) {
         if ($this->options['number'] == 4)
            $text .= $this->options['number'] . " <b style='color:green'> GOOD</b>";
         else
            $text .= "<del>" . $this->options['number'] . "</del><b style='color:red'> WRONG</b>";
      } else $text .= "?";
      $table = "<p>" . __("In all example widgets the datas are the same :", 'mydashboard') . "</p>"
         . "<table class='left' width='100%'><tr><th>X</th><th>Y</th></tr>"
         . "<tr><td >1</td><td >0</td></tr><tr class='even'><td >2</td><td >4</td></tr><tr class='odd'><td >3</td><td >1</td></tr><tr class='even'><td >5</td><td >4</td></tr>"
         . "</table>";

      $script = "<script type='text/javascript'>"
         . "onMaximize['" . $widgetId . "'] = function(){ $('#titrewidget1').html('" . __("Maximized", 'mydashboard') . "'); $('#titrewidget1').css('color','blue'); };"
         . "onMinimize['" . $widgetId . "'] = function(){ $('#titrewidget1').html('" . __("Minimized", 'mydashboard') . "'); $('#titrewidget1').css('color','red'); };"
         . "//$('#titrewidget1').html('" . __("Initialized", 'mydashboard') . "');"
         . "//$('#titrewidget1').css('color','green');"
         . "</script>";

      $widget1->setWidgetHtmlContent($text
         . $form
         . $table
         . $script
      );
      $widget1->toggleWidgetRefresh();

      return $widget1;
   }

}
