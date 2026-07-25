(function ($) {
	'use strict';

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
		var $titleLabel = $('<h6 class="mpcrbm-content-editor-label">Vehicle Title</h6>');
		// #post-status-info (word count/autosave-status/resize-handle row) is
		// deliberately NOT relocated here, unlike the other elements — left in
		// its original position inside #post-body-content, which is already
		// hidden via display:none below, so it's simply never shown at all
		// (removed per explicit request).
		$basicInfoCard.find('.mpcrbm-info-card-body').append($titleLabel, $titlediv, $descriptionLabel, $('#postdivrich'));
		$generalInfoTab.prepend($basicInfoCard);
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
			$featuredImageBox.find('.hndle').html('Featured Image <span class="mpcrbm-required">*</span>');

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
				// set. Style it as a fixed-aspect-ratio placeholder instead
				// (same 4:3 ratio the Gallery Images tooltip asks for — see
				// MPCRBM_Gallery_Imges_Settings.php) so the card holds its
				// position either way.
				$thumbLink.addClass('mpcrbm-featured-image-placeholder')
					.html('<i class="fas fa-image"></i><span>Upload image</span>');
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

	// Mobile sidebar drawer toggle.
	$shell.on('click', '.mpcrbm-shell-mobile-trigger', function (e) {
		e.preventDefault();
		$shell.toggleClass('mobile-menu-open');
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
