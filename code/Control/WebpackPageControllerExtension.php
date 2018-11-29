<?php

namespace Sunnysideup\WebpackRequirementsBackend\Control;




use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\SSViewer;
use SilverStripe\Core\Extension;




class WebpackPageControllerExtension extends Extension
{

    /**
     * you only need to set this if you have some themes that are enabled and some themes
     * that do not run webpack
     * @var {Array}
     */
    private static $webpack_enabled_themes = [];

    /**
     * override webpack server for custom set ups
     * set to true to make this class believe you are always running
     * the webpack server
     * @see IsWebpackDevServer
     * @var bool
     */
    private static $is_webpack_server = false;

    /**
     * override webpack server for custom set ups
     * this is the server used for checking if the webpack server is running
     * @see IsWebpackDevServer
     * @var bool
     */
    private static $webpack_socket_server = 'localhost';

    /**
     * usually this is set to current domain
     * only set if you need an alternative
     * @see: WebpackBaseURL
     * @var string
     */
    private static $webpack_server = '';

    /**
     *
     * @var int
     */
    private static $webpack_port = 3000;

    /**
     * this is the folder where the distilled files are placed.
     * If your theme is foo then you will find the distilled files in themes/foo_dist
     * @var string
     */
    private static $webpack_distribution_folder_extension = 'dist';

    /**
     *
     * @return bool
     */
    public function IsNotWebpackDevServer()
    {
        return $this->IsWebpackDevServer() ? false : true;
    }


    /**
     *
     * @return bool
     */
    public function IsWebpackDevServer()
    {
        $override = $this->owner->Config()->get('is_webpack_server');
        if ($override) {
            return $override;
        }
        if (Director::isDev()) {
            $socket = @fsockopen(
                $this->owner->Config()->get('webpack_socket_server'),
                $this->owner->Config()->get('webpack_port'),
                $errno,
                $errstr,
                1
            );
            return ! $socket ? false : true;
        }
    }


    /**
     *
     * @return string
     */
    public function WebpackBaseURL()
    {
        $webpackServer = $this->owner->Config()->get('webpack_server');
        if (! $webpackServer) {
            $webpackServer = Director::AbsoluteBaseURL('/');
        }
        if ($this->IsWebpackDevServer()) {
            $webpackServer = rtrim($webpackServer, '/') .':'.$this->owner->Config()->get('webpack_port');
        }

        return $webpackServer;
    }

    /**
     * @return string
     */
    public function WebpackDistributionFolderExtension()
    {
        return $this->owner->Config()->get('webpack_distribution_folder_extension');
    }

    /**
     *
     * @param string $type should be set to JS or CSS
     * @return string
     */
    public function WebpackFileHash($name = 'app.css')
    {
        $base = Director::baseFolder();
        $file = $name;
        $fullFile = $base.'/'.THEMES_DIR . "/" . Config::inst()->get(SSViewer::class, 'current_theme').'_'.$this->WebpackDistributionFolderExtension().'/'.$file;

        return @filemtime($fullFile);
    }
}
