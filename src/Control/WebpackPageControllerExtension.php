<?php

namespace Sunnysideup\WebpackRequirementsBackend\Control;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use Sunnysideup\WebpackRequirementsBackend\Api\Configuration;

/**
 * Class \Sunnysideup\WebpackRequirementsBackend\Control\WebpackPageControllerExtension
 *
 * @property \SilverStripe\CMS\Controllers\ContentController|\Sunnysideup\WebpackRequirementsBackend\Control\WebpackPageControllerExtension $owner
 */
class WebpackPageControllerExtension extends Extension
{
    /**
     * e.g. app.
     *
     * @var string
     */
    private static $distilled_file_base_name_css = 'main';
    private static $distilled_file_base_name_js = 'app';
    private static $distilled_file_base_name_js_vendor = 'runtime';

    public function AppCSSLocation(): string
    {
        return $this->getWebpackFile($this->getOwner()->Config()->get('distilled_file_base_name_css') . '.css');
    }

    public function AppVendorJSLocation(?bool $strict = false): string
    {
        return $this->getWebpackFile($this->getOwner()->Config()->get('distilled_file_base_name_js_vendor') . '.js', false);
    }

    public function AppJSLocation(): string
    {
        return $this->getWebpackFile($this->getOwner()->Config()->get('distilled_file_base_name_js') . '.js');
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
