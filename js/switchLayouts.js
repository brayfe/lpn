/**
 * @file
 * Context admin behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Remove any addable items that are already in the layout.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.layoutPerNodeSwitchLayouts = {
    'attach': function(context) {

      // On "Place content on page", append selected content to region & close modal.
      $('[data-layout-editor-switch').unbind('click').bind('click', function(e) {
        e.preventDefault();
        var nid = $(this).data('nid');
        var layout = $("[id^=edit-layout]").val();
        // Close modal.
        var opt = {};
        var theDialog = $("#drupal-modal").dialog(opt);
        theDialog.dialog("close");

        // Bootstrap theme alternative close method.
        //$('#drupal-modal').modal('toggle');
        //
        // Redirect back to layout editor with user-selected layout.
        window.location.href = "/node/" + nid + "?layout-editor=1&layout=" + layout;
      });

    }
  };

}(jQuery, Drupal));
