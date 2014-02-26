<?php
require('vendor/autoload.php');
require('config.php');
require('util.php');

function check_logged_in($smarty) {
    $app = Slim\Slim::getInstance();
    $message = $app->getCookie('message');
    if($message != '') {
        $smarty->assign('message',$message);
        $app->deleteCookie('message');
    }
    $logged_in = $app->getCookie('user_login');
    if($logged_in != '') {
        $author_full = Author::find($logged_in);
        $author = array('id' => $author_full->id,'email' => $author_full->email, 'name' => $author_full->name, 'gravatar' => get_gravatar($author_full->email));
        $smarty->assign('author',$author);
        return true;
    }
    return false;
}

$smarty->assign('site_name',SITE_NAME);
$smarty->assign('site_owner',SITE_OWNER);
$smarty->assign('site_contact',SITE_CONTACT);

check_logged_in($smarty);

$app->get('/', function() use ($smarty,$app) {
    //index
    $smarty->assign('title','Home');
    $smarty->display('index.tpl');
});

$app->get('/terms', function() use ($smarty) {
    $smarty->assign('title','Terms and Conditions');
    $smarty->display('terms.tpl');
});

$app->get('/privacy', function() use ($smarty) {
    $smarty->assign('title','Privacy Policy');
    $smarty->display('privacy.tpl');
});

$app->get('/search(/:page)', function($page = 1) use ($smarty,$app) {
    $req = $app->getCookie('search_query');
    if($req == '') {
        $req = $app->request->get('q');
        $app->setCookie('search_query',$req);
    }
    if($app->request->get('q') != '' && $req != $app->request->get('q')) {
        $req = $app->request->get('q');
        $app->setCookie('search_query',$req);
    }
    $title = array('title LIKE ?','%'.$req.'%');
    $snippets = Snippet::find('all',array('conditions' => $title));
    $numpages = (int)(count($snippets) / 10);
    if($numpages != count($snippets) / 10) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * 10;
    $curpage_bottom = $curpage_top - 10;
    unset($snippets);
    try{
    if($curpage_bottom < 10) {
        $snippets = Snippet::find('all',array('limit' => 10,'conditions' => $title));
    } else {
        $snippets = Snippet::find('all',array('limit' => 10,'offset' => $curpage_bottom, 'conditions' => $title));
    }
    } catch (Exception $e) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','4 '.$e->getMessage());
        $app->response->setStatus(404);
        return;
    }
    $pages = array();
    for($n = 1; $n <= $numpages; $n++)
        $pages[] = $n;
    $smarty->assign('url_prefix','search/');
    $smarty->assign('start_index',$curpage_bottom);
    $smarty->assign('numpages',$numpages);
    $smarty->assign('curpage',$page);
    $smarty->assign('pages',$pages);
    $smarty->assign('title','Search results');
    $smarty->assign('snippets',$snippets);
    $smarty->display('topsnips.tpl');
});

$app->get('/snippets/by-lang/:lang(/:page)', function($lang,$page = 1) use ($smarty,$app) {
    //get all snippets for lang
    $lang_n = is_numeric($lang);
    if(!$lang_n) {
        try {
            $lang_o = Language::find('all',array('conditions' =>array('name = ?',$lang)));
        } catch (Exception $e) {
            $app->response->setStatus(404);
            return;
        }
    }
    try {
        if($lang_n) {
            $snippets = Snippet::find('all',array('conditions' => array('language = ?',$lang)));
        } else {
            $snippets = Snippet::find('all',array('conditions' => array('language = ?',$lang_o->id)));
        }
    }
    catch (Exception $e) {
        $snippets = array('No snippets available.');
    }
    try {
        if($lang_n) {
            $langf = Language::find($lang);
        } else {
            $langf = $lang_o[0];
            $lang = $lang_o[0]->id;
        }
    }
    catch (Exception $e) {
        echo 2;
        echo $lang;
        echo "\n";
        print_r($lang_o);
        echo $e->getMessage();
        $app->response->setStatus(404);
        return;
    }
    if(count($snippets) == 0) {
        $smarty->assign('message','No snippets available.');
    }
    $numpages = (int)(count($snippets) / 10);
    if($numpages != count($snippets) / 10) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * 10;
    $curpage_bottom = $curpage_top - 10;
    unset($snippets);
    try{
    if($curpage_bottom < 10) {
        $snippets = Snippet::find('all',array('conditions' => array('language = ?',$lang), 'limit' => 10));
    } else {
        $snippets = Snippet::find('all',array('conditions' => array('language = ?',$lang), 'limit' => 10, 'offset' => $curpage_bottom));
    }
    } catch (Exception $e) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','5 '.$e->getMessage());
        $app->response->setStatus(404);
        return;
    }
    $pages = array();
    for($n = 1; $n <= $numpages; $n++)
        $pages[] = $n;
    $smarty->assign('url_prefix','snippets/'.$langf->id.'/');
    $smarty->assign('start_index',$curpage_bottom);
    $smarty->assign('numpages',$numpages);
    $smarty->assign('curpage',$page);
    $smarty->assign('pages',$pages);
    $smarty->assign('title','Snippets for '.ucfirst($langf->name).' (Page '.$page.')');
    $smarty->assign('snippets',$snippets);
    $smarty->display('topsnips.tpl');
});


$app->get('/snippets/by-author/:id(/:page)', function($id, $page = 1) use ($smarty,$app) {
    $snippets = Snippet::find('all',array('conditions' => array('author = ?',$id)));
    $numpages = (int)(count($snippets) / 10);
    if($numpages != count($snippets) / 10) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * 10;
    $curpage_bottom = $curpage_top - 10;
    unset($snippets);
    try{
    if($curpage_bottom < 10) {
        $snippets = Snippet::find('all',array('conditions' => array('author = ?',$id), 'limit' => 10));
    } else {
        $snippets = Snippet::find('all',array('conditions' => array('author = ?',$id), 'limit' => 10, 'offset' => $curpage_bottom));
    }
    } catch (Exception $e) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','1 '.$e->getMessage());
        $app->response->setStatus(404);
        return;
    }
    $pages = array();
    for($n = 1; $n <= $numpages; $n++)
        $pages[] = $n;
    $author = Author::find($id);
    $smarty->assign('url_prefix','snippets/by/'.$id.'/');
    $smarty->assign('start_index',$curpage_bottom);
    $smarty->assign('numpages',$numpages);
    $smarty->assign('curpage',$page);
    $smarty->assign('pages',$pages);
    $smarty->assign('title','Snippets by '.$author->name);
    $smarty->assign('snippets',$snippets);
    $smarty->display('topsnips.tpl');
});

$app->get('/snippets/top(/:page)', function($page = 1) use ($smarty,$app) {
    $snippets = Snippet::find('all',array('order' => 'rating desc'));
    $numpages = (int)(count($snippets) / 10);
    if($numpages != count($snippets) / 10) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * 10;
    $curpage_bottom = $curpage_top - 10;
    unset($snippets);
    try{
    if($curpage_bottom < 10) {
        $snippets = Snippet::find('all',array('order' => 'rating desc', 'limit' => 10));
    } else {
        $snippets = Snippet::find('all',array('order' => 'rating desc', 'limit' => 10, 'offset' => $curpage_bottom));
    }
    } catch (Exception $e) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','2 '.$e->getMessage());
        $app->response->setStatus(404);
        return;
    }
    $pages = array();
    for($n = 1; $n <= $numpages; $n++)
        $pages[] = $n;
    $smarty->assign('url_prefix','snippets/top/');
    $smarty->assign('start_index',$curpage_bottom);
    $smarty->assign('numpages',$numpages);
    $smarty->assign('curpage',$page);
    $smarty->assign('pages',$pages);
    $smarty->assign('title','Top Snippets by Score');
    $smarty->assign('snippets',$snippets);
    $smarty->display('topsnips.tpl');
});

$app->get('/snippets/recent(/:page)', function($page = 1) use ($app,$smarty) {
    $snippets = Snippet::find('all',array('order' => 'created_at desc'));
    $numpages = (int)(count($snippets) / 10);
    if($numpages != count($snippets) / 10) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * 10;
    $curpage_bottom = $curpage_top - 10;
    unset($snippets);
    try{
    if($curpage_bottom < 10) {
        $snippets = Snippet::find('all',array('order' => 'created_at desc', 'limit' => 10));
    } else {
        $snippets = Snippet::find('all',array('order' => 'created_at desc', 'limit' => 10, 'offset' => $curpage_bottom));
    }
    } catch (Exception $e) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','3 '.$e->getMessage());
        $app->response->setStatus(404);
        return;
    }
    $pages = array();
    for($n = 1; $n <= $numpages; $n++)
        $pages[] = $n;
    $smarty->assign('url_prefix','snippets/recent/');
    $smarty->assign('start_index',$curpage_bottom);
    $smarty->assign('numpages',$numpages);
    $smarty->assign('curpage',$page);
    $smarty->assign('pages',$pages);
    $smarty->assign('title','Recent Snippets (Page '.$page.')');
    $smarty->assign('snippets',$snippets);
    $smarty->display('topsnips.tpl');
});

$app->get('/snippets/new', function() use ($app,$smarty,$languages_list) {
    //create snippet form
    if(!check_logged_in($smarty)){
        $app->response->headers->set('Location',BASE_HREF.'/authors/login');
        $app->setCookie('redir','/snippets/new');
        $app->setCookie('message','You must login to perform this action.');
        return;
    }
    $smarty->assign('title','Create a New Snippet');
    $smarty->assign('languages',$languages_list);
    $smarty->display('cSnippet.tpl');
});

$app->get('/snippet/:id(/:action)', function($id,$action = null) use ($languages_list,$app,$smarty,$geshi) {
    //get snippet id
    try {
        $snip = Snippet::find($id);
    }
    catch ( Exception $e ) {
        $app->response->setStatus(404);
        return;
    }
    $smarty->assign('languages',$languages_list);
    $lang = Language::find($snip->language);
    $smarty->assign('title','"'.$snip->title.'"' . ' in '. ucfirst($lang->name));
    $smarty->assign('lang',$lang);
    $geshi->set_language($lang->name);
    $geshi->set_source(htmlspecialchars_decode($snip->code));
    $pauth = Author::find($snip->author);
    $cookie = $app->getCookie('user_login');
    if($action != null) {
        if($action == 'edit' && $cookie == $pauth->id) {
            //edit
            $smarty->assign('action',true);
            $smarty->assign('raw_code',$snip->code);
        } else {
            if($action == 'raw') {
                //raw output
                $app->response->headers->set('Content-Type','text/plain');
                echo $snip->code;
                return;
            } else {
                $app->response->headers->set('Location',BASE_HREF.'/snippet/'.$id);
                $app->response->setStatus(404);
                $app->setCookie('message','Not Authorized, are you logged in?');
            }
        }
    } else {
        //no need to parse the code if we're gonna edit
        try{
            $codef = $geshi->parse_code();
        } catch (Exception $e) {
            $codef = "Error processing source code.<br/>".$e->getMessage();
        }
        $smarty->assign('code',$codef);
    }
    $smarty->assign('snip_id',$id);
    $pauth = Author::find($snip->author);
    $sauth = array( 'id' => $pauth->id, 'name' => $pauth->name, 'gravatar' => get_gravatar($pauth->email) );
    $smarty->assign('sauth',$sauth);
    $smarty->display('snippet.tpl');
});

$app->post('/snippet', function() use ($app,$smarty) {
    //create snippet
    if(!check_logged_in($smarty)){
        $app->response->headers->set('Location',BASE_HREF.'/authors/login');
        $app->setCookie('redir','/snippets/new');
        $app->setCookie('message','You must login to perform this action.');
        return;
    }
    $snippet = new Snippet();
    $snippet->author = $app->request->post('user-id');
    $snippet->title = htmlspecialchars($app->request->post('title'));
    $snippet->code = htmlspecialchars($app->request->post('code'));
    $snippet->language = $app->request->post('language');
    $snippet->rating = 50;
    if($snippet->save()) {
        $app->response->headers->set('Location',BASE_HREF.'/snippet/'.$snippet->id);
        return;
    } else {
        $app->response->headers->set('Location',BASE_HREF.'/snippets/new');
        $app->setCookie('message','There was an error saving the snippet.');
    }
});

$app->delete('/snippet/:id', function($id) use ($app) {
    //delete snippet
    $snip = Snippet::find($id);
    $user = $app->getCookie('user_login');
    if($user == $snip->author) {
        $snip->delete();
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','"'.$snip->title.'" deleted.');
    } else {
        $app->response->headers->set('Location',BASE_HREF.'/snippet/'.$id);
        $app->response->setStatus(401);
        $app->setCookie('message','You are not authorized to edit this snippet, are you logged in?');
        return;
    }
});

$app->put('/snippet/:id', function($id) use ($app) {
    //update snippet
    $snip = Snippet::find($id);
    $user = $app->getCookie('user_login');
    if($user != $snip->author) {
        $app->response->headers->set('Location',BASE_HREF.'/snippet/'.$id);
        $app->response->setStatus(401);
        $app->setCookie('message','You are not authorized to edit this snippet, are you logged in?');
        return;
    }
    $snip->title = htmlspecialchars($app->request->put('title'));
    $snip->code = htmlspecialchars($app->request->put('code'));
    $snip->language = $app->request->put('language');
    if($snip->save()) {
        $app->response->headers->set('Location',BASE_HREF.'/snippet/'.$id);
        return;
    } else {
        $app->response->headers->set('Location',BASE_HREF.'/snippet/'.$id);
        $app->setCookie('message','There was an error saving the snippet.');
    }
});

$app->get('/captcha', function() use ($app) {
    $builder = new Gregwar\Captcha\CaptchaBuilder();
    $builder->build(200,50);
    $cstr = $builder->getPhrase();
    $app->response->headers->set('Content-Type', 'image/jpeg');
    $app->setCookie('create_user_form',$cstr);
    $builder->output();

});

$app->get('/authors/login', function() use ($smarty,$app) {
    if(check_logged_in($smarty)) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->deleteCookie('user_login');
        return;
    }
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
    $redir = $app->getCookie('redir');
    if($redir == '') $redir = '/';
    $app->response->headers->set('Location',BASE_HREF.$redir);
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
        $cpass = pbkdf2('',$app->request->put('old-password'),$author->salt);
        if($cpass == $author->password) {
            $newsalt = gen_salt();
            $author->password = pbkdf2('',$app->request->put('new-password'),$newsalt);
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

$app->get('/lang/:id', function($id) {
    //show language info
    echo 'it worked';
});

$app->put('/lang/:id', function($id) {
    //update language
});

$app->run();
