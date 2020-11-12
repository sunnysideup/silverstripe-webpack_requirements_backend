<?php

namespace Sunnysideup\WebpackRequirementsBackend\Control;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Controller;

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

    public function AppVendorJSLocation(): ?string
    {
        $file = 'vendors~' . $this->owner->Config()->get('distilled_file_base_name') . '.js';
        if($this->IsWebpackDevServer()) {
            $file = $this->getWebpackFile($file);
            return $file;
        } else {
            $file = Controller::join_links($this->WebpackFolderOnFileSystem() , $file);
            if(file_exists($file) ) {
                user_error(
                    'The following file should only exist if webpack is running (currently it seems it is not running but the file exists)

                    "'.$file.'"

                    You can delete this file or turn on webpack or check the settings in : '.Configuration::class. ' in case webpack is running (check port and location)'

                );
            }
        }
        return null;
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

    protected function getWebpackFile(string $file, ?bool $break = true): string
    {
        return Injector::inst()->get(Configuration::class)->getWebpackFile($file, $break);
    }

    protected function WebpackFolderOnFileSystem(?bool $break = true): string
    {
        return Injector::inst()->get(Configuration::class)->WebpackFolderOnFileSystem($break);
    }
}
