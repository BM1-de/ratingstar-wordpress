<?php
/**
 * Server-side Google review stars (JSON-LD AggregateRating).
 *
 * Fetches the public profile data from ratingstar.de/seal/<slug>.json (cached
 * in a transient) and prints an Organization AggregateRating snippet into
 * wp_head on the front page — only when there are actual ratings.
 *
 * @package RatingStar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs the AggregateRating JSON-LD for the configured RatingStar profile.
 */
class RatingStar_JsonLd {

	/** Transient key prefix for cached seal data. */
	const TRANSIENT_PREFIX = 'ratingstar_seal_';

	/** How long to cache a successful profile fetch. */
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/** Shorter cache for failed fetches, so we retry sooner. */
	const NEGATIVE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Hooks the JSON-LD output into wp_head.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'output' ), 20 );
	}

	/**
	 * Prints the JSON-LD snippet on the front page when appropriate.
	 */
	public function output(): void {
		// An organisation-level rating belongs on the front page once, not on
		// every sub-page (which would duplicate the AggregateRating).
		if ( ! is_front_page() ) {
			return;
		}

		$schema = $this->build_schema();

		if ( null === $schema ) {
			return;
		}

		// wp_json_encode escapes "/" to "<\/script>", so this is safe in <script>.
		echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
	}

	/**
	 * Builds the AggregateRating schema, or null when it should not be shown
	 * (feature off, no slug, no data, or no actual ratings).
	 *
	 * @return array|null
	 */
	public function build_schema(): ?array {
		$settings = RatingStar_Plugin::get_settings();

		if ( empty( $settings['jsonld_enabled'] ) || '' === $settings['profile_slug'] ) {
			return null;
		}

		$data = $this->get_data( $settings['profile_slug'] );

		if ( empty( $data ) ) {
			return null;
		}

		$count  = isset( $data['count'] ) ? (int) $data['count'] : 0;
		$rating = isset( $data['rating'] ) ? (float) $data['rating'] : 0.0;

		// Google requires a real rating; never emit a 0-value AggregateRating.
		if ( $count < 1 || $rating <= 0 ) {
			return null;
		}

		$tenant = ( isset( $data['tenant'] ) && is_array( $data['tenant'] ) ) ? $data['tenant'] : array();

		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'Organization',
			'name'            => ! empty( $tenant['name'] ) ? $tenant['name'] : get_bloginfo( 'name' ),
			'url'             => home_url( '/' ),
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => round( $rating, 1 ),
				'reviewCount' => $count,
				'bestRating'  => 5,
				'worstRating' => 1,
			),
		);

		if ( ! empty( $tenant['logo'] ) ) {
			$schema['logo'] = $tenant['logo'];
		}

		return $schema;
	}

	/**
	 * Returns the cached profile data, fetching it from RatingStar on a miss.
	 *
	 * @param string $slug Profile slug.
	 * @return array Profile data, or an empty array on failure.
	 */
	private function get_data( string $slug ): array {
		$key    = self::TRANSIENT_PREFIX . md5( $slug );
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : array();
		}

		$url = trailingslashit( RATINGSTAR_API_BASE ) . 'seal/' . rawurlencode( $slug ) . '.json';

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 8,
				'user-agent' => 'RatingStar-WP/' . RATINGSTAR_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $key, array(), self::NEGATIVE_TTL );
			return array();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			set_transient( $key, array(), self::NEGATIVE_TTL );
			return array();
		}

		set_transient( $key, $data, self::CACHE_TTL );

		return $data;
	}
}
