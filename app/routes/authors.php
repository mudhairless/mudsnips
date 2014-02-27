<?php

$app->get('/authors/login', function() use ($smarty,$app) {
    $redir = $app->getCookie('redir');
    $app->deleteCookie('redir');
    if(check_logged_in($smarty)) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->deleteCookie('user_login');
        return;
    }
    $smarty->assign('redir',$redir);
    $smarty->assign('title','Snippets Login');
    $smarty->display('login.tpl');
});

$app->post('/authors/login', function() use ($app) {
    $useremail = $app->request->post('email');
    $rawpass = $app->request->post('password');
    try {
    $user = Author::find('all',array('conditions' => array('email = ?',$useremail)));
    }
    catch (Exception $e) {
        $app->response->headers->set('Location',BASE_HREF.'/authors/login');
        $app->setCookie('message','The username or password do not match or are otherwise not correct');
        return;
    }
    $pass = pbkdf2('',$rawpass,$user[0]->salt,1024,512);
    if($pass != $user[0]->password) {
        $app->response->headers->set('Location',BASE_HREF.'/authors/login');
        $app->setCookie('message','The username or password do not match or are otherwise not correct');
        return;
    }
    $redir = $app->request->post('redir');
    if($redir == '') $redir = '/';
    $app->response->headers->set('Location',BASE_HREF.$redir);
    $app->deleteCookie('redir');
    $app->setCookie('user_login',$user[0]->id,'7 days');
});

$app->get('/authors/new', function() use ($smarty,$app) {
    //show new author form
    if(ALLOW_REGISTRATION){
        $smarty->assign('title','Create a new account');
        $smarty->assign('salt',gen_salt());
        $smarty->display('cUser.tpl');
    } else {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','New user registrations are disabled.');
        $app->response->setStatus(400);
    }
});

$app->delete('/author/:id', function($id) use ($app) {
    $user = Author::find($app->getCookie('user_login'));
    $duser = Author::find($id);
    if($user->id != 1) {
        $app->response->headers->set('Location',BASE_HREF.'/author/'.$id);
        $app->setCookie('message','Only an administrator can do that.');
        $app->response->setStatus(403);
        return;
    }
    if( $id == 1) {
        $app->response->headers->set('Location',BASE_HREF.'/author/1');
        $app->setCookie('message','You cannot delete the administrator account.');
        $app->response->setStatus(403);
        return;
    }
    $duser->delete();
    $msg = 'Deleted user '.$duser->name;
    try {
        $snips = Snippet::find('all',array('conditions' => array('author = ?',$id)));
    } catch (Exception $e) {
        $msg = $msg . ', no snippets to delete.';
    }
    if(count($snips) > 0) {
        $msg = $msg . ', also deleted ' . count($snips) . ' snippets as well.';
        foreach($snips as $snip) {
            $snip->delete();
        }
    }
    $app->response->headers->set('Location',BASE_HREF.'/');
    $app->setCookie('message',$msg);
});

$app->get('/author/:id', function($id) use ($app,$smarty) {
    //get author page
    check_logged_in($smarty);
    try {
    $user = Author::find($id);
    }
    catch (Exception $e){
        $app->response->setStatus(404);
        return;
    }
    if($user == null){
        //error out
        $app->response->setStatus(404);
        return;
    } else {
        $smarty->assign('gravatar',get_gravatar($user->email));
        $smarty->assign('title','Author Profile');
        $smarty->assign('user',$user);
        $smarty->display('lUser.tpl');
    }
});

$app->post('/author', function() use ($app) {
    //create author
    if(!ALLOW_REGISTRATION){
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','New user registrations are disabled.');
        $app->response->setStatus(400);
        return;
    }
    $fa = Author::find('all',array('conditions' => array('email = ?',$app->request->post('email'))));
    if($fa != null) {
        $app->response->headers->set('Location',BASE_HREF.'/authors/new');
        $app->setCookie('message','User already exists.');
        return;
    }
    $cookie = $app->getCookie('create_user_form');
    if($cookie == null || $cookie != $app->request->post('captcha')){
        $app->response->headers->set('Location',BASE_HREF.'/authors/new');
        $app->setCookie('message','Captcha does not match.');
        return;
    }
    $fa = new Author();
    $fa->name = htmlentities($app->request->post('name'));
    $fa->email = htmlentities($app->request->post('email'));
    $fa->url = htmlentities($app->request->post('url'));
    $fa->password = pbkdf2('',$app->request->post('password'),$app->request->post('verify'),1024,512);
    $fa->salt = $app->request->post('verify');
    $fa->about = htmlentities($app->request->post('about-me'));
    if($fa->save() == true){
        $app->response->headers->set('Location',BASE_HREF.'/author/'.$fa->id);
        return;
    } else {
        $app->response->headers->set('Location',BASE_HREF.'/authors/new');
        $app->setCookie('message','Database error');
        return;
    }

});

$app->put('/author/:id', function($id) use ($app,$smarty) {
    //update author
    $logged_in = $app->getCookie('user_login');
    if($logged_in != $id) {
        $app->response->headers->set('Location',BASE_HREF.'/authors/login');
        $app->setCookie('redir',BASE_HREF.'/author/'.$id);
        $app->setCookie('message','You must be logged in to perform that action.');
        return;
    }
    $author = Author::find($id);
    $email = $app->request->put('email');
    if( $email != '' ) {
        //change pass/email
        $cookie = $app->getCookie('create_user_form');
        if($cookie == null || $cookie != $app->request->post('captcha')){
            $app->response->headers->set('Location',BASE_HREF.'/author/'.$id);
            $app->setCookie('message','Captcha does not match.');
            return;
        }
        $cpass = pbkdf2('',$app->request->put('old-password'),$author->salt,1024,512);
        if($cpass == $author->password) {
            $newsalt = gen_salt();
            $author->password = pbkdf2('',$app->request->put('new-password'),$newsalt,1024,512);
            $author->salt = $newsalt;
            if($email != $author->email)
                $author->email = $email;
            if(!$author->save()) {
                $app->response->headers->set('Location',BASE_HREF.'/author/'.$id);
                $app->setCookie('message','Unknown error trying to update database.');
            }
        }
    } else {
        //update profile
        $author->about = $app->request->put('about-me');
        $author->url = $app->request->put('url');
        $author->name = $app->request->put('name');
        if(!$author->save()) {
            $app->response->headers->set('Location',BASE_HREF.'/author/'.$id);
            $app->setCookie('message','Unknown error trying to update database.');
        }
    }
    $app->response->headers->set('Location',BASE_HREF.'/author/'.$id);

});

$app->get('/authors/reset(/:code)', function($code = null) use ($app,$smarty) {
    if($code == null){
        $smarty->assign('title','Reset Account Password');
        $smarty->assign('step',1);
        $smarty->display('pass_reset.tpl');
    } else {
        if($code == 'step2') {
            //show reset code form
            $smarty->assign('tid',$app->getCookie('tid'));
            $smarty->assign('title','Enter Reset Code');
            $smarty->assign('step',2);
            $smarty->assign('uid',$app->getCookie('uid'));
            $smarty->display('pass_reset.tpl');
        } else {
            //show pass reset form
            $app->setCookie('rcode',$code);
            $uid = $app->getCookie('uid');
            try {
                $uidb = Password_reset::find('all',array('conditions' => array('code = ?', $code)));
            } catch (Exception $e) {
                if($uid == '') {
                    $app->response->headers->set('Location',BASE_HREF.'/');
                    $app->setCookie('message','Invalid token.');
                } else {
                    $uidb = array( new stdClass( ) );
                    $uidb->author = $uid;
                }
            }
            if($uid != '' && $uidb[0]->author == $uid) {
                $smarty->assign('uid',$uid);
            } else {
                $smarty->assign('uid',$uidb[0]->author);
            }
            $smarty->assign('title','Enter New Password');
            $smarty->assign('step',3);
            $smarty->display('pass_reset.tpl');
        }
    }
});

$app->post('/authors/reset(/:id)', function($id = null) use ($app) {
    if($id != null) {
        $user = Author::find($id);
        if($app->request->post('code') != '') {
            //from step2
            $app->response->headers->set('Location',BASE_HREF.'/authors/reset/'.$app->request->post('code'));
            return;
        } else {
            //from step3
            $code = $app->getCookie('rcode');
            try {
                $rcode = Password_reset::find('all', array('conditions' => array('author = ?',$id)));
            } catch (Exception $e) {
                $app->response->headers->set('Location',BASE_HREF.'/');
                $app->setCookie('message','Nothing to do.');
                return;
            }
            if($code != $rcode[0]->code) {
                $app->response->headers->set('Location',BASE_HREF.'/');
                $app->setCookie('message','Reset codes do not match.');
                return;
            }
            $cdate = new DateTime($rcode[0]->created_at);
            $now = new DateTime(null);
            $ddiff = $now->diff($cdate);
            if($ddiff->i > 15 && $ddiff->invert == 1) {
                //reset codes
                $app->response->headers->set('Location',BASE_HREF.'/authors/login');
                $app->setCookie('message','Code has expired, please try again.');
                foreach($rcode as $rr) {
                    $rr->delete();
                }
                return;
            }
            $newsalt = gen_salt();
            $user->password = pbkdf2('',$app->request->post('pass'),$newsalt,1024,512);
            $user->salt = $newsalt;
            $user->save();
            $app->response->headers->set('Location',BASE_HREF.'/authors/login');
            $app->setCookie('message','Your password has been reset.');
            foreach($rcode as $rr) {
                $rr->delete();
            }
            return;
        }
    } else {
        try {
            $user = Author::find('all',array('conditions' => array('email = ?',$app->request->post('email'))));
        } catch (Exception $e) {
            $app->response->headers->set('Location',BASE_HREF.'/authors/login');
            $app->setCookie('message','Could not locate account, is it spelled correctly?');
            $app->response->setStatus(401);
            return;
        }
        $cookie = $app->getCookie('create_user_form');
        if($cookie == null || $cookie != $app->request->post('captcha')){
            $app->response->headers->set('Location',BASE_HREF.'/authors/reset');
            $app->setCookie('message','Captcha does not match.');
            return;
        }
        try {
            $check = Password_reset::find('all',array('conditions' => array('email = ?',$app->request->post('email'))));
            foreach($check as $cc) {
                $cc->delete();
            }
        } catch (Exception $e) {
            //ignore
        }
        $rc = new Password_reset();
        $rc->code = hash('sha256',rand_string(512));
        $rc->author = $user[0]->id;
        $rc->save();
        $app->setCookie('tid',$rc->created_at);

        $body = 'Hello '.$user[0]->name.",\n" .
                "Someone has requested a password reset\n" .
                "for ".SITE_NAME.".\n" .
                "If this was not you you can ignore this message.\n" .
                "If this was you use this link to reset password:\n" .
                '<a href="'.BASE_HREF.'/authors/reset/'.$rc->code.'">'.BASE_HREF.'/authors/reset/'.$rc->code.'</a>'."\n".
                'or enter this code: '. $rc->code . ' in the form.'."\n".
                '- admin'."\n".
                '(This code will self destruct in 15 minutes.)';
        $headers = 'From: '. SITE_CONTACT."\r\n".'X-Mailer: php';
        if(!mail($user[0]->email,SITE_NAME." Password Reset",$body,$headers)){
            $app->response->headers->set('Location',BASE_HREF.'/');
            $app->setCookie('message','There was an issue sending the reset email, please try again later.');
            $app->response->setStatus(500);
            return;
        }
        $app->response->headers->set('Location',BASE_HREF.'/authors/reset/step2');
        $app->setCookie('message','Please check your email.');
        $app->setCookie('uid',$user[0]->id);
    }
});
