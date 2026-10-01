<?php

declare(strict_types=1);

namespace Duplicator\Addons\BrandAddon\Ajax;

use Duplicator\Addons\BrandAddon\Models\BrandEntity;
use Duplicator\Ajax\AbstractAjaxService;
use Duplicator\Ajax\AjaxWrapper;
use Duplicator\Core\CapMng;
use Duplicator\Libs\Snap\SnapUtil;
use Exception;

class ServicesBrand extends AbstractAjaxService
{
    /**
     * Init ajax calls
     *
     * @return void
     */
    public function init(): void
    {
        $this->addAjaxCall('wp_ajax_duplicator_brand_delete', 'brandDelete');
    }

    /**
     * Hook ajax wp_ajax_duplicator_brand_delete
     *
     * @return void
     */
    public function brandDelete(): void
    {
        AjaxWrapper::json(
            [
                self::class,
                'brandDeleteCallback',
            ],
            'duplicator_brand_delete',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            CapMng::CAP_CREATE
        );
    }

    /**
     * Brand delete callback
     *
     * @return array<string, mixed>
     */
    public static function brandDeleteCallback(): array
    {
        $inputData = filter_input_array(INPUT_POST, [
            'brand_ids' => [
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => ['default' => false],
            ],
        ]);
        $brandIDs  = $inputData['brand_ids'];

        if (empty($brandIDs) || in_array(false, $brandIDs)) {
            throw new Exception(__('Invalid Request.', 'duplicator-pro'));
        }

        $delCount = 0;
        foreach ($brandIDs as $id) {
            if (BrandEntity::deleteById($id)) {
                $delCount++;
            }
        }

        return [
            'success' => true,
            'ids'     => $brandIDs,
            'removed' => $delCount,
        ];
    }
}
