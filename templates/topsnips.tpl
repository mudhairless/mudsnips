{include file="header.tpl"}

{if !isset($message)}
<ul class="pagecounter" style="counter-reset: section {$start_index};">
{foreach $snippets as $snippet}
    <li><a href="snippet/{$snippet->id}">{$snippet->title}</a></li>
{/foreach}
</ul>
{/if}

{if isset($curpage)}
<ul class="pure-paginator">
    {if $curpage == 1}
        <li><a class="pure-button pure-button-disabled" href="{$url_prefix}{$curpage}">&#171;</a></li>
    {else}
        <li><a class="pure-button prev" href="{$url_prefix}{$curpage-1}">&#171;</a></li>
    {/if}
    {foreach $pages as $page}
        <li><a class="pure-button{if $curpage == $page} pure-button-active{/if}" href="{$url_prefix}{$page}">{$page}</a></li>
    {/foreach}
    {if $curpage == $numpages}
        <li><a class="pure-button pure-button-disabled" href="{$url_prefix}{$curpage}">&#187;</a></li>
    {else}
        <li><a class="pure-button next" href="{$url_prefix}{$curpage+1}">&#187;</a></li>
    {/if}
</ul>
{/if}
{include file="footer.tpl"}
