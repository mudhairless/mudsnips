<a href="#menu" id="menuLink" class="menu-link"><span> </span></a>
<div id="menu" class="pure-menu pure-menu-open">
    <a class="pure-menu-heading" href="/~mud/snippets/">{$site_name}</a>
{if !isset($install)}
    <ul>
        <li class=" ">
            <form method="get" class="pure-form" action="search"><input class="pure-input-rounded" style="width: 130px; margin-left: auto; margin-right: auto;" type="search" name="q" value="Search" /></form>
        </li>
        <li class=" ">
            <a href="snippets/recent">Recent Snippets</a>
        </li>
        <li class=" ">
            <a href="snippets/top">Top Snippets</a>
        </li>
        {if !isset($author)}
        <li class=" ">
            <a href="authors/new">Create Account</a>
        </li>
        <li class=" ">
            <a href="authors/login">Login</a>
        </li>
        {else}
        <a class="pure-menu-heading" href="author/{$author['id']}">{$author['name']}</a>
        <li class=" ">
            <img src="{$author['gravatar']}" class="gravatar"/>
        </li>
        <li class=" ">
            <a href="snippets/new">New Snippet</a>
        </li>
        <li class=" ">
            <a href="snippets/by-author/{$author['id']}">My Snippets</a>
        </li>
        <li class=" ">
            <a href="authors/login">Logout</a>
        </li>
        {/if}
    </ul>
{/if}
</div>
