/**
 * Admin Logs JS
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    $(document.body).on(
      "click",
      "table.swpl-logs .row-actions .view a",
      function () {
        var id = $(this).closest("tr").attr("id").split("swpl-log-")[1];

        $(document.body).trigger("swpl-modal-init");
        $(document.body).trigger(
          "swpl-modal-body",
          $("#swpl-log-modal-" + id).html()
        );
        $(document.body).trigger("swpl-modal-show");

        return false;
      }
    );

    $(document.body).on(
      "click",
      "table.swpl-logs .row-actions .delete a",
      function () {
        var $row = $(this).closest("tr");
        var data = {
          action: "swpl_delete_log",
          id: $row.attr("id").split("swpl-log-")[1],
        };

        $.post(ajaxurl, data)
          .done(function (r) {
            if (r.success) {
              $row.remove();

              var oldDisplayingNum = $(".tablenav-pages .displaying-num")
                .html()
                .replace(" items", "");
              var newDisplayingNum = parseInt(oldDisplayingNum, 10) - 1;
              $(".tablenav-pages .displaying-num").html(
                newDisplayingNum + " items"
              );
            } else {
              alert(r.data.message);
            }
          })
          .fail(function (xhr) {
            var message = "Unable to delete log";
            if (xhr.status === 400) {
              message = "No Response From Ajax Handler";
            } else if (xhr.status === 500) {
              message =
                "Server side error occured, please check your server error log.";
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
              message = xhr.responseJSON.message;
            }

            alert(message);
          });

        return false;
      }
    );
  });
})(jQuery);
