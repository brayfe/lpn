/**
 * @file
 * Context admin behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Adds two functions to the Add Content form.
   *
   * On initial load, it strips any content items that are already present on
   * the page.
   *
   * Second, via AJAX, it retrieves HTML content to populate the "preview" area
   * when a content option is clicked.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.layoutPerNodeAddContent = {
    'attach': function(context) {
      // On load, strip content items already present in working layout.
      $('.layout__region').each(function() {
        var $this = $(this);
        $('[data-layout-editor-object]', $this).each(function() {
          var id = $(this).data('layout-editor-object');
          var link = $('[data-layout-editor-addable="' + id + '"]');
          var parent = link.remove();
          parent.remove();
        });
      });

      // On click, build and display preview content.
      $('[data-layout-editor-addable').unbind('click').bind('click', function(e) {
        e.preventDefault();
        var nid = drupalSettings.field_layout_editor.nid;
        var id = $(this).data('layout-editor-addable');
        var type = $(this).data('layout-editor-type');
        $.ajax({
          url: Drupal.url("admin/config/layout-per-node/get"),
          type: 'POST',
          data: {
            'nid': nid,
            'type': type,
            'id': id,
          },
          dataType: 'json',
          success: function (results) {
            $("[data-layout-editor-preview]").html(results.content);
          }
        });
      });

    }
  };

}(jQuery, Drupal));
