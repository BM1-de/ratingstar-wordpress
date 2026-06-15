<?php
/**
 * Core plugin bootstrap.
 *
 * @package RatingStar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * Loads and wires up the feature modules and exposes the stored settings to them.
 */
final class RatingStar_Plugin {

	/** Option key under which all plugin settings are stored. */
	const OPTION_KEY = 'ratingstar_settings';

	private static ?RatingStar_Plugin $instance = null;

	public RatingStar_Settings $settings;

	public RatingStar_Seal $seal;

	public RatingStar_JsonLd $jsonld;

	/**
	 * Returns the single plugin instance, creating it on first call.
	 */
	public static function instance(): RatingStar_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->settings = new RatingStar_Settings();
		$this->settings->register();

		$this->seal = new RatingStar_Seal();
		$this->seal->register();

		$this->jsonld = new RatingStar_JsonLd();
		$this->jsonld->register();

		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Loads translations for self-hosted installs (WordPress.org loads them automatically).
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'ratingstar',
			false,
			dirname( plugin_basename( RATINGSTAR_FILE ) ) . '/languages'
		);
	}

	/**
	 * Returns the stored settings merged with defaults.
	 *
	 * @return array{profile_slug: string, embed_key: string}
	 */
	public static function get_settings(): array {
		$defaults = array(
			'profile_slug'   => '',
			'embed_key'      => '',
			'base_origin'    => RATINGSTAR_API_BASE,
			'jsonld_enabled' => true,
		);

		$stored = get_option( self::OPTION_KEY, array() );

		return wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults );
	}

	/**
	 * Returns the configured RatingStar base origin without a trailing slash.
	 * Every API URL must be built from this — never hard-code a host.
	 */
	public static function get_origin(): string {
		$settings = self::get_settings();
		$origin   = '' !== $settings['base_origin'] ? $settings['base_origin'] : RATINGSTAR_API_BASE;

		return untrailingslashit( $origin );
	}

	/**
	 * Validates the API key format: "rs_live_" followed by 40 alphanumeric chars.
	 */
	public static function is_valid_key( string $key ): bool {
		return 1 === preg_match( '/^rs_live_[A-Za-z0-9]{40}$/', $key );
	}
}
