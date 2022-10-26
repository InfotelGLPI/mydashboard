<?php
use Glpi\Event;
include('../../../inc/includes.php');
header('Content-Type: text/javascript');
?>
var root_mydasboard_doc = "<?php echo PLUGIN_MYDASHBOARD_WEBDIR; ?>";
/**
 *  Replace central by dashboard
 */
(function ($) {
    $.fn.mydashboard_replace_central = function () {

        init();
        var object = this;
        var root_mydashboard_doc = root_mydasboard_doc;
        var check_path = root_mydashboard_doc.split('/');
       // console.log(check_path[2]);

        // Start the plugin
        function init() {
            $(document).ready(function () {
                if(check_path[2] == 'plugins') {
                    var path = 'plugins/mydashboard/';
                    var url = window.location.href.replace(/front\/.*/, path);
                    if (window.location.href.indexOf('plugins') > 0) {
                        url = window.location.href.replace(/plugins\/.*/, path);
                    }
                }else {
                    var path = 'marketplace/mydashboard/';
                    var url = window.location.href.replace(/front\/.*/, path);
                    if (window.location.href.indexOf('marketplace') > 0) {
                        url = window.location.href.replace(/marketplace\/.*/, path);
                    }
                }

                var hrefs = $("a[href$='/front/central.php']");//, a[href$='/front/helpdesk.public.php']
                hrefs.each(function (href, value) {
                    if (value['pathname'].indexOf('plugins') < 0) {
                        $("a[href='" + value['pathname'] + "']").attr('href', url + 'front/menu.php');
                    }
                });
            });
        }

        return this;
    }
}(jQuery));

$(document).mydashboard_replace_central();

