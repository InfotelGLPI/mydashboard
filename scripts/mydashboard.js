/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2006-2014 by the mydashboard Development Team.

 https://forge.indepnet.net/projects/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

onMaximize = new Array();
onMinimize = new Array();
onInit = new Array();

//this object contains all methods to manage the dashboard
var mydashboard = {
    dashboard: Array(),
    dashboardJson: Array(),
    widgetsOptions: Array(),
    rootDoc: "",

    language: {
        dashboardNotSaved: "Dashboard not saved",
        dashboardSaved: "Dashboard saved",
        dashboardsliderClose: "Close",
        dashboardsliderOpen: "Dashboard"
    },

    setOriginalDashboard: function (dash) {
        this.dashboard = dash;
    },
    setLanguageData: function (languageData) {
        this.language = languageData;
    },
    setRootDoc: function (root) {
        this.rootDoc = root;
    },

    log: function (msg) {
        var d = new Date();
        var n = d.toLocaleString()
        $('.plugin_mydashboard_header_info_logbox').append("<div class='plugin_mydashboard_log_item'>" + n + " " + msg + "</div>");
    },
    //Save an adding into dashboard    
    saveAdding: function (id, interface, widgetId) {
        this.dashboard.push(widgetId);
        //    plugin_mydashboard_saveConfig({ id : id , widgetId : widgetId, order : plugin_mydashboard_dashboard.indexOf(widgetId)+1});
        this.saveConfig({id: id, interface: interface, widgetId: widgetId, order: this.dashboard.indexOf(widgetId)});
    },
    //Save a removal into dashboard    
    saveRemoval: function (id, interface, widgetId) {
        var i = this.dashboard.indexOf(widgetId);
        this.dashboard.splice(i, 1);

        this.saveConfig({id: id, interface: interface, widgetId: widgetId});
    },
    //Save an order change into dashboard    
    saveOrder: function (id, interface, sortedList) {
        sortedList.forEach(function (entry) {
            var i = mydashboard.dashboard.indexOf(entry);
            mydashboard.dashboard.splice(i, 1);
            mydashboard.dashboard.push(entry);
        });
        this.saveConfig({id: id, interface: interface, sortedList: this.dashboard});
    },
    //Save a dashboard modif    
    saveConfig: function (data) {
        this.infoWait();
        var request = $.ajax({
            url: this.rootDoc + "/plugins/mydashboard/ajax/saveConfig.php",
            type: "POST",
            data: data
        });
        this.handleResponse(request);
    },
    //Handles the respons of saveConfig
    handleResponse: function (request) {
        request.done(function (msg) {
            if (typeof msg.status !== "undefined") {
                $('.plugin_mydashboard_header_info').html(msg.status);
                mydashboard.log(msg.status + " " + msg.message);
            } else {
                mydashboard.log(msg);
            }
            setTimeout(function () {
                $('.plugin_mydashboard_header_info').html(mydashboard.oldmsg);
            }, 500);

            if (mydashboard.dashboard.length === 0) {
                window.location.reload();
            }
        });
        request.fail(function () {
            $('.plugin_mydashboard_header_info').html(mydashboard.language.dashboardNotSaved);
            //console.log("Erreur :"+"Dashboard non sauvegardé");
        });
    },

    //Displays a loadng image in the header info div
    infoWait: function () {
        if (typeof this.oldmsg === "undefined") {
            this.oldmsg = $('.plugin_mydashboard_header_info').html();
        }
        $('.plugin_mydashboard_header_info').html("<img src='" + this.rootDoc + "/plugins/mydashboard/pics/loading.gif' alt='loading'/>");
    },
    //Refresh all widgets that can be refreshed
    refreshAll: function () {
        this.log(this.language.refreshAll);
        $('.sDashboard-refresh-icon').trigger('click');
    },
    //Launch the automatic refresh with a specified delay
    automaticRefreshAll: function (delay) {
        setInterval(function () {
            mydashboard.refreshAll();
        }, delay);
    },

    //This function stores options for a specific widget 
    setWidgetOption: function (widgetId, widgetOption) {
        this.widgetsOptions[widgetId] = widgetOption;
    },

    //This is this function that the helper form header call when onchange if triggered on the form fomrId
    //AND refresh this widget with form values
    updateOption: function (widgetId, formId) {
        var widgetOptions = $('#' + formId).serializeArray();
        var widgetOptionsObject = {};
        $.each(widgetOptions,
            function (i, v) {
                widgetOptionsObject[v.name] = v.value;
            });
        this.setWidgetOption(widgetId, widgetOptionsObject);
        $('#' + widgetId).find('.sDashboard-refresh-icon').trigger("click");
    },

    //Get a json object ready to be added to the mydashboard
    formatWidgetData: function (dashboardId, widgetTitle, widgetId, widgetType, widgetData, enableRefresh, classname, view) {
        var widget =
        {
            widgetTitle: widgetTitle,
            widgetId: widgetId,
            widgetType: widgetType,
            container: dashboardId,
            widgetContent: widgetData,
            enableRefresh: enableRefresh,
            refreshCallBack: function () {
                return mydashboard.getWidgetData(dashboardId, classname, widgetId, view);
            }
        }
        return widget;
    },
    //Get a widget (basically by clicking a menu item link)
    getWidget: function (dashboardId, classname, widgetindex, view) {
        //if(plugin_mydashboard_dashboard.indexOf(widgetindex)>=0) return true;
        var dataForAjax = this.prepareDataForAjax(classname, widgetindex, view);

        $.ajax({
            url: this.rootDoc + "/plugins/mydashboard/ajax/getWidget.php",
            type: "POST",
            async: false,
            data: dataForAjax
        }).done(function (data) {
            mydashboard.handleData(dashboardId, data, widgetindex, true, view);
        });
    },
    //Get widget datas (basically when refreshing datas)
    getWidgetData: function (dashboardId, classname, widgetindex, view) {
        var WidgetData = {};
        var dataForAjax = this.prepareDataForAjax(classname, widgetindex, view);
        //the SYNCHRONEOUS ajax call to get new widget data (in the right order)
        $.ajax({
            url: this.rootDoc + "/plugins/mydashboard/ajax/getWidgetData.php",
            type: "POST",
            async: false,
            data: dataForAjax
        }).done(function (data) {
            WidgetData = mydashboard.handleData(dashboardId, data, widgetindex, false);
        });
        return WidgetData;
    },
    //Prepare data send when getting widget data
    prepareDataForAjax: function (classname, widgetindex, view) {
        var dataForAjax;
        //If it's a glpi core widget
        if (typeof view != "undefined") {
            dataForAjax = {
                dashboard_plugin_classname: classname,
                dashboard_plugin_widget_index: widgetindex,
                dashboard_plugin_widget_view: view
            };
        } else {  //If if a tiers plugin widget
            dataForAjax = {dashboard_plugin_classname: classname, dashboard_plugin_widget_index: widgetindex};
        }
        //If this widget has options (a form that parameterize the widget)
        if (typeof this.widgetsOptions[widgetindex] != "undefined") {
            $.extend(dataForAjax, {'options': this.widgetsOptions[widgetindex]});
        }
        return dataForAjax;
    },
    //Handle data recieving from getWidget and getWidgetData,
    //in the first case it adds the widget,
    //in the seconds it gives the widget datas to be refreshed
    handleData: function (dashboardId, data, widgetIndex, is_widget, view) {
        var WidgetData;
        try {
            var json = $.parseJSON(data);
            //        console.log("parse");
        } catch (e) {
            try {
                var json = eval('(' + data + ')');
                //            console.log("eval");
            } catch (e) {
                console.error('Error when getting data for "' + widgetIndex + '"');
                console.error('Error message : ' + e);
                if (typeof data != "undefined") {
                    if ((data.length) > 0) {
                        console.error('Data recieved:');
                        console.error(data);
                    } else {
                        console.error('Error : Empty data');
                        this.log(this.language.noDataRecieved + " \"" + widgetIndex + "\"");
                    }
                }
                return null;
            }
        }

        if (is_widget) {
            if (typeof view != "undefined") {
                json.widgetTitle += view;
            }
            this.log(this.language.dataRecieved + " \"" + json.widgetTitle + "\"");
            $('#' + dashboardId).sDashboard('addWidget', json);
            this.log(this.language.widgetAddedOnDashboard);
        } else {
            //We get the new data
            //We can't use parseJSON because json.data may not be a json object (it can be simple html string)
            //        WidgetData =  eval('('+json.data+')');
            WidgetData = json.data;
        }
        if (json.widgetType != 'html') {
            //Removing former html
            this.removeWidgetHtmlContent(widgetIndex);
            //Adding the new one
            this.addWidgetHtmlContent(widgetIndex, json.html);
        }

        try {
            var scriptsFunction = new Function(json.scripts);
            setTimeout(function () {
                scriptsFunction();
            }, 1);
        } catch (e) {
            console.error('Error when executing scripts of the widgets');
            console.error('scripts recieved :' + json.scripts);
        }
        this.changeTarget(widgetIndex, '_blank');
        return WidgetData;
    },

    //This function adds a widget to a specific dashboard (dashboardId)
    //We need its title, id, type, data, to know if it's refreshable or not, the container classname and if necessary the view number
    addWidget: function (dashboardId, widgetTitle, widgetId, widgetType, widgetData, enableRefresh, classname, view) {
        var isHTML = false;
        var widget = this.formatWidgetData(dashboardId, widgetTitle, widgetId, widgetType, widgetData, enableRefresh, classname, view);
        $('#' + dashboardId).sDashboard('addWidget', widget);

        this.log(" " + this.language.widgetAddedOnDashboard);
        this.changeTarget(widgetId, '_blank');
        this.dashboardJson.push(widget);
    },
    //Add html content to a widget
    addWidgetHtmlContent: function (container, content) {
        var location = $('#' + container).children(".sDashboardWidget").children(".sDashboardWidgetContent");
        if (location.children(".plugin_mydashboard_html_content").length === 0) {
            var div = "<div class='plugin_mydashboard_html_content' id='" + container + "htmlcontent'>";
            if (typeof content !== "undefined") div += content;
            div += "</div>";
            location.append(div);
        } else {
            if (typeof content !== "undefined") location.children(".plugin_mydashboard_html_content").html(content);
        }
    },
    //Remove html content
    removeWidgetHtmlContent: function (container) {
        var location = $('#' + container).children(".sDashboardWidget").children(".sDashboardWidgetContent");
        location.children(".plugin_mydashboard_html_content").html("");
    },
    //Change the target of links, used to open in new tab every links on the widgets
    changeTarget: function (widgetindex, newtarget) {
        $('#' + widgetindex).find('a').attr('target', newtarget);
    }
};

$(document).ready(function () {
    //===================Start:Showing Menu=====================================
    //Showing the menu on click
    $('.plugin_mydashboard_add_button').on('click', function (e) {
        $('.plugin_mydashboard_menuDashboard').css('top', $(this).offset().top + 25);
        $('.plugin_mydashboard_menuDashboard').css('left', $(this).offset().left - 40);
        $('.plugin_mydashboard_menuDashboard').width(400);
        $('.plugin_mydashboard_menuDashboard').zIndex(3);
        $('.plugin_mydashboard_menuDashboard').show();
    });
    //Hiding the menu when clicking outside the menu
    var menu = false;
    $('.plugin_mydashboard_add_button,.plugin_mydashboard_menuDashboard').click(function (e) {
        menu = true;
    });
    $(document).click(function () {
        if (!menu) {
            $('.plugin_mydashboard_menuDashboard').hide();
        } else {
            menu = false
        }
    });

    //===================Stop:Showing Menu=====================================
    //===================Start:AccordionEffect=================================
    //Now the accordion effect w/o jQuery Accordion (wasn't really customizable, and css from other plugin can override dashboard one)
    //at the beginning every lists of widgets are folded
    $('.plugin_mydashboard_menuDashboardListContainer,.plugin_mydashboard_menuDashboardList2').slideUp('fast');

    //binding when user wants to unfold/fold a list of widget
    $('.plugin_mydashboard_menuDashboardListTitle1').click(function () {
        var isOpened = $(this).hasClass('plugin_mydashboard_menuDashboardListTitle1Opened');
        $('.plugin_mydashboard_menuDashboardListTitle1').removeClass("plugin_mydashboard_menuDashboardListTitle1Opened");
        if (!isOpened) $(this).addClass("plugin_mydashboard_menuDashboardListTitle1Opened");
        $('.plugin_mydashboard_menuDashboardListTitle1').not(this).next("div").slideUp('fast');
        $(this).next("div").slideToggle('fast');
    });

    //This part is about lists of lists of widgets (when there are much widgets)
    //Every list of list are closed at the beginning
//   $('.plugin_mydashboard_menuDashboardList2').slideUp('fast');
    //Binding when user want to unfold/fold a list of widget
    $('.plugin_mydashboard_menuDashboardListTitle2').click(function () {
        $('.plugin_mydashboard_menuDashboardListTitle2').removeClass("plugin_mydashboard_menuDashboardListTitle1Opened");
        $(this).addClass("plugin_mydashboard_menuDashboardListTitle1Opened");
        $('.plugin_mydashboard_menuDashboardListTitle2').not(this).next("ul").slideUp('fast');
        $(this).next("ul").slideToggle('fast');
    });
    //===================Stop:AccordionEffect=================================
    //===================Start:ListItem click=================================
    //handling click on all listitem (button to add a specific widget), -> getWidget with data stored in a custom attribute (html5 prefixed as data-*)
    $('.plugin_mydashboard_menuDashboardListItem').click(function () {

        var dashboardId = $(this).parents('.plugin_mydashboard_menuDashboard').attr('data-dashboardid');
        var widgetId = $(this).attr('data-widgetid');
        var classname = $(this).attr('data-classname');
        var attrview = $(this).attr('data-view');
        var view = "";
        if (typeof attrview != "undefined") view = "<span class='plugin_mydashboard_discret'>&nbsp;-&nbsp;" + attrview + "</span>";
        mydashboard.getWidget(dashboardId, classname, widgetId, view);
    });
    //===================Start:Fullscreen mode=================================
    //handling click on the 'fullscreen' button                                                             
    $('.plugin_mydashboard_header_fullscreen').click(
        function () {
            $('#plugin_mydashboard_container').toggleFullScreen();
            var overlay = $('.sDashboard-overlay');
            $('#plugin_mydashboard_container').append(overlay);
            $('#plugin_mydashboard_container').toggleClass('plugin_mydashboard_fullscreen_view');
        });
    //===================Stop:Fullscreen mode=================================
    //===================Start:Log box=================================
    //Inner logs showing on click on the 'i', (added, removed ....)
    $('.plugin_mydashboard_header_info_logbox').slideUp();
    $('.plugin_mydashboard_header_info_img').click(
        function () {
            $('.plugin_mydashboard_header_info_logbox').slideToggle('fast');
        });
    //===================Stop:Log box=================================

    //Options for Datatables, colors of lines
    $.fn.dataTableExt.oStdClasses.sStripeOdd = 'tab_bg_2';
    $.fn.dataTableExt.oStdClasses.sStripeEven = 'tab_bg_2';
});