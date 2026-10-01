<?php

/**
 * Staging site admin notice
 */

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$mainSiteUrl = $tplMng->getDataValueStringRequired('mainSiteUrl');
?>
<div class="notice notice-warning dupli-admin-notice dupli-staging-notice">
    <p>
        <strong><?php esc_html_e('Staging Site', 'duplicator-pro'); ?></strong> -
        <?php esc_html_e('This is a staging copy of your site. Emails are disabled.', 'duplicator-pro'); ?>
        <?php if (!empty($mainSiteUrl)) : ?>
            <a href="<?php echo esc_url($mainSiteUrl); ?>">
                <?php esc_html_e('Go to main site', 'duplicator-pro'); ?>
            </a>
        <?php endif; ?>
    </p>
</div>
