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

	/** Supported seal variants (see seal.js). */
	const VARIANTS = array( 'banner', 'circle', 'card' );

	/** Default variant when none/invalid is given. */
	const DEFAULT_VARIANT = 'banner';

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
				'variant' => self::DEFAULT_VARIANT,
				'slug'    => '',
			),
			$atts,
			'ratingstar'
		);

		return $this->render_markup( (string) $atts['variant'], (string) $atts['slug'] );
	}

	/**
	 * Renders the Gutenberg block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ): string {
		$variant = isset( $attributes['variant'] ) ? (string) $attributes['variant'] : self::DEFAULT_VARIANT;

		return $this->render_markup( $variant, '' );
	}

	/**
	 * Builds the embed container and enqueues seal.js.
	 *
	 * @param string $variant       Requested variant.
	 * @param string $slug_override Optional slug overriding the configured one.
	 * @return string
	 */
	private function render_markup( string $variant, string $slug_override ): string {
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

		wp_enqueue_script( self::SCRIPT_HANDLE );

		// When the plugin emits server-side JSON-LD, suppress seal.js's own
		// rich snippet so the AggregateRating is not duplicated.
		$no_rich = empty( $settings['jsonld_enabled'] ) ? '' : ' data-no-richsnippet="1"';

		return sprintf(
			'<div class="rs-seal" data-slug="%1$s" data-variant="%2$s"%3$s></div>',
			esc_attr( $slug ),
			esc_attr( $variant ),
			$no_rich
		);
	}
}
