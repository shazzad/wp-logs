/**
 * Admin JS
 */

(function ($) {
  "use strict";

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
          '<a id="swpl-modal-close-btn" class="dashicons dashicons-no-alt" href="#"></a>' +
          '<div id="swpl-modal-body">' +
          '<div class="swpl-modal-header"></div>' +
          '<div class="swpl-modal-content"></div>' +
          '<div class="swpl-modal-footer"></div>' +
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

  ["swpl-modal-header", "swpl-modal-content", "swpl-modal-footer"].forEach(
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

  $(document.body).on("click", "#swpl-modal-close-btn", function () {
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
})(jQuery);
