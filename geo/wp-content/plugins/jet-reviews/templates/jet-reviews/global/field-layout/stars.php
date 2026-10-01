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
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The widget helper returns fixed icon markup built from normalized numeric rating values.
		echo $this->__get_stars( $val, $max ); ?>
</div>