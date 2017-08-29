<?php


class WebpackPageControllerExtension extends extension
{

    private static $webpack_enabled_themes = [];

    private static $port_for_webpack = 3000;

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
            $socket = @fsockopen('localhost', $this->owner->Config()->get('port_for_webpack'), $errno, $errstr, 1);
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
        if($this->IsWebpackDevServer()) {
            $str = rtrim($str, '/') .':'.$this->owner->Config()->get('port_for_webpack').'/';
        }

        return $str;
    }
}
