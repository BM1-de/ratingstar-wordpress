<?php
/**
 * Server-side Google review stars (JSON-LD).
 *
 * Fetches the ready-made JSON-LD from the RatingStar app and prints it into
 * wp_head on the front page. The endpoint is key-based when an API key is set
 * (rename-proof, preferred), otherwise slug-based. The app builds the schema —
 * LocalBusiness with an aggregateRating only when real ratings exist and the
 * plan allows it, a bare LocalBusiness otherwise — and the plugin passes it
 * through unchanged (never inventing or zero-ing a rating). Cached for 6 hours.
 *
 * @package RatingStar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs the RatingStar LocalBusiness JSON-LD for the configured profile.
 */
class RatingStar_JsonLd {

	/** Single transient holding the fetched JSON-LD (one profile per site). */
	const TRANSIENT_KEY = 'ratingstar_jsonld';

	/** Cache lifetime for a successful fetch (matches the contract's ~6h). */
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/** Shorter cache for failures (403/tier or transient errors), to retry sooner. */
	const NEGATIVE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Hooks the JSON-LD output into wp_head.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'output' ), 20 );
	}

	/**
	 * Prints the JSON-LD on the front page when the feature is enabled.
	 */
	public function output(): void {
		$settings = RatingStar_Plugin::get_settings();

		if ( empty( $settings['jsonld_enabled'] ) ) {
			return;
		}

		// One organisation-level snippet, on the front page only (never duplicated
		// across sub-pages).
		if ( ! is_front_page() ) {
			return;
		}

		$jsonld = $this->get_jsonld();

		if ( null === $jsonld ) {
			return;
		}

		// Re-encode the validated data so the output is safe inside <script>
		// (wp_json_encode escapes "/", preventing a "</script>" break-out).
		echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script>' . "\n";
	}

	/**
	 * Returns the cached JSON-LD as an array, fetching it on a miss. Null when
	 * nothing is configured, the request fails (e.g. 403 tier-gating), or the
	 * response is not valid schema.
	 *
	 * @return array|null
	 */
	private function get_jsonld(): ?array {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( false !== $cached ) {
			return ( is_array( $cached ) && array() !== $cached ) ? $cached : null;
		}

		$url = $this->endpoint();

		if ( null === $url ) {
			return null;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 8,
				'user-agent' => 'RatingStar-WP/' . RATINGSTAR_VERSION . '; ' . home_url( '/' ),
			)
		);

		// 403 (json-api-tier / embed-not-authorized) or a transient error: cache
		// an empty marker briefly and emit nothing, never breaking the page.
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( self::TRANSIENT_KEY, array(), self::NEGATIVE_TTL );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Must look like schema.org JSON-LD (has an @type); otherwise ignore.
		if ( ! is_array( $data ) || empty( $data['@type'] ) ) {
			set_transient( self::TRANSIENT_KEY, array(), self::NEGATIVE_TTL );
			return null;
		}

		set_transient( self::TRANSIENT_KEY, $data, self::CACHE_TTL );

		return $data;
	}

	/**
	 * Builds the JSON-LD endpoint URL. Key-based when an API key is configured
	 * (rename-proof, works on all plans), otherwise slug-based.
	 *
	 * @return string|null
	 */
	private function endpoint(): ?string {
		$settings = RatingStar_Plugin::get_settings();
		$origin   = RatingStar_Plugin::get_origin();

		if ( '' !== $settings['embed_key'] ) {
			return $origin . '/seal/k/' . rawurlencode( $settings['embed_key'] ) . '.jsonld';
		}

		if ( '' !== $settings['profile_slug'] ) {
			return $origin . '/seal/' . rawurlencode( $settings['profile_slug'] ) . '.jsonld';
		}

		return null;
	}

	/**
	 * Clears the cached JSON-LD (called when settings change so config/key/slug
	 * changes take effect without waiting for the TTL).
	 */
	public static function delete_cache(): void {
		delete_transient( self::TRANSIENT_KEY );
	}
}
