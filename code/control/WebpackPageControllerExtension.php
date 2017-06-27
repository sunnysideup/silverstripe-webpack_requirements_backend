<?php


class WebpackPageControllerExtension extends extension
{

    public function WebpackDevServer()
    {
        if (Director::isDev()) {
            $socket = @fsockopen('localhost', 3000, $errno, $errstr, 1);
            return !$socket ? false : true;
        }
    }
}
