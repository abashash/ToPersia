<?php

/**
 * REST point for overwrite subsites
 */

declare(strict_types=1);

namespace Duplicator\Addons\ImportAddon\RESTPoints;

use Duplicator\Addons\ImportAddon\Core\CapImport;
use Duplicator\Core\CapMng;
use Duplicator\Installer\Core\Params\Models\SiteOwrMap;
use Duplicator\Libs\Snap\SnapLog;
use Duplicator\Libs\Snap\SnapURL;
use Duplicator\Libs\Snap\SnapWP;
use Duplicator\Libs\WpUtils\WpUtilsMultisite;
use Duplicator\Utils\Logging\DupLog;
use Exception;
use WP_Error;
use WP_REST_Request;

class SubsiteActions extends \Duplicator\Core\REST\AbstractRESTPoint
{
    /**
     * return REST point route string
     *
     * @return string
     */
    protected function getRoute(): string
    {
        return '/multisite/subsite/actions';
    }

    /**
     * True if REST point is available
     *
     * @return boolean
     */
    public function isEnable()
    {
        return is_multisite();
    }

    /**
     * available methods
     *
     * @return string[]
     */
    public function getMethods(): array
    {
        return [
            'GET',
            'POST',
        ];
    }

    /**
     * Current user permission check
     *
     * @param WP_REST_Request $request request data
     *
     * @return WP_Error|true
     */
    public function permission(WP_REST_Request $request) // @phpstan-ignore missingType.generics
    {
        if (!CapMng::can(CapImport::CAP_IMPORT, false) || !is_super_admin() || !check_ajax_referer('wp_rest', false, false)) {
            return new WP_Error('rest_forbidden', esc_html__('You cannot execute this action.', 'duplicator-pro'));
        }
        return true;
    }

    /**
     * REST point arguments
     *
     * @return array<string, mixed>
     */
    protected function getArgs(): array
    {
        return [
            'data' => [
                'required'          => true,
                'type'              => 'string',
                'description'       => 'Subsite overwrite mapping',
                'validate_callback' => function ($param, \WP_REST_Request $request, $key): bool {
                    $param = json_decode($param, true);
                    if (!is_array($param)) {
                        return false;
                    }

                    foreach ($param as $item) {
                        if (!isset($item['sourceId']) || !is_int($item['sourceId'])) {
                            return false;
                        }
                        if (!isset($item['targetId']) || !is_int($item['targetId'])) {
                            return false;
                        }
                        if (!isset($item['newSlug'])  || !is_string($item['newSlug'])) {
                            return false;
                        }
                        if (!isset($item['blogName'])  || !is_string($item['blogName'])) {
                            return false;
                        }
                        switch ($item['targetId']) {
                            case SiteOwrMap::NEW_SUBSITE_WITH_SLUG:
                                if (strlen($item['newSlug']) == 0) {
                                    return false;
                                }
                                break;
                            case SiteOwrMap::NEW_SUBSITE_WITH_FULL_DOMAIN:
                                $parsedURL = SnapURL::parseUrl($item['newSlug']);
                                if (!is_array($parsedURL) || $parsedURL['host'] == false || $parsedURL['path'] == false) {
                                    return false;
                                }
                                break;
                            default:
                                if (
                                    $item['targetId'] <= SiteOwrMap::NEW_SUBSITE_NOT_VALID ||
                                    get_site($item['targetId']) == null
                                ) {
                                    return false;
                                }
                        }
                    }
                    return true;
                },
            ],
        ];
    }

    /**
     * REST poing logic
     *
     * @param \WP_REST_Request $request      REQUEST data
     * @param mixed[]          $responseBase response base data
     *
     * @return \WP_REST_Response
     */
    protected function respond(\WP_REST_Request $request, $responseBase): \WP_REST_Response // @phpstan-ignore missingType.generics
    {
        $response                 = $responseBase;
        $response['subsitesInfo'] = [];

        if (!class_exists('WP_Network')) {
            throw new Exception('the current version of WordPress does not support this action.');
        }

        $data = json_decode($request->get_param('data'), true);
        if (!is_array($data)) {
            throw new Exception('Can\'t decode data');
        }

        DupLog::trace('PARAM DATA ' . SnapLog::v2str($data));
        $currentUserId = get_current_user_id();

        foreach ($data as $subsiteInfo) {
            if ($subsiteInfo['targetId'] > 0) {
                $blogId = (int) $subsiteInfo['targetId'];
            } else {
                $blogId = self::createNewSubsite($subsiteInfo['targetId'], $subsiteInfo['newSlug'], $subsiteInfo['blogName'], $currentUserId);
            }

            self::addUserAdminAtSubsite($blogId);
            if (($info = WpUtilsMultisite::getSubsiteInfoById($blogId)) == false) {
                throw new Exception('Can\'t read subsite info ID: ' . $blogId);
            }

            $subsiteInfo['info']        = $info;
            $response['subsitesInfo'][] = $subsiteInfo;
        }

        $response['success'] = true;

        return new \WP_REST_Response($response, 200);
    }

    /**
     * Add current logged in user at subsite id
     *
     * @param int $blogId blog id
     *
     * @return bool
     */
    protected static function addUserAdminAtSubsite($blogId): bool
    {
        $userId = get_current_user_id();

        if (is_user_member_of_blog($userId, $blogId)) {
            return true;
        }

        $result = add_user_to_blog($blogId, $userId, 'administrator');
        if ($result instanceof WP_Error) {
            throw new \Exception($result->get_error_message());
        }

        return true;
    }

    /**
     * Create a new subsite, or adopt the subsite already existing at the target domain/path
     * so a retried import doesn't fail on its own partially completed previous attempt
     *
     * @param int    $type        Enum (SiteOwrMap::NEW_SUBSITE_WITH_SLUG, SiteOwrMap::NEW_SUBSITE_WITH_FULL_DOMAIN)
     * @param string $newSlug     subsite slug
     * @param string $blogTitle   blog title
     * @param int    $adminUserId admin user
     *
     * @return int subsite id, existing or newly created
     */
    protected static function createNewSubsite($type, $newSlug, $blogTitle, $adminUserId)
    {
        $networkId = function_exists('get_current_network_id') ? get_current_network_id() : 1;

        switch ($type) {
            case SiteOwrMap::NEW_SUBSITE_WITH_SLUG:
                if (($wpNetwork = \WP_Network::get_instance($networkId)) == false) {
                    throw new Exception('Can\'t read network info');
                }

                if (SnapWP::isSubdomainInstall()) {
                    $domain = $newSlug . '.' . SnapURL::wwwRemove($wpNetwork->domain);
                    $path   = $wpNetwork->path;
                } else {
                    $domain = $wpNetwork->domain;
                    $path   = trailingslashit($wpNetwork->path) . $newSlug;
                }
                break;
            case SiteOwrMap::NEW_SUBSITE_WITH_FULL_DOMAIN:
                if (!is_array($parsedURL = SnapURL::parseUrl($newSlug))) {
                    throw new Exception('Can\'t parse new subsite url');
                }
                $domain = $parsedURL['host'];
                $path   = trailingslashit($parsedURL['path']);
                break;
            default:
                throw new Exception('Invalid type [' . $type . '] of new subsite creation');
        }

        $newBlogId = wpmu_create_blog($domain, $path, $blogTitle, $adminUserId);

        if ($newBlogId instanceof WP_Error) {
            if (
                $newBlogId->get_error_code() === 'blog_taken' &&
                ($existingBlogId = domain_exists($domain, $path, $networkId)) !== null
            ) {
                DupLog::infoTrace('Subsite ' . $domain . $path . ' already exists (blog ID ' . $existingBlogId . '), adopting it for import retry');
                return (int) $existingBlogId;
            }
            throw new Exception($newBlogId->get_error_message());
        }

        return $newBlogId;
    }
}
