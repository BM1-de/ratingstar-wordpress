<?php
/**
 * Seal widget: [ratingstar] shortcode + Gutenberg block.
 *
 * Both render the official RatingStar embed container
 * (<div class="rs-seal" data-slug data-variant>) and load seal.js from
 * ratingstar.de only on pages that actually contain a seal.
 *
 * @package RatingStar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the seal shortcode and block and handles conditional asset loading.
 */
class RatingStar_Seal {

	/**
	 * Supported seal variants (seal.js SealConfig::VARIANT_META). Static variants
	 * work on all tiers (canonical keys + back-compat aliases); live variants
	 * (4-star+) are rendered by seal.js and degrade to a static seal on lower
	 * tiers. Canonical keys listed first.
	 */
	const VARIANTS = array(
		// Static (all tiers) — canonical keys + aliases.
		'seal-circle',
		'seal-circle-banner',
		'circle',
		'banner',
		'card',
		// Live (4-star and up).
		'profile-card',
		'bar',
		'floating',
		'hero',
		'quote',
		'carousel',
		'wall',
		'footer-bar',
	);

	/** Default variant when none/invalid is given. */
	const DEFAULT_VARIANT = 'banner';

	/** Variants that honour data-position (floating corner / footer bar). */
	const POSITIONABLE = array( 'floating', 'footer-bar' );

	/** Allowed data-position values. */
	const POSITIONS = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );

	/** Variants available as a static SVG (no-JS fallback). */
	const STATIC_VARIANTS = array( 'banner', 'circle', 'card' );

	/** Registered handle for the external seal.js. */
	const SCRIPT_HANDLE = 'ratingstar-seal';

	/**
	 * Hooks shortcode, block and assets.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_filter( 'script_loader_tag', array( $this, 'make_async' ), 10, 2 );
	}

	/**
	 * Registers (but does not enqueue) the external seal.js so it can be
	 * enqueued on demand from the shortcode/block render.
	 */
	public function register_assets(): void {
		wp_register_script(
			self::SCRIPT_HANDLE,
			RatingStar_Plugin::get_origin() . '/seal.js',
			array(),
			null,
			true
		);
	}

	/**
	 * Adds the async attribute to the seal.js tag (WP 6.0-compatible).
	 *
	 * @param string $tag    The full script tag.
	 * @param string $handle The script handle.
	 * @return string
	 */
	public function make_async( $tag, $handle ): string {
		if ( self::SCRIPT_HANDLE === $handle && false === strpos( $tag, ' async' ) ) {
			$tag = str_replace( ' src=', ' async src=', $tag );
		}

		return $tag;
	}

	/**
	 * Registers the shortcode and the block on init.
	 */
	public function on_init(): void {
		add_shortcode( 'ratingstar', array( $this, 'render_shortcode' ) );

		if ( function_exists( 'register_block_type' ) ) {
			register_block_type(
				RATINGSTAR_PATH . 'blocks/seal',
				array( 'render_callback' => array( $this, 'render_block' ) )
			);
		}
	}

	/**
	 * Renders the [ratingstar] shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'variant'  => self::DEFAULT_VARIANT,
				'slug'     => '',
				'position' => '',
				'static'   => '',
			),
			$atts,
			'ratingstar'
		);

		return $this->render_markup(
			(string) $atts['variant'],
			(string) $atts['slug'],
			(string) $atts['position'],
			filter_var( $atts['static'], FILTER_VALIDATE_BOOLEAN )
		);
	}

	/**
	 * Renders the Gutenberg block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ): string {
		$variant  = isset( $attributes['variant'] ) ? (string) $attributes['variant'] : self::DEFAULT_VARIANT;
		$slug     = isset( $attributes['slug'] ) ? (string) $attributes['slug'] : '';
		$position = isset( $attributes['position'] ) ? (string) $attributes['position'] : '';
		$static   = ! empty( $attributes['static'] );

		return $this->render_markup( $variant, $slug, $position, $static );
	}

	/**
	 * Builds the embed container and enqueues seal.js.
	 *
	 * @param string $variant       Requested variant.
	 * @param string $slug_override Optional slug overriding the configured one.
	 * @return string
	 */
	private function render_markup( string $variant, string $slug_override, string $position = '', bool $static = false ): string {
		$variant  = in_array( $variant, self::VARIANTS, true ) ? $variant : self::DEFAULT_VARIANT;
		$settings = RatingStar_Plugin::get_settings();
		$slug     = '' !== $slug_override ? sanitize_title( $slug_override ) : $settings['profile_slug'];

		if ( '' === $slug ) {
			// Only nudge logged-in admins; show nothing to visitors.
			if ( current_user_can( 'manage_options' ) ) {
				return '<p class="rs-seal-notice">' . esc_html__( 'RatingStar: set your profile slug under Settings → RatingStar.', 'ratingstar' ) . '</p>';
			}

			return '';
		}

		// Static SVG fallback (no JavaScript) — for email/PDF/AMP/JS-off contexts.
		if ( $static ) {
			$svg_variant = in_array( $variant, self::STATIC_VARIANTS, true ) ? $variant : 'banner';
			$src         = RatingStar_Plugin::get_origin() . '/seal/' . rawurlencode( $slug ) . '.svg?variant=' . rawurlencode( $svg_variant );

			return sprintf(
				'<img class="rs-seal-static" src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
				esc_url( $src ),
				esc_attr__( 'RatingStar rating seal', 'ratingstar' )
			);
		}

		wp_enqueue_script( self::SCRIPT_HANDLE );

		// data-position is only meaningful for the floating / footer-bar variants.
		$pos_attr = '';
		if ( '' !== $position && in_array( $variant, self::POSITIONABLE, true ) && in_array( $position, self::POSITIONS, true ) ) {
			$pos_attr = sprintf( ' data-position="%s"', esc_attr( $position ) );
		}

		// When the plugin emits server-side JSON-LD, suppress seal.js's own
		// rich snippet so the AggregateRating is not duplicated.
		$no_rich = empty( $settings['jsonld_enabled'] ) ? '' : ' data-no-richsnippet="1"';

		return sprintf(
			'<div class="rs-seal" data-slug="%1$s" data-variant="%2$s"%3$s%4$s></div>',
			esc_attr( $slug ),
			esc_attr( $variant ),
			$pos_attr,
			$no_rich
		);
	}
}
