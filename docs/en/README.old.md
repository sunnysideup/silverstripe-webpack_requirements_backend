# Webpack Requirements Backend

This module refines the Requirements class for Silverstripe. It moves all your CSS and JS requirements into one folder for easy inclusion via webpack. 

## install

Install this module using composer (or your preferred method).

use:

```php
<?php
RequirementsBackendForWebpack::set_copy_css_to_folder("themes/base/source/css/requirements");
RequirementsBackendForWebpack::set_copy_js_to_folder("themes/base/source/js/requirements");

 ```
to set the folders where the required files are saved.

Then, as you browse through the website, required files will be saved on those folders.

### careful ...
_*DO NOT CHANGE THE FILES IN THESE REQUIREMENTS FOLDERS AS THEY WILL BE OVERRITTEN BY NEW VERSIONS FROM THE MODULES / REGULAR THEME FOLDERS*_


## usage

The `customScripts` and `customCSS` calls work as normal. On the other hand, any css files included are copied to a special directory (for inclusion in a `webpack`) instead of being included in the HTML output.

You can go through this requirements folder and pick the CSS and JS you would like to include in your `webpack` using the standard webpack methodologies.

If you would like to change any CSS / JS then please change the original files only. 

External requirements are written to a `requirements file` called `requirements` in the same folder to give you greater flexibility on how to manage these. 
