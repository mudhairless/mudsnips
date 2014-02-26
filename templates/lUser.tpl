{include file="header.tpl"}
<div id='signup'>
{if !isset($author)}

<h1>Author Profile</h1>
<img src="{$gravatar}" /><br/>
Name: {$user->name}<br/>
Website: {$user->url}<br/>
About: {$user->about}

{else}

{if $author['id'] == $user->id}

<form class="pure-form pure-form-stacked"name="update_user" method="post" action="author/{$author['id']}">
    <h2>Update Profile</h2>
    <label for="name">Your Name</label><input name="name" type="text" value="{$user->name}" />
    <label for="url">Homepage</label><input name="url" type="url" value="{$user->url}" />
    <label for="about-me">About Me</label><textarea cols="40" rows="10" name="about-me">{$user->about}</textarea>
    <input type="hidden" name="_METHOD" value="PUT"/>
    <input type="submit" value="Update Account" /><br />
</form>

<hr />

<form class="pure-form pure-form-stacked"name="update_user" method="post" action="author/{$author['id']}">
    <h2>Change Account Info</h2>
    <label for="email">Email</label><input type="email" name="email" value="{$author['email']}" />
    <label for="old-password">Old Password</label><input type="password" name="old-password" />
    <label for="new-password">New Password</label><input type="password" name="new-password" />
    <label for="captcha">Human Verification</label><img id="captcha" src="captcha" /><input type="text" name="captcha" />
    <input type="hidden" name="_METHOD" value="PUT"/>
    <input type="button" value="I can't read that" onClick="refreshCaptcha();"/>
    <input type="submit" value="Change Password" />
</form>


{else}

<h1>Author Profile</h1>
<img src="{$gravatar}" /><br/>
Name: {$user->name}<br/>
Website: {$user->url}<br/>
About: {$user->about}

{/if}

{/if}
</div>
{include file="footer.tpl"}
