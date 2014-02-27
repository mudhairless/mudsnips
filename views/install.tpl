<html>
<head>
<title>{$title}</title>
</head>
<body>
    {if $step == 1}
        <h1>{$title}</h1>
        <form name="configuration" action="install.php" method="post">
        <label for="site_name">Site Name</label><input type="text" name="site_name" value="MudSnips"/><br/>
        <label for="site_owner">Site Owner</label><input type="text" name="site_owner" value=""/><br/>
        <label for="site_contact">Site Owner Contact URL</label><input type="url" name="site_contact" value=""/><br/>
        <label for="base_href">Base Href</label><input type="text" name="base_href" value="/" /><br/>
        <label for="db_connect">Database Connection String</label><input type="text" name="db_connect" value="sqlite://db.sqlite"/><br/>
        <label for="db_models_dir">Database Models Dir</label><input type="text" name="db_models_dir" value="models"/><br/>
        <input type="submit" value="Proceed" />
        </form>
    {/if}
</body>
</html>
