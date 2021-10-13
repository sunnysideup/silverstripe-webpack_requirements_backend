<?php

namespace Sunnysideup\WebpackRequirementsBackend\Control;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use Sunnysideup\WebpackRequirementsBackend\Api\Configuration;

class WebpackPageControllerExtension extends Extension
{
    /**
     * e.g. app.
     *
     * @var string
     */
    private static $distilled_file_base_name = 'app';

    public function AppCSSLocation(): string
    {
        return $this->getWebpackFile($this->getOwner()->Config()->get('distilled_file_base_name') . '.css');
    }

    public function AppVendorJSLocation(?bool $strict = false): string
    {
        $file = 'vendors~' . $this->getOwner()->Config()->get('distilled_file_base_name') . '.js';
        //vendor~app.js is not included if there is no vendor stuff to be included.
        return $this->getWebpackFile($file, false);
    }

    public function AppJSLocation(): string
    {
        return $this->getWebpackFile($this->getOwner()->Config()->get('distilled_file_base_name') . '.js');
    }

    public function IsNotWebpackDevServer(): bool
    {
        return Injector::inst()->get(Configuration::class)->IsNotWebpackDevServer();
    }

    public function IsWebpackDevServer(): bool
    {
        return Injector::inst()->get(Configuration::class)->IsWebpackDevServer();
    }

    public function WebpackFolderOnFrontEnd(string $file): string
    {
        return Injector::inst()->get(Configuration::class)->WebpackFolderOnFrontEnd();
    }

    protected function getWebpackFile(string $file, ?bool $break = true): string
    {
        return Injector::inst()->get(Configuration::class)->getWebpackFile($file, $break);
    }

    protected function WebpackFolderOnFileSystem(?bool $break = true): string
    {
        return Injector::inst()->get(Configuration::class)->WebpackFolderOnFileSystem($break);
    }
}
