/* global jQuery, yqqlData */
(function ($) {
	'use strict';

	function normalizeQuantity($box, value) {
		var $input = $box.find('.yqql-quantity__input');
		var min = parseInt($box.attr('data-min'), 10) || 1;
		var max = parseInt($box.attr('data-max'), 10) || 0;

		value = parseInt(value, 10);

		if (isNaN(value) || value < min) {
			value = min;
		}

		if (max > 0 && value > max) {
			value = max;
		}

		$input.val(value);
		$box.find('.yqql-quantity__button--minus').prop('disabled', value <= min);
		$box.find('.yqql-quantity__button--plus').prop('disabled', max > 0 && value >= max);

		return value;
	}

	$(document).on('click', '.yqql-quantity__button', function (event) {
		event.preventDefault();

		var $button = $(this);
		var $box = $button.closest('.yqql-loop');
		var $input = $box.find('.yqql-quantity__input');
		var current = normalizeQuantity($box, $input.val());

		if ($button.hasClass('yqql-quantity__button--plus')) {
			normalizeQuantity($box, current + 1);
		} else {
			normalizeQuantity($box, current - 1);
		}
	});

	$(document).on('change', '.yqql-quantity__input', function () {
		var $box = $(this).closest('.yqql-loop');
		normalizeQuantity($box, $(this).val());
	});

	$(document).on('click', '.yqql-add-to-quote', function (event) {
		event.preventDefault();

		var $button = $(this);
		var $box = $button.closest('.yqql-loop');
		var $input = $box.find('.yqql-quantity__input');
		var $status = $box.find('.yqql-status');
		var quantity = normalizeQuantity($box, $input.val());
		var originalLabel = $button.text();
		var success = false;

		$status.empty();

		$button
			.prop('disabled', true)
			.addClass('is-loading')
			.text(yqqlData.i18n.adding);

		$.ajax({
			url: yqqlData.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'yqql_add_to_quote',
				product_id: $box.attr('data-product-id'),
				quantity: quantity,
				nonce: $box.attr('data-nonce')
			}
		})
			.done(function (response) {
				if (!response || !response.success) {
					var error = response && response.data && response.data.message
						? response.data.message
						: yqqlData.i18n.genericError;

					$status.text(error);
					return;
				}

				success = true;
				$status.text(response.data.message || '');

				if (response.data.quote_url) {
					$('<a>', {
						href: response.data.quote_url,
						text: yqqlData.i18n.viewQuote
					}).appendTo($status);
				}

				$button.text(yqqlData.i18n.updateQuote);

				$(document.body).trigger('yqql_product_added', [response.data]);
			})
			.fail(function () {
				$status.text(yqqlData.i18n.networkError);
			})
			.always(function () {
				$button
					.prop('disabled', false)
					.removeClass('is-loading');

				if (!success) {
					$button.text(originalLabel);
				}
			});
	});
})(jQuery);
