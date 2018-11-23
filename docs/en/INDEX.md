# Webpack Requirements Backend

This module refines the Requirements class for Silverstripe. It moves all your CSS and JS requirements into one folder for easy inclusion via webpack.

## what it does

You need to add two includes to your `Page.ss` template:
 * WebpackCSSLinks
 * WebpackJSLinks
 Your html template should look something like this:
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
     <script
         src="https://code.jquery.com/jquery-3.3.1.min.js"
         integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
         crossorigin="anonymous">
     </script>
     <% include WebpackJSLinks %>
 </body>
 </html>
 ```

The `customScripts` and `customCSS` calls work as normal. On the other hand, any css files included are copied to a special directory (for inclusion in a `webpack`) instead of being included in the HTML output.

_*DO NOT CHANGE THE FILES IN THESE COLLATED REQUIREMENTS FOLDERS AS THEY WILL BE OVERWRITTEN BY NEW VERSIONS FROM THE MODULES / REGULAR THEME FOLDERS*_

You can go through this requirements folder and pick the CSS and JS you would like to include in your `webpack` using the standard webpack methodologies.

If you would like to change any CSS / JS then please change the original files only.

External requirements are written to a `requirements file` called `requirements` in the same folder to give you greater flexibility on how to manage these.

You can also exclude files from being blocked so that they can be included as per usual.
