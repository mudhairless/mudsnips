<?php
require('vendor/autoload.php');
require('data/config.php');
require('util.php');

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

$app->get('/captcha', function() use ($app) {
    $builder = new Gregwar\Captcha\CaptchaBuilder();
    $builder->build(200,50);
    $cstr = $builder->getPhrase();
    $app->response->headers->set('Content-Type', 'image/jpeg');
    $app->setCookie('create_user_form',$cstr);
    $builder->output();

});

$app->get('/lang/:id', function($id) {
    //show language info
    echo 'it worked';
});

$app->put('/lang/:id', function($id) {
    //update language
});

$app->notFound(function() use ($app,$smarty) {
    $smarty->assign('title','Page Not Found (404)');
    $smarty->assign('requested',trim(str_replace('/',' ',$app->environment['PATH_INFO'])));
    $smarty->display('404.tpl');
});

require('app/routes/snippets.php');
require('app/routes/authors.php');

$app->run();
