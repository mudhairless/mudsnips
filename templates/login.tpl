{include file="header.tpl"}
<div id="signup">
<form class="pure-form pure-form-stacked"name="create_user" method="post" action="authors/login">
    <label for="email">Email address</label><input name="email" type="email" />
    <label for="password">Password</label><input name="password" type="password" />
    <input type="submit" value="Login" />
</form>
</div>
{include file="footer.tpl"}
