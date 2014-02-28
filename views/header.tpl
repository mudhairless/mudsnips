<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>{$title} | {$site_name}</title>
<base href="/~mud/snippets/" />
<link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.4.2/pure-min.css">
<link rel="stylesheet" href="assets/styles/style.css" />
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/mootools/1.4.5/mootools-yui-compressed.js"></script>
<script type="text/javascript" src="assets/scripts/Form.PasswordStrength.js"></script>
<script type="text/javascript" src="assets/scripts/main.js"></script>
</head>
<body>
    <div id="layout">
    {include file="menu.tpl"}
    <div id="main">
    <div class="content">
        <div id="header"><h1>{$title}</h1></div>
        {if isset($message)}<div id="error">{$message}</div>{/if}

