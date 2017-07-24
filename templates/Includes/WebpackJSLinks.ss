<% if $WebpackDevServer %>
    <script src="http://localhost:3000/{$WebpackProductionFolder}/js/main.js"></script>
<% else %>
    <script src="$MainJSLink"></script>
<% end_if %>
