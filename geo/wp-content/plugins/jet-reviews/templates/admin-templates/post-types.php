<?php // phpcs:disable WordPress.Security.EscapeOutput.UnsafePrintingFunction ?>
<!-- Existing translated Vue settings notice uses _e(). -->
<div
	class="jet-reviews-settings-page jet-reviews-settings-page__post-types"
>
    <div><?php _e( 'Your settings have been saved and transferred to the appropriate review types. ', 'jet-reviews' ); ?><a :href="sourceTypeListUrl"><?php _e( 'View review types', 'jet-reviews' ); ?></a></div>
</div>
<?php // phpcs:enable WordPress.Security.EscapeOutput.UnsafePrintingFunction ?>
