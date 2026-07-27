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
	 * Canonical seal variants (seal.js SealConfig::VARIANT_META). Static
	 * variants work on all plans; live variants (4-star+) are rendered by
	 * seal.js and degrade to a static seal on lower plans. Legacy values are
	 * accepted via VARIANT_ALIAS.
	 */
	const VARIANTS = array(
		// Static (all plans).
		'seal-circle',
		'seal-circle-banner',
		// Live (4-star and up).
		'profile-card',
		'bar',
		'hero',
		'quote',
		'carousel',
		'wall',
		'footer-bar',
	);

	/**
	 * Legacy variant values → canonical keys (SealConfig::VARIANT_ALIAS).
	 * The former stand-alone floating badge is now the profile card fixed to
	 * a screen corner via its position setting.
	 */
	const VARIANT_ALIAS = array(
		'circle'   => 'seal-circle',
		'banner'   => 'seal-circle-banner',
		'card'     => 'profile-card',
		'floating' => 'profile-card',
		'pro'      => 'profile-card',
	);

	/** Default variant when none/invalid is given. */
	const DEFAULT_VARIANT = 'seal-circle-banner';

	/** Variants that honour data-position (profile card: inline or screen corner). */
	const POSITIONABLE = array( 'profile-card' );

	/**
	 * Variants that may be emitted site-wide from the settings: the overlays
	 * that position themselves fixed to the viewport, independent of where
	 * their container sits in the markup.
	 */
	const SITEWIDE_VARIANTS = array( 'profile-card', 'footer-bar' );

	/** Allowed data-position values (profile card placement). */
	const POSITIONS = array( 'inline', 'bottom-right', 'bottom-left', 'top-right', 'top-left' );

	/**
	 * Canonical variant → variant name of the static SVG endpoint
	 * (?variant= is its own namespace: banner | circle | card).
	 */
	const STATIC_SVG_MAP = array(
		'seal-circle'        => 'circle',
		'seal-circle-banner' => 'banner',
		'profile-card'       => 'card',
	);

	/**
	 * Whitelisted per-embed override keys (seal-embed.js EMBED_OVERRIDES).
	 * Cosmetic only — camelCase key becomes a kebab-cased data attribute on the
	 * embed div (pcColor → data-pc-color). Value validation (enums, ranges,
	 * hex colours) happens in seal.js; invalid values fall back silently.
	 * Tier gates and the §5b transparency hint are server-controlled on
	 * purpose and must never appear here.
	 */
	const EMBED_OVERRIDES = array(
		'size',
		'pcPosition',
		'pcWidth',
		'pcShowCount',
		'pcColor',
		'pcMobile',
		'pcClick',
		'barShowCount',
		'barShowVerified',
		'heroShowCount',
		'heroShowVerified',
		'quotePick',
		'quoteWidth',
		'carCount',
		'carRotate',
		'carMin',
		'carWidth',
		'wallCols',
		'wallTotal',
		'wallMin',
		'wallSort',
		'wallWidth',
		'footerBarBg',
		'footerBarText',
		'footerBarLinkColor',
		'footerBarLinkHover',
		'footerBarPosition',
		'reviewsOnlyNamed',
		'reviewsOnlyWithComment',
	);

	/** Registered handle for the external seal.js. */
	const SCRIPT_HANDLE = 'ratingstar-seal';

	/**
	 * Hooks shortcode, block and assets.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_filter( 'script_loader_tag', array( $this, 'make_async' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'render_sitewide' ) );
	}

	/**
	 * Prints the site-wide seal configured under Settings → RatingStar into
	 * wp_footer — no theme edit or per-page block needed. Restricted to the
	 * self-positioning variants; runs before wp_print_footer_scripts (20), so
	 * the seal.js enqueue inside render_markup() still makes it out.
	 */
	public function render_sitewide(): void {
		$settings = RatingStar_Plugin::get_settings();
		$variant  = (string) $settings['sitewide_variant'];

		if ( '' === $variant || ! in_array( $variant, self::SITEWIDE_VARIANTS, true ) ) {
			return;
		}

		$markup = $this->render_markup(
			$variant,
			'',
			(string) $settings['sitewide_position'],
			false,
			$this->parse_overrides_text( (string) $settings['sitewide_overrides'] )
		);

		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_markup() escapes every attribute.
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
	 * Besides variant/slug/position/static, every whitelisted per-embed
	 * override is accepted as its own kebab-cased attribute, e.g.
	 * [ratingstar variant="carousel" car-width="s" car-count="4"].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ): string {
		$defaults = array(
			'variant'  => self::DEFAULT_VARIANT,
			'slug'     => '',
			'position' => '',
			'static'   => '',
		);

		foreach ( self::EMBED_OVERRIDES as $camel ) {
			$defaults[ self::kebab_case( $camel ) ] = '';
		}

		$atts = shortcode_atts( $defaults, $atts, 'ratingstar-de-seal' );

		$overrides = array();
		foreach ( self::EMBED_OVERRIDES as $camel ) {
			$kebab = self::kebab_case( $camel );
			if ( '' !== (string) $atts[ $kebab ] ) {
				$overrides[ $kebab ] = (string) $atts[ $kebab ];
			}
		}

		return $this->render_markup(
			(string) $atts['variant'],
			(string) $atts['slug'],
			(string) $atts['position'],
			filter_var( $atts['static'], FILTER_VALIDATE_BOOLEAN ),
			$overrides
		);
	}

	/**
	 * Renders the Gutenberg block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ): string {
		$variant   = isset( $attributes['variant'] ) ? (string) $attributes['variant'] : self::DEFAULT_VARIANT;
		$slug      = isset( $attributes['slug'] ) ? (string) $attributes['slug'] : '';
		$position  = isset( $attributes['position'] ) ? (string) $attributes['position'] : '';
		$static    = ! empty( $attributes['static'] );
		$overrides = $this->parse_overrides_text( isset( $attributes['overrides'] ) ? (string) $attributes['overrides'] : '' );

		return $this->render_markup( $variant, $slug, $position, $static, $overrides );
	}

	/**
	 * Converts a camelCase override key to its kebab-cased attribute form
	 * (pcColor → pc-color, as used by data-pc-color).
	 */
	private static function kebab_case( string $key ): string {
		return strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $key ) );
	}

	/**
	 * Parses the block's free-text override field into a whitelisted
	 * kebab-key => value map. Accepts the attribute snippet as produced by the
	 * embed generator in the RatingStar portal — keys with or without the
	 * data- prefix, kebab or camelCase, values bare or quoted:
	 * pc-color=gold, data-car-count="4", reviewsOnlyNamed=1.
	 *
	 * @param string $text Raw field value.
	 * @return array<string, string>
	 */
	private function parse_overrides_text( string $text ): array {
		if ( '' === trim( $text ) ) {
			return array();
		}

		$allowed = array();
		foreach ( self::EMBED_OVERRIDES as $camel ) {
			$allowed[ self::kebab_case( $camel ) ] = true;
		}

		$overrides = array();

		if ( preg_match_all( '/(?:data-)?([a-zA-Z][a-zA-Z0-9-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))/', $text, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$key = self::kebab_case( $match[1] );

				if ( ! isset( $allowed[ $key ] ) ) {
					continue;
				}

				// Exactly one of the three value groups is non-empty.
				$value = '';
				for ( $i = 4; $i >= 2; $i-- ) {
					if ( isset( $match[ $i ] ) && '' !== $match[ $i ] ) {
						$value = $match[ $i ];
						break;
					}
				}

				if ( '' !== $value ) {
					$overrides[ $key ] = $value;
				}
			}
		}

		return $overrides;
	}

	/**
	 * Builds the embed container and enqueues seal.js.
	 *
	 * @param string $variant       Requested variant.
	 * @param string $slug_override Optional slug overriding the configured one.
	 * @param string $position      Optional data-position (floating/footer-bar).
	 * @param bool   $static        Render the static SVG fallback instead.
	 * @param array  $overrides     Whitelisted per-embed overrides (kebab-key => value).
	 * @return string
	 */
	private function render_markup( string $variant, string $slug_override, string $position = '', bool $static = false, array $overrides = array() ): string {
		// Normalize legacy values (banner, circle, card, floating, pro) onto
		// the canonical keys before validating.
		$variant  = self::VARIANT_ALIAS[ $variant ] ?? $variant;
		$variant  = in_array( $variant, self::VARIANTS, true ) ? $variant : self::DEFAULT_VARIANT;
		$settings = RatingStar_Plugin::get_settings();
		$slug     = '' !== $slug_override ? sanitize_title( $slug_override ) : $settings['profile_slug'];

		if ( '' === $slug ) {
			// Only nudge logged-in admins; show nothing to visitors.
			if ( current_user_can( 'manage_options' ) ) {
				return '<p class="rs-seal-notice">' . esc_html__( 'RatingStar: set your profile slug under Settings → RatingStar.', 'ratingstar-de-seal' ) . '</p>';
			}

			return '';
		}

		// Static SVG fallback (no JavaScript) — for email/PDF/AMP/JS-off
		// contexts. Only the variants with a static counterpart support it;
		// for the live-only variants the flag is ignored (never a silently
		// different motif) and the live widget renders instead. The image is
		// rendered and cached by the app (plus CDN / browser caching via
		// long-lived cache headers) and linked to the public profile like
		// every live widget (attribution, no nofollow).
		if ( $static && isset( self::STATIC_SVG_MAP[ $variant ] ) ) {
			$svg_variant = self::STATIC_SVG_MAP[ $variant ];
			$origin      = RatingStar_Plugin::get_origin();
			$src         = $origin . '/seal/' . rawurlencode( $slug ) . '.svg?variant=' . rawurlencode( $svg_variant );

			return sprintf(
				'<a class="rs-seal-static-link" href="%1$s" target="_blank" rel="noopener"><img class="rs-seal-static" src="%2$s" alt="%3$s" loading="lazy" decoding="async" /></a>',
				esc_url( $origin . '/t/' . rawurlencode( $slug ) ),
				esc_url( $src ),
				esc_attr__( 'RatingStar rating seal', 'ratingstar-de-seal' )
			);
		}

		wp_enqueue_script( self::SCRIPT_HANDLE );

		// data-position is only meaningful for the floating / footer-bar variants.
		$pos_attr = '';
		if ( '' !== $position && in_array( $variant, self::POSITIONABLE, true ) && in_array( $position, self::POSITIONS, true ) ) {
			$pos_attr = sprintf( ' data-position="%s"', esc_attr( $position ) );
		}

		// Per-embed overrides travel as individual data attributes; seal.js
		// validates the values (invalid ones fall back silently).
		$override_attrs = '';
		foreach ( $overrides as $kebab => $value ) {
			$override_attrs .= sprintf( ' data-%s="%s"', $kebab, esc_attr( $value ) );
		}

		// When the plugin emits server-side JSON-LD, suppress seal.js's own
		// rich snippet so the AggregateRating is not duplicated.
		$no_rich = empty( $settings['jsonld_enabled'] ) ? '' : ' data-no-richsnippet="1"';

		return sprintf(
			'<div class="rs-seal" data-slug="%1$s" data-variant="%2$s"%3$s%4$s%5$s></div>',
			esc_attr( $slug ),
			esc_attr( $variant ),
			$pos_attr,
			$override_attrs,
			$no_rich
		);
	}
}
