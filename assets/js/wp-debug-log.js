/**
 * Admin Bar Menu Js
 */

(function ($) {
  "use strict";

  $(document.body).on("click", "#swpl-wp-debug-log-delete-btn", function () {
    $(document.body).trigger("swpl-modal-hide");
    $.post(ajaxurl, { action: "swpl_delete_wp_debug_log" });
    return false;
  });

  $(document.body).on(
    "click",
    "#wp-admin-bar-shazzad-wp-logs-debug-log a",
    function () {
      $(document.body).trigger("swpl-modal-init");
      $(document.body).trigger("swpl-modal-loading", "Loading...");
      $(document.body).trigger("swpl-modal-show");

      $.post(ajaxurl, { action: "swpl_wp_debug_log" })
        .done(function (r) {
          if (r.data.modal) {
            for (var key in r.data.modal) {
              $(document.body).trigger("swpl-modal-" + key, r.data.modal[key]);
            }
          } else {
            $(document.body).trigger("swpl-modal-body", r.data.message);
          }
        })
        .fail(function (xhr) {
          var header = "Server Error";
          var content = "Unable to load logs";

          if (xhr.status === 400) {
            content = "No Response From Ajax Handler";
          } else if (xhr.status === 500) {
            header = "WordPress in Trouble";
            content =
              "Server side error occured, please check your server error log.";
          } else if (xhr.responseJSON && xhr.responseJSON.message) {
            content = xhr.responseJSON.message;
          }

          $(document.body).trigger("swpl-modal-header", header);
          $(document.body).trigger("swpl-modal-content", content);
        })
        .always(function () {
          $(document.body).trigger("swpl-modal-loading", false);
        });

      return false;
    }
  );
})(jQuery);
