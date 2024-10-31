(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	function integrationType() {
		jQuery("#stripe_integration_workflow").click(() => {
			displayNavTab();
			displayForm();
		});
	}

	function selectSettingTab() {
		jQuery(document).ready(() => {
			displayNavTab();
			displayForm();
		});
	}

	function displayForm() {
		if (jQuery("#stripe_integration_workflow").is(':checked')) {
			jQuery(".obs-default-form").css("display", "none");
			jQuery(".obs-stripe-form").css("display", "block");
		} else {
			jQuery(".obs-stripe-form").css("display", "none");
			jQuery(".obs-default-form").css("display", "block");
		}
	}

	function displayNavTab() {
		if (jQuery("#stripe_integration_workflow").is(':checked')) {
			jQuery(".nav-tab.default").css("display", "block");
		} else {
			jQuery(".nav-tab.stripe").css("display", "block");
		}
	}

	function exchangeRateComposeLink(originalHref, a) {
		var url = new URL(originalHref);
		var existentExchangeRate = url.searchParams.get("exchange_rate");

		var exchangeRateOrderDate = url.searchParams.get("order_date");
		var exchangeRateCurrencies = url.searchParams.get("currencies");
		var exchangeRate = prompt('Enter exchange rate for - ' + exchangeRateCurrencies + ' at ' + exchangeRateOrderDate, existentExchangeRate);

		if (existentExchangeRate) {
			var newHref = originalHref.replace(/(exchange_rate=).*?(&)/, '$1' + exchangeRate + '$2');
		} else {
			var newHref = originalHref.replace(/(exchange_rate)/, 'exchange_rate=' + exchangeRate)
		}

		$(a).attr('href', newHref);
	}

	function reissueInvoiceComposeLink(originalHref, e) {
		e.preventDefault();
		var url = new URL(originalHref);
		var newHref = originalHref.replace(/(&reissue_invoice=true)/, '');
		var reissueInvoice = url.searchParams.get("reissue_invoice");
		if (reissueInvoice || reissueInvoice == "true") {
			$(e.target).attr('href', newHref);
			var confirmReissue = confirm('Do you want to try to reissue this invoice?')
			if (confirmReissue) {
				window.location.href = $(e.target).attr('href');
			}
		}
	}

	function adjustGenerateInvoiceButtonLinks() {
		jQuery('a.wc-action-button-generate_invoice_obs').each( function( _i, a ){
			var newButtonText = $(a).attr('aria-label');
			$(a).text(newButtonText);
			jQuery(a).removeClass('button');
			jQuery(a).addClass('obs-invoice-special-button');
		});

		jQuery('a.wc-action-button-generate_invoice_obs[aria-label*="Needs Exchange Rate"]').each( function( _i, a ){
			var originalHref = $(a).attr('href');
			var voidHref = "javascript:void(0);";
			$(a).attr('href', voidHref);

			$(a).click(function(e) {
				exchangeRateComposeLink(originalHref, a);
			});
		});

		jQuery('a.wc-action-button-generate_invoice_obs[aria-label*="Reissue Invoice"]').each( function(_i, a) {
			$(a).click(function(e) {
				e.preventDefault();
				var confirmReissue = confirm('If the invoice does not exist, clicking this button will generate a new invoice. If the invoice already exists, an error message will be displayed with a link to the existing invoice.');
				
				if(confirmReissue) {
					window.location = $(a).attr('href')
				} 
			});
		});
	}

	function sendDocumentComposeLink() {
		var url = new URL(originalHref);
		var existentSendDocument = url.searchParams.get("send_document");

		if (existentSendDocument) {
			var newHref = originalHref.replace(/(send_document=).*?(&)/, '$1' + existentSendDocument.toString() + '$2');
		} else {
			var newHref = originalHref.replace(/(send_document)/, 'send_document=' + existentSendDocument.toString());
		}

		$(a).attr('href', newHref);
	}

	function adjustGenerateSendButtonLinks() {
		jQuery('a.wc-action-button-send_document_obs').each( function( _i, a ){
			var newButtonText = $(a).attr('aria-label');
			$(a).text(newButtonText);
			jQuery(a).removeClass('button');
			jQuery(a).addClass('obs-invoice-special-button');
		});

		jQuery('a.wc-action-button-generate_invoice_obs[aria-label*="Send Document"]').each( function( _i, a ){
			var originalHref = $(a).attr('href');
			var voidHref = "javascript:void(0);";
			$(a).attr('href', voidHref);

			$(a).click(function(e) {
				sendDocumentComposeLink(originalHref, a);
			});
		});
	}

	function cahngeApiKeyForOBS() {
		jQuery('a#obschangeapikey').on( 'click', function changeOBSApiKey(e) {
			e.preventDefault();
			jQuery(".wp-admin #wpcontent .obs-collapsible").css("display", "block");
			jQuery(".wp-admin #wpcontent #obschangeapikey").css("display", "none");
		});
	}

	jQuery(window).on('load', function () {
		selectSettingTab();
		integrationType();
		adjustGenerateInvoiceButtonLinks();
		adjustGenerateSendButtonLinks();
		cahngeApiKeyForOBS();
	});
})(jQuery);
