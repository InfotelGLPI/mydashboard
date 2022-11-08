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

/**
 * Class PluginMydashboardBarChart
 */
abstract class PluginMydashboardBarChart extends PluginMydashboardChart
{

    /**
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    static function launchGraph($graph_datas = [], $graph_criterias = [])
    {

        $onclick = 0;
        if (count($graph_criterias) > 0)
        {
            $onclick = 1;
        }
        $name    = $graph_datas['name'];
        $datas   = $graph_datas['data'];
        $ids     = $graph_datas['ids'];
        $label   = $graph_datas['label'] ?? "";
        $labels  = $graph_datas['labels'];
        $title   = $graph_datas['title'] ?? "";
        $comment = $graph_datas['comment'] ?? "";
        $url     = $graph_criterias['url'] ?? PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php";

        $json_criterias = json_encode($graph_criterias);
        $theme          = PluginMydashboardPreference::getPalette(Session::getLoginUserID());
        $graph          = "<script type='text/javascript'>

          var canvas$name = echarts.init(document.getElementById('$name'), '$theme');
          window.onresize = function() {
            canvas$name.resize();
          };
          var option;

            option = {
//               title: {
//                text: '$title',
//                textStyle: {
//                  fontSize: '14',
//                  },
//                subtext: '$comment'
//              },
              tooltip: {
                backgroundColor: 'rgba(255,255,255)',
                trigger: 'axis',
                axisPointer: {
                  type: 'shadow'
                }
              },
              legend: { show: true, bottom: 0, itemHeight: 10, itemWidth: 8 },
              toolbox: {
                show: true,
                feature: {
                  dataView: { show: true, readOnly: false },
                  magicType: { show: true, type: ['line', 'bar'] },
                  restore: { show: true },
                  saveAsImage: { show: true },
//                  myPDFExport: {
//                        show: true,
//                        title: 'PDF Export',
//                        icon: 'path://M432.45,595.444c0,2.177-4.661,6.82-11.305,6.82c-6.475,0-11.306-4.567-11.306-6.82s4.852-6.812,11.306-6.812C427.841,588.632,432.452,593.191,432.45,595.444L432.45,595.444z M421.155,589.876c-3.009,0-5.448,2.495-5.448,5.572s2.439,5.572,5.448,5.572c3.01,0,5.449-2.495,5.449-5.572C426.604,592.371,424.165,589.876,421.155,589.876L421.155,589.876z M421.146,591.891c-1.916,0-3.47,1.589-3.47,3.549c0,1.959,1.554,3.548,3.47,3.548s3.469-1.589,3.469-3.548C424.614,593.479,423.062,591.891,421.146,591.891L421.146,591.891zM421.146,591.891',
//                        onclick: function (){
////                            const btnExport = document.getElementById('export');
//
//                            self.addEventListener('click', async () => {
//                            try {
//  
//                            function loadImage(src) {
//                              return new Promise((resolve, reject) => {
//                                const img = new Image();
//                                img.onload = () => resolve(img);
//                                img.onerror = reject;
//                                img.src = src;
//                              });
//                            }
//                            function getChartImage(chart) {
//                              return loadImage(chart.getDataURL());
//                            }
//                            const img = await getChartImage(canvas$name);
//                            const dpr = canvas$name.getDevicePixelRatio();
//                        
//                            const doc = new jspdf.jsPDF({
//                              unit: 'px',
//                              orientation: 'l',
//                              hotfixes: ['px_scaling']
//                            });
//                        
////                            doc.addImage(img1.src, 'PNG', 0, 0, img1.width / dpr1, img1.height / dpr1);
//                            const canvas = await html2canvas(document.getElementById('$name'));
//                            const pageWidth = doc.internal.pageSize.getWidth();
//                            const pageHeight = doc.internal.pageSize.getHeight();
//                        
//                            const widthRatio = pageWidth / canvas.width;
//                            const heightRatio = pageHeight / canvas.height;
//    
//                            const canvasWidth = img.width / dpr;
//                            const canvasHeight = img.height / dpr;
//                            
//                            const marginX = (pageWidth - canvasWidth) / 2;
//                            const marginY = (pageHeight - canvasHeight) / 2;
//                        
//                            doc.addImage(img.src, 'PNG', marginX, marginY, canvasWidth, canvasHeight);
//                            
//                        
//                            await doc.save('charts.pdf', {
//                              returnPromise: true
//                            });
//                           } catch (e) {
//                                console.error('failed to export', e);
//                              }
//                        });
//                        }
//                    },
                }
              },
              calculable: true,
//              grid: { left: 16, right: 32, top: 32, bottom: 32, containLabel: true },
              xAxis: [
                {
                  type: 'category',
                  data: $labels,
                  axisPointer: {
                    type: 'shadow'
                  }
                }
              ],
              yAxis: [
                {
                  type: 'value'
                },{
                  type: 'value'
                }
                
              ],
              series: $datas
            };
            
            option && canvas$name.setOption(option);
            //canvas$name.resize();
            canvas$name.on('click', function(params) {
              // Print name in console
            //  console.log(params);
              if ($onclick) {
                 var idx = params.dataIndex;
                 var tab = $ids;
                 var selected_id = tab[idx];
                 $.ajax({
                    url: '$url',
                    type: 'POST',
                    data:
                    {
                        selected_id:selected_id,
                        params: $json_criterias
                      },
                    success:function(response) {
                            window.open(response);
                          }
                 });
               }
});

             
          </script>";

        return $graph;
    }


    /**
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    static function launchMultipleGraph($graph_datas = [], $graph_criterias = [])
    {

        $onclick = 0;
        if (count($graph_criterias) > 0)
        {
            $onclick = 1;
        }
        $name    = $graph_datas['name'];
        $datas   = $graph_datas['data'];
        $ids     = $graph_datas['ids'];
        $label   = $graph_datas['label'] ?? "";
        $labels  = $graph_datas['labels'];
        $title   = $graph_datas['title'] ?? "";
        $comment = $graph_datas['comment'] ?? "";
        $url     = $graph_criterias['url'] ?? PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php";

        $json_criterias = json_encode($graph_criterias);
        $theme          = PluginMydashboardPreference::getPalette(Session::getLoginUserID());
        $graph          = "<script type='text/javascript'>

          var canvas$name = echarts.init(document.getElementById('$name'), '$theme');
          window.onresize = function() {
            canvas$name.resize();
          };
          var option;

            option = {
//               title: {
//                text: '$title',
//                textStyle: {
//                  fontSize: '14',
//                  },
//                subtext: '$comment'
//              },
              tooltip: {
                backgroundColor: 'rgba(255,255,255)',
                trigger: 'axis',
                axisPointer: {
                  type: 'shadow'
                }
              },
              legend: { show: true, bottom: 0, itemHeight: 10, itemWidth: 8 },
              toolbox: {
                show: true,
                feature: {
                  dataView: { show: true, readOnly: false },
                  magicType: { show: true, type: ['line', 'bar'] },
                  restore: { show: true },
                  saveAsImage: { show: true },
//                  myPDFExport: {
//                        show: true,
//                        title: 'PDF Export',
//                        icon: 'path://M432.45,595.444c0,2.177-4.661,6.82-11.305,6.82c-6.475,0-11.306-4.567-11.306-6.82s4.852-6.812,11.306-6.812C427.841,588.632,432.452,593.191,432.45,595.444L432.45,595.444z M421.155,589.876c-3.009,0-5.448,2.495-5.448,5.572s2.439,5.572,5.448,5.572c3.01,0,5.449-2.495,5.449-5.572C426.604,592.371,424.165,589.876,421.155,589.876L421.155,589.876z M421.146,591.891c-1.916,0-3.47,1.589-3.47,3.549c0,1.959,1.554,3.548,3.47,3.548s3.469-1.589,3.469-3.548C424.614,593.479,423.062,591.891,421.146,591.891L421.146,591.891zM421.146,591.891',
//                        onclick: function (){
////                            const btnExport = document.getElementById('export');
//
//                            self.addEventListener('click', async () => {
//                            try {
//  
//                            function loadImage(src) {
//                              return new Promise((resolve, reject) => {
//                                const img = new Image();
//                                img.onload = () => resolve(img);
//                                img.onerror = reject;
//                                img.src = src;
//                              });
//                            }
//                            function getChartImage(chart) {
//                              return loadImage(chart.getDataURL());
//                            }
//                            const img = await getChartImage(canvas$name);
//                            const dpr = canvas$name.getDevicePixelRatio();
//                        
//                            const doc = new jspdf.jsPDF({
//                              unit: 'px',
//                              orientation: 'l',
//                              hotfixes: ['px_scaling']
//                            });
//                        
////                            doc.addImage(img1.src, 'PNG', 0, 0, img1.width / dpr1, img1.height / dpr1);
//                            const canvas = await html2canvas(document.getElementById('$name'));
//                            const pageWidth = doc.internal.pageSize.getWidth();
//                            const pageHeight = doc.internal.pageSize.getHeight();
//                        
//                            const widthRatio = pageWidth / canvas.width;
//                            const heightRatio = pageHeight / canvas.height;
//    
//                            const canvasWidth = img.width / dpr;
//                            const canvasHeight = img.height / dpr;
//                            
//                            const marginX = (pageWidth - canvasWidth) / 2;
//                            const marginY = (pageHeight - canvasHeight) / 2;
//                        
//                            doc.addImage(img.src, 'PNG', marginX, marginY, canvasWidth, canvasHeight);
//                            
//                        
//                            await doc.save('charts.pdf', {
//                              returnPromise: true
//                            });
//                           } catch (e) {
//                                console.error('failed to export', e);
//                              }
//                        });
//                        }
//                    },
                }
              },
              calculable: true,
//              grid: { left: 16, right: 32, top: 32, bottom: 32, containLabel: true },
              xAxis: [
                {
                  type: 'category',
                  data: $labels,
                  axisPointer: {
                    type: 'shadow'
                  }
                }
              ],
              yAxis: [
                {
                  type: 'value'
                },{
                  type: 'value'
                }
                
              ],
              series: $datas
            };
            
            option && canvas$name.setOption(option);
            //canvas$name.resize();
            canvas$name.on('click', function(params) {
              // Print name in console
            //  console.log(params);
              if ($onclick) {
                 var idx = params.dataIndex;
                 var serie = params.seriesIndex;

                 var tab = $ids;
                 var selected_id = tab[serie][idx];
                 $.ajax({
                    url: '$url',
                    type: 'POST',
                    data:
                    {
                        selected_id:selected_id,
                        params: $json_criterias
                      },
                    success:function(response) {
                            window.open(response);
                          }
                 });
               }
         });
          </script>";

        return $graph;
    }

    /**
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    static function launchHorizontalGraph($graph_datas = [], $graph_criterias = [])
    {

        $onclick = 0;
        if (count($graph_criterias) > 0)
        {
            $onclick = 1;
        }
        $name    = $graph_datas['name'];
        $datas   = $graph_datas['data'];
        $ids     = $graph_datas['ids'];
        $label   = $graph_datas['label'] ?? "";
        $labels  = $graph_datas['labels'];
        $title   = $graph_datas['title'] ?? "";
        $comment = $graph_datas['comment'] ?? "";
        $url     = $graph_criterias['url'] ?? PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php";

        $json_criterias = json_encode($graph_criterias);
        $theme          = PluginMydashboardPreference::getPalette(Session::getLoginUserID());
        $graph          = "<script type='text/javascript'>

              var canvas$name = echarts.init(document.getElementById('$name'), '$theme');
              window.onresize = function() {
                canvas$name.resize();
              };
              var option;

            option = {
//               title: {
//                text: '$title',
//                textStyle: {
//                  fontSize: '14',
//                  },
//                subtext: '$comment'
//              },
              tooltip: {
                backgroundColor: 'rgba(255,255,255)',
                trigger: 'axis',
                axisPointer: {
                  type: 'shadow'
                }
              },
              legend: { show: true, bottom: 0, itemHeight: 10, itemWidth: 8 },
              toolbox: {
                show: true,
                feature: {
                  dataView: { show: true, readOnly: false },
                  magicType: { show: true, type: ['line', 'bar'] },
                  restore: { show: true },
                  saveAsImage: { show: true },
//                  myPDFExport: {
//                        show: true,
//                        title: 'PDF Export',
//                        icon: 'path://M432.45,595.444c0,2.177-4.661,6.82-11.305,6.82c-6.475,0-11.306-4.567-11.306-6.82s4.852-6.812,11.306-6.812C427.841,588.632,432.452,593.191,432.45,595.444L432.45,595.444z M421.155,589.876c-3.009,0-5.448,2.495-5.448,5.572s2.439,5.572,5.448,5.572c3.01,0,5.449-2.495,5.449-5.572C426.604,592.371,424.165,589.876,421.155,589.876L421.155,589.876z M421.146,591.891c-1.916,0-3.47,1.589-3.47,3.549c0,1.959,1.554,3.548,3.47,3.548s3.469-1.589,3.469-3.548C424.614,593.479,423.062,591.891,421.146,591.891L421.146,591.891zM421.146,591.891',
//                        onclick: function (){
////                            const btnExport = document.getElementById('export');
//
//                            self.addEventListener('click', async () => {
//                            try {
//  
//                            function loadImage(src) {
//                              return new Promise((resolve, reject) => {
//                                const img = new Image();
//                                img.onload = () => resolve(img);
//                                img.onerror = reject;
//                                img.src = src;
//                              });
//                            }
//                            function getChartImage(chart) {
//                              return loadImage(chart.getDataURL());
//                            }
//                            const img = await getChartImage(canvas$name);
//                            const dpr = canvas$name.getDevicePixelRatio();
//                        
//                            const doc = new jspdf.jsPDF({
//                              unit: 'px',
//                              orientation: 'l',
//                              hotfixes: ['px_scaling']
//                            });
//                        
////                            doc.addImage(img1.src, 'PNG', 0, 0, img1.width / dpr1, img1.height / dpr1);
//                            const canvas = await html2canvas(document.getElementById('$name'));
//                            const pageWidth = doc.internal.pageSize.getWidth();
//                            const pageHeight = doc.internal.pageSize.getHeight();
//                        
//                            const widthRatio = pageWidth / canvas.width;
//                            const heightRatio = pageHeight / canvas.height;
//    
//                            const canvasWidth = img.width / dpr;
//                            const canvasHeight = img.height / dpr;
//                            
//                            const marginX = (pageWidth - canvasWidth) / 2;
//                            const marginY = (pageHeight - canvasHeight) / 2;
//                        
//                            doc.addImage(img.src, 'PNG', marginX, marginY, canvasWidth, canvasHeight);
//                            
//                        
//                            await doc.save('charts.pdf', {
//                              returnPromise: true
//                            });
//                           } catch (e) {
//                                console.error('failed to export', e);
//                              }
//                        });
//                        }
//                    },
                }
              },
              calculable: true,
              grid: { left: 16, right: 32, top: 32, bottom: 32, containLabel: true },
              //Specific
              yAxis: [
                {
                  type: 'category',
                  data: $labels
                }
              ],
              xAxis: [
                {
                  type: 'value'
                }
              ],
              series: $datas
            };
            
            option && canvas$name.setOption(option);
            //canvas$name.resize();
            canvas$name.on('click', function(params) {
              // Print name in console
            //  console.log(params);
              if ($onclick) {
                             var idx = params.dataIndex;
                             var tab = $ids;
                             var selected_id = tab[idx];
                             $.ajax({
                                url: '$url',
                                type: 'POST',
                                data:
                                {
                                    selected_id:selected_id,
                                    params: $json_criterias
                                  },
                                success:function(response) {
                                        window.open(response);
                                      }
                             });
                           }
            });
          </script>";

        return $graph;
    }
}
