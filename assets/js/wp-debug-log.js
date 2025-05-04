/**
 * Admin Bar Menu Js
 */

(function ($, config) {
  ("use strict");

  $(document.body).on("swpl__modal__init", function () {
    if ($("#swpl-modal").length === 0) {
      $("body").append(
        '<div id="swpl-modal">' +
          '<div id="swpl__modal--inner">' +
          '<a id="swpl__modal--close-btn" class="dashicons dashicons-no-alt" href="#"></a>' +
          '<div id="swpl__modal--body">' +
          '<div class="swpl__modal--header"></div>' +
          '<div class="swpl__modal--content"></div>' +
          '<div class="swpl__modal--footer"></div>' +
          "</div>" +
          '<div id="swpl__modal--loading" style="display:none;"></div>' +
          "</div>" +
          "</div>"
      );
    }
  });

  $(document.body).on("swpl__modal--loading", function (e, html) {
    if (false !== html) {
      $("#swpl__modal--loading").html(html).show();
    } else {
      $("#swpl__modal--loading").empty().hide();
    }
  });

  $(document.body).on("swpl__modal--body", function (e, html) {
    $("#swpl__modal--body").html(html);
  });

  [
    "swpl__modal--header",
    "swpl__modal--content",
    "swpl__modal--footer",
  ].forEach(function (target) {
    $(document.body).on(target, function (e, html) {
      if (html) {
        $("#swpl__modal--body ." + target)
          .html(html)
          .show();
      } else {
        $("#swpl__modal--body ." + target)
          .empty()
          .hide();
      }
    });
  });

  $(document.body).on("swpl__modal--show", function (e, show) {
    $("html,body").addClass("swpl__modal--active");
  });

  $(document.body).on("swpl__modal--hide", function () {
    $("html,body").removeClass("swpl__modal--active");
    $("#swpl__modal--body > div").empty().hide();
  });

  $(document.body).on("click", "#swpl__modal--close-btn", function () {
    $(document.body).trigger("swpl__modal--hide");
    return false;
  });

  // Cancel update button event
  $(document.body).on("click", "#swpl-modal", function (e) {
    e.preventDefault();
    if (0 === $(e.target).closest("#swpl__modal--inner").length) {
      $(document.body).trigger("swpl__modal--hide");
    }

    return false;
  });

  $(document).ready(function () {
    $(document.body).trigger("swpl__modal__init");
  });

  $(document.body).on("click", "#swpl-wp-debug-log-delete-btn", function () {
    $(document.body).trigger("swpl__modal--hide");

    $.ajax({
      url: `${config.root}swpl/v1/debug-log`,
      headers: {
        "X-WP-Nonce": config.nonce,
      },
      method: "DELETE",
    });

    return false;
  });

  $(document.body).on(
    "click",
    "#wp-admin-bar-shazzad-wp-logs-debug-log a",
    function () {
      $(document.body).trigger("swpl__modal__init");
      $(document.body).trigger("swpl__modal--loading", "Loading...");
      $(document.body).trigger("swpl__modal--show");

      $.ajax({
        url: `${config.root}swpl/v1/debug-log`,
        // add nonce to the request
        headers: {
          "X-WP-Nonce": config.nonce,
        },
        method: "GET",
        dataType: "json",
      })
        .done(function (r) {
          if (r.data) {
            for (var key in r.data) {
              $(document.body).trigger("swpl__modal--" + key, r.data[key]);
            }
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
          $(document.body).trigger("swpl__modal--content", content);
        })
        .always(function () {
          $(document.body).trigger("swpl__modal--loading", false);
        });

      return false;
    }
  );
})(jQuery, swplWpDebugLog);
