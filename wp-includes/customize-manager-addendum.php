<?php
/**
 * Extensions to WP_Customize_Manager.
 *
 * @package WordPress
 */

add_action( 'customize_register', '_better_code_editing_amend_custom_css_help_text', 11 );
add_action( 'customize_controls_enqueue_scripts', '_better_code_editing_customize_controls_enqueue_scripts' );
add_action( 'customize_controls_print_footer_scripts', '_better_code_editing_amend_customize_pane_settings', 1001 );

/**
 * Set up code editor for Custom CSS.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function _better_code_editing_amend_custom_css_help_text( WP_Customize_Manager $wp_customize ) {
	$wp_customize->custom_css_code_editor_settings = wp_code_editor_settings( array(
		'file' => 'custom.css',
	) );

	if ( empty( $wp_customize->custom_css_code_editor_settings ) ) {
		return;
	}

	$section = $wp_customize->get_section( 'custom_css' );
	if ( ! $section ) {
		return;
	}

	$section->description = '<p>';
	$section->description .= __( 'Add your own CSS code here to customize the appearance and layout of your site.', 'better-code-editing' );
	$section->description .= sprintf(
		' <a href="%1$s" class="external-link" target="_blank">%2$s<span class="screen-reader-text">%3$s</span></a>',
		esc_url( __( 'https://codex.wordpress.org/CSS', 'default' ) ),
		__( 'Learn more about CSS', 'default' ),
		/* translators: accessibility text */
		__( '(opens in a new window)', 'default' )
	);
	$section->description .= '</p>';

	$section->description .= '<p>' . __( 'When using a keyboard to navigate:', 'better-code-editing' ) . '</p>';
	$section->description .= '<ul>';
	$section->description .= '<li>' . __( 'In the CSS edit field, Tab enters a tab character.', 'better-code-editing' ) . '</li>';
	$section->description .= '<li>' . __( 'To move keyboard focus, press Esc then Tab for the next element, or Esc then Shift+Tab for the previous element.', 'better-code-editing' ) . '</li>';
	$section->description .= '</ul>';

	$section->description .= '<p>';
	$section->description .= sprintf(
		/* translators: placeholder is link to user profile */
		__( 'The edit field automatically highlights code syntax. You can disable this in your %s to work in plain text mode.', 'better-code-editing' ),
		sprintf(
			' <a href="%1$s" class="external-link" target="_blank">%2$s<span class="screen-reader-text">%3$s</span></a>',
			esc_url( get_edit_profile_url() . '#syntax_highlighting' ),
			__( 'user profile', 'better-code-editing' ),
			/* translators: accessibility text */
			__( '(opens in a new window)', 'default' )
		)
	);
	$section->description .= '</p>';
}

/**
 * Enqueue assets for Customizer.
 *
 * @global WP_Customize_Manager $wp_customize
 */
function _better_code_editing_customize_controls_enqueue_scripts() {
	global $wp_customize;
	if ( ! empty( $wp_customize->custom_css_code_editor_settings ) ) {
		wp_enqueue_code_editor( $wp_customize->custom_css_code_editor_settings );
	}
	wp_add_inline_script( 'customize-controls', file_get_contents( dirname( BETTER_CODE_EDITING_PLUGIN_FILE ) . '/wp-admin/js/customize-controls-addendum.js' ) );
}

/**
 * Add Customizer integration.
 *
 * @see WP_Customize_Manager::customize_pane_settings()
 * @global WP_Customize_Manager $wp_customize
 */
function _better_code_editing_amend_customize_pane_settings() {
	global $wp_customize;
	if ( empty( $wp_customize->custom_css_code_editor_settings ) ) {
		return;
	}
	printf( '<script>window._wpCustomizeSettings.codeEditor = %s</script>;', wp_json_encode( $wp_customize->custom_css_code_editor_settings ) );

	/* translators: placeholder is error count */
	$l10n = _n_noop( 'There is %d error which must be fixed before you can save.', 'There are %d errors which must be fixed before you can save.', 'better-code-editing' );
	printf( '<script>window._wpCustomizeControlsL10n.customCssErrorNotice = %s</script>;', wp_json_encode( wp_array_slice_assoc( $l10n, array( 'singular', 'plural' ) ) ) );
}
