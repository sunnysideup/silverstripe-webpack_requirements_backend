<?php

namespace Sunnysideup\WebpackRequirementsBackend\View;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TaskRunner;
use SilverStripe\View\Requirements_Backend;
use SilverStripe\View\SSViewer;
use Sunnysideup\WebpackRequirementsBackend\Api\Configuration;
use Sunnysideup\WebpackRequirementsBackend\Api\NoteRequiredFiles;

class RequirementsBackendForWebpack extends Requirements_Backend
{
    use Configurable;

    /**
     * Whether to add caching query params to the requests for file-based requirements.
     * Eg: themes/myTheme/js/main.js?m=123456789. The parameter is a timestamp generated by
     * filemtime. This has the benefit of allowing the browser to cache the URL infinitely,
     * while automatically busting this cache every time the file is changed.
     *
     * @var bool
     */
    protected $suffix_requirements = false;

    /**
     * Whether to combine CSS and JavaScript files.
     *
     * @var bool
     */
    protected $combined_files_enabled = false;

    /**
     * Force the JavaScript to the bottom of the page, even if there's a script tag in the body already.
     *
     * @var bool
     */
    protected $force_js_to_bottom = true;

    /**
     * e.g. /app/javascript/test.js.
     *
     * @var array
     */
    private static $files_to_ignore = [
        'vendor/silverstripe/admin/client/dist/styles/bundle.css',
        'vendor/silverstripe/login-forms/client/dist/styles/bundle.css',
        // no-catcha
        'vendor/undefinedoffset/silverstripe-nocaptcha/javascript/NocaptchaField.js',
        // debug bar
        'vendor/lekoala/silverstripe-debugbar/assets/vendor/font-awesome/css/font-awesome.min.css',
        'vendor/lekoala/silverstripe-debugbar/assets/vendor/highlightjs/styles/github.css',
        'vendor/lekoala/silverstripe-debugbar/assets/debugbar.css',
        'vendor/lekoala/silverstripe-debugbar/assets/widgets.css',
        'vendor/lekoala/silverstripe-debugbar/assets/openhandler.css',
        'vendor/lekoala/silverstripe-debugbar/javascript/sqlqueries/widget.css',
        'vendor/lekoala/silverstripe-debugbar/javascript/config/widget.css',
        'vendor/lekoala/silverstripe-debugbar/assets/widgets/mails/widget.css',
        'vendor/lekoala/silverstripe-debugbar/assets/vendor/jquery/dist/jquery.min.js',
        'vendor/lekoala/silverstripe-debugbar/assets/vendor/highlightjs/highlight.pack.js',
        'vendor/lekoala/silverstripe-debugbar/assets/debugbar.js',
        'vendor/lekoala/silverstripe-debugbar/assets/widgets.js',
        'vendor/lekoala/silverstripe-debugbar/assets/openhandler.js',
        'vendor/lekoala/silverstripe-debugbar/javascript/sqlqueries/widget.js',
        'vendor/lekoala/silverstripe-debugbar/javascript/widgets.js',
        'vendor/lekoala/silverstripe-debugbar/javascript/config/widget.js',
        'vendor/lekoala/silverstripe-debugbar/assets/widgets/mails/widget.js',
    ];

    /**
     * @var array
     */
    private static $urls_to_exclude = [];

    /**
     * @var array
     */
    private static $classes_to_exclude = [
        'SilverStripe\\UserForms\\Control\\UserDefinedFormController',
    ];

    /**
     * @var bool
     */
    private static $force_update = true;

    /**
     * @param string $content
     *
     * @return string HTML content
     */
    public function includeInHTML($content)
    {
        if (self::is_themed_request()) {
            //=====================================================================
            // start copy-ish from parent class

            $hasHead = false !== strpos($content, '</head>') || false !== strpos($content, '</head ');
            $hasRequirements = $this->css || $this->javascript || $this->customCSS || $this->customScript || $this->customHeadTags;
            if ($hasHead && $hasRequirements) {
                $requirements = '';
                $jsRequirements = '';
                $requirementsCSSFiles = [];
                $requirementsJSFiles = [];

                // Combine files - updates $this->javascript and $this->css
                $this->processCombinedFiles();
                $isDev = Director::isDev();
                $toIgnore = $this->Config()->get('files_to_ignore');
                foreach (array_keys(array_diff_key($this->javascript, $this->blocked)) as $file) {
                    $ignore = in_array($file, $toIgnore, true);
                    if ($isDev || $ignore) {
                        $path = Convert::raw2xml($this->pathForFile($file));
                        if ($path) {
                            if ($isDev) {
                                $requirementsJSFiles[$path] = $path;
                            }

                            if ($ignore) {
                                $jsRequirements .= "<script type=\"text/javascript\" src=\"{$path}\"></script>\n";
                            }
                        }
                    }
                }

                // Add all inline JavaScript *after* including external files they might rely on
                if ($this->customScript) {
                    foreach (array_diff_key($this->customScript, $this->blocked) as $script) {
                        $jsRequirements .= "<script type=\"text/javascript\">\n//<![CDATA[\n";
                        $jsRequirements .= "{$script}\n";
                        $jsRequirements .= "\n//]]>\n</script>\n";
                    }
                }

                foreach (array_diff_key($this->css, $this->blocked) as $file => $params) {
                    $ignore = in_array($file, $this->Config()->get('files_to_ignore'), true);
                    if ($isDev || $ignore) {
                        $path = Convert::raw2xml($this->pathForFile($file));
                        if ($path) {
                            $media = isset($params['media']) && ! empty($params['media']) ? $params['media'] : '';
                            if ($isDev) {
                                $requirementsCSSFiles[$path . '_' . $media] = $path;
                            }

                            if ($ignore) {
                                if ('' !== $media) {
                                    $media = " media=\"{$media}\"";
                                }

                                $requirements .= "<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"{$path}\" />\n";
                            }
                        }
                    }
                }

                foreach (array_diff_key($this->customCSS, $this->blocked) as $css) {
                    $requirements .= "<style type=\"text/css\">\n{$css}\n</style>\n";
                }

                foreach (array_diff_key($this->customHeadTags, $this->blocked) as $customHeadTag) {
                    $requirements .= "{$customHeadTag}\n";
                }

                // Remove all newlines from code to preserve layout
                $jsRequirements = preg_replace('#>\n*#', '>', $jsRequirements);

                // Forcefully put the scripts at the bottom of the body instead of before the first
                // script tag.
                $content = preg_replace('#(<\\/body[^>]*>)#i', $jsRequirements . '\\1', $content);

                // Put CSS at the bottom of the head
                $content = preg_replace('#(<\\/head>)#i', $requirements . '\\1', $content);

                //end copy-ish from parent class
                //=====================================================================

                //copy files ...
                if (NoteRequiredFiles::can_save_requirements()) {
                    //css
                    foreach ($requirementsCSSFiles as $cssFile) {
                        Injector::inst()->get(NoteRequiredFiles::class)->noteFileRequired($cssFile, 'css');
                    }

                    //js
                    foreach ($requirementsJSFiles as $jsFile) {
                        Injector::inst()->get(NoteRequiredFiles::class)->noteFileRequired($jsFile, 'js');
                    }
                }
            }

            return $content;
        }

        return parent::includeInHTML($content);
    }

    /**
     * Attach requirements inclusion to X-Include-JS and X-Include-CSS headers on the given
     * HTTP Response.
     */
    public function includeInResponse(HTTPResponse $response)
    {
        if (self::is_themed_request()) {
            //do nothing
        } else {
            return parent::includeInResponse($response);
        }

        //$this->process_combined_files();
        //do nothing ...
    }

    public static function is_themed_request(): bool
    {
        if (Config::inst()->get(SSViewer::class, 'theme_enabled')
            &&
            Config::inst()->get(Configuration::class, 'enabled')
        ) {
            if (Controller::has_curr()) {
                $controller = Controller::curr();
                foreach (Config::inst()->get(static::class, 'classes_to_exclude') as $class) {
                    if ($controller instanceof $class) {
                        return false;
                    }
                }

                return ! $controller instanceof LeftAndMain && ! $controller instanceof TaskRunner;
            }
        }

        return false;
    }

    /**
     * required! not sure why....
     */
    public function deleteAllCombinedFiles()
    {
        $combinedFolder = $this->getCombinedFilesFolder();
        if ($combinedFolder) {
            // @var GeneratedAssetHandler|null $assetHandler
            $assetHandler = $this->getAssetHandler();
            if ($assetHandler) {
                $assetHandler->removeContent($combinedFolder);
            }
        }
    }
}
