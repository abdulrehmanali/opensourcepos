<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Locale extends BaseConfig
{
    // The default locale for the application.
    // This locale is used when no other is specified.
    public $defaultLocale = 'en';

    // You can specify which locales are available for the application.
    // This will allow the system to fall back to available locales.
    public $availableLocales = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
    ];

    // The timezone to be used in the application
    public $timezone = 'UTC';

    // The date and time format used by the application.
    // You can define custom formats here.
    public $dateFormat = 'Y-m-d H:i:s';

    // Specify custom formatting for numbers (if needed).
    public $numberFormat = [
        'decimal' => '.',
        'thousands' => ',',
    ];

    // Locale-specific language configuration, such as pluralization rules,
    // translations, and other locale-specific behavior, can be added here.
    public $language = [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
    ];

    // Locale for currency settings, such as symbol, format, etc.
    public $currency = [
        'en' => 'USD',  // Default currency is US Dollars
        'es' => 'EUR',  // For Spanish, for example
    ];

    // You can also define whether to show the locale selector based on user preferences.
    public $showLocaleSelector = true;

    // The fallback locale in case a specific locale is not available.
    public $fallbackLocale = 'en';

    // Custom configurations for different locales (if needed).
    public $customLocales = [
        'en' => [
            'currencySymbol' => '$',
        ],
        'es' => [
            'currencySymbol' => '€',
        ],
    ];

    // You can also add a setting for loading language files dynamically per locale
    // which can be useful if you want different translation files for each language.
    public $languageFiles = [
        'en' => 'english',
        'es' => 'spanish',
    ];

    // Method to get the default locale (optional)
    public static function getDefault()
    {
        return 'en';
    }
}
