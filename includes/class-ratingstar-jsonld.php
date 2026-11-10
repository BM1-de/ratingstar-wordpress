<?php
/**
 * Server-side Google review stars (JSON-LD).
 *
 * Fetches the ready-made JSON-LD from the RatingStar app and prints it into
 * wp_head on the front page. The endpoint is key-based when an API key is set
 * (rename-proof, preferred), otherwise slug-based. The app builds the schema —
 * LocalBusiness with an aggregateRating only when real ratings exist and the
 * plan allows it, a bare LocalBusiness otherwise — and the plugin passes its
 * content on unchanged (never inventing or zero-ing a rating). The response
 * itself never reaches the page as raw text: it is decoded, checked to be a
 * LocalBusiness object and encoded again. Cached for 6 hours.
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

	/** Timeout of the request to RatingStar in seconds. */
	const HTTP_TIMEOUT = 5;

	/**
	 * Writes <, >, &, ' and " as \uXXXX: a company name or review text that
	 * contains "</script>" or "<!--" cannot end or distort the script block.
	 */
	const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

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

		// Re-encode the validated data so the output is safe inside <script>.
		echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $jsonld, self::JSON_FLAGS ) . '</script>' . "\n";
	}

	/**
	 * Returns the cached JSON-LD as an array, fetching it on a miss. Null when
	 * nothing is configured, the request fails (e.g. 403 tier-gating), the
	 * response is not a LocalBusiness object, or the cache takes no entries.
	 *
	 * @return array|null
	 */
	private function get_jsonld(): ?array {
		$cached = get_transient( self::TRANSIENT_KEY );

		// An empty array marks a remembered failure.
		if ( false !== $cached ) {
			return self::is_local_business( $cached ) ? $cached : null;
		}

		$url = $this->endpoint();

		if ( null === $url ) {
			return null;
		}

		// Store the entry as "failed" before asking RatingStar. If the cache takes
		// no entries (read-only database, failing object cache), the answer could
		// not be stored either and every front page view would ask again — so
		// print nothing and do not ask.
		if ( ! set_transient( self::TRANSIENT_KEY, array(), self::NEGATIVE_TTL ) ) {
			return null;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => self::HTTP_TIMEOUT,
				'user-agent' => 'RatingStar-WP/' . RATINGSTAR_VERSION . '; ' . home_url( '/' ),
			)
		);

		// 403 (json-api-tier / embed-not-authorized) or a transient error: the
		// empty marker stored above stays for a short time; emit nothing, never
		// breaking the page.
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Anything but a LocalBusiness object (error page, broken JSON, another
		// type) is ignored.
		if ( ! self::is_local_business( $data ) ) {
			return null;
		}

		set_transient( self::TRANSIENT_KEY, $data, self::CACHE_TTL );

		return $data;
	}

	/**
	 * Whether the value is a decoded schema.org LocalBusiness object.
	 *
	 * @param mixed $data Decoded response or cached value.
	 */
	private static function is_local_business( $data ): bool {
		return is_array( $data ) && isset( $data['@type'] ) && 'LocalBusiness' === $data['@type'];
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
