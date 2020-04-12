/**
 * Admin JS
 * @package WordPress
 * @subpackage SERVED Admin
 * @author Shazzad Hossain Khan
 * @url https://shazzad.me
**/


(function($) {
	"use strict";

	var datepickerInit = function ( $wrap ){
		$wrap.find('input.date_input').each(function(){
			var data = $(this).data() || {
				format: 'Y-m-d',
				formatDate: 'Y-m-d'
			};
			if(typeof(data.closeondateselect) !== 'undefined' ){
				data.closeOnDateSelect = true;
			}
			if(typeof(data.mindate) !== 'undefined' ){
				data.minDate = data.mindate;
			}
			data.scrollInput = false;
			data.hours12 = true;
			$(this).datetimepicker(data);
		});
	},
	setUrlParameter = function(url, key, value) {
		var baseUrl = url.split('?').length === 2 ? url.split('?')[0] : url,
			urlQueryString = url.split('?').length === 2 ? '?' + url.split('?')[1] : '',
			newParam = key + '=' + value,
			params = '?' + newParam;

		// If the "search" string exists, then build params from it
		if (urlQueryString) {
			var updateRegex = new RegExp('([\?&])' + key + '[^&]*');
			var removeRegex = new RegExp('([\?&])' + key + '=[^&;]+[&;]?');

			if (typeof value === 'undefined' || value === null || value === '') { // Remove param if value is empty
				params = urlQueryString.replace(removeRegex, "$1");
				params = params.replace(/[&;]$/, "");

			} else if (urlQueryString.match(updateRegex) !== null) { // If param exists already, update it
				params = urlQueryString.replace(updateRegex, "$1" + newParam);

			} else { // Otherwise, add it to end of query string
				params = urlQueryString + '&' + newParam;
			}
		}

		// no parameter was set so we don't need the question mark
		params = params === '?' ? '' : params;
		return baseUrl + params;
	},
	formFilesInit = function($form) {
		$form.find('.wffwt_submit_bottom').prepend('<input type="submit" value="'+ $form.find('.wffwt_submit_bottom .form_button').val() +'" class="W4_Loggable_upload_files_pre_submit">');
		$form.find('.wffwt_submit_bottom .form_button').hide();

		var wrapDiv = $('#W4_loggable-uploader-resume_files');
		wrapDiv.on('W4_loggable/upload_complete', function(){
			$form.find('.W4_Loggable_upload_files_pre_submit').hide();
			$form.find('.form_button').show();
			$form.find('.form_button').trigger('click');
			return false;
		});

		$form.on('click', '.W4_Loggable_upload_files_pre_submit', function(){
			if (! $(this).hasClass('ld')) {
				$(this).val('Uploading files, please wait').addClass('ld').prop('disabled', true);
				wrapDiv.trigger('W4_loggable/start_upload');
			}

			return false;
		});

		$form.on('click', '.delete-file', function() {
			if (confirm('Are you sure you want to delete this file')) {
				var $t = $(this);
				$.ajax({
					url: $form.attr('action') + '/files/'+ $t.data('id'),
					method: 'delete'
				})
				.done(function(r){
					if (r.success) {
						$t.parent('li').remove();
					} else {
						alert(r.message);
					}
				});
			}
			return false;
		});
	},
	handleAnyCheckbox = function($form, name, val) {
		var input = '[name="'+ name +'array()"]';
		$form.find(input).on('change', function(){
			if ($(this).val() === val) {
				if ($(this).is(':checked')) {
					$form.find(input).not(this).prop("checked", false).prop('disabled', true);
				} else {
					$form.find(input).prop('disabled', false);
				}
			}
		});

		if ($form.find(input).filter('[value="'+ val +'"]').is(':checked')) {
			var self = $form.find(input).filter('[value="'+ val +'"]');
			$form.find(input).not(self).prop("checked", false).prop('disabled', true);
			// console.log('checked');
		}
	};

	$(document).ready(function(){
		$(document.body).on('W4_loggable/datepickerInit', function(e, $wrap){
			datepickerInit($wrap);
		});
		$(document.body).trigger('W4_loggable/datepickerInit', [$('body')]);

		/* confirm action */
		$(document.body).on('click', '.W4_Loggable_ca', function(){
			var d = $(this).data('confirm') || 'Are you sure you want to do this ?';
			if(! confirm(d)){
				return false;
			}
		});

		$(document.body).on('click', '.W4_loggable-toggle-btn', function(e){
			var _that = $(this);
			var _toggle = $(this).closest('.W4_loggable-toggle');
			if( _toggle.find('.W4_loggable-toggle-content').is(':visible') ){
				_toggle.removeClass('W4_loggable-toggle-active');
				_toggle.find('.W4_loggable-toggle-content').slideUp();
			} else {
				_toggle.addClass('W4_loggable-toggle-active');
				_toggle.find('.W4_loggable-toggle-content').slideDown();
			}

			$(document.body).trigger('W4_loggable/toggle', _toggle);
		});

		function crudFormActions(element, urlKey) {
			$(document.body).on('W4_Loggable_'+ element +'_add_form/done', function(e, r, $form){
		console.log($form);
				if (r.message) {
					window.location.href = setUrlParameter(W4_loggable[urlKey], 'message', r.message);
				} else {
		  window.location.href = setUrlParameter(W4_loggable[urlKey], 'message', $form.data('success_text'));
		}
			});
			$(document.body).on('W4_Loggable_'+ element +'_edit_form/done', function($form, r){
				if (r.success) {
					window.location.href = setUrlParameter(W4_loggable[urlKey], 'message', r.message);
		} else {
		  window.location.href = setUrlParameter(W4_loggable[urlKey], 'message', $form.data('success_text'));
				}
			});
		}

		/* crud forms */
	crudFormActions('supplier_customer', 'supplierCustomersPageUrl');
		crudFormActions('customer_product', 'customerProductsPageUrl');
	crudFormActions('supplier', 'suppliersPageUrl');


	function object_select2() {
  		var objects = ['supplier', 'customer', 'manager', 'product'];

  		for( var i=0; i<objects.length; i++ )
  		{
  			var object = objects[i];

  			$('.W4_Loggable_select2_'+ object).each(function(){

  				var $that = $(this);

  				var settings = {
  					placeholder: "Select "+ object,
  					allowClear: $that.data('select2-allowclear') ? $that.data('select2-allowclear') : false,
  					escapeMarkup: function (markup) { return markup; },
  					templateResult: function(item){ return templateSelection(item, $that); },
  					templateSelection: function(item){ return templateSelection(item, $that); }
  				};

  				if ($that.hasClass('ajax')) {
  					settings.minimumInputLength = 1;
  					settings.ajax = {
  						url: ajaxurl + '?action=W4_Loggable_select2_'+ object +'s',
  						dataType: 'json',
  						delay: 250,
  						data: function( params ){
  							return {
  								s: params.term, // search term
  								paged: params.page, // search term
  								per_page: 10
  							};
  						},
  						processResults: function( data, params ){
  							params.page = params.page || 1;
  							params.per_page = params.per_page || 10;
  							return {
  								results: data.items,
  								pagination: {
  									more: (params.page * params.per_page) < data.total
  								}
  							};
  						},
  						cache: true
  					};
  				}

  				$that.not(':hidden, .select2-hidden-accessible').select2( settings );
  			});
  		}

  		// other select2
  		$('.W4_Loggable_select2').each(function(){
  			var $that = $(this);
  			var settings = {
  				placeholder: "Select",
  				allowClear: $that.data('select2-allowclear') ? $that.data('select2-allowclear') : false,
  				escapeMarkup: function (markup) { return markup; },
  				templateResult: function(item){ return templateSelection(item, $that); },
  				templateSelection: function(item){ return templateSelection(item, $that); }
  			};
  			$that.not(':hidden, .select2-hidden-accessible').select2( settings );
  		});
  	}

	object_select2();

	function templateSelection(item, $that){
  		var markup = '';
  		if( typeof(item.text) !== 'undefined' && '' != item.text ){
  			markup += item.text;
  		} else {
  			markup += item.title;
  		}
  		return markup;
  	}
	});

})(jQuery);
