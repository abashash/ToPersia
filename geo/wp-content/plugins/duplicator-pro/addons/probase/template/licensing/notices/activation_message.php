<?php

use Duplicator\Libs\Snap\SnapUtil;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$licenseMessage      = $tplMng->getDataValueString('license_message');
$licenseSuccess      = $tplMng->getDataValueBool('license_success');
$licenseRequestError = $tplMng->getDataValueArray('license_request_error');

if (empty($licenseMessage)) {
    return;
}

$details = "";
if (!empty($licenseRequestError)) {
    $details =  'Message: ' . $licenseRequestError['message'] . "\n" .
                'Error code: ' . $licenseRequestError['code'];
    if (strlen($licenseRequestError['requestDetails'])) {
        $details .= "\n\n" . 'Request Details' . "\n" . $licenseRequestError['requestDetails'];
    }
    if (strlen($licenseRequestError['details'])) {
        $details .= "\n" . 'Response Details' . "\n" . $licenseRequestError['details'];
    }
}

?>
<p>
    <?php if (!$licenseSuccess) { ?>
        <i class="fa fa-exclamation-triangle"></i>&nbsp;
        <?php
    }
    echo wp_kses(
        $licenseMessage,
        [
            'a'    => [
                'href'   => [],
                'target' => [],
            ],
            'span' => [
                'class' => [],
            ],
            'br'   => [],
            'b'    => [
                'class' => [],
            ],
        ]
    );
    ?>
</p>
<?php if (!empty($licenseRequestError)) { ?>
    <p>
        <?php echo esc_html__('Error:', 'duplicator-pro') ?> <b><?php echo esc_html($licenseRequestError['message']); ?></b>
    </p>
    <b>Details:</b>
    <textarea class="dup-error-message-textarea" disabled ><?php echo esc_textarea($details); ?></textarea>
    <button
        data-dup-copy-value="<?php echo esc_attr($details); ?>"
        data-dup-copy-title="<?php echo esc_attr("Copy Error Message to clipboard"); ?>"
        data-dup-copied-title="<?php echo esc_attr("Error Message copied to clipboard"); ?>"
        class="button dup-btn-copy-error-message">
        <?php esc_html_e('Copy error details', 'duplicator-pro'); ?>
    </button>
    <?php if (!SnapUtil::isCurlEnabled()) {
        $tplMng->render('licensing/notices/curl_message');
    } ?>
    <p>
        <?php
        printf(
            wp_kses(
                __("If the error persists please open a ticket <a href=\"%s\">here</a> and attach the error details.", 'duplicator-pro'),
                [
                    'a' => [
                        'href' => [],
                    ],
                ]
            ),
            esc_url(DUPLICATOR_BLOG_URL . 'my-account/support/')
        );
        ?>
    </p>
<?php } ?>
