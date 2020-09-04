<?php

namespace Sunnysideup\WebpackRequirementsBackend\Api;

use Exception;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Flushable;

class NoteRequiredFiles implements Flushable
{
    use Configurable;

    /**
     * @var bool
     */
    private static $save_requirements_in_folder = false;

    /**
     * @var string
     */
    private static $copy_css_to_folder = 'raw_requirements/css';

    /**
     * @var string
     */
    private static $copy_js_to_folder = 'raw_requirements/js';

    public static function flush()
    {
        if (Director::isDev()) {
            $theme = Configuration::get_theme_for_webpack();
            if ($theme && self::can_save_requirements()) {
                //make raw requirements writeable
                $base = Director::baseFolder();
                $themeFolderWithBase = $base . '/' . Configuration::webpack_theme_folder();
                $srcFolder = $themeFolderWithBase . '/' . Config::inst()->get(Configuration::class, 'webpack_src_folder_extension');
                $rawFolders = [
                    $themeFolderWithBase,
                    $themeFolderWithBase . '/' . Config::inst()->get(Configuration::class, 'webpack_distribution_folder_extension'),
                    $themeFolderWithBase . '/' . Config::inst()->get(Configuration::class, 'webpack_node_modules_folder_extension'),
                    $themeFolderWithBase . '/' . Config::inst()->get(Configuration::class, 'webpack_raw_requirements_folder_extension'),
                    $srcFolder,
                    $srcFolder . '/' . Config::inst()->get(self::class, 'copy_css_to_folder'),
                    $srcFolder . '/' . Config::inst()->get(self::class, 'copy_js_to_folder'),
                ];
                foreach ($rawFolders as $folder) {
                    Filesystem::makeFolder($folder);
                }
                $files = [
                    $srcFolder . '/main.js',
                    $srcFolder . '/style.scss',
                ];
                foreach ($files as $file) {
                    if (! file_exists($file)) {
                        @file_put_contents($file, '//add your customisations in this file');
                    }
                }

                // $varArray = [
                //     'themeName' => Configuration::get_theme_for_webpack(),
                //     'devWebAddress' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : Director::protocolAndHost(),
                //     'distributionFolder' => Configuration::get_theme_for_webpack().'_'.$this->Config()->get( 'webpack_distribution_folder_extension'),
                // ];
                // $str = 'module.exports = '.json_encode($varArray).'';
                // @file_put_contents($base.'/'.$this->Config()->get('webpack_variables_file_location'), $str);
            }
        }
    }

    /**
     * @return bool
     */
    public static function can_save_requirements(): bool
    {
        if (Director::isDev()) {
            if (Config::inst()->get(self::class, 'save_requirements_in_folder')) {
                if (RequirementsBackendForWebpack::is_themed_request()) {
                    if (Configuration::get_theme_for_webpack()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param  string $fileLocation
     * @param  string $type
     */
    public function noteFileRequired(string $fileLocation, string $type = '')
    {
        if (! $type) {
            $type = pathinfo($fileLocation, PATHINFO_EXTENSION);
        }
        $folderLocation = '';
        switch ($type) {
            case 'js':
                $folderLocation = $this->Config()->get('copy_js_to_folder');
                break;
            case 'css':
                $folderLocation = $this->Config()->get('copy_css_to_folder');
                break;
            default:
                user_error('Please make sure to set type to js or css');
                return;
        }
        $fileLocationArray = explode('?', $fileLocation);
        $fileLocation = array_shift($fileLocationArray);
        $base = Director::baseFolder();
        $themeFolderWithBase = $base . '/' . Configuration::webpack_theme_folder();
        $folderLocationWithBase = $themeFolderWithBase . '/' . $folderLocation;
        Filesystem::makeFolder($folderLocationWithBase);
        if (! file_exists($folderLocationWithBase)) {
            user_error('Please update RequirementsBackendForWebpack for the right folder or create ' . $folderLocationWithBase);
        }
        if (strpos($fileLocation, '//') !== false) {
            $logFile = $folderLocationWithBase . '/TO.INCLUDE.FROM.PAGE.SS.FILE.log';
            $line = $_SERVER['REQUEST_URI'] . ' | ' . $fileLocation;
            $this->addLinesToFile($logFile, $fileLocation);
        } else {
            $from = $fileLocation;
            $line = '@import \'PROJECT_ROOT' . $from . '\'';
            $logFile = $folderLocationWithBase . '/TO.INCLUDE.USING.WEBPACK.METHODS.log';
            $this->addLinesToFile($logFile, $line);
        }
    }

    protected function addLinesToFile($fileLocation, $line, $count = 0)
    {
        $line .= "\n";
        $lines = '';
        try {
            $lines = [];
            if (file_exists($fileLocation)) {
                $lines = file($fileLocation);
            }
            if (! in_array($line, $lines, true)) {
                //last catch!
                if (is_writable($fileLocation)) {
                    $handle = fopen($fileLocation, 'a');
                    fwrite($handle, $line);
                } else {
                    echo '
                    <br />
                    Please run something like: <br />
                    sudo touch ' . $fileLocation . ' && sudo chown www-data ' . $fileLocation . ' && sudo chmod 0775 ' . $fileLocation . '';
                    user_error('
                        Trying to write ' . $line . ' to ' . $fileLocation . '<br />
                        ');
                }
            }
        } catch (Exception $e) {
            $this->makeFolderWritable($fileLocation);
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
}
