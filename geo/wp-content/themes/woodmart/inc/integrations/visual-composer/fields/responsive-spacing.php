<?php
/**
 * Get responsive spacing param.
 *
 * @package woodmart
 */

if ( ! defined( 'WOODMART_THEME_DIR' ) ) {
	exit( 'No direct script access allowed' );
}

if ( ! function_exists( 'woodmart_get_responsive_spacing_param' ) ) {
	/**
	 * Get responsive spacing param.
	 *
	 * @param array  $settings Settings.
	 * @param string $value Value.
	 *
	 * @return false|string
	 */
	function woodmart_get_responsive_spacing_param( $settings, $value ) {
		ob_start();
		?>
		<div class="vc_css-editor vc_row vc_ui-flex-row wd-responsive-spacing-wrapper">
			<div class="xts-layout-onion-tabs">
				<span class="wd-desktop xts-active" data-value="desktop">
					<span>
						<?php esc_html_e( 'Desktop', 'woodmart' ); ?>
					</span>
				</span>
				<span class="wd-tablet" data-value="tablet">
					<span>
						<?php esc_html_e( 'Tablet', 'woodmart' ); ?>
					</span>
				</span>
				<span class="wd-mobile" data-value="mobile">
					<span>
						<?php esc_html_e( 'Mobile', 'woodmart' ); ?>
					</span>
				</span>
			</div>
			<?php echo woodmart_get_responsive_spacing_template( 'tablet' ); // phpcs:ignore ?>
			<?php echo woodmart_get_responsive_spacing_template( 'mobile' ); // phpcs:ignore ?>

			<input type="hidden" name="<?php echo esc_attr( $settings['param_name'] ); ?>" class="wpb_vc_param_value wd-responsive-spacing-value" value="<?php echo esc_attr( $value ); ?>">
		</div>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'woodmart_get_responsive_spacing_template' ) ) {
	/**
	 * Get responsive spacing template.
	 *
	 * @param string $device Device.
	 *
	 * @return string
	 */
	function woodmart_get_responsive_spacing_template( $device ) {
		$css_editor = new WPBakeryCssEditor();
		$html       = $css_editor->onionLayout();

		return str_replace(
			'<div class="vc_layout-onion">',
			'<div class="vc_layout-onion wd-responsive-spacing" data-device="' . esc_attr( $device ) . '">',
			$html
		);
	}
}