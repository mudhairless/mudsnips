{include file="header.tpl"}
<div id="signup">
<form class="pure-form pure-form-aligned"name="create_user" method="post" action="authors/login">
    <div class="pure-control-group"><label for="email">Email address</label><input name="email" type="email" /></div>
    <div class="pure-control-group"><label for="password">Password</label><input name="password" type="password" /></div>
    <div class="pure-controls"><input style="width: 8em;" type="submit" class="pure-button pure-button-primary"value="Login" />
    <a href="authors/reset" class="pure-button">Forgot Password?</a></div>
    </div>
</form>
</div>
{include file="footer.tpl"}
