<?php

namespace Duplicator\Addons\AmazonS3Addon\Models;

use Duplicator\Addons\AmazonS3Addon\AmazonS3Addon;

class WasabiStorage extends AmazonS3CompatibleStorage
{
    /**
     * Return the storage type
     *
     * @return int
     */
    public static function getSType(): int
    {
        return 10;
    }

    /**
     * Returns the storage type name.
     *
     * @return string
     */
    public static function getStypeName(): string
    {
        return __('Wasabi', 'duplicator-pro');
    }

    /**
     * Returns the storage type icon URL
     *
     * @return string Returns the storage icon URL
     */
    public static function getStypeIconURL(): string
    {
        return AmazonS3Addon::getAddonUrl() . '/assets/img/wasabi.svg';
    }

    /**
     * Return true if the endpoint is generated automatically
     *
     * @return bool
     */
    public function isAutofillEndpoint(): bool
    {
        return true;
    }

    /**
     * Renders a link to the Wasabi console file manager.
     *
     * @return string HTML anchor
     */
    protected function getConnectedLocationHtml(): string
    {
        return '<a href="https://console.wasabisys.com/file_manager/" target="_blank">'
            . esc_html__('Bucket List', 'duplicator-pro')
            . '</a>';
    }

    /**
     * Returns the storage location label.
     *
     * @return string The storage location label
     */
    protected function getLocationLabel(): string
    {
        return __('Bucket List', 'duplicator-pro');
    }

    /**
     * Get documentation links
     *
     * @return array<int,array<string,string>>
     */
    protected static function getDocumentationLinks(): array
    {
        return [
            [
                'label' => __('Wasabi Academy', 'duplicator-pro'),
                'url'   => 'https://docs.wasabi.com/',
            ],
            [
                'label' => __('S3 Compatible API', 'duplicator-pro'),
                'url'   => 'https://docs.wasabi.com/docs/wasabi-api',
            ],
        ];
    }
}
