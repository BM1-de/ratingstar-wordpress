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
					'profile_slug' => '',
					'embed_key'    => '',
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
	}

	/**
	 * Sanitizes the submitted settings and verifies the profile slug remotely.
	 *
	 * The remote check only runs when the slug actually changed, so unrelated saves
	 * don't trigger an HTTP request. A failed check still stores the value (so the
	 * admin can correct a typo) but surfaces an error notice.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array{profile_slug: string, embed_key: string}
	 */
	public function sanitize( $input ): array {
		$current = RatingStar_Plugin::get_settings();
		$input   = is_array( $input ) ? $input : array();

		$slug = isset( $input['profile_slug'] ) ? sanitize_title( wp_unslash( $input['profile_slug'] ) ) : '';
		$key  = isset( $input['embed_key'] ) ? sanitize_text_field( wp_unslash( $input['embed_key'] ) ) : '';

		if ( '' !== $slug && $slug !== $current['profile_slug'] ) {
			$result = $this->validate_slug( $slug );

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

		return array(
			'profile_slug' => $slug,
			'embed_key'    => $key,
		);
	}

	/**
	 * Checks whether a profile exists for the given slug.
	 *
	 * @param string $slug Sanitized profile slug.
	 * @return string|WP_Error Profile display name on success, WP_Error otherwise.
	 */
	private function validate_slug( string $slug ) {
		$url = trailingslashit( RATINGSTAR_API_BASE ) . 't/' . rawurlencode( $slug );

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
			'<code>' . esc_html( trailingslashit( RATINGSTAR_API_BASE ) . 't/<slug>' ) . '</code>'
		);
		echo '</p>';
	}

	/**
	 * Renders the embed key input.
	 */
	public function render_field_key(): void {
		$settings = RatingStar_Plugin::get_settings();

		printf(
			'<input type="text" id="ratingstar_embed_key" name="%1$s[embed_key]" value="%2$s" class="regular-text" autocomplete="off" />',
			esc_attr( RatingStar_Plugin::OPTION_KEY ),
			esc_attr( $settings['embed_key'] )
		);

		echo '<p class="description">' . esc_html__( 'Embed / API key for the Google review stars snippet (Seal & widget codes in your RatingStar backend).', 'ratingstar' ) . '</p>';
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
