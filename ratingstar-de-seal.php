<?php
/**
 * Plugin Name:       RatingStar.de Seal
 * Plugin URI:        https://ratingstar.de/plugins#wordpress
 * Description:       Embed your RatingStar seal and Google review stars (rich snippets) into WordPress.
 * Version:           1.0.2
 * Requires at least: 6.3
 * Requires PHP:      8.1
 * Author:            Baumgärtner Marketing GmbH
 * Author URI:        https://bm1.de
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ratingstar-de-seal
 *
 * @package RatingStar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RATINGSTAR_VERSION', '1.0.2' );
define( 'RATINGSTAR_FILE', __FILE__ );
define( 'RATINGSTAR_PATH', plugin_dir_path( __FILE__ ) );
define( 'RATINGSTAR_URL', plugin_dir_url( __FILE__ ) );

/** Base URL of the RatingStar platform (profiles, seal, snippet endpoints). */
define( 'RATINGSTAR_API_BASE', 'https://ratingstar.de' );

require_once RATINGSTAR_PATH . 'includes/class-ratingstar-plugin.php';
require_once RATINGSTAR_PATH . 'includes/class-ratingstar-settings.php';
require_once RATINGSTAR_PATH . 'includes/class-ratingstar-seal.php';
require_once RATINGSTAR_PATH . 'includes/class-ratingstar-jsonld.php';

RatingStar_Plugin::instance();
