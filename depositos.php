<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('inc/config.php');
include_once('inc/function.php');


// $myfile = fopen("req-depositos.txt", "w") or die("Unable to open file!");
// fwrite($myfile, json_encode($_POST));
// fclose($myfile);
if( isset( $_POST['key'] ) && $_POST['key'] == "YOUR_DATA" )
{
	$res = addPedido( $_POST, 'deposito' );
	if($res == 1)
	{
		if( isAprovado( $_POST, 'deposito' ))
		{
			$res = updateSaldo( $_POST, 'deposito' );
			if($res == 1)
			{
				die('1');
			}

		} else
		{
			die('0');
		}
	}
}


if( isset( $_POST['key'] ) && $_POST['key'] == "N#J07K#PA5KUW" )
{
	$res = addPedido( $_POST, 'permuta' );
	if($res == 1)
	{
		if( isAprovado( $_POST, 'permuta' ))
		{
			$res = updateSaldo( $_POST, 'permuta' );
			if($res == 1)
			{
				die('1');
			}

		} else
		{
			die('0');
		}
	}
}

die('0');