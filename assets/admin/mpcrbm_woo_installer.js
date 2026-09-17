/**
 * MPCRBM WooCommerce Installer
 *
 * Drives a chunked install: repeatedly downloads small byte ranges of the
 * WooCommerce ZIP (0–70% of the bar), extracts it locally (70–90%), activates
 * it (90–100%), then redirects. Keeps every request tiny so memory/time limited
 * hosts never crash or time out.
 */
(function ($) {
	'use strict';

	var config    = window.mpcrbm_woo_installer || {};
	var $overlay  = null;
	var $popup    = null;
	var $btn      = null;
	var $progress = null;
	var $fill     = null;
	var $status   = null;
	var $actions  = null;
	var isWorking = false;

	$(document).ready(function () {
		$overlay  = $('#mpcrbm-woo-overlay');
		$popup    = $overlay.find('.mpcrbm-woo-popup');
		$btn      = $('#mpcrbm-woo-install-btn');
		$progress = $('#mpcrbm-woo-progress');
		$fill     = $('#mpcrbm-woo-progress-fill');
		$status   = $('#mpcrbm-woo-status-text');
		$actions  = $overlay.find('.mpcrbm-woo-actions');

		if (!$overlay.length) {
			return;
		}

		$btn.on('click', function (e) {
			e.preventDefault();
			if (isWorking) {
				return;
			}
			startProcess();
		});
	});

	/**
	 * Begin the install pipeline.
	 */
	function startProcess() {
		isWorking = true;
		$btn.prop('disabled', true);
		$actions.slideUp(250);
		$progress.slideDown(300);

		if (config.woo_installed === 'yes') {
			// Files already on disk — skip download/extract, just activate.
			setProgress(90, config.i18n.activating);
			activate();
		} else {
			setProgress(2, config.i18n.downloading);
			downloadChunk();
		}
	}

	/**
	 * Recursively download the ZIP one chunk at a time.
	 * Download progress is mapped to the 0–70% range of the bar.
	 */
	function downloadChunk() {
		$.ajax({
			url:      config.ajax_url,
			type:     'POST',
			dataType: 'json',
			data: {
				action: 'mpcrbm_woo_download_chunk',
				nonce:  config.nonce
			},
			success: function (response) {
				if (!response || !response.success) {
					showError(response && response.data && response.data.message
						? response.data.message
						: config.i18n.install_error);
					return;
				}

				var data    = response.data || {};
				var percent = typeof data.percent === 'number' ? data.percent : 0;
				setProgress(Math.min(70, Math.round(percent * 0.7)), config.i18n.downloading);

				if (data.done) {
					setProgress(72, config.i18n.installing);
					install();
				} else {
					downloadChunk();
				}
			},
			error: function () {
				showError(config.i18n.install_error);
			}
		});
	}

	/**
	 * Extract the downloaded ZIP into the plugins directory.
	 */
	function install() {
		$.ajax({
			url:      config.ajax_url,
			type:     'POST',
			dataType: 'json',
			data: {
				action: 'mpcrbm_woo_install',
				nonce:  config.nonce
			},
			success: function (response) {
				if (response && response.success) {
					setProgress(90, config.i18n.activating);
					activate();
				} else {
					showError(response && response.data && response.data.message
						? response.data.message
						: config.i18n.install_error);
				}
			},
			error: function () {
				showError(config.i18n.install_error);
			}
		});
	}

	/**
	 * Activate WooCommerce.
	 */
	function activate() {
		$.ajax({
			url:      config.ajax_url,
			type:     'POST',
			dataType: 'json',
			data: {
				action: 'mpcrbm_woo_activate',
				nonce:  config.nonce
			},
			success: function (response) {
				if (response && response.success) {
					showSuccess();
				} else {
					showError(response && response.data && response.data.message
						? response.data.message
						: config.i18n.activate_error);
				}
			},
			error: function () {
				showError(config.i18n.activate_error);
			}
		});
	}

	function setProgress(percent, text) {
		$fill.css('width', percent + '%');
		$status.text(text).removeClass('mpcrbm-success mpcrbm-error');
	}

	function showSuccess() {
		setProgress(100, config.i18n.success);
		$popup.addClass('mpcrbm-state-success');
		$status.addClass('mpcrbm-success');

		$popup.find('.mpcrbm-woo-icon').html(
			'<svg width="40" height="40" viewBox="0 0 24 24" fill="none">' +
			'<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>' +
			'<path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
			'</svg>'
		);

		$popup.find('.mpcrbm-woo-title').text(config.i18n.success);
		$popup.find('.mpcrbm-woo-desc').text(config.i18n.redirecting);

		setTimeout(function () {
			window.location.href = config.redirect_url;
		}, 1500);
	}

	function showError(message) {
		isWorking = false;
		$popup.addClass('mpcrbm-state-error');
		$status.text(message).addClass('mpcrbm-error');
		$fill.css('width', '100%');

		$btn.prop('disabled', false);
		$actions.slideDown(250);

		setTimeout(function () {
			$popup.removeClass('mpcrbm-state-error');
			$progress.slideUp(250);
			$fill.css('width', '0%');
		}, 3500);
	}

})(jQuery);
