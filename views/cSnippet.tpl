{include file="header.tpl"}

<form class="pure-form pure-form-stacked" name="csnippet" method="post" action="snippet">
    <label for="title">Title</label><input type="text" name="title" />
    <label for="language">Language</label><select name="language">
        {foreach $languages as $l}
        <option value="{$l@index+1}"{if $l == 'freebasic'} selected="selected"{/if}>{$l|capitalize}</option>
        {/foreach}
    </select>
    <label for="code">Code</label><textarea id="code_editor" cols="80" rows="15" name="code"></textarea>
    <input type="hidden" name="user-id" value="{$author['id']}" />
    <input type="submit" class="pure-button pure-button-primary" value="Create Snippet" />
</form>

{include file="footer.tpl"}
