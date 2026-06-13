<?php
/**
 * Dependencies for editor.js (hand-maintained; no build step).
 *
 * register_block_type() reads this alongside the editorScript so the block
 * editor loads the required wp.* packages before editor.js runs.
 *
 * @package RatingStar
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-block-editor',
		'wp-element',
		'wp-components',
		'wp-i18n',
	),
	'version'      => '0.1.0',
);
