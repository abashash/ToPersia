<?php
/**
 * Points layout template
 */
?>
<div class="jet-review__field-heading">
	<div class="jet-review__field-label"><?php
			echo esc_html( $label );
		$this->__html( $settings, 'fields_label_suffix', '<span class="jet-review__field-label-suffix">%s</span>' );
	?></div>
	<?php if ( 'above' === $value_pos ) : ?>
		<div class="jet-review__field-val"><?php echo esc_html( $val ); ?></div>
	<?php endif; ?>
</div>
<?php
	if ( 'yes' === $progress ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The widget helper returns fixed markup built from normalized numeric rating values.
			echo $this->__get_progressbar( $val, $max, $value_pos );
	}
?>