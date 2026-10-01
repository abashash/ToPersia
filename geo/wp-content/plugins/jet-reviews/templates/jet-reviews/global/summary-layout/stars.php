<?php
/**
 * Points layout template
 */
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The widget helper returns fixed icon markup built from normalized numeric rating values.
echo $this->__get_stars( $val, $max );
