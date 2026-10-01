<?php

/**
 * Quick connect integration for DupCloud storage via ProBase license.
 */

declare(strict_types=1);

namespace Duplicator\Addons\ProBase\DupCloud;

use Duplicator\Addons\ProBase\LicensingController;
use Duplicator\Addons\ProBase\Models\LicenseData;
use Duplicator\Addons\ProBase\ProBase;
use Duplicator\Ajax\AjaxWrapper;
use Duplicator\Core\CapMng;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;

/**
 * Handles DupCloud quick connect via ProBase license.
 * Injects the "Connect via License" button and AJAX into DupCloud storage UI.
 */
class DupCloudQuickConnect
{
    const AJAX_ACTION_QUICK_CONNECT = 'duplicator_dupcloud_quick_connect';

    /** @var array<string,mixed> */
    private static $tokens = [];

    /**
     * Initialize hooks
     *
     * @return void
     */
    public static function init(): void
    {
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);
        add_action('duplicator_dupcloud_connect_buttons', [self::class, 'renderConnectButton']);
        add_action('duplicator_dupcloud_after_settings', [self::class, 'renderAutoActivate']);
        add_action('duplicator_license_check_remote_data_success', [self::class, 'handleLicenseDataSuccess'], 10, 2);
        add_action('wp_ajax_' . self::AJAX_ACTION_QUICK_CONNECT, [self::class, 'getStorageTokens']);
    }

    /**
     * Return template file path for probase dupcloud templates
     *
     * @param string $path    path to the template file
     * @param string $slugTpl slug of the template
     *
     * @return string
     */
    public static function getTemplateFile(string $path, string $slugTpl): string
    {
        if (strpos($slugTpl, 'probase/dupcloud/') === 0) {
            return ProBase::getAddonPath() . '/template/' . $slugTpl . '.php';
        }

        return $path;
    }

    /**
     * Render the "Connect via License" button and JS
     *
     * @param mixed $storage The cloud storage instance
     *
     * @return void
     */
    public static function renderConnectButton($storage): void
    {
        TplMng::getInstance()->render(
            'probase/dupcloud/connect_button',
            ['storage' => $storage]
        );
    }

    /**
     * Render auto-activate script after license activation
     *
     * @param mixed $storage The cloud storage instance
     *
     * @return void
     */
    public static function renderAutoActivate($storage): void
    {
        if (!LicensingController::isActivationLicenseRender()) {
            return;
        }

        if (!CapMng::can(CapMng::CAP_STORAGE, false)) {
            return;
        }

        ?>
        <script>
            jQuery(document).ready(function($) {
                var $button = $('#dupli-dupcloud-license-connect-btn');
                if ($button.length) {
                    DupliJs.Storage.DupCloud.LicenseConnect($button, true);
                }
            });
        </script>
        <?php
    }

    /**
     * AJAX handler for getting storage tokens
     *
     * @return void
     */
    public static function getStorageTokens(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'getStorageTokensCallback',
            ],
            self::AJAX_ACTION_QUICK_CONNECT,
            SnapUtil::sanitizeStrictInput(INPUT_POST, 'nonce'),
            CapMng::CAP_STORAGE
        );
    }

    /**
     * Get storage tokens callback
     *
     * @return array{tokens:array<string,mixed>,html:string}
     */
    public static function getStorageTokensCallback(): array
    {
        $tokens = self::requestStorageTokens();

        return [
            'tokens' => $tokens,
            'html'   => TplMng::getInstance()->render(
                'probase/dupcloud/quick_connect',
                ['tokens' => $tokens],
                false
            ),
        ];
    }

    /**
     * Handle license data success to capture storage tokens
     *
     * @param LicenseData $license    The license data instance
     * @param object      $remoteData The remote data from license check
     *
     * @return void
     */
    public static function handleLicenseDataSuccess(LicenseData $license, $remoteData): void
    {
        if (
            !isset($remoteData->storage_tokens) ||
            !is_array($remoteData->storage_tokens)
        ) {
            return;
        }

        foreach ($remoteData->storage_tokens as $tokenObj) {
            self::$tokens[] = [
                'license_key'  => $tokenObj->license_key,
                'token'        => $tokenObj->token,
                'product_name' => $tokenObj->product_name,
                'price_name'   => $tokenObj->price_name,
                'expiration'   => $tokenObj->expiration,
                'is_lifetime'  => $tokenObj->is_lifetime,
            ];
        }
    }

    /**
     * Request storage tokens from license server
     *
     * @return array<string,mixed>
     */
    private static function requestStorageTokens(): array
    {
        $licenseData = LicenseData::getInstance();

        add_filter('duplicator_license_request_params', [self::class, 'addStorageTokensRequest']);
        $licenseData->getLicenseData(true);
        remove_filter('duplicator_license_request_params', [self::class, 'addStorageTokensRequest']);

        return self::$tokens;
    }

    /**
     * Add storage tokens request parameter to license request
     *
     * @param array<string,mixed> $params Request parameters
     *
     * @return array<string,mixed>
     */
    public static function addStorageTokensRequest(array $params): array
    {
        $params['request_storage_tokens'] = 'true';
        return $params;
    }
}
