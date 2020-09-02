<?php

namespace Sunnysideup\WebpackRequirementsBackend\Control;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

use Sunnysideup\WebpackRequirementsBackend\Api\Configuration;

class WebpackPageControllerExtension extends Extension
{
    /**
     * e.g. app
     * @var string
     */
    private static $distilled_file_base_name = 'app';

    public function AppCSSLocation(): string
    {
        return $this->getWebpackFile($this->owner->Config()->get('distilled_file_base_name') . '.css');
    }

    public function AppVendorJSLocation(): string
    {
        return $this->getWebpackFile('vendors~' . $this->owner->Config()->get('distilled_file_base_name') . '.js');
    }

    public function AppJSLocation(): string
    {
        return $this->getWebpackFile($this->owner->Config()->get('distilled_file_base_name') . '.js');
    }

    /**
     * @return bool
     */
    public function IsNotWebpackDevServer(): bool
    {
        return Injector::inst()->get(Configuration::class)->IsNotWebpackDevServer();
    }

    /**
     * @return bool
     */
    public function IsWebpackDevServer(): bool
    {
        return Injector::inst()->get(Configuration::class)->IsWebpackDevServer();
    }


    public function WebpackFolderOnFrontEnd(string $file): string
    {
        return Injector::inst()->get(Configuration::class)->WebpackFolderOnFrontEnd();
    }

    protected function getWebpackFile(string $file): string
    {
        return Injector::inst()->get(Configuration::class)->getWebpackFile($file);
    }

}
