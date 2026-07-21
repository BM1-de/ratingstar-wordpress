/**
 * RatingStar Seal block — editor UI (no build step; classic createElement).
 *
 * Dynamic block: save() returns null, the markup is rendered in PHP. The
 * editor shows a static placeholder (seal.js does not run inside the editor;
 * a live iframe preview was tried and dropped — it swallowed the clicks
 * meant to select the block) plus the variant, an optional slug override,
 * a placement selector for the profile card and the per-embed override
 * passthrough, matching the [ratingstar] shortcode's attributes.
 */
( function ( blocks, blockEditor, element, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var TextareaControl = components.TextareaControl;
	var ToggleControl = components.ToggleControl;

	// Canonical VARIANT_META set (see seal.js): static variants work on all
	// plans; live variants need a 4-star+ plan and degrade otherwise (told in
	// the select's help text). Labels follow the RatingStar portal naming.
	var VARIANTS = [
		{ label: __( 'Round seal', 'ratingstar' ), value: 'seal-circle' },
		{ label: __( 'Banner seal', 'ratingstar' ), value: 'seal-circle-banner' },
		{ label: __( 'Profile card', 'ratingstar' ), value: 'profile-card' },
		{ label: __( 'Trust bar', 'ratingstar' ), value: 'bar' },
		{ label: __( 'Hero snippet', 'ratingstar' ), value: 'hero' },
		{ label: __( 'Featured quote', 'ratingstar' ), value: 'quote' },
		{ label: __( 'Carousel', 'ratingstar' ), value: 'carousel' },
		{ label: __( 'Wall of love', 'ratingstar' ), value: 'wall' },
		{ label: __( 'Footer bar', 'ratingstar' ), value: 'footer-bar' }
	];

	// Legacy stored values → canonical keys (kept working server-side too).
	// The former floating badge is the profile card fixed to a screen corner.
	var VARIANT_ALIAS = {
		circle: 'seal-circle',
		banner: 'seal-circle-banner',
		card: 'profile-card',
		floating: 'profile-card',
		pro: 'profile-card'
	};

	var POSITIONS = [
		{ label: __( 'Default (portal setting)', 'ratingstar' ), value: '' },
		{ label: __( 'Inline (at this spot)', 'ratingstar' ), value: 'inline' },
		{ label: __( 'Bottom right', 'ratingstar' ), value: 'bottom-right' },
		{ label: __( 'Bottom left', 'ratingstar' ), value: 'bottom-left' },
		{ label: __( 'Top right', 'ratingstar' ), value: 'top-right' },
		{ label: __( 'Top left', 'ratingstar' ), value: 'top-left' }
	];

	var POSITIONABLE = [ 'profile-card' ];

	// Variants that exist as a static SVG. Only these offer the static-image
	// toggle — never a silently different motif for the live-only variants.
	var STATIC_CAPABLE = [ 'seal-circle', 'seal-circle-banner', 'profile-card' ];

	blocks.registerBlockType( 'ratingstar/seal', {
		edit: function ( props ) {
			var a = props.attributes;
			var variant = a.variant || 'seal-circle-banner';
			// Blocks saved before the canonical keys keep working: map the
			// stored legacy value onto its canonical successor for the UI.
			variant = VARIANT_ALIAS[ variant ] || variant;
			var showPosition = POSITIONABLE.indexOf( variant ) !== -1;
			var showStatic = STATIC_CAPABLE.indexOf( variant ) !== -1;

			var controls = [
				el( SelectControl, {
					key: 'variant',
					label: __( 'Variant', 'ratingstar' ),
					value: variant,
					options: VARIANTS,
					help: __( 'The round and banner seal work on every plan; the other variants are live widgets (4-star plan and up — below that, seal.js shows the banner seal instead).', 'ratingstar' ),
					onChange: function ( value ) {
						var next = { variant: value };
						// Live-only variants have no static image — drop the flag
						// instead of silently rendering a different motif.
						if ( STATIC_CAPABLE.indexOf( value ) === -1 ) {
							next.static = false;
						}
						props.setAttributes( next );
					}
				} ),
				el( TextControl, {
					key: 'slug',
					label: __( 'Profile slug (optional override)', 'ratingstar' ),
					value: a.slug || '',
					placeholder: __( 'Default: slug from Settings → RatingStar', 'ratingstar' ),
					onChange: function ( value ) { props.setAttributes( { slug: value } ); }
				} )
			];

			if ( showStatic ) {
				controls.push( el( ToggleControl, {
					key: 'static',
					label: __( 'Static image (no JavaScript)', 'ratingstar' ),
					help: __( 'Render the seal as a plain, linked SVG image instead of the live widget — for email/PDF/AMP/no-JS.', 'ratingstar' ),
					checked: !! a.static,
					onChange: function ( value ) { props.setAttributes( { static: value } ); }
				} ) );
			}

			if ( showPosition ) {
				controls.push( el( SelectControl, {
					key: 'position',
					label: __( 'Placement', 'ratingstar' ),
					value: a.position || '',
					options: POSITIONS,
					help: __( 'Inline renders the card right here; a corner floats it fixed on screen (the former floating badge).', 'ratingstar' ),
					onChange: function ( value ) { props.setAttributes( { position: value } ); }
				} ) );
			}

			// Per-embed overrides: pass-through of the whitelisted data-*
			// attributes produced by the embed generator in the RatingStar
			// portal (seal.js validates the values).
			controls.push( el( TextareaControl, {
				key: 'overrides',
				label: __( 'Embed attributes (advanced)', 'ratingstar' ),
				help: __( 'Optional appearance overrides for this embed, as key=value pairs — e.g. pc-color=gold car-count=4. Copy them from the embed generator in your RatingStar portal; the data- prefix may be included or left out.', 'ratingstar' ),
				value: a.overrides || '',
				onChange: function ( value ) { props.setAttributes( { overrides: value } ); }
			} ) );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el( PanelBody, { title: __( 'Seal settings', 'ratingstar' ), initialOpen: true }, controls )
				),
				el(
					'div',
					useBlockProps( { className: 'rs-seal-editor-preview' } ),
					el(
						'div',
						{
							style: {
								padding: '16px',
								border: '1px dashed #c3c4c7',
								borderRadius: '4px',
								textAlign: 'center',
								color: '#50575e',
								fontSize: '13px'
							}
						},
						el( 'strong', null, '★ RatingStar' ),
						el( 'br' ),
						__( 'Seal preview', 'ratingstar' ) + ' — ' + variant + ( a.slug ? ' (' + a.slug + ')' : '' )
					)
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.element,
	window.wp.components,
	window.wp.i18n
);
