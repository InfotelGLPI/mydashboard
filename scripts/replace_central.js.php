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

                var hrefs = $("a[href$='/front/central.php']");
                hrefs.each(function (href, value) {
                    $("a[href='" + value['pathname'] + "']").attr('href', root_mydasboard_doc + '/front/menu.php');
                });
            });
        }

        return this;
    }
}(jQuery));

$(document).mydashboard_replace_central();

