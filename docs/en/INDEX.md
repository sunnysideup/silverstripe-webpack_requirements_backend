# Webpack Requirements Backend

This module refines the Requirements class for Silverstripe. It moves all your CSS and JS requirements into one folder for easy inclusion via webpack.

## what it does

The `customScripts` and `customCSS` calls work as normal. On the other hand, any css and js files included via requirement calls (in `.php` or `.ss` files) are copied to a special directory (for inclusion in a `webpack`) instead of being included in the HTML output. You can then include your webpack files by adding two includes to your `Page.ss` template:

 * WebpackCSSLinks
 * WebpackJSLinks

 Your html template should look something like this (note the **includes**)
 ```html
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Webpack example</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <% include WebpackCSSLinks %>
    </head>
    <body>
        <% include WebpackJSLinks %>
    </body>
</html>
 ```

All css/jss files required will be copied to a special requirements folder. You can go through this requirements folder and pick the CSS and JS you would like to include in your `webpack` using the standard webpack methodologies.

_*DO NOT CHANGE THE FILES IN THESE COLLATED REQUIREMENTS FOLDERS AS THEY WILL BE OVERWRITTEN BY NEW VERSIONS FROM THE MODULES / REGULAR THEME FOLDERS*_

If you would like to change any CSS / JS then please change the original files only.

External requirements (e.g. https://www.google.com/better.css) are written to a `requirements file` called `requirements` in the same folder to give you greater flexibility on how to manage these.

You can also exclude files from being blocked so that they can be included as per usual (see configs).
