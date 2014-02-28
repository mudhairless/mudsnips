<?php
require('vendor/autoload.php');
require('lib/util.php');

if(file_exists('data/config.php')) {
    header('Location: index.php');
    die();
}

$app = new Slim\Slim();
$smarty = new Smarty();
$smarty->setTemplateDir('views/');
$smarty->setCompileDir('data/smarty/templates_c/');
$smarty->setConfigDir('data/smarty/configs/');
$smarty->setCacheDir('data/smarty/cache/');
$smarty->assign('site_name','MudSnips');
$smarty->assign('site_contact','sir_mud@users.sf.net');
$smarty->assign('site_owner','Ebben Feagan');
$smarty->assign('install',1);

$app->get('/', function() use($smarty) {

    $smarty->assign('title','Installation');
    $smarty->assign('step',1);
    $smarty->display('install.tpl');

});

$app->post('/',function() use($app) {
    $rdb = $app->request->post('db_connect');
    $rdmd = 'models';
    $rsn = $app->request->post('site_name');
    $rso = $app->request->post('site_owner');
    $rsc = $app->request->post('site_contact');
    $rbh = $app->request->post('base_href');
    $rsk = gen_salt() . gen_salt();

    $fh = fopen('data/config.php','w') or die("can't open config.php $php_errormsg");
    fwrite($fh,'<?php'."\n");
    fwrite($fh,"define('BASE_HREF','$rbh');"."\n");
    fwrite($fh,"define('SITE_NAME','$rsn');"."\n");
    fwrite($fh,"define('SITE_OWNER','$rso');"."\n");
    fwrite($fh,"define('SITE_CONTACT','$rsc');"."\n");
    fwrite($fh,"define('ALLOW_REGISTRATION',true);\n");
    fwrite($fh,"define('RESULTS_PER_PAGE',10);\n");
    fwrite($fh,'ActiveRecord\Config::initialize(function($cfg){$cfg->set_model_directory("'.$rdmd.'");$cfg->set_connections(array("development" => "'.$rdb.'"));});'."\n");
    fwrite($fh,'$slimconfig = array("cookies.encrypt" => true, "cookies.secret_key" => "'.$rsk.'");'."\n");
    fwrite($fh,'$app = new Slim\Slim($slimconfig);'."\n");
    fwrite($fh,'$smarty = new Smarty();'."\n");
    fwrite($fh,"$smarty->setTemplateDir('views/');\n".
                "$smarty->setCompileDir('data/smarty/templates_c/');\n".
                "$smarty->setConfigDir('data/smarty/configs/');\n".
                "$smarty->setCacheDir('data/smarty/cache/');\n");
    fwrite($fh,'$geshi = new GeSHi("","php");'."\n");
    fwrite($fh,'$geshi->set_header_type(GESHI_HEADER_PRE_VALID);'."\n");
    fwrite($fh,'$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);'."\n");
    fwrite($fh,'require("langlist.php");'."\n\n");
    fflush($fh);
    fclose($fh);

    $app->response->headers->set('Location',$rbh.'/');
});

$app->run();
