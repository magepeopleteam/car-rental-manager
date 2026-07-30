(function ($) {
	'use strict';

	// Top-of-page loading bar. Deliberately NOT a switch to AJAX/pushState
	// navigation (every link here still causes a real full page load) — that
	// was considered and rejected: every page in this shell (color pickers,
	// Chart.js, select2, file uploads, the WP Settings API's POST-to-
	// options.php-then-redirect save flow) assumes a fresh page load, so
	// AJAX-swapping content in would mean re-auditing all of that for
	// comparatively little UX gain. This is the cheap version of the same
	// win: instant feedback that a click registered (bar grows toward 90%
	// immediately), and a smoothed arrival on the next page (finishes to
	// 100%, then fades) instead of a silent hang in between. Self-contained
	// — injects its own element — so it applies to every page this script is
	// enqueued on with no per-page template changes. Runs as its own IIFE,
	// at the very top of this file, so it's unaffected by the early
	// "if (!$shell.length) return;" further down (this needs to run on the
	// Add/Edit Car screen too, not just the 7 dashboard-style pages).
	(function () {
		// enqueue_shell_assets() only prints this script on this plugin's own
		// screens (MPCRBM_Admin_Shell::is_plugin_screen()/is_metabox_screen()),
		// with in_footer=true — so document.body already exists here.
		var bar = document.createElement('div');
		bar.className = 'mpcrbm-shell-loadbar';
		document.body.appendChild(bar);

		// rAF so the browser paints the width:0 starting state first — without
		// this the width:0 → 30% change would happen in the same frame and the
		// CSS transition would have nothing to animate from.
		window.requestAnimationFrame(function () {
			bar.classList.add('is-active');
			bar.style.width = '30%';
			setTimeout(function () {
				bar.classList.add('is-done');
			}, 250);
		});

		document.addEventListener('click', function (e) {
			var link = e.target.closest && e.target.closest('a[href]');
			if (!link || link.target === '_blank' || link.hasAttribute('download') ||
				e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
				return;
			}
			var href = link.getAttribute('href') || '';
			if (!href || href.charAt(0) === '#' || href.toLowerCase().indexOf('javascript:') === 0) {
				return;
			}
			try {
				if (new URL(link.href, window.location.href).origin !== window.location.origin) {
					return;
				}
			} catch (err) {
				return;
			}
			bar.classList.remove('is-done');
			bar.classList.add('is-active');
			bar.style.width = '90%';
		});
	})();

	// Add/Edit Car screen only — DOM relocation, run SYNCHRONOUSLY (not inside
	// jQuery(document).ready()) rather than deferred to DOMContentLoaded.
	//
	// Why: WordPress's own TinyMCE bootstrap for the content editor is an inline
	// <script> placed later in the page (near the closing </body>) that checks
	// document.readyState directly and calls tinymce.init() IMMEDIATELY if the
	// document is already 'interactive' — it does not wait for a DOMContentLoaded
	// event listener. Since this file (mpcrbm-shell.js) is enqueued as a plain,
	// non-deferred <script src>, it is fetched and executed in document order,
	// BEFORE that later inline script runs. But jQuery(document).ready() queues
	// its callback for the DOMContentLoaded event, which fires AFTER this script
	// finishes executing — meaning WP's inline TinyMCE bootstrap can (and does)
	// run first regardless, initializing the "content" editor's iframe in its
	// ORIGINAL DOM position. If this file's relocation code then runs afterwards
	// and moves #postdivrich (which by then contains that live iframe) into the
	// new card, the browser reloads the iframe as a side effect of relocating it
	// in the DOM — silently wiping the just-initialized editable body, so typing
	// into the "Vehicle Description" field does nothing.
	// Running this block synchronously, at parse time, avoids the race entirely:
	// by this script's position in the page (after #postdivrich, the metabox
	// panel, and every tab already exist further up the document, but before
	// WP's own TinyMCE bootstrap script further down), the move completes before
	// TinyMCE ever touches the textarea — so TinyMCE initializes directly in the
	// new location instead of being relocated after the fact.

	// Add/Edit Car screen only: force WordPress's own single-column post-edit
	// layout instead of the default 2-column one. WP's "#post-body.columns-2
	// #postbox-container-1 {float:right; ...}" rule (wp-admin/css/common.css)
	// is conditioned specifically on the "columns-2" class, so simply swapping
	// it for "columns-1" (WP's own class for this, normally toggled via the
	// Screen Options "Layout" radios — hidden on this screen per an earlier
	// request) makes that rule stop applying on its own; no CSS overrides needed.
	$('#post-body.metabox-holder').removeClass('columns-2').addClass('columns-1');

	// Add/Edit Car screen only: move the native Title field + content editor
	// into the "General Info" tab of the Information Settings metabox, so they
	// read as part of that tab instead of sitting above the tabbed panel.
	// Only the DOM position changes — name="post_title"/name="content" stay
	// inside the same <form id="post">, so saving/autosave is unaffected.
	// NOTE: the tab panel has no id="mpcrbm_general_info" — that string only
	// appears as the value of a data-tabs="" attribute (the tab-switching JS
	// in mpcrbm_global.js matches on it as a plain string, not a DOM id), so
	// it must be selected as an attribute selector, not "#mpcrbm_general_info".
	var $generalInfoTab = $('[data-tabs="#mpcrbm_general_info"]');
	var $titlediv = $('#titlediv');
	if ($generalInfoTab.length && $titlediv.length) {
		// Wrapped in the same .mpcrbm-info-card markup the "Vehicle Details" /
		// "Capacity & Stock" / etc. cards use (see MPCRBM_General_Settings.php +
		// mpcrbm-shell.css), so it picks up identical styling automatically.
		var $basicInfoCard = $(
			'<div class="mpcrbm-info-card mpcrbm-basic-info-card">' +
				'<div class="mpcrbm-info-card-header"><i class="fas fa-file-lines"></i><h3>Basic Information</h3></div>' +
				'<div class="mpcrbm-info-card-body"></div>' +
			'</div>'
		);
		// Label for the content editor, matching the reference design's
		// "Vehicle Description" heading — inserted right above #postdivrich
		// rather than baked into the card header, since the card header
		// already labels the whole card ("Basic Information").
		// h6 (not p) so these inherit the exact same "div.mpcrbm h6" framework
		// style the Vehicle Details section's field labels (Car Type, Fuel
		// Type, etc.) use, per explicit request to match that design.
		var $descriptionLabel = $('<h6 class="mpcrbm-content-editor-label">Vehicle Description</h6>');
		// Label for the title field, matching the same pattern as the
		// description label above — inserted right before #titlediv.
		var $titleLabel = $('<h6 class="mpcrbm-content-editor-label">Vehicle Title <span class="mpcrbm-required-mark">*</span></h6>');
		// WP core's #title has no "required" attribute of its own (and no filter
		// to add one) — set here since this is the one place already touching
		// the element after it's moved into the form other required fields live in.
		$titlediv.find('#title').prop('required', true);
		// #post-status-info (word count/autosave-status/resize-handle row) is
		// deliberately NOT relocated here, unlike the other elements — left in
		// its original position inside #post-body-content, which is already
		// hidden via display:none below, so it's simply never shown at all
		// (removed per explicit request).
		$basicInfoCard.find('.mpcrbm-info-card-body').append($titleLabel, $titlediv, $descriptionLabel, $('#postdivrich'));
		$generalInfoTab.prepend($basicInfoCard);
	}

	// Relabels #postimagediv .inside's contents — called once at page load
	// AND every time WP's media modal rewrites that same markup afterward
	// (see the MutationObserver below). Must therefore re-find every element
	// fresh each call rather than caching jQuery objects from an earlier run,
	// since WP replaces the actual DOM nodes each time, not just their text.
	function relabelFeaturedImageInside($featuredImageBox) {
		var $thumbLink = $featuredImageBox.find('#set-post-thumbnail');
		var $howto = $featuredImageBox.find('#set-post-thumbnail-desc');
		var $removeP = $featuredImageBox.find('#remove-post-thumbnail').text('Remove').closest('p');
		if ($thumbLink.find('img').length) {
			// A featured image is already set: turn the descriptive
			// "Click the image to edit or update" text into a "Change
			// image" link that proxy-clicks the same thickbox trigger
			// (same proxy-click pattern used for the Update/Preview
			// top-bar buttons below) rather than rewriting the thickbox
			// link itself.
			$howto.text('Change image').addClass('mpcrbm-change-image').on('click', function () {
				$thumbLink[0].click();
			});
			// Group "Change image" + "Remove" into one flex row (mpcrbm-
			// shell.css) so Remove sits right-aligned opposite Change
			// image, instead of stacking as two separate block-level <p>
			// elements the way WP core prints them.
			$howto.add($removeP).wrapAll('<div class="mpcrbm-featured-image-actions"></div>');
		} else {
			// No featured image set yet: WP core's link is plain text
			// with no fixed size, so the card would be visibly shorter
			// than the "has image" state and jump in height once one is
			// set. Style it as a fixed-aspect-ratio dropzone instead
			// (same 4:3 ratio the Gallery Images tooltip asks for — see
			// MPCRBM_Gallery_Imges_Settings.php) so the card holds its
			// position either way.
			$thumbLink.addClass('mpcrbm-featured-image-placeholder').html(
				'<i class="fas fa-cloud-upload-alt"></i>' +
				'<strong>Click to upload or drag &amp; drop</strong>' +
				'<span>PNG, JPG or WebP (max. 5MB)</span>'
			);

			// WP core doesn't render the "Change image"/"Remove" row at all
			// when no thumbnail is set (_wp_post_thumbnail_html(), wp-admin/
			// includes/post.php) — synthesized here purely so the layout
			// matches the "has image" state. Reuses the same id/classes
			// (safe: the real ones only exist when an image IS set, i.e.
			// never at the same time as these) so the existing "Change
			// image"/"Remove" CSS applies unchanged; "Remove" additionally
			// gets "mpcrbm-inert" since there's nothing to remove yet, per
			// explicit request to still show it for consistent layout.
			var $emptyActions = $(
				'<div class="mpcrbm-featured-image-actions">' +
					'<p class="hide-if-no-js howto mpcrbm-change-image" id="set-post-thumbnail-desc">Change image</p>' +
					'<p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail" class="mpcrbm-inert" aria-disabled="true">Remove</a></p>' +
				'</div>'
			);
			$emptyActions.find('#set-post-thumbnail-desc').on('click', function () {
				$thumbLink[0].click();
			});
			$emptyActions.find('#remove-post-thumbnail').on('click', function (e) {
				e.preventDefault();
			});
			$thumbLink.after($emptyActions);
		}
	}

	// Move the native "Car Feature Image" box (WP core's postimagediv, labelled
	// via this CPT's custom "featured_image" label) out of the side postbox
	// column and into a persistent right sidebar inside the metabox's
	// tabsContent area — visible alongside every tab, not just one, matching
	// the reference layout.
	// IMPORTANT: append the sidebar as a plain sibling of the .tabsItem panels
	// — do NOT wrap the .tabsItem panels in a new parent (e.g. via .wrapAll()).
	// mpcrbm_global.js's tab switcher runs
	// "tabsContent.children('[data-tabs=\"...\"]')", a DIRECT-CHILD-only
	// selector; wrapping .tabsItem in another div takes it out of
	// .tabsContent's direct children and silently breaks switching to any tab
	// other than the one already open on load. The sidebar itself has no
	// data-tabs attribute, so the tab switcher already ignores it as-is.
	var $tabsContent = $('#mpcrbm_meta_box_panel .tabsContent');
	var $featuredImageBox = $('#postimagediv');
	// The "Gallery Images" tab's nav <li> was removed (see MPCRBM_Settings.php)
	// since its content moves here instead; the content itself is still
	// rendered by MPCRBM_Gallery_Imges_Settings::add_tabs_content() (untouched)
	// and just gets relocated, same as the featured image box above.
	var $galleryTabItem = $('[data-tabs="#mpcrbm_settings_gallery_images"]');
	if ($tabsContent.length && ($featuredImageBox.length || $galleryTabItem.length)) {
		var $sidebar = $('<div class="mpcrbm-tabs-sidebar"></div>');

		if ($featuredImageBox.length) {
			$sidebar.append($featuredImageBox);

			// Relabel the native postimagediv chrome to the simplified
			// "Featured Image *" / "Change image" / "Remove" wording from the
			// reference design, instead of this CPT's verbose "Set/Remove Car
			// featured image" labels (built by string concatenation in
			// MPCRBM_CPT.php's featured_image/set_featured_image/
			// remove_featured_image labels — left alone there since those
			// labels are shared by every CPT this plugin registers, not just
			// Car, so relabeling only makes sense scoped to this screen).
			// The .hndle title (in .postbox-header, not .inside) only needs
			// setting once — unlike relabelFeaturedImageInside() below, WP
			// never rewrites it after this point.
			$featuredImageBox.find('.hndle').html('Featured Image <span class="mpcrbm-required">*</span>');

			relabelFeaturedImageInside($featuredImageBox);

			// WP's featured-image picker (wp-includes/js/media-editor.js —
			// wp.media.featuredImage.set()/remove()) replaces #postimagediv
			// .inside's ENTIRE innerHTML via an AJAX call ($('.inside',
			// '#postimagediv').html(html)) every time the user sets or
			// removes the image through the media modal, with fresh, un-
			// relabeled markup straight from the server — silently undoing
			// relabelFeaturedImageInside() above, which otherwise only ever
			// runs once at page load. Re-running it on every such change
			// (rather than only once) is what keeps "Featured Image"/"Change
			// image"/"Remove"/the empty-state dropzone showing correctly
			// after the user actually interacts with the picker, not just on
			// first load. Guarded with disconnect()/observe() around the
			// call since relabelFeaturedImageInside() itself edits this same
			// subtree — without that, every relabel would immediately
			// re-trigger this same observer.
			var $inside = $featuredImageBox.find('.inside');
			if ($inside.length && window.MutationObserver) {
				var insideObserver = new MutationObserver(function () {
					insideObserver.disconnect();
					relabelFeaturedImageInside($featuredImageBox);
					insideObserver.observe($inside[0], { childList: true });
				});
				insideObserver.observe($inside[0], { childList: true });
			}
		}

		if ($galleryTabItem.length) {
			// No icon+title card header here (unlike the other sidebar/tab
			// cards) — the reference design's card starts directly with the
			// "Enable/Disable Gallery" toggle row, which already reads as
			// the card's top edge; a second "Gallery Images" header above it
			// would just duplicate the label lower down (.mpcrbm-gallery-
			// images-label, next to the info icon).
			var $galleryCard = $(
				'<div class="mpcrbm-info-card mpcrbm-gallery-sidebar-card">' +
					'<div class="mpcrbm-info-card-body"></div>' +
				'</div>'
			);
			// .tabsItem carries its own card-like styling (shadow/padding/radius)
			// scoped to ".tabsContent .tabsItem" — dropping the class avoids a
			// "card inside a card" look once it's nested in .mpcrbm-info-card-body.
			// display is force-set too: this tab was never the initially active
			// one, so nothing guarantees it wasn't sitting hidden (inline
			// style/class) before being pulled out of the normal tab flow.
			$galleryTabItem.removeClass('tabsItem').css('display', 'block');
			$galleryCard.find('.mpcrbm-info-card-body').append($galleryTabItem);
			$sidebar.append($galleryCard);
		}

		$tabsContent.append($sidebar);
	}

	// Reveal the panel now that every relocation above is done — see
	// MPCRBM_Admin_Shell::print_metabox_reveal_style() for why it starts
	// hidden (an inline <style> in <head>, removed here rather than just
	// overridden, so nothing else needs to out-specificity it later).
	document.getElementById('mpcrbm-metabox-reveal') && document.getElementById('mpcrbm-metabox-reveal').remove();
}(jQuery));

jQuery(function ($) {
	'use strict';

	/* ======================================================================
	   Add/Edit Car screen — floating step navigator (Back / Next) with
	   per-step required-field validation.

	   Markup: MPCRBM_Admin_Shell::render_edit_screen_stepnav() (printed on
	   admin_footer). Styling: the "floating step navigator" section of
	   mpcrbm-shell.css.

	   Navigation is performed by TRIGGERING A CLICK on the existing
	   ".tabLists li[data-tabs-target]" item, never by showing/hiding panels
	   directly — mp_global/assets/mp_style/mpcrbm_global.js owns the real tab
	   switcher, and going through it keeps everything it does intact: the
	   slideUp/slideDown animation, the per-post "remember the active tab"
	   localStorage entry, the scroll-into-view, mpcrbm_all_content_change(),
	   and the TinyMCE re-layout nudge the General Info tab needs. Duplicating
	   any of that here would mean two code paths to keep in sync.

	   The step list is read from the DOM for the same reason the PHP side
	   doesn't hardcode it: add-ons extend the tab strip through the
	   'mpcrbm_settings_tab_navigation' action.
	   ====================================================================== */
	var mpcrbmStepNav = (function () {
		var $bar     = $('#mpcrbm-stepnav');
		var $tabList = $('#mpcrbm_meta_box_panel .mpcrbm_settings .tabLists');
		var $tabs    = $tabList.children('li[data-tabs-target]');
		var total    = $tabs.length;

		// Nothing to step through (no bar on this screen, or a single tab) —
		// leave the bar hidden rather than showing inert Back/Next controls.
		if (!$bar.length || total < 2) {
			return null;
		}

		var $panels  = $('#mpcrbm_meta_box_panel .mpcrbm_settings .tabsContent');
		var i18n     = (typeof mpcrbmShell !== 'undefined' && mpcrbmShell.i18n) ? mpcrbmShell.i18n : {};

		var $fill      = $bar.find('.mpcrbm-stepnav__bar-fill');
		var $ring      = $bar.find('.mpcrbm-stepnav__ring');
		var $ringNum   = $bar.find('.mpcrbm-stepnav__ring-num');
		var $eyebrow   = $bar.find('.mpcrbm-stepnav__eyebrow');
		var $title     = $bar.find('.mpcrbm-stepnav__title');
		var $dots      = $bar.find('.mpcrbm-stepnav__dots');
		var $prev      = $bar.find('[data-mpcrbm-step="prev"]');
		var $next      = $bar.find('[data-mpcrbm-step="next"]');
		var $finish    = $bar.find('[data-mpcrbm-step="finish"]');
		var $alertText = $bar.find('.mpcrbm-stepnav__alert-text');

		// visited[i]: the user has actually been on step i (or tried to leave
		// it). errored[i]: that step's last validation found empty required
		// fields. A step is only ever flagged red once visited — on a brand-new
		// car every step is empty, and lighting all of them up on load would be
		// noise, not information.
		var visited = [];
		var errored = [];
		var current = 0;
		var alertTimer = null;
		// False until the initial tab restore has settled — see the note where
		// it's flipped, at the bottom of this module.
		var booted = false;
		// True only for the duration of a programmatic navigate() — see there.
		var bypass = false;

		// Minimal printf for the localized strings above: handles the %1$s /
		// %2$s / %s forms actually used, nothing more.
		function fmt(template, a, b) {
			if (!template) {
				return '';
			}
			return String(template)
				.replace(/%1\$s/g, a)
				.replace(/%2\$s/g, b)
				.replace(/%s/g, a);
		}

		// The <li> is "<span class="mi mi-..."></span>Label" — the icon span is
		// empty, so .text() already yields just the label.
		function stepLabel(index) {
			return $.trim($tabs.eq(index).text());
		}

		function panelFor(index) {
			var target = $tabs.eq(index).attr('data-tabs-target');
			return target ? $panels.children('[data-tabs="' + target + '"]') : $();
		}

		// True when anything BETWEEN the field and its tab panel is hidden —
		// a collapsed "[data-collapse]" section (the framework only reveals
		// those by adding .mActive) or a ".hidden_content" repeater template.
		// The panel itself is excluded from the walk on purpose: inactive
		// panels are display:none, and this same check has to work while
		// validating a step the user isn't currently looking at.
		// getComputedStyle reports an element's OWN cascaded display even
		// inside a display:none subtree, so the ancestor walk stays accurate
		// for hidden panels.
		function isHiddenWithinPanel(el, panel) {
			var node = el;
			while (node && node !== panel && node.nodeType === 1) {
				var style = window.getComputedStyle(node);
				if (style && (style.display === 'none' || style.visibility === 'hidden')) {
					return true;
				}
				node = node.parentNode;
			}
			return false;
		}

		// Required fields in a step that constraint validation actually applies
		// to. Mirrors the HTML spec's "barred from constraint validation" list
		// (disabled / readonly / type=hidden) — which is what keeps, for
		// example, MPCRBM_Date_Settings' hidden+readonly "Repeated Start Date"
		// pair from being reported as an unfillable blocker — plus the
		// hidden-by-UI cases above.
		function requiredFields(index) {
			var $panel = panelFor(index);
			if (!$panel.length) {
				return $();
			}
			var panel = $panel[0];

			return $panel.find('[required]').filter(function () {
				if (this.disabled || this.readOnly || this.type === 'hidden') {
					return false;
				}
				return !isHiddenWithinPanel(this, panel);
			});
		}

		function isFieldValid(el) {
			if (typeof el.checkValidity === 'function') {
				return el.checkValidity();
			}
			return $.trim($(el).val() || '') !== '';
		}

		// Field's own label, for the "“Price/Day” is required" message. Most
		// fields sit inside "<section><label class="label"><div><h6>"; the
		// relocated post title is the exception — mpcrbm-shell.js puts its
		// <h6> as a SIBLING before #titlediv rather than an ancestor's child.
		function fieldLabel(el) {
			var $el    = $(el);
			var $label = $el.closest('label, section').find('h6').first();

			if (!$label.length) {
				$label = $el.closest('#titlediv').prevAll('h6').first();
			}
			if (!$label.length) {
				return i18n.thisField || 'This field';
			}

			// Drop the "*" span (and any icon span) before reading the text.
			var text = $.trim($label.clone().children('span').remove().end().text());

			return text || i18n.thisField || 'This field';
		}

		function markInvalid($field) {
			// mpcrbm_required is the framework's own red-border class (see
			// mpcrbm_check_required() in mpcrbm_global.js, which also toggles it
			// on keyup/change) — reused so the two agree on how an empty
			// required field looks. mpcrbm-step-invalid adds this feature's own
			// glow/pulse and is what the "clear as you type" handler keys off.
			$field.addClass('mpcrbm-step-invalid mpcrbm_required').attr('aria-invalid', 'true');
		}

		function clearInvalid($field) {
			// Drops mpcrbm_required too — markInvalid() is what put it there, and
			// the framework's own keyup/change handler would reach the same
			// conclusion for a field that now has a value.
			$field.removeClass('mpcrbm-step-invalid mpcrbm_required').removeAttr('aria-invalid');
		}

		// Validates one step and records the result in errored[]. Returns the
		// array of offending elements (empty when the step is complete).
		function validateStep(index) {
			var invalid = [];

			requiredFields(index).each(function () {
				if (isFieldValid(this)) {
					clearInvalid($(this));
				} else {
					invalid.push(this);
					markInvalid($(this));
				}
			});

			errored[index] = invalid.length > 0;

			return invalid;
		}

		function focusField(el) {
			try {
				el.focus({ preventScroll: true });
			} catch (err) {
				el.focus();
			}
			// The framework's tab switcher ends with a 1000ms jQuery scroll
			// animation on html/body (mpcrbm_page_scroll_to). Left running it
			// would drag the viewport straight back off the field this is trying
			// to reveal, so hand scroll control over explicitly.
			$('html, body').stop(true);

			if (el.scrollIntoView) {
				el.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}

		function notify(message) {
			$alertText.text(message);
			$bar.addClass('has-alert');
			window.clearTimeout(alertTimer);
			alertTimer = window.setTimeout(hideAlert, 8000);
		}

		function hideAlert() {
			window.clearTimeout(alertTimer);
			$bar.removeClass('has-alert');
		}

		function shake() {
			$bar.removeClass('is-shaking');
			// Force a reflow so re-adding the class restarts the animation
			// instead of being coalesced into a no-op.
			void $bar[0].offsetWidth;
			$bar.addClass('is-shaking');
			window.setTimeout(function () {
				$bar.removeClass('is-shaking');
			}, 500);
		}

		function buildDots() {
			$dots.empty();

			for (var i = 0; i < total; i++) {
				var label = stepLabel(i);
				var $button = $('<button type="button"></button>')
					.attr({ 'data-mpcrbm-goto': i, title: label, 'aria-label': label })
					.append($('<span class="mpcrbm-stepnav__dot-num"></span>').text(i + 1))
					.append($('<i class="fas fa-check mpcrbm-stepnav__dot-check" aria-hidden="true"></i>'));

				$('<li class="mpcrbm-stepnav__dot"></li>').append($button).appendTo($dots);
			}
		}

		function render() {
			var isFirst = current === 0;
			var isLast  = current === total - 1;
			var percent = Math.round(((current + 1) / total) * 100);

			$ringNum.text(current + 1);
			// setProperty, not .css(): jQuery only learned to write CSS custom
			// properties in 3.2, and WP has shipped older jQuery on sites that
			// haven't updated.
			$ring[0].style.setProperty('--mpcrbm-stepnav-pct', percent + '%');
			$fill.css('width', percent + '%');
			$eyebrow.text(fmt(i18n.stepOf, current + 1, total));
			$title.text(stepLabel(current));

			$prev.prop('disabled', isFirst);
			$next.prop('hidden', isLast);
			$finish.prop('hidden', !isLast);

			$dots.children().each(function (index) {
				$(this)
					.toggleClass('is-current', index === current)
					.toggleClass('is-error', !!errored[index])
					.toggleClass('is-done', index !== current && !errored[index] && !!visited[index]);
			});
		}

		// Re-checks every step already flagged red and clears the ones that are
		// now complete, so dots stop lying as soon as the user fills a field in.
		function refreshErrored() {
			var changed   = false;
			var remaining = 0;

			for (var i = 0; i < total; i++) {
				if (errored[i]) {
					if (validateStep(i).length) {
						remaining++;
					} else {
						changed = true;
					}
				}
			}
			if (changed) {
				render();
			}
			// Retract the message once nothing is blocking any more, rather than
			// leaving a stale "X is required" sitting there until its timeout.
			if (!remaining) {
				hideAlert();
			}
		}

		// Moves to a step unconditionally. The bypass flag tells the click handler
		// below that this move has already been vetted (or is a deliberate jump
		// to a blocking step), so it only syncs state instead of re-gating it —
		// without it, every programmatic move would recurse back through the gate.
		function navigate(index) {
			if (index === current) {
				return;
			}
			bypass = true;
			// The framework's switcher (delegated on document) performs the actual
			// switch; trigger() is synchronous, so bypass is back to false before
			// anything else can observe it.
			$tabs.eq(index).trigger('click');
			bypass = false;
		}

		// The wizard invariant: step N can only be entered once steps 0..N-1 are
		// all complete. Moving BACKWARD is always allowed — you can always return
		// to something you've already seen, including to fix it.
		//
		// Every user-initiated move funnels through here (Back, Next, a dot, a
		// click on the tab strip), so the rule can't be sidestepped by picking a
		// different control. Returns true when the move happened.
		function requestStep(index) {
			index = Math.max(0, Math.min(total - 1, index));

			if (index === current) {
				return true;
			}
			if (index < current) {
				hideAlert();
				navigate(index);

				return true;
			}

			// Forward: find the first step at or before the target that still has
			// empty required fields. Validating as we go is what paints those
			// fields red, so the user can see the actual blockers, not just a
			// message about them.
			var block   = -1;
			var invalid = [];

			for (var i = 0; i < index; i++) {
				var bad = validateStep(i);
				// Mark it evaluated either way: a step we've just proved complete
				// should read as done in the rail even if the user never opened it
				// (skipping ahead over already-filled steps is a normal thing to do
				// when editing an existing car). The loop stops at the blocker, so
				// steps beyond it stay unevaluated and keep their neutral look.
				visited[i] = true;
				if (bad.length) {
					block   = i;
					invalid = bad;
					break;
				}
			}

			visited[current] = true;
			render();

			if (block < 0) {
				hideAlert();
				navigate(index);

				return true;
			}

			// Land the user on the blocker, then explain — navigate() syncs through
			// the click handler, which clears any standing message, so notifying
			// first would only flash it.
			if (block === current) {
				focusField(invalid[0]);
				notify(invalid.length === 1
					? fmt(i18n.fieldRequired, fieldLabel(invalid[0]))
					: fmt(i18n.fieldsRequired, invalid.length));
			} else {
				navigate(block);
				// The switcher slides panels for 350ms; focusing before that
				// settles would target a still-hidden field.
				window.setTimeout(function () {
					focusField(invalid[0]);
				}, 400);
				notify(fmt(i18n.stepLocked, stepLabel(block)));
			}

			shake();

			return false;
		}

		// Validates every step. Returns true when all are complete; otherwise
		// jumps to the first incomplete one, focuses its first empty field and
		// explains why in the bar.
		//
		// This also fixes a real failure mode of the plain HTML5 path: required
		// fields living in a NON-active tab panel are display:none, and browsers
		// refuse to submit a form containing an invalid control they can't focus
		// — silently, with only a console warning. Switching to the offending
		// tab first is what makes the message visible at all.
		function validateAll() {
			var firstBad = -1;
			var invalid  = [];

			for (var i = 0; i < total; i++) {
				var bad = validateStep(i);
				if (bad.length && firstBad < 0) {
					firstBad = i;
					invalid  = bad;
				}
			}

			render();

			if (firstBad < 0) {
				hideAlert();
				return true;
			}

			// navigate(), not requestStep(): this jump is deliberately ungated —
			// its whole purpose is to land on the blocking step, which the gate
			// would otherwise refuse to enter. Jump FIRST, then speak, since
			// navigate() syncs through the click handler and that clears any
			// standing message.
			if (firstBad === current) {
				focusField(invalid[0]);
			} else {
				navigate(firstBad);
				// The switcher slides panels for 350ms; focusing before that
				// settles would target a still-hidden field.
				window.setTimeout(function () {
					focusField(invalid[0]);
				}, 400);
			}

			notify(invalid.length === 1
				? fmt(i18n.stepBlocked, stepLabel(firstBad))
				: fmt(i18n.stepsBlocked, stepLabel(firstBad), invalid.length));
			shake();

			return false;
		}

		// ── Wiring ──────────────────────────────────────────────────────────

		// Records the new position after a switch that has already been allowed.
		function syncTo(index) {
			if (booted && visited[current]) {
				validateStep(current);
			}
			current = index;
			visited[index] = true;
			hideAlert();
			render();
		}

		// Single choke point for the tab strip. Bound on the <ul> rather than on
		// document so it runs BEFORE the framework's document-delegated switcher,
		// which is what makes stopPropagation() able to veto a move.
		$tabList.on('click', 'li[data-tabs-target]', function (e) {
			var index = $tabs.index(this);

			if (index < 0 || index === current) {
				return;
			}

			// Two cases must never be gated:
			//  - bypass: navigate() already vetted this move.
			//  - !booted: mpcrbm_global.js restores the remembered tab by
			//    triggering a click on it from its own ready handler. Vetoing that
			//    would stop the framework's switcher from ever running and leave
			//    the panel area blank — and there is nothing to gate on load
			//    anyway, since the user hasn't asked to go anywhere yet.
			if (bypass || !booted) {
				syncTo(index);

				return;
			}

			// User-initiated: hold the framework's switcher back and let
			// requestStep() decide, then perform the move itself via navigate().
			e.preventDefault();
			e.stopPropagation();
			requestStep(index);
		});

		$dots.on('click', 'button[data-mpcrbm-goto]', function (e) {
			e.preventDefault();
			requestStep(parseInt($(this).attr('data-mpcrbm-goto'), 10));
		});

		$prev.on('click', function (e) {
			e.preventDefault();
			requestStep(current - 1);
		});

		$next.on('click', function (e) {
			e.preventDefault();
			// No separate validation pass here — requestStep() already refuses to
			// enter current+1 until every step up to and including this one is
			// complete, and reports the offending field when the blocker is the
			// step you're standing on.
			requestStep(current + 1);
		});

		// Last step: hand off to the top bar's own Update button, which already
		// owns the validate-then-proxy-click-#publish path (see below) — so
		// there's one save route, not two.
		$finish.on('click', function (e) {
			e.preventDefault();

			var $update = $('#mpcrbm-edit-topbar-update');

			if ($update.length) {
				$update.trigger('click');
			} else if ($('#publish').length) {
				$('#publish')[0].click();
			}
		});

		// Clear a field's error state as soon as it becomes valid. Delegated on
		// document (the class is added long after load) and scoped to the class
		// itself so it costs nothing until something is actually invalid.
		$(document).on('input change', '.mpcrbm-step-invalid', function () {
			if (isFieldValid(this)) {
				clearInvalid($(this));
				refreshErrored();
			}
		});

		// The framework's ready handler activates a tab by triggering a click on
		// it, and whether that has already happened by the time this runs
		// depends on script enqueue order. Both orders are covered: if it ran
		// first, the active <li> is already marked and read here; if it runs
		// after, its click lands on the sync handler above.
		var $active = $tabs.filter('.active').first();
		current = $active.length ? $tabs.index($active) : 0;
		visited[current] = true;

		// Mirror whatever WP decided the real publish button says (Publish /
		// Update / Schedule / Submit for Review) instead of a fixed label.
		var publishLabel = $('#publish').val();
		if (publishLabel) {
			$bar.find('.mpcrbm-stepnav__finish-label').text(publishLabel);
		}

		buildDots();
		render();

		// Every jQuery ready callback for this page runs in the same tick, so a
		// zero-delay timeout lands after mpcrbm_global.js's own ready handler has
		// restored the remembered tab. Until then `booted` stays false and the
		// sync handler skips its "validate the step being left" pass — otherwise
		// re-opening a half-finished car on, say, the Pricing tab would flag
		// General Info red before the user had touched anything.
		window.setTimeout(function () {
			booted = true;
		}, 0);

		$bar.removeAttr('hidden');
		// One-shot entrance class, dropped again once it has played — see the
		// note on .is-entering in mpcrbm-shell.css for why it can't just live on
		// the base rule.
		$bar.addClass('is-entering');
		window.setTimeout(function () {
			$bar.removeClass('is-entering');
		}, 400);

		// Drives the matching padding-bottom on #wpbody-content so the last
		// card in the form can still be scrolled clear of this bar.
		$('body').addClass('mpcrbm-stepnav-active');

		return { validateAll: validateAll };
	})();

	// Edit-screen top bar: Update/Preview proxy-click the real native controls
	// (WP's #publish submit input and #post-preview link) rather than moving
	// them — #publish lives inside <form id="post">, and the top bar is
	// injected via in_admin_header, which runs BEFORE that form even opens in
	// the DOM, so relocating it there would take it out of its form entirely.
	// Native .click() (not jQuery's .trigger('click')) is used so the browser
	// actually performs the click's default action (real form submit / real
	// link navigation), not just fire bound handlers.
	var $topbarUpdate = $('#mpcrbm-edit-topbar-update');
	var $realPublish = $('#publish');
	if ($topbarUpdate.length && $realPublish.length) {
		// Matches whatever WP itself decided this button should say
		// (Publish / Update / Schedule / Submit for Review).
		$topbarUpdate.text($realPublish.val());
		$topbarUpdate.on('click', function (e) {
			e.preventDefault();

			// Publishing/updating is gated on every step's required fields being
			// filled in — the browser would refuse this submit anyway (see
			// validateAll()'s note on non-focusable controls in hidden tabs), but
			// silently; this switches to the offending tab and says what's wrong.
			// Deliberately NOT applied to "Save Draft" further down: a draft is
			// allowed to be incomplete, which is the point of one.
			if (mpcrbmStepNav && !mpcrbmStepNav.validateAll()) {
				return;
			}

			$realPublish[0].click();
		});
	}

	// Split-button "Save Draft" dropdown (tour-booking-manager's .ttbm-split-
	// publish component) — always available, on both unpublished AND already-
	// published cars (per explicit request, so a published car can be taken
	// back to Draft from here too, same as the reference plugin).
	var $splitPublish = $('#mpcrbm-edit-topbar-publish');
	var $publishToggle = $('#mpcrbm-edit-topbar-publish-toggle');
	if ($splitPublish.length) {
		$('#mpcrbm-edit-topbar-save-draft').on('click', function (e) {
			e.preventDefault();
			var $realSaveDraft = $('#save-post');
			if ($realSaveDraft.length) {
				// Post is already unpublished (draft/pending/etc.) — WP's own
				// "Save Draft"/"Save as Pending" control already does the
				// right thing, same proxy-click approach as Update/Preview.
				$realSaveDraft[0].click();
			} else {
				// Post is currently published — WP has no native "Save
				// Draft" control to proxy in this state (post_submit_meta_box(),
				// wp-admin/includes/meta-boxes.php only renders #save-post
				// while a post is unpublished). Instead: set the real status
				// <select> (CSS-hidden in #submitdiv but still a functional
				// part of <form id="post">, always with a "draft" <option>
				// regardless of current status) to "draft", then submit via
				// the hidden early "press Enter to save" button — id="save",
				// unconditionally present for every post, publish or not
				// (post_submit_meta_box()'s very first line). NOT via
				// #publish: _wp_translate_postdata() (wp-admin/includes/
				// post.php) forces post_status back to "publish" whenever
				// $_POST['publish'] is set, which would silently undo this.
				$('#post_status').val('draft');
				$('#hidden_post_status').val('draft');
				$('#save')[0].click();
			}
		});

		$publishToggle.on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var isOpen = $splitPublish.toggleClass('is-open').hasClass('is-open');
			$publishToggle.attr('aria-expanded', isOpen ? 'true' : 'false');
		});

		// Click-outside and Escape both close the menu, standard dropdown
		// behavior — bound on document since the menu itself sits outside
		// the click target when either happens.
		$(document).on('click', function (e) {
			if ($splitPublish.hasClass('is-open') && !$splitPublish.is(e.target) && $splitPublish.has(e.target).length === 0) {
				$splitPublish.removeClass('is-open');
				$publishToggle.attr('aria-expanded', 'false');
			}
		});

		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' && $splitPublish.hasClass('is-open')) {
				$splitPublish.removeClass('is-open');
				$publishToggle.attr('aria-expanded', 'false');
			}
		});
	}

	var $topbarPreview = $('#mpcrbm-edit-topbar-preview');
	var $realPreview = $('#post-preview');
	if ($topbarPreview.length && $realPreview.length) {
		$topbarPreview.on('click', function (e) {
			e.preventDefault();
			$realPreview[0].click();
		});
	}

	// Live-sync the header title as the user types, so it doesn't just show
	// the value from when the page first loaded.
	var $topbarTitle = $('#mpcrbm-edit-topbar-title');
	var $realTitleField = $('#title');
	if ($topbarTitle.length && $realTitleField.length) {
		$realTitleField.on('input', function () {
			$topbarTitle.text($(this).val() || '');
		});
	}

	// Sidebar items with a sub-menu (currently just "Car Rental" — see the
	// has-children/is-active logic in MPCRBM_Admin_Shell::render_shell_open()/
	// render_edit_screen_chrome()) toggle that sub-menu open/closed instead of
	// navigating; its sub-menu items already link individually, so the parent
	// link's only job here is expand/collapse. Delegated from document (not
	// $shell below) so this also covers the Add/Edit Car screen's fixed
	// sidebar, which renders the same .mpcrbm-shell-menu markup outside of
	// any .mpcrbm-shell wrapper.
	$(document).on('click', '.mpcrbm-shell-menu > li.has-children > a', function (e) {
		e.preventDefault();
		$(this).closest('li').toggleClass('mpcrbm-shell-submenu-collapsed');
	});

	// Mobile sidebar drawer toggle — delegated on document rather than $shell,
	// since the Add/Edit Car screen's fixed sidebar/topbar
	// (MPCRBM_Admin_Shell::render_edit_screen_chrome()) render outside any
	// .mpcrbm-shell wrapper, and every handler below this point bails out
	// early on that screen (see the "!$shell.length" guard). Toggling a class
	// on <body> (always present) is what the matching CSS at the end of
	// mpcrbm-shell.css keys off for that screen; toggling .mpcrbm-shell's own
	// class too keeps the 7 dashboard pages' existing behavior unchanged.
	$(document).on('click', '.mpcrbm-shell-mobile-trigger', function (e) {
		e.preventDefault();
		$('body').toggleClass('mpcrbm-mobile-menu-open');
		$('.mpcrbm-shell').toggleClass('mobile-menu-open');
	});

	var $shell = $('.mpcrbm-shell');

	if (!$shell.length) {
		return;
	}

	// Sidebar collapse/expand, persisted per-user.
	$shell.on('click', '.mpcrbm-shell-fold-trigger', function (e) {
		e.preventDefault();
		var isCompact = $shell.hasClass('side-menu-compact');
		var nextStyle = isCompact ? 'full' : 'compact';

		$shell.toggleClass('side-menu-compact', !isCompact);

		if (typeof mpcrbmShell === 'undefined') {
			return;
		}

		$.post(mpcrbmShell.ajaxUrl, {
			action: 'mpcrbm_set_menu_layout_style',
			nonce: mpcrbmShell.nonce,
			style: nextStyle
		});
	});

	// Generic in-page tab handler for tabs rendered with data-target="#selector".
	$shell.on('click', '.mpcrbm-shell-page-tab[data-target]', function (e) {
		e.preventDefault();
		var $tab = $(this);
		var target = $tab.data('target');

		$tab.closest('.mpcrbm-shell-page-tabs').find('li').removeClass('is-active');
		$tab.closest('li').addClass('is-active');

		$(target).addClass('is-active').siblings('[data-tab-panel]').removeClass('is-active');
	});
});
