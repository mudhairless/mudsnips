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
    $numpages = (int)(count($snippets) / RESULTS_PER_PAGE);
    if($numpages != count($snippets) / RESULTS_PER_PAGE) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * RESULTS_PER_PAGE;
    $curpage_bottom = $curpage_top - RESULTS_PER_PAGE;
    unset($snippets);
    try{
    if($curpage_bottom < RESULTS_PER_PAGE) {
        $snippets = Snippet::find('all',array('limit' => RESULTS_PER_PAGE,'conditions' => $title));
    } else {
        $snippets = Snippet::find('all',array('limit' => RESULTS_PER_PAGE,'offset' => $curpage_bottom, 'conditions' => $title));
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
    $numpages = (int)(count($snippets) / RESULTS_PER_PAGE);
    if($numpages != count($snippets) / RESULTS_PER_PAGE) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * RESULTS_PER_PAGE;
    $curpage_bottom = $curpage_top - RESULTS_PER_PAGE;
    unset($snippets);
    try{
    if($curpage_bottom < RESULTS_PER_PAGE) {
        $snippets = Snippet::find('all',array('conditions' => array('language = ?',$lang), 'limit' => RESULTS_PER_PAGE));
    } else {
        $snippets = Snippet::find('all',array('conditions' => array('language = ?',$lang), 'limit' => RESULTS_PER_PAGE, 'offset' => $curpage_bottom));
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
    $numpages = (int)(count($snippets) / RESULTS_PER_PAGE);
    if($numpages != count($snippets) / RESULTS_PER_PAGE) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * RESULTS_PER_PAGE;
    $curpage_bottom = $curpage_top - RESULTS_PER_PAGE;
    unset($snippets);
    try{
    if($curpage_bottom < RESULTS_PER_PAGE) {
        $snippets = Snippet::find('all',array('conditions' => array('author = ?',$id), 'limit' => RESULTS_PER_PAGE));
    } else {
        $snippets = Snippet::find('all',array('conditions' => array('author = ?',$id), 'limit' => RESULTS_PER_PAGE, 'offset' => $curpage_bottom));
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
    $numpages = (int)(count($snippets) / RESULTS_PER_PAGE);
    if($numpages != count($snippets) / RESULTS_PER_PAGE) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * RESULTS_PER_PAGE;
    $curpage_bottom = $curpage_top - RESULTS_PER_PAGE;
    unset($snippets);
    try{
    if($curpage_bottom < RESULTS_PER_PAGE) {
        $snippets = Snippet::find('all',array('order' => 'rating desc', 'limit' => RESULTS_PER_PAGE));
    } else {
        $snippets = Snippet::find('all',array('order' => 'rating desc', 'limit' => RESULTS_PER_PAGE, 'offset' => $curpage_bottom));
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
    $numpages = (int)(count($snippets) / RESULTS_PER_PAGE);
    if($numpages != count($snippets) / RESULTS_PER_PAGE) $numpages++;
    if($numpages < 1) $numpages = 1;
    if($page > $numpages) {
        $app->response->headers->set('Location',BASE_HREF.'/');
        $app->setCookie('message','$page > $numpages'.'<br/>'.$page.'::'.$numpages);
        $app->response->setStatus(404);
        return;
    }
    $curpage_top = $page * RESULTS_PER_PAGE;
    $curpage_bottom = $curpage_top - RESULTS_PER_PAGE;
    unset($snippets);
    try{
    if($curpage_bottom < RESULTS_PER_PAGE) {
        $snippets = Snippet::find('all',array('order' => 'created_at desc', 'limit' => RESULTS_PER_PAGE));
    } else {
        $snippets = Snippet::find('all',array('order' => 'created_at desc', 'limit' => RESULTS_PER_PAGE, 'offset' => $curpage_bottom));
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
    switch($snip->rating) {
        case 0:
            $rating = 'Unrated';
            break;
        case ($snip->rating < 10):
            $rating = 'Hated';
            break;
        case ($snip->rating < 50):
            $rating = 'Disliked';
            break;
        case ($snip->rating == 50):
            $rating = 'Divisive';
            break;
        case ($snip->rating > 90):
            $rating = 'Loved';
            break;
        default:
            $rating = 'Liked';
    }
    $smarty->assign('rating',$rating);
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
    $snippet->rating = 0;
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

$app->get('/rate/:id/:func', function($id,$func) use ($app) {
    try {
        $user = Author::find($app->getCookie('user_login'));
        $snip = Snippet::find($id);
    } catch (Exception $e) {
        $app->response->headers->set('Location',BASE_HREF.'/authors/login');
        $app->setCookie('message','You must log in before performing that action.');
        $app->setCookie('redir','/snippet/'.$id);
        $app->response->setStatus(403);
        return;
    }
    switch($func) {
        case 'like':
            $action = true;
            break;
        case 'dislike':
            $action = false;
            break;
        default:
            $app->response->headers->set('Location',BASE_HREF.'/snippet/'.$id);
            $app->setCookie('message','Incorrect verb for rating, are you making stuff up?');
            $app->response->setStatus(403);
            return;
    }
    $votes = unserialize($user->votes);
    if(is_array($votes)) {
        //check votes
        if(isset($votes[(string)$id])){
            if($votes[(string)$id] == $action) {
                $app->response->headers->set('Location',BASE_HREF.'/snippet/'.$id);
                $app->setCookie('message','You have already voted for this snippet.');
                return;
            }
        }
    } else {
        $votes = array();
    }
    $votes[(string)$id] = $action;
    $user->votes = serialize($votes);
    $user->save();

    $cur_rate = $snip->rating;
    if($cur_rate == 0) {
        if($action){
            $cur_rate = 100;
        } else {
            $cur_rate = 1;
        }
    } else {
        if($action){
            if(!isset($votes[(string)$id])){
                $cur_rate = ($cur_rate + 100) / 2;
            } else {
                $cur_rate = ($cur_rate + 200) / 2;
            }
        } else {
            if(!isset($votes[(string)$id])){
                $cur_rate = ($cur_rate / 2);
            } else {
                $cur_rate = ($cur_rate - 100) / 2;
            }
        }
    }
    if($cur_rate < 1) {
        if(isset($votes[(string)$id])){
            $cur_rate = 0;
        } else {
            $cur_rate = 1;
        }
    }
    if($cur_rate > 100) $cur_rate = 100;
    if(isset($votes[(string)$id]) && !$action){
        unset($votes[(string)$id]);
        $user->votes = serialize($votes);
        $user->save();
    }
    $snip->rating = $cur_rate;
    $snip->save();
    $app->response->headers->set('Location',BASE_HREF.'/snippet/'.$id);
    $app->setCookie('message','Vote Recorded.');
});

$app->get('/lang/:id', function($id) {
    //show language info
    echo 'it worked';
});

$app->put('/lang/:id', function($id) {
    //update language
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

$app->run();
