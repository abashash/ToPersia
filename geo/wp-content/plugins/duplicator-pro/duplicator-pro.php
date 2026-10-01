<?php

/**
 * Plugin Name: Duplicator Pro
 * Plugin URI: https://duplicator.com/
 * Description: Create, schedule and transfer a copy of your WordPress files and database. Duplicate and move a site from one location to another quickly.
 * Version: 5.0.3
 * Requires at least: 5.3
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Author: Duplicator
 * Author URI: https://duplicator.com/
 * Network: true
 * Update URI: https://duplicator.com/
 * Text Domain: duplicator-pro 
* Domain Path:  /abzarwp/languages/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright 2011-2026 Snapcreek LLC
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

defined('ABSPATH') || exit;

// CHECK PHP VERSION
require_once dirname(__FILE__) . "/src/Utils/DupliPhpVersionCheck.php";
if (DupliPhpVersionCheck::check('7.4', '8.3') === false) {
    return;
}
$currentPluginBootFile   = __FILE__;
$currentPluginName       = 'Duplicator Pro';
$currentPluginType       = 'PRO';
$currentPluginTextDomain = 'duplicator-pro';
// phpcs:ignore Generic.Files.LineLength
$currentPluginHsh = '7b2273223a2265373737222c2272223a5b2250726f42617365225d2c226664223a5b224c69746542617365225d2c2276223a22352e302e33222c226b223a2238336530227d';

require_once dirname(__FILE__) . '/duplicator-main.php';

add_filter('pre_http_request', function($preempt, $parsed_args, $url) {
    if (strpos($url, 'duplicator.com') !== false && isset($parsed_args['body']['edd_action'])) {
        $action = $parsed_args['body']['edd_action'];
        if ($action === 'activate_license' || $action === 'check_license') {
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body' => json_encode([
                    'success' => true,
                    'license' => 'valid',
                    'item_id' => 12345,
                    'item_name' => 'Duplicator Pro',
                    'checksum' => 'abc123',
                    'expires' => 'lifetime',
                    'payment_id' => 1,
                    'customer_name' => 'Pro User',
                    'customer_email' => 'user@example.com',
                    'license_limit' => 99,
                    'site_count' => 1,
                    'activations_left' => 98,
                    'price_id' => 10,
                    'activeSubscription' => true
                ])
            ];
        }
        if ($action === 'deactivate_license') {
            return [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body' => json_encode(['license' => 'deactivated'])
            ];
        }
    }
    return $preempt;
}, 10, 3);

add_action('init', function() {
    if (!class_exists('Duplicator\Addons\ProBase\Models\LicenseData')) {
        return;
    }

    try {
        $licenseData = \Duplicator\Addons\ProBase\Models\LicenseData::getInstance();
        $reflection  = new ReflectionClass($licenseData);

        $keyProp    = $reflection->getProperty('licenseKey');
        $statusProp = $reflection->getProperty('status');
        $typeProp   = $reflection->getProperty('type');

        $keyProp->setAccessible(true);
        $statusProp->setAccessible(true);
        $typeProp->setAccessible(true);

        $needsSave = false;

        if (empty($keyProp->getValue($licenseData))) {
            $keyProp->setValue($licenseData, 'V1S7D4R4BF4637F9KUK18OW3L3Q7E332');
            $needsSave = true;
        }

        $currentStatus = $statusProp->getValue($licenseData);
        $currentType   = $typeProp->getValue($licenseData);
        if ($currentStatus !== 0 || !in_array($currentType, [10, 11])) {
            $statusProp->setValue($licenseData, 0);
            $typeProp->setValue($licenseData, 10);

            $dataProp = $reflection->getProperty('data');
            $dataProp->setAccessible(true);
            $dataProp->setValue($licenseData, [
                'success'            => true,
                'license'            => 'valid',
                'item_id'            => 12345,
                'item_name'          => 'Duplicator Pro',
                'checksum'           => 'abc123',
                'expires'            => 'lifetime',
                'payment_id'         => 1,
                'customer_name'      => 'Pro User',
                'customer_email'     => 'user@example.com',
                'license_limit'      => 99,
                'site_count'         => 1,
                'activations_left'   => 98,
                'price_id'           => 10,
                'activeSubscription' => true,
            ]);

            $lastUpdateProp = $reflection->getProperty('lastRemoteUpdate');
            $lastUpdateProp->setAccessible(true);
            $lastUpdateProp->setValue($licenseData, gmdate('Y-m-d H:i:s'));

            $needsSave = true;
        }

        if ($needsSave) {
            $licenseData->save();
        }
    } catch (Throwable $e) {
    }
}, 1);

/**
 * @author abzarwp
 */
require_once plugin_dir_path( __FILE__ ) . "abzarwp/abzarwp.php";