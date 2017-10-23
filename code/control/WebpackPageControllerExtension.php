<?php


class WebpackPageControllerExtension extends extension
{

    /**
     *
     * @var {Array}
     */
    private static $webpack_enabled_themes = [];

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
        if (Director::isDev()) {
            $socket = @fsockopen('localhost', $this->owner->Config()->get('webpack_port'), $errno, $errstr, 1);
            return !$socket ? false : true;
        }
    }


    /**
     *
     * @return string
     */
    public function WebpackBaseURL()
    {
        $str = Director::AbsoluteBaseURL('/');
        if ($this->IsWebpackDevServer()) {
            $str = rtrim($str, '/') .':'.$this->owner->Config()->get('webpack_port').'/';
        }

        return $str;
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
