/** 
 *  Replace central by dashboard
 */
(function ($) {
    $.fn.mydashboard_replace_central = function () {

        init();
        var object = this;

        // Start the plugin
        function init() {
            $(document).ready(function () {
                var path = 'plugins/mydashboard/';
                var url = window.location.href.replace(/front\/.*/, path);
                if (window.location.href.indexOf('plugins') > 0) {
                    url = window.location.href.replace(/plugins\/.*/, path);
                }

                $("a[href*='/front/central.php'], a[href$='/front/helpdesk.public.php']").attr('href', url+'front/menu.php');
            });
        }
        
        return this;
    }
}(jQuery));

$(document).mydashboard_replace_central();