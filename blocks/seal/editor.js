/**
 * RatingStar Seal block — editor UI (no build step; classic createElement).
 *
 * Dynamic block: save() returns null, the markup is rendered in PHP. The editor
 * shows a static placeholder (seal.js does not run inside the editor) plus a
 * variant selector in the inspector sidebar.
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

	var VARIANTS = [
		{ label: __( 'Banner', 'ratingstar' ), value: 'banner' },
		{ label: __( 'Circle', 'ratingstar' ), value: 'circle' },
		{ label: __( 'Card', 'ratingstar' ), value: 'card' }
	];

	blocks.registerBlockType( 'ratingstar/seal', {
		edit: function ( props ) {
			var variant = props.attributes.variant || 'banner';

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Seal settings', 'ratingstar' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Variant', 'ratingstar' ),
							value: variant,
							options: VARIANTS,
							onChange: function ( value ) {
								props.setAttributes( { variant: value } );
							}
						} )
					)
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
						__( 'Seal preview', 'ratingstar' ) + ' — ' + variant
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
