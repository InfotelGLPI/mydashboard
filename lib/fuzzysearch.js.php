<?php
use Glpi\Event;
include('../../../inc/includes.php');
header('Content-Type: text/javascript');

?>
var root_md_doc = "<?php echo PLUGIN_MYDASHBOARD_WEBDIR; ?>";

$(function() {
   var list = [];

   // prepare options for fuzzy lib
   var fuzzy_options = {
      pre: "<b>",
      post: "</b>",
      extract: function(el) {
          return el.title;
      }
   };

   // when the shortcut for fuzzy is called
   // $(document).on('keyup', null, 'alt+ctrl+g', function() {
   //    trigger_fuzzy();
   // });

   // when a key is pressed in fuzzy input, launch match
   $(document).on('click', ".md-home-trigger-fuzzy", function(key) {
      trigger_homesearch_fuzzy();
   });

   var fuzzy_started = false;
    var trigger_homesearch_fuzzy = function() {
        // remove old fuzzy modal
        removeFuzzy();

        // retrieve html of fuzzy input
        $.get(root_md_doc+'/ajax/fuzzysearch.php', {
            'action': 'getHtml',
        }, function(html) {
            // add modal to body and show it
            $('#searchwidgets').append(html);
            //$('#md-fuzzysearch').modal('show');

            // retrieve current menu data
            $.getJSON(root_md_doc+'/ajax/fuzzysearch.php', {
                'action': 'getList',
            }, function(data) {
                list = data;

                // start fuzzy after some time
                setTimeout(function() {
                    if ($("#md-fuzzysearch .results li").length == 0) {
                        startFuzzy();
                    }
                }, 100);
            });

            // focus input element
            $("#md-fuzzysearch input").trigger("focus");

            // don't bind key events twice
            if (fuzzy_started) {
                return;
            }
            fuzzy_started = true;

            // general key matches
            $(document).on('keyup', function(key) {
                switch (key.key) {
                    case "Escape":
                        removeFuzzy();
                        break;

                    case "ArrowUp":
                        selectPrev();
                        break;

                    case "ArrowDown":
                        selectNext();
                        break;

                    case "Enter":
                        // find url, if one selected, go for it, else try to find first element
                        var url = $("#md-fuzzysearch .results .active a").attr('href');
                        if (url == undefined) {
                            url = $("#md-fuzzysearch .results li:first a").attr('href');
                        }
                        if (url != undefined) {
                            document.location = url;
                        }
                        break;
                }
            });

            // when a key is pressed in fuzzy input, launch match
            $(document).on('keyup', "#md-fuzzysearch input", function(key) {
                if (key.key != "Escape"
                    && key.key != "ArrowUp"
                    && key.key != "ArrowDown"
                    && key.key != "Enter") {
                    startFuzzy();
                }
            });
        });
    };

   var startFuzzy = function() {

      // retrieve input
      var input_text = $("#md-fuzzysearch input").val();
       input_text = "\'"+input_text;

      //clean old results
      $("#md-fuzzysearch .results").empty();

      // launch fuzzy search on this list
      //var results = fuzzy.filter(input_text, list, fuzzy_options);
      const options = {
         // isCaseSensitive: false,
         // includeScore: false,
         // shouldSort: true,
         // includeMatches: false,
         // findAllMatches: false,
          minMatchCharLength: 3,
         // location: 0,
         // threshold: 0.6,
         // distance: 100,
         includeScore: false,
         ignoreLocation: true,
         useExtendedSearch: true,
         // ignoreFieldNorm: false,
         // fieldNormWeight: 1,
         keys: [
            "title",
         ]
      };
      //console.log(list);
      const fuse = new Fuse(list, options);

      var results = fuse.search(input_text);
       var target = '_blank';
//
//      const searchWrapper = query => {
//         if (!query) return fuse.getIndex().records.map(({ $: item, i: idx }) => ({ idx, item }));
//         results =  fuse.search(query);
//      };
//// Change the pattern
//      console.log(results);



      // append new results
      results.map(function(el) {
         //console.log(el);
          var finaltitle = el.item.title;
         $("#md-fuzzysearch .results")
            .append("<span class='plugin_mydashboard_menuDashboardListItem' data-widgetid='"+el.item.widgetid+"'><i class='"+el.item.icon+"'></i> "+finaltitle+"</span>");
      });
       $('.plugin_mydashboard_menuDashboardListItem').click(function () {
           var widgetId = $(this).attr('data-widgetid');
           addNewWidget(widgetId);
       });

      selectFirst();
   };

   /**
    * Clean generated Html
    */
   var removeFuzzy = function() {
      $("#md-fuzzysearch").remove();
   };

   /**
    * Select the first element in the results list
    */
   var selectFirst = function() {
      $("#md-fuzzysearch .results li:first()").addClass('active');
      scrollToSelected();
   };

   /**
    * Select the last element in the results list
    */
   var selectLast = function() {
      $("#md-fuzzysearch .results li:last()").addClass('active');
      scrollToSelected();
   };

   /**
    * Select the next element in the results list.
    * If no selected, select the first.
    */
   var selectNext = function() {
      if ($("#md-fuzzysearch .results .active").length == 0) {
         selectFirst();
      } else {
         $("#md-fuzzysearch .results .active:not(:last-child)")
            .removeClass('active')
            .next()
            .addClass("active");
         scrollToSelected();
      }
   };

   /**
    * Select the previous element in the results list.
    * If no selected, select the last.
    */
   var selectPrev = function() {
      if ($("#md-fuzzysearch .results .active").length == 0) {
         selectLast();
      } else {
         $("#md-fuzzysearch .results .active:not(:first-child)")
            .removeClass('active')
            .prev()
            .addClass("active");
         scrollToSelected();
      }
   };

   /**
    * Force scroll to the selected element in the results list
    */
   var scrollToSelected = function() {
      var results = $("#md-fuzzysearch .results");
      var selected = results.find('.active');

      if (selected.length) {
         results.scrollTop(results.scrollTop() + selected.position().top - results.height()/2 + selected.height()/2 - 25);
      }
   };
});
