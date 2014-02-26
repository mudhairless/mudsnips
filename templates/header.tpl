<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>{$title} | {$site_name}</title>
<base href="/~mud/snippets/" />
<link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.4.2/pure-min.css">
<link rel="stylesheet" href="style.css" />
<script src="//ajax.googleapis.com/ajax/libs/mootools/1.4.5/mootools-yui-compressed.js"></script>
<script>
var ii = 0;
function refreshCaptcha() {
    var ifield = $('captcha');
    ifield.src = "captcha?"+ii;
    ii++;
}

window.onload = function (window, document) {

    var layout   = $('layout');
    var menu     = $('menu');
    var menuLink = $('menuLink');

    $('changes').addEvent('click', function (e) {
        var i = confirm("Are you sure you want to delete this snippet?\nThis action cannot be undone.");
        if(!i) {
            return false;
        }
    });

    function toggleClass(element, className) {
        var classes = element.className.split(/\s+/),
            length = classes.length,
            i = 0;

        for(; i < length; i++) {
          if (classes[i] === className) {
            classes.splice(i, 1);
            break;
          }
        }
        // The className is not found
        if (length === classes.length) {
            classes.push(className);
        }

        element.className = classes.join(' ');
    }
    menuLink.addEvent('click', function (e) {
        var active = 'active';
        toggleClass(layout, active);
        toggleClass(menu, active);
        toggleClass(menuLink, active);
        return false;
    });

}

</script>
</head>
<body>
    <div id="layout">
    {include file="menu.tpl"}
    <div id="main">
    <div class="content">
        <div id="header"><h1>{$title}</h1></div>
        {if isset($message)}<div id="error">{$message}</div>{/if}

