<?php
require('vendor/autoload.php');
include('data/config.php');
require('lib/util.php');

if(!isset($smarty)){
    header('Location: install.php');
    die();
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

$app->get('/search(/:page(/:format))', function($page = 1, $format = 'html') use ($smarty,$app) {
    if($format == 'json') {
        //output json
        $req = $app->request->get('q');
        if($page == 1) {
            $snippets = Snippet::find('all',
                array(
                    'conditions' => array('title like ?','%'.$req.'%'),
                    'limit' => RESULTS_PER_PAGE
                    )
                );
        } else {
            $snippets = Snippet::find('all',
                array(
                    'conditions' => array('title like ?','%'.$req.'%'),
                    'limit' => RESULTS_PER_PAGE,
                    'offset' => $page
                    )
                );
        }
        if(count($snippets) == 0) {
            //nothing to output
            $app->response->setStatus(404);
            echo json_encode(array(
                'error' => 'No results found.',
                'results' => null,
                'version' => '1.0'
                ));
            return;
        }
        $res = array();
        foreach($snippets as $snip) {
            $res[] = $snip->to_json(array('only' => array('id','title')));
        }
        $jres = array(
                'query' => $req,
                'results' => $res,
                'error' => null,
                'version' => '1.0',
                'next-offset' => $page * RESULTS_PER_PAGE
                );
        $app->response->setStatus(200);
        echo json_encode($jres);
        return;
    } else {
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
        $smarty->assign('url_prefix','search/');
        $smarty->assign('title','Search results');
        snippetList($snippets,$page,$smarty,$app,function($offset) use ($title) {
            if($offset == null) {
                return Snippet::find('all',array('limit' => RESULTS_PER_PAGE,'conditions' => $title));
            } else {
                return Snippet::find('all',array('limit' => RESULTS_PER_PAGE,'offset' => $offset, 'conditions' => $title));
            }
        });
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
