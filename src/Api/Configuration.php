<?php

namespace Sunnysideup\WebpackRequirementsBackend\Api;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\View\SSViewer;

class Configuration
{
    use Configurable;

    /**
     * @var bool
     */
    private static $enabled = true;

    /**
     * you only need to set this if you have some themes that are enabled and some themes
     * that do not run webpack
     * @todo: implement
     * @var array
     */
    private static $webpack_enabled_themes = [];

    /**
     * @var string
     */
    private static $webpack_theme = '';

    /**
     * this is the folder where the distilled files are placed.
     * If your theme is foo then you will find the distilled files in themes/foo_dist
     * @var string
     */
    private static $webpack_distribution_folder_extension = 'dist';

    /**
     * @var string
     */
    private static $webpack_src_folder_extension = 'src';

    /**
     * @var string
     */
    private static $webpack_node_modules_folder_extension = 'my_node_modules';

    /**
     * @var string
     */
    private static $webpack_raw_requirements_folder_extension = 'raw_requirements';

    /**
     * override webpack server for custom set ups
     * set to true to make this class believe you are always running
     * the webpack server
     * @see IsWebpackDevServer
     * @var bool
     */
    private static $is_webpack_server_override = false;

    /**
     * override webpack server for custom set ups
     * this is the server used for checking if the webpack server is running
     * @see IsWebpackDevServer
     * @var string
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
     * @var int
     */
    private static $webpack_port = 35729;

    public static function get_theme_for_webpack(): string
    {
        $theme = (string) Config::inst()->get(self::class, 'webpack_theme');
        if (! $theme) {
            $array = SSViewer::get_themes();
            if (! empty($array)) {
                while ($theme && strpos($theme, '$') !== false && strpos($theme, 'silverstripe/admin') !== false) {
                    $theme = (string) array_shift($array);
                }
            }
        }
        if (! $theme) {
            $theme = (string) Config::inst()->get(SSViewer::class, 'theme');
        }
        if (! $theme) {
            user_error('please set webpack_theme ASAP: ' . "\n" .self::class. "\n" . '  webpack_theme: [your theme here]' . "\n");
        }
        return $theme;
    }

    /**
     * @return string
     */
    public static function webpack_theme_folder(): string
    {
        return THEMES_DIR . '/' . self::get_theme_for_webpack();
    }

    /**
     * @return bool
     */
    public function IsNotWebpackDevServer(): bool
    {
        return $this->IsWebpackDevServer() ? false : true;
    }

    /**
     * @return bool
     */
    public function IsWebpackDevServer(): bool
    {
        $override = $this->Config()->get('is_webpack_server_override');
        if ($override) {
            return $override;
        }
        if (Director::isDev()) {
            $socket = @fsockopen(
                $this->Config()->get('webpack_socket_server'),
                $this->Config()->get('webpack_port'),
                $errno,
                $errstr,
                1
            );
            return ! $socket ? false : true;
        }

        return false;
    }

    /**
     * @return string e.g. resources/themes/app_dist
     */
    public function WebpackFolderOnFrontEnd(): string
    {
        return ModuleResourceLoader::resourceURL($this->WebpackFolderOnFileSystem(false));
    }

    public function getWebpackFile(string $file, ?bool $break = true): string
    {
        foreach (['.gz',  ''] as $extension) {
            $fileLocation = $this->WebpackFolderOnFileSystem(true) . '/' . $file . $extension;
            if (file_exists($fileLocation)) {
                $hash = filemtime($fileLocation);
                return $this->WebpackFolderOnFrontEnd() . '/' . $file . '?x=' . $hash;
            }
        }
        if ($break && Director::isDev()) {
            user_error('Could find: ' . $fileLocation . ' based on ' . $file . ',' . $this->WebpackFolderOnFileSystem(true));
        }
        return '';
    }

    /**
     * @param  boolean $withBase include baseFolder?
     * @return string return /var/www/html/themes/app_dist
     */
    public function WebpackFolderOnFileSystem(?bool $withBase = true): string
    {
        $location = '';
        if ($withBase) {
            $location .= Director::baseFolder() . '/';
        }
        $location .= THEMES_DIR . '/' . self::get_theme_for_webpack() . '/' . $this->Config()->get('webpack_distribution_folder_extension');

        return $location;
    }

    // /**
    //  * @return string
    //  */
    // public function WebpackBaseURL(): string
    // {
    //     $webpackServer = $this->Config()->get('webpack_server');
    //     if (! $webpackServer) {
    //         $webpackServer = Director::AbsoluteBaseURL('/');
    //     }
    //     if ($this->IsWebpackDevServer()) {
    //         $webpackServer = rtrim($webpackServer, '/') . ':' . $this->Config()->get('webpack_port');
    //     }
    //
    //     return $webpackServer;
    // }
}
