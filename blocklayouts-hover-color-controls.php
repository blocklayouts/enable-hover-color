<?php
/**
 * Plugin Name:         Blocklayouts Hover Color Controls
 * Description:         Add hover colors to core blocks in the WordPress block editor (Gutenberg)
 * Version:             1.0.2
 * Requires at least:   6.3
 * Requires PHP:        7.4
 * Author:              Blocklayouts
 * Author URI:          https://blocklayouts.com/
 * License:             GPL-2.0-or-later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         blocklayouts-hover-color-controls
 *
 * @package blocklayouts-hover-color-controls
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'BLOCKLAYOUTS_HOVER_COLOR_CONTROLS_VERSION', '1.0.2' );

/**
 * Enqueue block editor assets.
 *
 * @since 1.0.0
 */
function blocklayouts_hover_color_controls_enqueue_editor_assets() {

	$asset_file = include plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

	wp_enqueue_script(
		'blocklayouts-hover-color-controls-editor-scripts',
		plugin_dir_url( __FILE__ ) . 'build/index.js',
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'blocklayouts_hover_color_controls_enqueue_editor_assets' );

/**
 * Register and enqueue stylesheet for the editor & frontend.
 *
 * @since 1.0.0
 */
function blocklayouts_hover_color_controls_enqueue_block_styles() {
	wp_enqueue_style(
		'blocklayouts-hover-color-controls-block-styles',
		plugins_url( '/build/style.css', __FILE__ ),
		array(),
		BLOCKLAYOUTS_HOVER_COLOR_CONTROLS_VERSION
	);
}
add_action( 'enqueue_block_assets', 'blocklayouts_hover_color_controls_enqueue_block_styles' );

/**
 * Validates a preset color slug.
 *
 * @since 1.0.2
 *
 * @param mixed $slug Preset color slug from the block attributes.
 * @return string The slug, or an empty string when it is not valid.
 */
function blocklayouts_hover_color_controls_sanitize_preset_slug( $slug ) {
	if ( ! is_string( $slug ) || ! preg_match( '/^[a-zA-Z0-9_-]+$/', $slug ) ) {
		return '';
	}
	return $slug;
}

/**
 * Validates a custom color value.
 *
 * Accepts hex colors, rgb()/rgba()/hsl()/hsla() functions, preset color
 * variables and plain color keywords. Anything else is rejected so no extra
 * CSS can be added through the attribute.
 *
 * @since 1.0.2
 *
 * @param mixed $color Custom color from the block attributes.
 * @return string The color, or an empty string when it is not valid.
 */
function blocklayouts_hover_color_controls_sanitize_custom_color( $color ) {
	if ( ! is_string( $color ) ) {
		return '';
	}

	$color    = trim( $color );
	$patterns = array(
		'/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/',
		'/^(?:rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/+-]+(?:deg|rad|turn)?[0-9.,%\s\/+-]*\)$/i',
		'/^var\(--wp--preset--color--[a-zA-Z0-9_-]+\)$/',
		'/^[a-zA-Z]+$/',
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $color ) ) {
			return $color;
		}
	}

	return '';
}

/**
 * Filters the block content to add hover color classes and styles.
 *
 * @param string $block_content The block content.
 * @param array  $block The full block, including name and attributes.
 * @return string Modified block content.
 */
function blocklayouts_hover_color_controls_render_block( $block_content, $block ) {
	// Return early if no block content or attributes.
	if ( empty( $block_content ) || empty( $block['attrs'] ) ) {
		return $block_content;
	}

	$attrs         = $block['attrs'];
	$hover_styles  = array();
	$hover_classes = array();

	// Add transition duration (whole milliseconds only).
	if ( isset( $attrs['hoverTransitionDuration'] ) && is_numeric( $attrs['hoverTransitionDuration'] ) ) {
		$hover_styles[] = sprintf( '--hover-transition-duration: %dms;', absint( $attrs['hoverTransitionDuration'] ) );
	}

	// Add transition timing function (only the values offered in the editor).
	$allowed_timings = array( 'ease', 'linear', 'ease-in', 'ease-out', 'ease-in-out' );
	if ( ! empty( $attrs['hoverTransitionTiming'] ) && in_array( $attrs['hoverTransitionTiming'], $allowed_timings, true ) ) {
		$hover_styles[] = sprintf( '--hover-transition-timing: %s;', $attrs['hoverTransitionTiming'] );
	}

	// Preset and custom attribute for each hover color: CSS variable => [preset attr, custom attr, class].
	$color_settings = array(
		'--hover-color'            => array( 'hoverTextColor', 'customHoverTextColor', 'has-hover__color' ),
		'--hover-background-color' => array( 'hoverBackgroundColor', 'customHoverBackgroundColor', 'has-hover__background-color' ),
		'--hover-border-color'     => array( 'hoverBorderColor', 'customHoverBorderColor', 'has-hover__border-color' ),
	);

	foreach ( $color_settings as $css_var => $setting ) {
		list( $preset_attr, $custom_attr, $class ) = $setting;

		$color = '';
		if ( ! empty( $attrs[ $preset_attr ] ) ) {
			$slug = blocklayouts_hover_color_controls_sanitize_preset_slug( $attrs[ $preset_attr ] );
			if ( '' !== $slug ) {
				$color = sprintf( 'var(--wp--preset--color--%s)', $slug );
			}
		}
		if ( ! empty( $attrs[ $custom_attr ] ) ) {
			$custom = blocklayouts_hover_color_controls_sanitize_custom_color( $attrs[ $custom_attr ] );
			if ( '' !== $custom ) {
				$color = $custom;
			}
		}

		if ( '' !== $color ) {
			$hover_classes[] = $class;
			$hover_styles[]  = sprintf( '%s: %s;', $css_var, $color );
		}
	}

	// If no hover styles, return original content.
	if ( empty( $hover_styles ) ) {
		return $block_content;
	}

	// Create new HTML processor instance.
	$processor = new WP_HTML_Tag_Processor( $block_content );

	// Process the first HTML tag.
	if ( $processor->next_tag() ) {
		// Add hover classes.
		foreach ( array_unique( $hover_classes ) as $class ) {
			$processor->add_class( $class );
		}

		// Add or merge style attribute.
		$style          = implode( ' ', $hover_styles );
		$existing_style = $processor->get_attribute( 'style' );
		if ( is_string( $existing_style ) && '' !== trim( $existing_style ) ) {
			$style = rtrim( trim( $existing_style ), ';' ) . '; ' . $style;
		}
		$processor->set_attribute( 'style', $style );

		return $processor->get_updated_html();
	}

	return $block_content;
}
add_filter( 'render_block', 'blocklayouts_hover_color_controls_render_block', 10, 2 );