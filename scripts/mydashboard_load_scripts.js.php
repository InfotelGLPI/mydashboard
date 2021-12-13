<?php
use Glpi\Event;
include('../../../inc/includes.php');
header('Content-Type: text/javascript');

?>

var root_mydashboard_doc = "<?php echo PLUGIN_MYDASHBOARD_WEBDIR; ?>";

(function ($) {
   $.fn.mydashboard_load_scripts = function () {

      // Start the plugin
      function init() {
         //            $(document).ready(function () {
         // Send data
         $.ajax({
            url: root_mydashboard_doc + '/ajax/loadscripts.php',
            type: 'POST',
            dataType: 'html',
            data: 'action=load',
            success: function (response, opts) {
               var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
               while (scripts = scriptsFinder.exec(response)) {
                  eval(scripts[1]);
               }
            }
         });
      }

      init();

      return this;
   };
}(jQuery));

$(document).mydashboard_load_scripts();
