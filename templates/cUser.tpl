{include file="header.tpl"}
<div id="signup">
<form class="pure-form pure-form-stacked" name="create_user" method="post" action="author">
    <label for="name">Your Name</label><input name="name" type="text" />
    <label for="email">Email address</label><input name="email" type="email" />
    <label for="password">Password</label><input id="password" name="password" type="password" />
    <label for="url">Homepage</label><input name="url" type="url" />
    <label for="about-me">About Me</label><textarea cols="40" rows="10" name="about-me"></textarea>
    <label for="captcha">Human Verification</label><img id="captcha" src="captcha" /><input type="text" name="captcha" />
    <input type="button" value="I can't read that" onClick="refreshCaptcha();"/>
    <input type="hidden" name="verify" value="{$salt}" />
    <p>Clicking Create Account indicates that you have read and accept our site's <a href="terms">Terms</a> and <a href="privacy">Privacy Policy</a></p>
    <input type="submit" value="Create Account" />
</form>
</div>
{include file="footer.tpl"}
