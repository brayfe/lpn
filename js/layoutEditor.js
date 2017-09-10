/**
 * @file
 * Context admin behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Provide the UI for drag-and-drop layout editing.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.layoutPerNodeLayoutEditor = {
    'attach': function(context) {

      // On Load action.
      // When in "Layout Editor" mode, make eligible fields draggable.
      // See addElements().
      $( window ).load( function(e) {
        var layoutEditor = getParameterByName('layout-editor');
        if (layoutEditor == 1) {
          addElements();
        }
      });

      // On "Place content on page", prepend selected content to region & close modal.
      $('[data-layout-editor-add').unbind('click').bind('click', function(e) {
        e.preventDefault();
        var nid = drupalSettings.field_layout_editor.nid;
        var content = $("[data-layout-editor-preview]").contents();
        var region = $(this).data('region');
        var regionCSS = region.replace('_', '-');
        $('article > div > div > [class*=region--'+ regionCSS + ']').prepend(content);
        // Close modal.
        if ( $( '.modal-backdrop' ).length ) {
          // Bootstrap method.
          $('#drupal-modal').modal('toggle');
        }
        else {
          // Non-Bootstrap method.
          var opt = {};
          $('.modal-backdrop').remove();
          var theDialog = $("#drupal-modal").dialog(opt);
          theDialog.dialog("close");
        }
        // Add draggability to newly-placed object.
        addElements();
      });

      // On save, write current field placement to database.
      $('a[id$="layout-editor-save"]').unbind('click').bind('click', function(e) {
        e.preventDefault();
        var layout = retrieveCurrentLayout();
        var nid = drupalSettings.field_layout_editor.nid;
        $.ajax({
          url: Drupal.url("admin/layout-per-node/set"),
          type: 'POST',
          data: {
            'id': nid,
            'layout': layout,
          },
          dataType: 'json',
          success: function (results) {
            window.location.href = "/node/" + nid;
          },
          error: function(data) {
            alert('There was a problem saving the layout as it is. At least one region must have content.');
          },
        });
      });

      // Helper function: find what content has been placed in what region.
      function retrieveCurrentLayout() {
        var pageLayout = new Object();
        var templateClasses = $('article > div.content > div').attr("class").split(' ');
        if (templateClasses) {
          var lastElement = templateClasses.length - 1;
          // Get the last element from the class list for that div.
          var template = templateClasses[lastElement];
          $('article > div > div > [class*=region--]').each(function() {
            var $this = $(this);
            var region = getRegionName($this);
            pageLayout[region] = {};
            var $this = $(this);
            $('[data-layout-editor-object]', $this).each(function() {
              var id = $(this).data('layout-editor-object');
              var type = $(this).data('layout-editor-type');
              pageLayout[region][id] = type;
            });
          });
          var final = {};
          final[template] = pageLayout;
          return final;
        }
      }

      // Helper function: parse URL to retrieve region & node.
      function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
        if (!results) {
          return null;
        }
        if (!results[2]) {
          return '';
        }
        return decodeURIComponent(results[2].replace(/\+/g, " "));
      }

      function getRegionName(item) {
        var className = item.attr('class').match(/\S+region--\S+/);
        if (className) {
          var split = className[0].split('region--');
          var region = split[1];
          return region;
        }
      }

      // Make eligible content draggable.
      function addElements() {
        // Loop through all Drupal "regions" in the main-wrapper.
        $('article > div > div > [class*=region--]').each(function() {
          // Loop through all elements in node-level "region"
          // If they are in a page builder region & are not core/system
          // add sortable & droppable.

          var region = getRegionName($(this));

          var $this = $(this);
          $('[data-layout-editor-object]', $this).each(function() {
            // Important: identifies what region the object is presently in.
            $(this).attr('data-layout-editor-region', region );

            // Less important: add drag & remove handles.
            if (!$(this).hasClass('ui-sortable-handle')) {
              $(this).addClass('ui-sortable-handle');
              $(this).prepend($('<a class="context-block-handle button button--small"></a>'));
              $(this).prepend($('<a class="context-block-remove button button--small"></a>').click(function() {
                $(this).parent ('[data-layout-editor-object]').eq(0).fadeOut('medium', function() {
                  $(this).remove();
                });
                return false;
              }));
            }
          });

          // Make each region sortable. Limit sortability to items which have the
          // data-layout-editor-object attribute
          $(this).sortable({items: "> [data-layout-editor-object]"});

          // Make each region droppable.
          // Note: the "accept" parameter limits droppability to layout editor
          // object with a *different* data-layout-editor-region attribute.
          $(this).droppable({
              accept: "[data-layout-editor-type]:not([data-layout-editor-region=" + region + "])",
              tolerance: 'pointer',
              drop: function (e, ui) {
                  $(this).removeClass('layout-editor-highlighted');
                  var dropped = ui.draggable;
                  var droppedOn = $(this);
                  $(this).prepend(dropped.clone().removeAttr('style').removeAttr('data-layout-editor-region').attr('data-layout-editor-region', region ));
                  dropped.remove();
              },
              over: function (event, ui) {
                $(this).addClass('layout-editor-highlighted');
              },
              out: function (event, ui) {
                $(this).removeClass('layout-editor-highlighted');
              },
          });
        });

      }
    }

  };


}(jQuery, Drupal));
