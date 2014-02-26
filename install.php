<?php
require('vendor/autoload.php');
require('util.php');

if(file_exists('config.php')) {
    header('Location: index.php');
    die();
}

$app = new Slim\Slim();
$smarty = new Smarty();

$app->get('/', function() use($smarty) {

    $smarty->assign('title','MudSnip Installation');
    $smarty->assign('step',1);
    $smarty->display('install.tpl');

});

$app->post('/',function() use($app) {
    $rdb = $app->request->post('db_connect');
    $rdmd = $app->request->post('db_models_dir');
    $rsn = $app->request->post('site_name');
    $rso = $app->request->post('site_owner');
    $rsc = $app->request->post('site_contact');
    $rbh = $app->request->post('base_href');
    $rsk = gen_salt() . gen_salt();

    $fh = fopen('config.php','w') or die("can't open config.php $php_errormsg");
    fwrite($fh,'<?php'."\n");
    fwrite($fh,"define('BASE_HREF','$rbh');"."\n");
    fwrite($fh,"define('SITE_NAME','$rsn');"."\n");
    fwrite($fh,"define('SITE_OWNER','$rso');"."\n");
    fwrite($fh,"define('SITE_CONTACT','$rsc');"."\n");
    fwrite($fh,"define('ALLOW_REGISTRATION',true);\n");
    fwrite($fh,'ActiveRecord\Config::initialize(function($cfg){$cfg->set_model_directory("'.$rdmd.'");$cfg->set_connections(array("development" => "'.$rdb.'"));});'."\n");
    fwrite($fh,'$slimconfig = array("cookies.encrypt" => true, "cookies.secret_key" => "'.$rsk.'");'."\n");
    fwrite($fh,'$app = new Slim\Slim($slimconfig);'."\n");
    fwrite($fh,'$smarty = new Smarty();'."\n");
    fwrite($fh,'$geshi = new GeSHi("","php");'."\n");
    fwrite($fh,'$geshi->set_header_type(GESHI_HEADER_PRE_VALID);'."\n");
    fwrite($fh,'$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);'."\n");
    fwrite($fh,'require("langlist.php");'."\n\n");
    fflush($fh);
    fclose($fh);

    $app->response->headers->set('Location',$rbh.'/');
});

$app->run();
