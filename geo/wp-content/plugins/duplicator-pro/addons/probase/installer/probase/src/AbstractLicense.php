<?php

namespace Duplicator\Installer\Addons\ProBase;

use Exception;

abstract class AbstractLicense
{
    const TYPE_UNKNOWN         = -1;
    const TYPE_UNLICENSED      = 0;
    const TYPE_PERSONAL        = 1;
    const TYPE_FREELANCER      = 2;
    const TYPE_BUSINESS        = 3;
    const TYPE_PERSONAL_AUTO   = 4;
    const TYPE_FREELANCER_AUTO = 5;
    const TYPE_BUSINESS_AUTO   = 6;
    const TYPE_GOLD            = 7;
    const TYPE_BASIC           = 8;
    const TYPE_PLUS            = 9;
    const TYPE_PRO             = 10;
    const TYPE_ELITE           = 11;
    const TYPE_ENTERPRISE      = 12;

    const FEATURE_PACKAGE_COMPONENTS_PLUS = 1011;
    const FEATURE_STORAGE_PLUS            = 1012;

    /**
     * Return true if multisite is enabled
     *
     * @return bool
     */
    protected static function isMultisite()
    {
        // This is to avoid warnings in PHP 5.6 because isn't possibile declare an abstract static method.
        throw new Exception('This method must be extended');
    }

    /**
     * Returns the license type this installer file is made of.
     *
     * @return int Returns an enum type of License
     */
    public static function getType()
    {
        // This is to avoid warnings in PHP 5.6 because isn't possibile declare an abstract static method.
        throw new Exception('This method must be extended');
    }

    /**
     * Return upsell URL
     *
     * @return string
     */
    public static function getUpsellURL()
    {
        // This is to avoid warnings in PHP 5.6 because isn't possibile declare an abstract static method.
        throw new Exception('This method must be extended');
    }

    /**
     * Return true if license is unlimited
     *
     * @return bool
     */
    public static function isUnlimited()
    {
        return in_array(
            static::getType(),
            [
                self::TYPE_BUSINESS,
                self::TYPE_BUSINESS_AUTO,
                self::TYPE_GOLD,
            ]
        );
    }

    /**
     * Return true if license have the capability
     *
     * @param int  $capability capability key
     * @param ?int $license    ENUM license type, if null Get currnt licnse type
     *
     * @return bool
     */
    public static function can($capability, $license = null)
    {
        if (is_null($license)) {
            $license = static::getType();
        }

        switch ($capability) {
            case self::FEATURE_PACKAGE_COMPONENTS_PLUS:
                return in_array(
                    $license,
                    [
                        self::TYPE_BUSINESS,
                        self::TYPE_BUSINESS_AUTO,
                        self::TYPE_GOLD,
                        self::TYPE_PRO,
                        self::TYPE_ELITE,
                        self::TYPE_ENTERPRISE,
                    ]
                );
            case self::FEATURE_STORAGE_PLUS:
                return in_array(
                    $license,
                    [
                        self::TYPE_PERSONAL,
                        self::TYPE_PERSONAL_AUTO,
                        self::TYPE_FREELANCER,
                        self::TYPE_FREELANCER_AUTO,
                        self::TYPE_BUSINESS,
                        self::TYPE_BUSINESS_AUTO,
                        self::TYPE_GOLD,
                        self::TYPE_PRO,
                        self::TYPE_ELITE,
                        self::TYPE_ENTERPRISE,
                    ]
                );
            default:
                return false;
        }
    }

    /**
     * Return true if license can be upgraded
     *
     * @param ?int $license ENUM license type, if null Get currnt licnse type
     *
     * @return bool
     */
    public static function canBeUpgraded($license = null)
    {
        if (is_null($license)) {
            $license = static::getType();
        }

        return !in_array($license, [
            self::TYPE_BUSINESS,
            self::TYPE_BUSINESS_AUTO,
            self::TYPE_GOLD,
            self::TYPE_ELITE,
            self::TYPE_ENTERPRISE,
        ]);
    }

    /**
     * Return license description
     *
     * @param ?int $license ENUM license type, if null Get currnt licnse type
     * @param bool $article if true add article before description
     *
     * @return string
     */
    public static function getLicenseToString($license = null, $article = false)
    {
        if (is_null($license)) {
            $license = static::getType();
        }

        switch ($license) {
            case self::TYPE_UNLICENSED:
                return ($article ? 'an ' : '') . 'Unlicensed';
            case self::TYPE_PERSONAL:
            case self::TYPE_PERSONAL_AUTO:
                return ($article ? 'a ' : '') . 'Personal';
            case self::TYPE_FREELANCER:
            case self::TYPE_FREELANCER_AUTO:
                return ($article ? 'a ' : '') . 'Freelancer';
            case self::TYPE_BUSINESS:
            case self::TYPE_BUSINESS_AUTO:
                return ($article ? 'a ' : '') . 'Business';
            case self::TYPE_GOLD:
                return ($article ? 'a ' : '') . 'Gold';
            case self::TYPE_BASIC:
                return ($article ? 'a ' : '') . 'Basic';
            case self::TYPE_PLUS:
                return ($article ? 'a ' : '') . 'Plus';
            case self::TYPE_PRO:
                return ($article ? 'a ' : '') . 'Pro';
            case self::TYPE_ELITE:
                return ($article ? 'a ' : '') . 'Elite';
            case self::TYPE_ENTERPRISE:
                return ($article ? 'an ' : '') . 'Enterprise';
            default:
                return ($article ? 'an ' : '') . 'Unknown Type';
        }
    }

    /**
     * Get best license from two given
     *
     * @param int $l1 ENUM license
     * @param int $l2 ENUM license
     *
     * @return int ENUM license
     */
    protected static function getBestLicense($l1, $l2)
    {
        $weights = [
            self::TYPE_UNLICENSED      => -1,
            self::TYPE_BASIC           => 0,
            self::TYPE_PERSONAL        => 1,
            self::TYPE_PERSONAL_AUTO   => 1,
            self::TYPE_PLUS            => 2,
            self::TYPE_FREELANCER      => 3,
            self::TYPE_FREELANCER_AUTO => 3,
            self::TYPE_BUSINESS        => 4,
            self::TYPE_BUSINESS_AUTO   => 4,
            self::TYPE_GOLD            => 5,
            self::TYPE_PRO             => 6,
            self::TYPE_ELITE           => 7,
            self::TYPE_ENTERPRISE      => 8,
        ];

        $l1Weight = ($weights[$l1] ?? -1);
        $l2Weight = ($weights[$l2] ?? -1);

        return ($l1Weight >= $l2Weight ? $l1 : $l2);
    }
}
