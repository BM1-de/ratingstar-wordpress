<?php
/**
 * Settings page: RatingStar profile slug + embed key.
 *
 * Registers the "Settings → RatingStar" admin page. The profile slug is verified
 * against the live profile at https://ratingstar.de/t/<slug> on save.
 *
 * @package RatingStar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings screen and its sanitize/validation logic.
 */
class RatingStar_Settings {

	/** Admin page + menu slug. */
	const PAGE_SLUG = 'ratingstar';

	/** Settings group passed to settings_fields(). */
	const GROUP = 'ratingstar_settings_group';

	/**
	 * Hooks the settings screen into the admin.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds the "RatingStar" entry under Settings.
	 */
	public function add_menu(): void {
		add_options_page(
			__( 'RatingStar', 'ratingstar' ),
			__( 'RatingStar', 'ratingstar' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registers the option, section and fields with the Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			RatingStar_Plugin::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(
					'profile_slug'       => '',
					'embed_key'          => '',
					'base_origin'        => RATINGSTAR_API_BASE,
					'jsonld_enabled'     => true,
					'sitewide_variant'   => '',
					'sitewide_position'  => '',
					'sitewide_overrides' => '',
				),
			)
		);

		add_settings_section(
			'ratingstar_main',
			__( 'RatingStar connection', 'ratingstar' ),
			array( $this, 'render_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'profile_slug',
			__( 'Profile slug', 'ratingstar' ),
			array( $this, 'render_field_slug' ),
			self::PAGE_SLUG,
			'ratingstar_main',
			array( 'label_for' => 'ratingstar_profile_slug' )
		);

		add_settings_field(
			'embed_key',
			__( 'Embed key', 'ratingstar' ),
			array( $this, 'render_field_key' ),
			self::PAGE_SLUG,
			'ratingstar_main',
			array( 'label_for' => 'ratingstar_embed_key' )
		);

		add_settings_field(
			'jsonld_enabled',
			__( 'Google review stars', 'ratingstar' ),
			array( $this, 'render_field_jsonld' ),
			self::PAGE_SLUG,
			'ratingstar_main',
			array( 'label_for' => 'ratingstar_jsonld_enabled' )
		);

		add_settings_field(
			'base_origin',
			__( 'RatingStar base URL', 'ratingstar' ),
			array( $this, 'render_field_origin' ),
			self::PAGE_SLUG,
			'ratingstar_main',
			array( 'label_for' => 'ratingstar_base_origin' )
		);

		add_settings_section(
			'ratingstar_sitewide',
			__( 'Site-wide seal', 'ratingstar' ),
			array( $this, 'render_section_sitewide' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'sitewide_variant',
			__( 'Show on every page', 'ratingstar' ),
			array( $this, 'render_field_sitewide_variant' ),
			self::PAGE_SLUG,
			'ratingstar_sitewide',
			array( 'label_for' => 'ratingstar_sitewide_variant' )
		);

		add_settings_field(
			'sitewide_position',
			__( 'Position', 'ratingstar' ),
			array( $this, 'render_field_sitewide_position' ),
			self::PAGE_SLUG,
			'ratingstar_sitewide',
			array( 'label_for' => 'ratingstar_sitewide_position' )
		);

		add_settings_field(
			'sitewide_overrides',
			__( 'Embed attributes (advanced)', 'ratingstar' ),
			array( $this, 'render_field_sitewide_overrides' ),
			self::PAGE_SLUG,
			'ratingstar_sitewide',
			array( 'label_for' => 'ratingstar_sitewide_overrides' )
		);
	}

	/**
	 * Sanitizes the submitted settings and verifies the profile slug remotely.
	 *
	 * The remote check only runs when the slug actually changed, so unrelated saves
	 * don't trigger an HTTP request. A failed check still stores the value (so the
	 * admin can correct a typo) but surfaces an error notice.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array{profile_slug: string, embed_key: string, base_origin: string, jsonld_enabled: bool, sitewide_variant: string, sitewide_position: string, sitewide_overrides: string}
	 */
	public function sanitize( $input ): array {
		$current = RatingStar_Plugin::get_settings();
		$input   = is_array( $input ) ? $input : array();

		$slug   = isset( $input['profile_slug'] ) ? sanitize_title( wp_unslash( $input['profile_slug'] ) ) : '';
		$jsonld = ! empty( $input['jsonld_enabled'] );

		// Base origin: fall back to the default when the field is emptied.
		$origin_raw = isset( $input['base_origin'] ) ? esc_url_raw( trim( (string) wp_unslash( $input['base_origin'] ) ) ) : '';
		$origin     = '' !== $origin_raw ? untrailingslashit( $origin_raw ) : RATINGSTAR_API_BASE;

		// API key: accept only the rs_live_<40> form. Keep the previous key on a
		// malformed value (never store free text); an explicit empty clears it.
		$key_raw = isset( $input['embed_key'] ) ? sanitize_text_field( wp_unslash( $input['embed_key'] ) ) : '';
		$key     = $current['embed_key'];
		if ( '' === $key_raw ) {
			$key = '';
		} elseif ( RatingStar_Plugin::is_valid_key( $key_raw ) ) {
			$key = $key_raw;
		} else {
			add_settings_error(
				RatingStar_Plugin::OPTION_KEY,
				'key_invalid',
				__( 'The API key must look like “rs_live_” followed by 40 letters or digits. Kept the previous value.', 'ratingstar' ),
				'error'
			);
		}

		if ( '' !== $slug && $slug !== $current['profile_slug'] ) {
			$result = $this->validate_slug( $slug, $origin );

			if ( is_wp_error( $result ) ) {
				add_settings_error(
					RatingStar_Plugin::OPTION_KEY,
					'slug_invalid',
					sprintf(
						/* translators: 1: profile slug, 2: error detail */
						__( 'The profile slug “%1$s” could not be verified: %2$s', 'ratingstar' ),
						$slug,
						$result->get_error_message()
					),
					'error'
				);
			} else {
				add_settings_error(
					RatingStar_Plugin::OPTION_KEY,
					'slug_ok',
					sprintf(
						/* translators: %s: RatingStar profile / company name */
						__( 'Connected to RatingStar profile: %s', 'ratingstar' ),
						$result
					),
					'success'
				);
			}
		}

		// Site-wide seal: variant/position must come from the known sets,
		// anything else falls back to off/default. The overrides field stores
		// the raw text; the whitelist filter runs at render time.
		$sitewide_variant = isset( $input['sitewide_variant'] ) ? sanitize_key( wp_unslash( $input['sitewide_variant'] ) ) : '';
		if ( ! in_array( $sitewide_variant, RatingStar_Seal::SITEWIDE_VARIANTS, true ) ) {
			$sitewide_variant = '';
		}

		$sitewide_position = isset( $input['sitewide_position'] ) ? sanitize_key( wp_unslash( $input['sitewide_position'] ) ) : '';
		if ( ! in_array( $sitewide_position, RatingStar_Seal::POSITIONS, true ) ) {
			$sitewide_position = '';
		}

		$sitewide_overrides = isset( $input['sitewide_overrides'] ) ? sanitize_textarea_field( wp_unslash( $input['sitewide_overrides'] ) ) : '';

		// Drop the cached JSON-LD so config/key/slug changes take effect now.
		RatingStar_JsonLd::delete_cache();

		return array(
			'profile_slug'       => $slug,
			'embed_key'          => $key,
			'base_origin'        => $origin,
			'jsonld_enabled'     => $jsonld,
			'sitewide_variant'   => $sitewide_variant,
			'sitewide_position'  => $sitewide_position,
			'sitewide_overrides' => $sitewide_overrides,
		);
	}

	/**
	 * Checks whether a profile exists for the given slug.
	 *
	 * @param string $slug Sanitized profile slug.
	 * @return string|WP_Error Profile display name on success, WP_Error otherwise.
	 */
	private function validate_slug( string $slug, string $origin ) {
		$url = untrailingslashit( $origin ) . '/t/' . rawurlencode( $slug );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 8,
				'redirection' => 2,
				'user-agent' => 'RatingStar-WP/' . RATINGSTAR_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 404 === $code ) {
			return new WP_Error( 'not_found', __( 'no profile exists for this slug.', 'ratingstar' ) );
		}

		if ( 200 !== $code ) {
			return new WP_Error(
				'http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'unexpected response (HTTP %d).', 'ratingstar' ),
					$code
				)
			);
		}

		return $this->extract_profile_name( wp_remote_retrieve_body( $response ), $slug );
	}

	/**
	 * Pulls the human-readable profile name out of the page title for confirmation.
	 *
	 * @param string $body     Response body.
	 * @param string $fallback Value to return when no title is found.
	 * @return string
	 */
	private function extract_profile_name( string $body, string $fallback ): string {
		if ( preg_match( '/<title>(.*?)<\/title>/is', $body, $matches ) ) {
			$title = html_entity_decode( wp_strip_all_tags( $matches[1] ), ENT_QUOTES, 'UTF-8' );
			// Profile titles render as "Company · RatingStar"; drop the suffix.
			$title = trim( (string) preg_replace( '/\s*[·|–-]\s*RatingStar\s*$/u', '', $title ) );

			if ( '' !== $title ) {
				return $title;
			}
		}

		return $fallback;
	}

	/**
	 * Renders the section intro text.
	 */
	public function render_section_intro(): void {
		echo '<p>' . esc_html__( 'Enter your RatingStar profile details. They are used by the seal widget and the Google review stars.', 'ratingstar' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Whitelist this site’s domain in your RatingStar backend (tab “Auslieferung” / Delivery) and verify it with a TXT _ratingstar.<domain> DNS record — otherwise the seal data is blocked with HTTP 403. The key-based output works on all plans.', 'ratingstar' ) . '</p>';
	}

	/**
	 * Renders the profile slug input.
	 */
	public function render_field_slug(): void {
		$settings = RatingStar_Plugin::get_settings();

		printf(
			'<input type="text" id="ratingstar_profile_slug" name="%1$s[profile_slug]" value="%2$s" class="regular-text" placeholder="%3$s" />',
			esc_attr( RatingStar_Plugin::OPTION_KEY ),
			esc_attr( $settings['profile_slug'] ),
			esc_attr__( 'e.g. stadtwerk-tauberfranken', 'ratingstar' )
		);

		echo '<p class="description">';
		printf(
			/* translators: %s: example profile URL pattern */
			esc_html__( 'The slug from your profile URL %s.', 'ratingstar' ),
			'<code>' . esc_html( RatingStar_Plugin::get_origin() . '/t/<slug>' ) . '</code>'
		);
		echo '</p>';
	}

	/**
	 * Renders the embed key input.
	 */
	public function render_field_key(): void {
		$settings = RatingStar_Plugin::get_settings();

		printf(
			'<input type="text" id="ratingstar_embed_key" name="%1$s[embed_key]" value="%2$s" class="regular-text code" autocomplete="off" placeholder="rs_live_…" />',
			esc_attr( RatingStar_Plugin::OPTION_KEY ),
			esc_attr( $settings['embed_key'] )
		);

		echo '<p class="description">' . esc_html__( 'API key (format: “rs_live_” + 40 characters) from your RatingStar backend. Drives the rename-proof key-based endpoints. Optional for now.', 'ratingstar' ) . '</p>';
	}

	/**
	 * Renders the Google-stars (JSON-LD) toggle.
	 */
	public function render_field_jsonld(): void {
		$settings = RatingStar_Plugin::get_settings();

		printf(
			'<label><input type="checkbox" id="ratingstar_jsonld_enabled" name="%1$s[jsonld_enabled]" value="1" %2$s /> %3$s</label>',
			esc_attr( RatingStar_Plugin::OPTION_KEY ),
			checked( ! empty( $settings['jsonld_enabled'] ), true, false ),
			esc_html__( 'Output Google review stars (JSON-LD) on the front page', 'ratingstar' )
		);
		echo '<p class="description">' . esc_html__( 'Adds an AggregateRating snippet to the homepage so Google can show review stars, and disables the seal’s built-in snippet to avoid duplicates.', 'ratingstar' ) . '</p>';
	}

	/**
	 * Renders the base URL (origin) input — advanced, for staging/self-hosted.
	 */
	public function render_field_origin(): void {
		$settings = RatingStar_Plugin::get_settings();

		printf(
			'<input type="url" id="ratingstar_base_origin" name="%1$s[base_origin]" value="%2$s" class="regular-text code" placeholder="%3$s" />',
			esc_attr( RatingStar_Plugin::OPTION_KEY ),
			esc_attr( $settings['base_origin'] ),
			esc_attr( RATINGSTAR_API_BASE )
		);
		echo '<p class="description">';
		printf(
			/* translators: %s: default origin URL */
			esc_html__( 'Advanced: the RatingStar origin every URL is built from. Leave as %s unless you use a staging or self-hosted RatingStar.', 'ratingstar' ),
			'<code>' . esc_html( RATINGSTAR_API_BASE ) . '</code>'
		);
		echo '</p>';
	}

	/**
	 * Renders the site-wide section intro text.
	 */
	public function render_section_sitewide(): void {
		echo '<p>' . esc_html__( 'Show a seal automatically on every page — no theme edit or per-page block needed. Only the self-positioning overlay variants are available here. If you enable this, don’t also place the same variant via block or shortcode, or it will appear twice.', 'ratingstar' ) . '</p>';
	}

	/**
	 * Renders the site-wide variant select.
	 */
	public function render_field_sitewide_variant(): void {
		$settings = RatingStar_Plugin::get_settings();

		$choices = array(
			''             => __( 'Off', 'ratingstar' ),
			'profile-card' => __( 'Profile card (floating)', 'ratingstar' ),
			'footer-bar'   => __( 'Footer bar', 'ratingstar' ),
		);

		printf( '<select id="ratingstar_sitewide_variant" name="%s[sitewide_variant]">', esc_attr( RatingStar_Plugin::OPTION_KEY ) );
		foreach ( $choices as $value => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $settings['sitewide_variant'], $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		echo '<p class="description">' . esc_html__( 'Both are live variants (4-star plan and up); on lower plans seal.js degrades them to a static seal. The floating profile card replaces the former floating badge.', 'ratingstar' ) . '</p>';
	}

	/**
	 * Renders the site-wide position select.
	 */
	public function render_field_sitewide_position(): void {
		$settings = RatingStar_Plugin::get_settings();

		$choices = array(
			''             => __( 'Default (portal setting)', 'ratingstar' ),
			'bottom-right' => __( 'Bottom right', 'ratingstar' ),
			'bottom-left'  => __( 'Bottom left', 'ratingstar' ),
			'top-right'    => __( 'Top right', 'ratingstar' ),
			'top-left'     => __( 'Top left', 'ratingstar' ),
		);

		printf( '<select id="ratingstar_sitewide_position" name="%s[sitewide_position]">', esc_attr( RatingStar_Plugin::OPTION_KEY ) );
		foreach ( $choices as $value => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $settings['sitewide_position'], $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		echo '<p class="description">' . esc_html__( 'Screen corner for the floating profile card; the footer bar ignores this.', 'ratingstar' ) . '</p>';
	}

	/**
	 * Renders the site-wide overrides textarea (data-* passthrough).
	 */
	public function render_field_sitewide_overrides(): void {
		$settings = RatingStar_Plugin::get_settings();

		printf(
			'<textarea id="ratingstar_sitewide_overrides" name="%1$s[sitewide_overrides]" class="large-text code" rows="2" placeholder="%2$s">%3$s</textarea>',
			esc_attr( RatingStar_Plugin::OPTION_KEY ),
			esc_attr( 'footer-bar-bg=#1b1314 footer-bar-text=#ffffff' ),
			esc_textarea( $settings['sitewide_overrides'] )
		);

		echo '<p class="description">' . esc_html__( 'Optional appearance overrides as key=value pairs — copy them from the embed generator in your RatingStar portal; the data- prefix may be included or left out.', 'ratingstar' ) . '</p>';
	}

	/**
	 * Renders the settings page wrapper and form.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors( RatingStar_Plugin::OPTION_KEY ); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
