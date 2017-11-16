<?php

if(defined('webpack_requirements_backend_off')) {
    //do nothing
} else {
    $options = Config::inst()->get('WebpackPageControllerExtension', 'webpack_enabled_themes');
    if(count($options) === 0 || in_array(Config::inst()->get('SSViewer', 'theme'), $options)) {
        Requirements::set_backend(new Requirements_Backend_For_Webpack());
    }
    unset($options);
}
