<?php
/**
 * Review summary template
 */
$data     = $review_data;
$average  = $this->__get_average( $data );
$settings = $this->get_settings();
$allowed_layouts = [ 'stars', 'percentage', 'points' ];
$layout   = ! empty( $settings['summary_layout'] ) && in_array( $settings['summary_layout'], $allowed_layouts, true ) ? $settings['summary_layout'] : 'points';
$val_pos  = ! empty( $settings['summary_value_position'] ) ? esc_attr( $settings['summary_value_position'] ) : 'above';
$progress = isset( $settings['summary_progressbar'] ) ? esc_attr( $settings['summary_progressbar'] ) : 'yes';
$result_p = ! empty( $settings['summary_result_position'] ) ? $settings['summary_result_position'] : 'right';
$result_a = ! empty( $settings['summary_average_alignment'] ) ? $settings['summary_average_alignment'] : 'center';
$content_source = isset( $settings['content_source'] ) ? $settings['content_source'] : 'manually';
$summary_title  = ! empty( $data['summary_title'] ) ? $data['summary_title'] : '';
$summary_legend = ! empty( $data['summary_legend'] ) ? $data['summary_legend'] : '';
$summary_text   = '';

if ( ! empty( $data['summary_text'] ) ) {
	$summary_text = 'post-meta' === $content_source
		? nl2br( esc_html( $data['summary_text'] ) )
		: wp_kses_post( $data['summary_text'] );
}

if ( true === $average['valid'] ) {
	$val = $average['val'];
	$max = $average['max'];
} else {
	$val = $average['percent'];
	$max = 100;
}

$this->add_render_attribute( 'summary_result', 'class', 'jet-review__summary' );
$this->add_render_attribute( 'summary_result', 'class', 'jet-review-summary-' . esc_attr( $result_p ) );
$this->add_render_attribute( 'summary_result', 'class', 'jet-review-summary-align-' . esc_attr( $result_a ) );

?>
<div <?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor escapes render attributes when building this string.
echo $this->get_render_attribute_string( 'summary_result' );
?>>
	<div class="jet-review__summary-content"><?php
		if ( '' !== $summary_title ) {
			printf( '<h5 class="jet-review__summary-title">%s</h5>', esc_html( $summary_title ) );
		}

		if ( '' !== $summary_text ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Post-meta text is escaped before nl2br(); other sources are filtered through wp_kses_post() above.
			printf( '<div class="jet-review__summary-text">%s</div>', $summary_text );
		}
	?></div>
	<div class="jet-review__summary-data"><?php
		include $this->__get_global_template( 'summary-layout/' . $layout );
		if ( '' !== $summary_legend ) {
			printf( '<div class="jet-review__summary-legend">%s</div>', esc_html( $summary_legend ) );
		}
	?></div>
</div>
