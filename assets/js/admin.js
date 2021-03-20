/**
 * Admin JS
 */

(function ($) {
	"use strict";

	$(document).ready(function () {
		/* confirm action */
		$(document.body).on('click', '.w4_loggable_ca', function () {
			var d = $(this).data('confirm') || 'Are you sure you want to do this ?';
			if (!confirm(d)) {
				return false;
			}
		});
	});

})(jQuery);
