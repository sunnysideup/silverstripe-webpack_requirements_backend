
<script src="{$WebpackBaseURL}{$ThemeDir}_{$WebpackDistributionFolderExtension}/bundle.js?x=$WebpackFileHash(JS)" charset="utf-8"></script>

<% if $IsWebpackDevServer %>
<script src="{$ThemeDir}_node_modules/node_modules/jquery/dist/jquery.js" charset="utf-8"></script>
<% end_if %>
