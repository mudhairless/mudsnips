<?php

function ratingToString($val) {
    switch($val) {
        case 0:
            $rating = 'Unrated';
            break;
        case ($val < 10):
            $rating = 'Hated';
            break;
        case ($val < 50):
            $rating = 'Disliked';
            break;
        case ($val == 50):
            $rating = 'Divisive';
            break;
        case ($val > 90):
            $rating = 'Loved';
            break;
        default:
            $rating = 'Liked';
    }
    return $rating;
}

function snippetList( $snippets, $page, $smarty, $app, $func) {
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
            $snippets = $func(null);
        } else {
            $snippets = $func($curpage_bottom);
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
    $smarty->assign('start_index',$curpage_bottom);
    $smarty->assign('numpages',$numpages);
    $smarty->assign('curpage',$page);
    $smarty->assign('pages',$pages);
    $smarty->assign('snippets',$snippets);
    $smarty->display('topsnips.tpl');
}

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

    $smarty->assign('url_prefix','snippets/'.$langf->id.'/');
    $smarty->assign('title','Snippets for '.ucfirst($langf->name).' (Page '.$page.')');
    snippetList($snippets,$page,$smarty,$app,function($offset) use ($lang) {
        if($offset == null) {
            return Snippet::find('all',array('conditions' => array('language = ?',$lang), 'limit' => RESULTS_PER_PAGE));
        } else {
            return Snippet::find('all',array('conditions' => array('language = ?',$lang), 'limit' => RESULTS_PER_PAGE, 'offset' => $offset));
        }
    });
});


$app->get('/snippets/by-author/:id(/:page)', function($id, $page = 1) use ($smarty,$app) {
    $snippets = Snippet::find('all',array('conditions' => array('author = ?',$id)));
    $author = Author::find($id);
    $smarty->assign('url_prefix','snippets/by/'.$id.'/');
    $smarty->assign('title','Snippets by '.$author->name);
    snippetList($snippets,$page,$smarty,$app,function($offset) use ($id) {
        if($offset == null) {
            return Snippet::find('all',array('conditions' => array('author = ?',$id), 'limit' => RESULTS_PER_PAGE));
        } else {
            return Snippet::find('all',array('conditions' => array('author = ?',$id), 'limit' => RESULTS_PER_PAGE, 'offset' => $offset));
        }
    });
});

$app->get('/snippets/top(/:page)', function($page = 1) use ($smarty,$app) {
    $snippets = Snippet::find('all',array('order' => 'rating desc'));

    $smarty->assign('url_prefix','snippets/top/');
    $smarty->assign('title','Top Snippets by Score');
    snippetList($snippets,$page,$smarty,$app,function($offset) {
        if($offset == null) {
            return Snippet::find('all',array('order' => 'rating desc', 'limit' => RESULTS_PER_PAGE));
        } else {
            return Snippet::find('all',array('order' => 'rating desc', 'limit' => RESULTS_PER_PAGE, 'offset' => $offset));
        }
    });
});

$app->get('/snippets/recent(/:page)', function($page = 1) use ($app,$smarty) {
    $snippets = Snippet::find('all',array('order' => 'created_at desc'));

    $smarty->assign('url_prefix','snippets/recent/');
    $smarty->assign('title','Recent Snippets (Page '.$page.')');
    snippetList($snippets,$page,$smarty,$app,function($offset) {
        if($offset == null) {
            return Snippet::find('all',array('order' => 'created_at desc', 'limit' => RESULTS_PER_PAGE));
        } else {
            return Snippet::find('all',array('order' => 'created_at desc', 'limit' => RESULTS_PER_PAGE, 'offset' => $offset));
        }
    });
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
    $rating = ratingToString($snip->rating);
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

