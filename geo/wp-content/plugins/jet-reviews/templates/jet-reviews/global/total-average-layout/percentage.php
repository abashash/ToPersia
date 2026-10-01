<?php
/**
 * Points layout template
 */

if ( 'yes' !== $progress || 'inside' !== $val_pos ) {
	?><div class="jet-review__total-average-val"><?php echo esc_html( $total_average['percent'] . '%' ); ?></div><?php
}

if ( 'yes' === $progress ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The widget helper returns fixed markup built from normalized numeric rating values.
	echo $this->__get_progressbar( $val, $max, $val_pos, 'percentage' );
}
