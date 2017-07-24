<% if $WebpackDevServer %>
    <link  type="text/css" rel="stylesheet" href="http://localhost:3000/{$WebpackProductionFolder}/css/main.css" />
<% else %>
    <link type="text/css" rel="stylesheet" href="$MainCSSLink" />
<% end_if %>
