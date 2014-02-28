var ii = 0;
function refreshCaptcha() {
    var ifield = $('captcha');
    ifield.src = "captcha?"+ii;
    ii++;
}

window.onload = function (window, document) {

    //var pf = new Form.PasswordStrength('password');

    var layout   = $('layout');
    var menu     = $('menu');
    var menuLink = $('menuLink');

    $('code_editor').addEvent('keydown', function (e) {
        if(e.key === "tab") {
            this.set('value',this.get('value')+'    ');
            return false;
        }
    });

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
