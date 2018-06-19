<?php


class WebpackPageControllerExtension extends Extension/*
### @@@@ START UPGRADE REQUIRED @@@@ ###
FIND: extends Extension
NOTE: Check for use of $this->anyVar and replace with $this->anyVar[$this->owner->ID] or consider turning the class into a trait 
### @@@@ END UPGRADE REQUIRED @@@@ ###
*/
{

    /**
     *
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
     *
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
            $webpackServer = rtrim($webpackServer, '/') .':'.$this->owner->Config()->get('webpack_port').'/';
        }

        return $webpackServer;
    }

    public function WebpackDistributionFolderExtension()
    {
        return $this->owner->Config()->get('webpack_distribution_folder_extension');
    }

    public function WebpackFileHash($type = 'JS')
    {
        $base = Director::baseFolder();
        if ($type === 'JS') {
            $file = 'bundle.js';
        } elseif ($type === 'CSS') {
            $file = 'style.css';
        } else {
            user_error('Please specify JS or CSS, '.$type.' specified.');
        }
        $fullFile = $base.'/'.THEMES_DIR . "/" . Config::inst()->get('SSViewer', 'theme').'_'.$this->WebpackDistributionFolderExtension().'/'.$file;

        return @filemtime($fullFile);
    }
}
