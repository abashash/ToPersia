<?php

namespace Duplicator\Addons\ProBase\Translations;

/**
 * Pulls Pro translations from the duplicator.com GlotPress channel.
 *
 * Pro is distributed outside wp.org and cannot use translate.wordpress.org.
 * This class short-circuits WordPress's translations API to fetch .mo files
 * from translations.duplicator.com instead.
 *
 * Belongs to ProBase: the channel is Pro-only and Lite must not pull from it.
 */
class Translations
{
    /**
     * GlotPress endpoint serving the Pro translations as JSON.
     */
    const API_URL = 'https://translations.duplicator.com/packages/duplicator-pro/packages.json';

    const BASE_KEY       = 'duplicator_translations_';
    const CACHE_LIFESPAN = 15; // seconds

    /**
     * The project type.
     *
     * @var string
     */
    private $type = 'plugin';

    /**
     * The project dir slug.
     *
     * @var string
     */
    private $slug = '';

    /**
     * The API URL.
     *
     * @var string
     */
    private $apiUrl = '';

    /**
     * Installed translations.
     *
     * @var array<string,string[]>
     */
    private static $installedTranslations = [];

    /**
     * Available languages.
     *
     * @var array<string>
     */
    private static $availableLanguages = [];

    /**
     * Class Constructor
     *
     * @param string $slug   Project directory slug.
     * @param string $apiUrl Full GlotPress API URL for the project.
     * @param string $type   Project type. Either plugin or theme.
     */
    public function __construct($slug, $apiUrl, $type = 'plugin')
    {
        $this->type   = $type;
        $this->slug   = $slug;
        $this->apiUrl = $apiUrl;
    }

    /**
     * Gets the transient key.
     *
     * @return string
     */
    protected function getKey(): string
    {
        return self::BASE_KEY . $this->slug . '_' . $this->type;
    }

    /**
     * Adds a new project to load translations
     *
     * @return void
     */
    public function init(): void
    {
        if (has_action('admin_init', [ $this, 'registerCleanTranslationsCache' ]) === false) {
            add_action('admin_init', [ $this, 'registerCleanTranslationsCache' ], 9999);
        }

        // Short-circuits translations API requests for private projects.
        add_filter(
            'translations_api',
            function ($result, $requestedType, $args) {
                if ($this->type . 's' === $requestedType && $this->slug === $args['slug']) {
                    $translations = $this->getTranslations($args['slug'], $this->apiUrl);
                    if (isset($translations->{ $args['slug'] }) && is_array($translations->{ $args['slug'] })) {
                        return $translations->{ $args['slug'] };
                    }
                }
                return $result;
            },
            10,
            3
        );

        // Filters the translations transients to include the private plugin or theme. @see wp_get_translation_updates().
        add_filter(
            'site_transient_update_plugins',
            function ($value) {
                if (!$value) {
                    $value = new \stdClass();
                }

                if (!isset($value->translations)) {
                    $value->translations = [];
                }

                $translations = $this->getTranslations($this->slug, $this->apiUrl);

                if (!isset($translations->{ $this->slug }['translations'])) {
                    return $value;
                }

                if (empty(self::$installedTranslations)) {
                    self::$installedTranslations = wp_get_installed_translations($this->type . 's');
                }

                if (empty(self::$availableLanguages)) {
                    self::$availableLanguages = get_available_languages();
                }

                foreach ((array) $translations->{ $this->slug }['translations'] as $translation) {
                    if (in_array($translation['language'], self::$availableLanguages, true)) {
                        if (isset(self::$installedTranslations[ $this->slug ][ $translation['language'] ]) && $translation['updated']) {
                            $local  = new \DateTime(self::$installedTranslations[ $this->slug ][ $translation['language'] ]['PO-Revision-Date']);
                            $remote = new \DateTime($translation['updated']);

                            if ($local >= $remote) {
                                continue;
                            }
                        }

                        $translation['type'] = $this->type;
                        $translation['slug'] = $this->slug;

                        $value->translations[] = $translation;
                    }
                }

                return $value;
            }
        );
    }

    /**
     * Registers actions for clearing translation caches.
     *
     * @return void
     */
    public function registerCleanTranslationsCache(): void
    {
        $clearPluginTranslations = function (): void {
            $this->cleanTranslationsCache();
        };

        add_action('set_site_transient_update_plugins', $clearPluginTranslations);
        add_action('delete_site_transient_update_plugins', $clearPluginTranslations);
    }

    /**
     * Clears existing translation cache for a given type.
     *
     * @return void
     */
    public function cleanTranslationsCache(): void
    {
        $translations = get_site_transient($this->getKey());

        if (!is_object($translations)) {
            return;
        }

        /*
        * Don't delete the cache if the transient gets changed multiple times
        * during a single request. Set cache lifetime to maximum 15 seconds.
        */
        $timeNotChanged = isset($translations->_last_checked) && ( time() - $translations->_last_checked ) > self::CACHE_LIFESPAN;

        if (! $timeNotChanged) {
            return;
        }

        delete_site_transient($this->getKey());
    }

    /**
     * Gets the translations for a given project.
     *
     * @param string $slug Project directory slug.
     * @param string $url  Full GlotPress API URL for the project.
     *
     * @return object Translation data wrapper with $slug entry and _last_checked timestamp.
     */
    public function getTranslations($slug, $url)
    {
        $translations = get_site_transient($this->getKey());

        if (
            is_object($translations) &&
            isset($translations->{ $slug }) &&
            is_array($translations->{ $slug })
        ) {
            return $translations;
        }

        $result = json_decode(wp_remote_retrieve_body(wp_remote_get($url, [ 'timeout' => 2 ])), true);
        if (! is_array($result)) {
            $result = [];
        }

        $translations                = new \stdClass();
        $translations->{ $slug }     = $result;
        $translations->_last_checked = time();

        set_site_transient($this->getKey(), $translations);

        return $translations;
    }
}
