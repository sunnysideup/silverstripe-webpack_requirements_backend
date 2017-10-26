
<script src="{$WebpackBaseURL}{$ThemeDir}_{$WebpackDistributionFolderExtension}/bundle.js?x=$WebpackFileHash(JS)" charset="utf-8"></script>

<% if $IsWebpackDevServer %>
<script src="themes/sswebpack_engine_only/node_modules/jquery/dist/jquery.js" charset="utf-8"></script>
<% end_if %>
