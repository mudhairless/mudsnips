{include file="header.tpl"}
    {if $step == 1}
        <form class="pure-form pure-form-stacked" name="configuration" action="install.php" method="post">
        <label for="site_name">Site Name</label><input type="text" name="site_name" value="MudSnips"/><br/>
        <label for="site_owner">Site Owner</label><input type="text" name="site_owner" value="Your Name"/><br/>
        <label for="site_contact">Site Owner Contact (used as link)</label><input type="url" name="site_contact" value="mailto:yourmail@mail.com"/><br/>
        <label for="base_href">Base Href</label><input type="text" name="base_href" value="/" /><br/>
        <label for="db_connect">Database Connection String</label><input type="text" name="db_connect" value="sqlite://data/db.sqlite"/><br/>
        <input type="submit" value="Proceed" />
        </form>
    {else}
    <h3>How did you get here?</h3>
    {/if}
{include file="footer.tpl"}
