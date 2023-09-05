<html>
<head>
    <title>{l s='Connect thirty bees account'}</title>
</head>
<body>
    <form style="display:none" action="{$redirectUrl|escape:htmlall}" method="POST">
        {foreach $connectUrls as $key => $connectUrl}
            <input type="hidden" name="connect[{$connectUrl.id}][baseUrl]" value="{$connectUrl.baseUrl}" />
            <input type="hidden" name="connect[{$connectUrl.id}][url]" value="{$connectUrl.url}" />
            <input type="hidden" name="connect[{$connectUrl.id}][code]" value="{$connectUrl.code}" />
        {/foreach}
        <input type="submit" value="{l s='Submit'}" />
    </form>
    <script type="text/javascript">
        const form = document.querySelector('form');
        form.submit();
    </script>
</body>
</html>
