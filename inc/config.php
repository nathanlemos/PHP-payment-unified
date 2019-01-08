<?php
session_start();

// set default DB ID
if (!isset($_SESSION["dbID"])) {
    $_SESSION['dbID'] = 1;
}
$configType = array();
function GETConfigText()
{
    $arrayCARD = array();
    $filename = "CONFIG_FILE_DESTINATION";
    $fp = fopen($filename, "r");
    while ($line = fgets($fp)) {
        $arr = explode("|",$line);
        $content = substr($line,(strlen($arr[0]) + 1));
        $arrayCARD[$arr[0]] = trim($content);
    }
    fclose($fp);
    return $arrayCARD;
}
// get config
$configType = GETConfigText();
// config lang
$config['language'] = $configType['language'];
if (isset($_SESSION["tlang"])) {
    $config['language'] = trim($_SESSION["tlang"]);
} else {
    $_SESSION["tlang"] = $config['language'];
}
//include_once('lang.php');

$config["db"] = array(); // YOU DATA DB
// Connection
// Create connection DB web
$dbWeb = new mysqli($config['db']['dbweb']['host'], $config['db']['dbweb']['username'], $config['db']['dbweb']['password'], $config['db']['dbweb']['dbname']);
$dbWeb->set_charset($config['db']['dbweb']['charset']);
// Check connection
if ($dbWeb->connect_error) {
    die("Connection DB WEb failed: " . $dbWeb->connect_error);
}
// Create connection DB config
$dbConfig = new mysqli($config['db']['dbconfig']['host'], $config['db']['dbconfig']['username'], $config['db']['dbconfig']['password'], $config['db']['dbconfig']['dbname']);
$dbConfig->set_charset($config['db']['dbconfig']['charset']);
// Check connection
if ($dbConfig->connect_error) {
    die("Connection DB Config failed: " . $dbConfig->connect_error);
}
function RefreshDBGame($dbId) {
    global $config;
    if (!isset($config['db']['dbgame'][$dbId])) {
        echo 'DB Server Game không tồn tại';
        exit;
    }
    try {

        // Create connection DB game
        $dbGame = new mysqli($config['db']['dbgame'][$dbId]['host'], $config['db']['dbgame'][$dbId]['username'], $config['db']['dbgame'][$dbId]['password'], $config['db']['dbgame'][$dbId]['dbname']);
        $dbGame->set_charset($config['db']['dbgame'][$dbId]['charset']);
        // Check connection
        if ($dbGame->connect_error) {
            die("Connection DB Config failed: " . $dbGame->connect_error);
        }
        return $dbGame;
    }
    catch(Exception $ex) {
        echo 'Lỗi không thể kết nối Server Game';
    }
}
$dbGame = RefreshDBGame($_SESSION['dbID']);
function siteURLHOME() {
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ||
        $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol.$domainName;
}
// config
date_default_timezone_set($configType['timeZone']);
$timestamp = time();
$SDKLogin = $configType['SDKLogin'];
$config['name'] = $configType['name'];
$config['typeRegister'] = 'email'; // phone , email
// config thẻ cào
$config['recharge'] = array(
    'thecao'=>$configType['thecao'],
    'typeNap'=>$configType['typeNap'], // api hoặc inweb
    'linkApiCard'=>$configType['linkApiCard'],
    'passwordApi'=>$configType['passwordApi'],
);
$timezone  = +7; //(GMT +7:00)
$config['monthcard'] = $configType['monthcard']; // giá thẻ tháng
$config['alphaTest'] = $configType['alphaTest'];
$config['kcNhan'] = $configType['kcNhan'];
$config['typePrice'] = $configType['typePrice'];
$config['itemsCodeDays'] = $configType['itemsCodeDays'];
$config['wheelmoney'] = $configType['wheelmoney'];
$config['version'] = $configType['version'];
$config['langConfig'] = array(
    'en'=>'English',
);
for($i = 1; $i <= 12 ; $i++){
    $config['wheelAward'.$i] = $configType['wheelAward'.$i];
}
if($config['recharge']['thecao'] == "th"){
    $config['tmpay'] = array(
        'merchant_id'=>'9MJH170630',
        'resp_url'=>siteURLHOME().'/api/getpay.html',
    );
    $config['MenhGiaTheNap'] = array(
        '0'=>0,
        '1'=>50,
        '2'=>90,
        '3'=>150,
        '4'=>300,
        '5'=>500,
        '6'=>1000,
    );
    $config['MenhGiaTheNapGoc'] = array(
        '0'=>0,
        '1'=>50,
        '2'=>90,
        '3'=>150,
        '4'=>300,
        '5'=>500,
        '6'=>1000,
    );
}