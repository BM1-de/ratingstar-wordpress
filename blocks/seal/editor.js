/**
 * RatingStar Seal block — editor UI (no build step; classic createElement).
 *
 * Dynamic block: save() returns null, the markup is rendered in PHP. The editor
 * shows a static placeholder (seal.js does not run inside the editor) plus the
 * variant, an optional slug override and — for the floating/footer-bar variants
 * — a position selector, matching the [ratingstar] shortcode's attributes.
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
	var ToggleControl = components.ToggleControl;

	// Full VARIANT_META set (see seal.js): static variants work on all plans;
	// live variants need a 4-star+ plan and degrade to a static seal otherwise.
	var VARIANTS = [
		{ label: __( 'Banner (static)', 'ratingstar' ), value: 'banner' },
		{ label: __( 'Circle (static)', 'ratingstar' ), value: 'circle' },
		{ label: __( 'Card (static)', 'ratingstar' ), value: 'card' },
		{ label: __( 'Profile card (live)', 'ratingstar' ), value: 'profile-card' },
		{ label: __( 'Bar (live)', 'ratingstar' ), value: 'bar' },
		{ label: __( 'Floating badge (live)', 'ratingstar' ), value: 'floating' },
		{ label: __( 'Hero (live)', 'ratingstar' ), value: 'hero' },
		{ label: __( 'Quote (live)', 'ratingstar' ), value: 'quote' },
		{ label: __( 'Carousel (live)', 'ratingstar' ), value: 'carousel' },
		{ label: __( 'Wall (live)', 'ratingstar' ), value: 'wall' },
		{ label: __( 'Footer bar (live)', 'ratingstar' ), value: 'footer-bar' }
	];

	var POSITIONS = [
		{ label: __( 'Default', 'ratingstar' ), value: '' },
		{ label: __( 'Bottom right', 'ratingstar' ), value: 'bottom-right' },
		{ label: __( 'Bottom left', 'ratingstar' ), value: 'bottom-left' },
		{ label: __( 'Top right', 'ratingstar' ), value: 'top-right' },
		{ label: __( 'Top left', 'ratingstar' ), value: 'top-left' }
	];

	var POSITIONABLE = [ 'floating', 'footer-bar' ];

	blocks.registerBlockType( 'ratingstar/seal', {
		edit: function ( props ) {
			var a = props.attributes;
			var variant = a.variant || 'banner';
			var showPosition = POSITIONABLE.indexOf( variant ) !== -1;

			var controls = [
				el( SelectControl, {
					key: 'variant',
					label: __( 'Variant', 'ratingstar' ),
					value: variant,
					options: VARIANTS,
					onChange: function ( value ) { props.setAttributes( { variant: value } ); }
				} ),
				el( TextControl, {
					key: 'slug',
					label: __( 'Profile slug (optional override)', 'ratingstar' ),
					value: a.slug || '',
					placeholder: __( 'Default: slug from Settings → RatingStar', 'ratingstar' ),
					onChange: function ( value ) { props.setAttributes( { slug: value } ); }
				} ),
				el( ToggleControl, {
					key: 'static',
					label: __( 'Static image (no JavaScript)', 'ratingstar' ),
					help: __( 'Render a plain SVG image (banner/circle/card) instead of the live widget — for email/PDF/AMP/no-JS.', 'ratingstar' ),
					checked: !! a.static,
					onChange: function ( value ) { props.setAttributes( { static: value } ); }
				} )
			];

			if ( showPosition ) {
				controls.push( el( SelectControl, {
					key: 'position',
					label: __( 'Position', 'ratingstar' ),
					value: a.position || '',
					options: POSITIONS,
					help: __( 'Only used by the floating and footer-bar variants.', 'ratingstar' ),
					onChange: function ( value ) { props.setAttributes( { position: value } ); }
				} ) );
			}

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
