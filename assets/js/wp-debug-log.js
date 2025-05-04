/**
 * Admin Bar Menu Js
 */

(function ($) {
  ("use strict");

  /* confirm action */
  $(document.body).on("click", ".swpl_ca", function () {
    var d = $(this).data("confirm") || "Are you sure you want to do this ?";
    if (!confirm(d)) {
      return false;
    }
  });

  $(document.body).on("swpl-modal-init", function () {
    if ($("#swpl-modal").length === 0) {
      $("body").append(
        '<div id="swpl-modal">' +
          '<div id="swpl-modal-inner">' +
          '<a id="swpl__modal--close-btn-btn" class="dashicons dashicons-no-alt" href="#"></a>' +
          '<div id="swpl-modal-body">' +
          '<div class="swpl__modal--header"></div>' +
          '<div class="swpl-modal-content"></div>' +
          '<div class="swpl__modal--footer"></div>' +
          "</div>" +
          '<div id="swpl-modal-loading" style="display:none;"></div>' +
          "</div>" +
          "</div>"
      );
    }
  });

  $(document.body).on("swpl-modal-loading", function (e, html) {
    if (false !== html) {
      $("#swpl-modal-loading").html(html).show();
    } else {
      $("#swpl-modal-loading").empty().hide();
    }
  });

  $(document.body).on("swpl-modal-body", function (e, html) {
    $("#swpl-modal-body").html(html);
  });

  ["swpl__modal--header", "swpl-modal-content", "swpl__modal--footer"].forEach(
    function (target) {
      $(document.body).on(target, function (e, html) {
        if (html) {
          $("#swpl-modal-body ." + target)
            .html(html)
            .show();
        } else {
          $("#swpl-modal-body ." + target)
            .empty()
            .hide();
        }
      });
    }
  );

  $(document.body).on("swpl-modal-show", function (e, show) {
    $("html,body").addClass("swpl-modal-active");
  });

  $(document.body).on("swpl-modal-hide", function () {
    $("html,body").removeClass("swpl-modal-active");
    $("#swpl-modal-body > div").empty().hide();
  });

  $(document.body).on("click", "#swpl__modal--close-btn-btn", function () {
    $(document.body).trigger("swpl-modal-hide");
    return false;
  });

  // Cancel update button event
  $(document.body).on("click", "#swpl-modal", function (e) {
    e.preventDefault();
    if (0 === $(e.target).closest("#swpl-modal-inner").length) {
      $(document.body).trigger("swpl-modal-hide");
    }

    return false;
  });

  $(document).ready(function () {
    $(document.body).trigger("swpl-modal-init");
  });

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

          $(document.body).trigger("swpl__modal--header", header);
          $(document.body).trigger("swpl-modal-content", content);
        })
        .always(function () {
          $(document.body).trigger("swpl-modal-loading", false);
        });

      return false;
    }
  );
})(jQuery);
