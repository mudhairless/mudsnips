{include file="header.tpl"}
{if !isset($action)}
<div class="pure-g">
    <div class="pure-u-1-5">
        <h3>Written by</h3>
        <p class="without-top-margin"><a href="author/{$sauth['id']}">{$sauth['name']}</a>
        <br/>View As: <a href="snippet/{$snip_id}/raw">Plain Text</a></p>
    </div>
    <div class="pure-u-1-5">
        <h3></h3>
        <img src="{$sauth['gravatar']}" />
    </div>
    <div class="pure-u-1-5">
        <h3>License Info</h3>
        <p><a rel="license" href="http://creativecommons.org/licenses/by-sa/4.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/4.0/88x31.png" /></a></p>
    </div>
    <div class="pure-u-1-5">
        <h3>Rating</h3>
        <p class="without-top-margin">{$rating}<br/><a href="rate/{$snip_id}/like" class="pure-button pure-button-sm button-like">Like</a><a href="rate/{$snip_id}/dislike" class="pure-button pure-button-sm button-dislike">Dislike</a></p>
    </div>
    {if isset($author)}
    {if $author['id'] == $sauth['id']}
    <div class="pure-u-1-2">
        <h3>Your Snippet Options</h3>
        <p><div class="pure-u-1-3"><a class="pure-button" href="snippet/{$snip_id}/edit">Edit</a></div>
        <div class="pure-u-1-3"><form name="delete_snippet" method="post" action="snippet/{$snip_id}"><input type="hidden" name="_METHOD" value="DELETE"/><input id="changes" class="pure-button" type="submit" value="Delete"/></form></div></p>
    </div>
    {/if}
    {/if}
    <div class="pure-u-1-5">
        <h3>Related</h3>
        <p><a href="snippets/by-lang/{$lang->name}">More in {$lang->name|capitalize}</a></p>
    </div>
</div>
<div class="snippet">
{$code}
</div>
{else}
<form class="pure-form pure-form-stacked" name="edit_snippet" action="snippet/{$snip_id}" method="post">
<input type="hidden" name="_METHOD" value="PUT"/>
<label for="title">Title</label><input type="text" name="title" value="{$ctitle}" />
<label for="language">Language</label><select name="language">
    {foreach $languages as $l}
    <option value="{$l@index+1}"{if $l == $lang->name} selected="selected"{/if}>{$l|capitalize}</option>
    {/foreach}
</select>
<label for="code">Code</label><textarea id="code_editor" cols="80" rows="15">{$raw_code}</textarea>
<input type="submit" value="Submit Changes" />
</form>
{/if}

{include file="footer.tpl"}
