(function ($) {
	'use strict';

	$(function () {
		var $modal = $('#mpcrbm_bm_modal');
		var $dialog = $modal.find('.mpcrbm-bm-modal');
		var $form = $('#mpcrbm_add_bm_form');
		var lastFocused = null;

		if (!$modal.length) {
			return;
		}

		function getFocusableElements() {
			return $dialog
				.find('a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])')
				.filter(':visible');
		}

		function openModal(event) {
			if (event) {
				event.preventDefault();
				lastFocused = event.currentTarget;
			}

			$modal.addClass('is-open').attr('aria-hidden', 'false');
			$('body').addClass('mpcrbm-bm-modal-open');

			window.setTimeout(function () {
				$('#mpcrbm_bm_first_name').trigger('focus');
			}, 30);
		}

		function closeModal() {
			$modal.removeClass('is-open').attr('aria-hidden', 'true');
			$('body').removeClass('mpcrbm-bm-modal-open');

			if (window.history && window.history.replaceState) {
				var url = new window.URL(window.location.href);
				if (url.searchParams.get('action') === 'add') {
					url.searchParams.delete('action');
					url.searchParams.delete('error');
					window.history.replaceState({}, document.title, url.toString());
				}
			}

			if (lastFocused) {
				$(lastFocused).trigger('focus');
			} else {
				$('#mpcrbm_open_bm_modal').trigger('focus');
			}
		}

		function secureRandomIndex(maximum) {
			if (window.crypto && window.crypto.getRandomValues) {
				var values = new Uint32Array(1);
				window.crypto.getRandomValues(values);
				return values[0] % maximum;
			}
			return Math.floor(Math.random() * maximum);
		}

		function generatePassword() {
			var groups = [
				'ABCDEFGHJKLMNPQRSTUVWXYZ',
				'abcdefghjkmnpqrstuvwxyz',
				'23456789',
				'!@#$%&*?'
			];
			var allCharacters = groups.join('');
			var password = groups.map(function (group) {
				return group.charAt(secureRandomIndex(group.length));
			});

			while (password.length < 16) {
				password.push(allCharacters.charAt(secureRandomIndex(allCharacters.length)));
			}

			for (var index = password.length - 1; index > 0; index -= 1) {
				var swapIndex = secureRandomIndex(index + 1);
				var temporary = password[index];
				password[index] = password[swapIndex];
				password[swapIndex] = temporary;
			}

			var $password = $('#mpcrbm_bm_password');
			$password.val(password.join('')).attr('type', 'text').trigger('focus');
			var $toggle = $('.mpcrbm-bm-password-toggle');
			$toggle
				.attr('aria-label', $toggle.data('hide-label'))
				.find('i').removeClass('fa-eye').addClass('fa-eye-slash');
		}

		$('.mpcrbm-open-bm-modal').on('click', openModal);
		$('.mpcrbm-bm-modal-close, .mpcrbm-bm-modal-cancel').on('click', closeModal);

		$modal.on('mousedown', function (event) {
			if (event.target === this) {
				closeModal();
			}
		});

		$(document).on('keydown.mpcrbmBranchManagerModal', function (event) {
			if (!$modal.hasClass('is-open')) {
				return;
			}

			if (event.key === 'Escape') {
				event.preventDefault();
				closeModal();
				return;
			}

			if (event.key !== 'Tab') {
				return;
			}

			var $focusable = getFocusableElements();
			if (!$focusable.length) {
				event.preventDefault();
				return;
			}

			var first = $focusable.get(0);
			var last = $focusable.get($focusable.length - 1);
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		});

		$('.mpcrbm-bm-generate-password').on('click', generatePassword);

		$('.mpcrbm-bm-password-toggle').on('click', function () {
			var $button = $(this);
			var $password = $('#mpcrbm_bm_password');
			var showPassword = $password.attr('type') === 'password';

			$password.attr('type', showPassword ? 'text' : 'password');
			$button
				.attr('aria-label', showPassword ? $button.data('hide-label') : $button.data('show-label'))
				.find('i')
				.toggleClass('fa-eye', !showPassword)
				.toggleClass('fa-eye-slash', showPassword);
		});

		$form.on('submit', function () {
			if (this.checkValidity && !this.checkValidity()) {
				return;
			}

			var $submit = $(this).find('.mpcrbm-bm-modal-submit');
			$submit.prop('disabled', true).attr('aria-busy', 'true');
			$submit.find('span').text($submit.data('loading-text'));
		});

		if ($modal.data('auto-open') === 1 || $modal.hasClass('is-open')) {
			openModal();
		}
	});
})(jQuery);
