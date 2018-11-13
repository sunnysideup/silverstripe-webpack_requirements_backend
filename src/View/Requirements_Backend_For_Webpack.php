<?php

namespace Sunnysideup\WebpackRequirementsBackend\View;





use Exception;





use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ThemeResourceLoader;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\SSViewer;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Assets\Filesystem;
use Sunnysideup\WebpackRequirementsBackend\Control\WebpackPageControllerExtension;
use SilverStripe\View\Requirements_Backend;
use SilverStripe\Core\Flushable;



/**
 * Requirements_Backend_For_Webpack::set_files_to_ignore(
 *  'app/javascript/myfile.js';
 * );
 *
 *
 */
class Requirements_Backend_For_Webpack extends Requirements_Backend implements Flushable
{

    /**
     * @var string
     */
    private static $webpack_variables_file_location = 'themes/webpack-variables.js';

    /**
     * we need this method because Requirements_Backend does not extend Object!
     * @param string
     */
    public static function set_webpack_variables_file_location($str)
    {
        self::$webpack_variables_file_location = $tr;
    }

    /**
     * IMPORTANT ... you will use this one more than others ...
     * e.g. /app/javascript/test.js
     * @var array
     */
    private static $files_to_ignore = [];

    /**
     * we need this method because Requirements_Backend does not extend Object!
     * @var array $array
     */
    public static function set_files_to_ignore($array)
    {
        self::$files_to_ignore = $array;
    }

    /**
     * @return array
     */
    public static function get_files_to_ignore()
    {
        return self::$files_to_ignore = $array;
    }

    /**
     * @var string
     */
    private static $working_theme_folder_extension = "app";

    /**
     * we need this method because Requirements_Backend does not extend Object!
     * @var string $string
     */
    public static function set_working_theme_folder_extension($string)
    {
        self::$working_theme_folder_extension = $string;
    }


    /**
     * we need this method because Requirements_Backend does not extend Object!
     * @return string
     */
    public static function get_working_theme_folder_extension()
    {
        return self::$working_theme_folder_extension = $string;
    }

    /**
     * @var string
     */
    private static $copy_css_to_folder = "src/raw_requirements/css";

    /**
     * we need this method because Requirements_Backend does not extend Object!
     * @var string $string
     */
    public static function set_copy_css_to_folder($string)
    {
        self::$copy_css_to_folder = $string;
    }

    /**
     * @var string
     */
    private static $copy_js_to_folder = "src/raw_requirements/js";

    /**
     * we need this method because Requirements_Backend does not extend Object!
     * @param string $string
     */
    public static function set_copy_js_to_folder($string)
    {
        self::$copy_js_to_folder = $string;
    }

    /**
     * @var array
     */
    private static $urls_to_exclude = array();

    /**
     * we need this method because Requirements_Backend does not extend Object!
     * @param array $array
     */
    public static function set_urls_to_exclude($a)
    {
        self::$urls_to_exclude = $a;
    }

    /**
     *
     * @return array
     */
    public static function get_urls_to_exclude()
    {
        return self::$urls_to_exclude;
    }

    /**
     * @var bool
     */
    private static $force_update = true;

    /**
     *
     * @param bool
     */
    public static function set_force_update($bool)
    {
        self::$force_update = $bool;
    }


    /**
     *
     * @return bool
     */
    public static function get_force_update($bool)
    {
        return self::$force_update;
    }

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
     * Whether to combine CSS and JavaScript files
     *
     * @var bool
     */
    protected $combined_files_enabled = false;


    /**
     * Force the JavaScript to the bottom of the page, even if there's a script tag in the body already
     *
     * @var boolean
     */
    protected $force_js_to_bottom = true;


    /**
     * @return string
     */
    protected static function webpack_current_theme_as_set_in_db()
    {
        $v = null;
        if (Security::database_is_ready()) {
            try {
                $v = SiteConfig::current_site_config()->Theme;
            } catch (Exception $e) {
                //dont worry!
            }
        }
        if (! $v) {
            $v = Config::inst()->get(SSViewer::class, 'current_theme');
        }

        if (! $v) {
            user_error('We recommend you set a theme as soon as possible.', E_USER_NOTICE);
        }

        return $v;
    }


    /**
     * @return string
     */
    protected static function webpack_theme_folder_for_customisation()
    {
        return '/themes/'.self::webpack_current_theme_as_set_in_db().'_'.self::$working_theme_folder_extension.'/';
    }


    /**
     * Update the given HTML content with the appropriate include tags for the registered
     * requirements. Needs to receive a valid HTML/XHTML template in the $content parameter,
     * including a head and body tag.
     *
     * @param string $templateFile No longer used, only retained for compatibility
     * @param string $content      HTML content that has already been parsed from the $templateFile
     *                             through {@link SSViewer}
     * @return string HTML content augmented with the requirements tags
     */
    public function includeInHTML($templateFile, $content)
    {
        if ($this->themedRequest()) {

            //=====================================================================
            // start copy-ish from parent class

            $hasHead = (strpos($content, '</head>') !== false || strpos($content, '</head ') !== false) ? true : false;
            $hasRequirements = ($this->css || $this->javascript || $this->customCSS || $this->customScript || $this->customHeadTags) ? true: false;
            if ($hasHead && $hasRequirements) {
                $requirements = '';
                $jsRequirements = '';
                $requirementsCSSFiles = array();
                $requirementsJSFiles = array();

                // Combine files - updates $this->javascript and $this->css
                $this->process_combined_files();
                $isDev = Director::isDev();
                foreach (array_diff_key($this->javascript, $this->blocked) as $file => $dummy) {
                    $ignore = in_array($file, self::$files_to_ignore) ? true : false;
                    if ($isDev || $ignore) {
                        $path = Convert::raw2xml($this->path_for_file($file));
                        if ($path) {
                            if ($isDev) {
                                $requirementsJSFiles[$path] = $path;
                            }
                            if (in_array($file, self::$files_to_ignore)) {
                                $jsRequirements .= "<script type=\"text/javascript\" src=\"$path\"></script>\n";
                            }
                        }
                    }
                }

                // Add all inline JavaScript *after* including external files they might rely on
                if ($this->customScript) {
                    foreach (array_diff_key($this->customScript, $this->blocked) as $script) {
                        $jsRequirements .= "<script type=\"text/javascript\">\n//<![CDATA[\n";
                        $jsRequirements .= "$script\n";
                        $jsRequirements .= "\n//]]>\n</script>\n";
                    }
                }

                foreach (array_diff_key($this->css, $this->blocked) as $file => $params) {
                    $ignore = in_array($file, self::$files_to_ignore) ? true : false;
                    if ($isDev || $ignore) {
                        $path = Convert::raw2xml($this->path_for_file($file));
                        if ($path) {
                            $media = (isset($params['media']) && !empty($params['media'])) ? $params['media'] : "";
                            if ($isDev) {
                                $requirementsCSSFiles[$path."_".$media] = $path;
                            }
                            if ($ignore) {
                                if ($media !== '') {
                                    $media = " media=\"{$media}\"";
                                }
                                $requirements .= "<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"$path\" />\n";
                            }
                        }
                    }
                }

                foreach (array_diff_key($this->customCSS, $this->blocked) as $css) {
                    $requirements .= "<style type=\"text/css\">\n$css\n</style>\n";
                }

                foreach (array_diff_key($this->customHeadTags, $this->blocked) as $customHeadTag) {
                    $requirements .= "$customHeadTag\n";
                }

                // Remove all newlines from code to preserve layout
                $jsRequirements = preg_replace('/>\n*/', '>', $jsRequirements);

                // Forcefully put the scripts at the bottom of the body instead of before the first
                // script tag.
                $content = preg_replace("/(<\/body[^>]*>)/i", $jsRequirements . "\\1", $content);

                // Put CSS at the bottom of the head
                $content = preg_replace("/(<\/head>)/i", $requirements . "\\1", $content);

                //end copy-ish from parent class
                //=====================================================================

                //copy files ...
                if ($this->canSaveRequirements()) {
                    $themeFolderForSavingFiles = self::webpack_theme_folder_for_customisation();
                    //css
                    $cssFolder = $themeFolderForSavingFiles.self::$copy_css_to_folder;

                    foreach ($requirementsCSSFiles as $cssFile) {
                        $this->moveFileToRequirementsFolder($cssFile, $cssFolder);
                    }
                    //js
                    $jsFolder = $themeFolderForSavingFiles.self::$copy_js_to_folder;
                    foreach ($requirementsJSFiles as $jsFile) {
                        $this->moveFileToRequirementsFolder($jsFile, $jsFolder);
                    }
                }
            }
            return $content;
        } else {
            return parent::includeInHTML($templateFile, $content);
        }
    }

    /**
     * Attach requirements inclusion to X-Include-JS and X-Include-CSS headers on the given
     * HTTP Response
     *
     * @param SS_HTTPResponse $response
     */
    public function include_in_response(HTTPResponse $response)
    {
        if ($this->themedRequest()) {
            //do nothing
        } else {
            return parent::include_in_response($response);
        }
        //$this->process_combined_files();
        //do nothing ...
    }

    /**
     *
     *
     *
     * @return bool
     */
    protected function canSaveRequirements()
    {
        if (self::webpack_current_theme_as_set_in_db()) {
            if (Director::isDev()) {
                if ($this->themedRequest()) {
                    $socket = @fsockopen('localhost', 3000, $errno, $errstr, 1);
                    if ($socket) {
                        return true;
                    }
                }
            }
        }
    }

    /**
     *
     *
     * @return bool
     */
    protected function themedRequest()
    {
        return Config::inst()->get(SSViewer::class, 'theme') && Config::inst()->get(SSViewer::class, 'theme_enabled') ? true : false;
    }

    /**
     *
     * @param  string $fileLocation
     * @param  string $folderLocation
     *
     */
    protected function moveFileToRequirementsFolder($fileLocation, $folderLocation)
    {
        $base = Director::baseFolder();
        $folderLocationWithBase = $base . $folderLocation;
        Filesystem::makeFolder($folderLocationWithBase);
        if (!file_exists($folderLocationWithBase)) {
            user_error('Please update Requirements_Backend_For_Webpack for the right folder or create '.$folderLocationWithBase);
        }
        if (strpos($fileLocation, "//") !== false) {
            $logFile = $folderLocationWithBase."/TO.INCLUDE.FROM.PAGE.SS.FILE.log";
            $line = $_SERVER['REQUEST_URI']." | ".$fileLocation;
            $this->addLinesToFile($logFile, $fileLocation);
        } else {
            $from = $fileLocation;
            $to = basename($fileLocation);
            $line = '@import \'site'.$from.'\'';
            $logFile = $folderLocationWithBase."/TO.INCLUDE.USING.WEBPACK.METHODS.log";
            $this->addLinesToFile($logFile, $line);
            if (in_array($fileLocation, self::$files_to_ignore)) {
                //to be completed ...
            } else {
                // if (! file_exists($to) || self::$force_update) {
                //     copy($from, $to);
                // }
            }
        }
    }

    protected function copyIfYouCan($from, $to, $count = 0)
    {
        try {
            copy($from, $to);
        } catch (Exception $e) {
            $count++;
            $this->makeFolderWritable();
            if ($count < 3) {
                $this->copyIfYouCan($from, $to, $count);
            }
        }
    }

    protected function addLinesToFile($fileLocation, $line, $count = 0)
    {
        $line .= "\n";
        try {
            $lines = [];
            if (file_exists($fileLocation)) {
                $lines = file($fileLocation);
            }
            if (! in_array($line, $lines)) {
                //last catch!
                if (is_writable($fileLocation)) {
                    $handle = fopen($fileLocation, 'a');
                    fwrite($handle, $line);
                }
            }
        } catch (Exception $e) {
            $this->makeFolderWritable();
            $count++;
            if ($count < 3) {
                $this->addLinesToFile($fileLocation, $lines, $count);
            }
        }
    }

    protected function makeFolderWritable($fileLocation)
    {
        if (file_exists($fileLocation)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname($fileLocation)));
            foreach ($iterator as $item) {
                chmod($item, '0664');
            }
        }
    }


    public static function flush()
    {
        if (Director::isDev()) {
            $theme = self::webpack_current_theme_as_set_in_db();
            $distributionFolderExtension = Config::inst()->get(WebpackPageControllerExtension::class, 'webpack_distribution_folder_extension');
            if ($theme) {
                //make raw requirements writeable
                $base = Director::baseFolder();
                $themeFolderForCustomisation = self::webpack_theme_folder_for_customisation();
                $rawFolders = [
                    $base.$themeFolderForCustomisation.'src/sass',
                    $base.$themeFolderForCustomisation.''.self::$copy_css_to_folder,
                    $base.$themeFolderForCustomisation.''.self::$copy_js_to_folder,
                    $base.'/'.ThemeResourceLoader::inst()->getPath('UPGRADE-FIX-REQUIRED.foo.bar')/*
### @@@@ START UPGRADE REQUIRED @@@@ ###
FIND: THEMES_DIR
NOTE: Please review update and fix as required 
### @@@@ END UPGRADE REQUIRED @@@@ ###
*/ . "/" . $theme.'_'.$distributionFolderExtension
                ];
                foreach ($rawFolders as $folder) {
                    Filesystem::makeFolder($folder);
                }
                $files = [
                    $base.$themeFolderForCustomisation.'src/main.js',
                    $base.$themeFolderForCustomisation.'src/sass/style.sass'
                ];
                foreach ($files as $file) {
                    if (!file_exists($file)) {
                        file_put_contents($file, '//add your customisations in this file');
                    }
                }

                $varArray = [
                    'themeName' => self::webpack_current_theme_as_set_in_db(),
                    'devWebAddress' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : Director::protocolAndHost(),
                    'distributionFolder' => self::webpack_current_theme_as_set_in_db().'_'.Config::inst()->get(WebpackPageControllerExtension::class, 'webpack_distribution_folder_extension')
                ];
                $str = 'module.exports = '.json_encode($varArray).'';
                @file_put_contents($base.'/'.self::$webpack_variables_file_location, $str);
            }
        }
    }
}
