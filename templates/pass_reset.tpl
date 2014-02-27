{include file="header.tpl"}
{if $step == 1}
<form class="pure-form pure-form-aligned" method="post" action="authors/reset">
    <div class="pure-control-group"><label for="email">Email </label><input type="email" name="email" /></div>
    <div class="pure-control-group"><label for="captcha">Human Verification</label><img id="captcha" src="captcha" /></div>
    <div class="pure-control-group"><label></label><input type="text" name="captcha" /></div>
    <div class="pure-controls"><input type="button" class="pure-button" value="I can't read that" onClick="refreshCaptcha();"/><input type="submit" class="pure-button pure-button-primary" value="Reset" /></div>
</form>
{else}
{if $step == 2}
{$tid}
<form class="pure-form" method="post" action="authors/reset/{$uid}">
    <label for="code">Reset Code </label><input type="text" name="code" />
    <input type="submit" class="pure-button pure-button-primary" value="Reset" />
</form>
{else}
<form class="pure-form" method="post" action="authors/reset/{$uid}">
    <label for="pass">New Password </label><input type="password" name="pass" />
    <input type="submit" class="pure-button pure-button-primary" value="Reset Password" />
</form>
{/if}
{/if}
{include file="footer.tpl"}
