<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\View\Requirements;
use Sunnysideup\WebpackRequirementsBackend\Api\Configuration;
use Sunnysideup\WebpackRequirementsBackend\View\RequirementsBackendForWebpack;

if (defined('webpack_requirements_backend_off')) {
    //do nothing
} else {
    $options = Config::inst()->get(Configuration::class, 'webpack_enabled_themes');
    if (0 === count($options) || in_array(Configuration::get_theme_for_webpack(), $options, true)) {
        Requirements::set_backend(new RequirementsBackendForWebpack());
    }

    unset($options);
}
