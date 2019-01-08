<?php
@include_once('inc/config.php');
@include_once('inc/function.php');

$isSandbox = false; // Manter em false pois está em producao
$notification = array(
	'url'   => 'https://ws.pagseguro.uol.com.br/v2/transactions/notifications/',
	'email' => 'YOUR_DATA',
	'token' => 'YOUR_DATA'
);

if ( $isSandbox )
{
	$notification['url']   = 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/notifications/';
	$notification['token'] = 'YOUR_DATA';
}

if( isset($_POST['notificationType'])
	&& $_POST['notificationType'] == 'transaction')
{

	$notificationCode = $_POST['notificationCode'];
	$obj = curlDownloadObj( $notification['url'] . $notificationCode . '?email=' . $notification['email'] . '&token=' . $notification['token'] );
	if (!$obj)
	{
		die('Erro ao realizar a operacao [1]');
	}

// $myfile = fopen("req-pagseguro.txt", "w") or die("Unable to open file!");
// fwrite($myfile, json_encode($obj));
// fclose($myfile);

	$res = addPedido( $obj, 'pagseguro' );
	if($res == 1)
	{
		if( isAprovado( $obj, 'pagseguro' ))
		{
			if( isMonthCard($obj, 'pagseguro') == 1 )
			{
				$res = insertMonthCard( $obj, 'pagseguro' );
			}
			else
			{
				$res = updateSaldo( $obj, 'pagseguro' );
			}

			if($res == 1)
			{
				die('Finalizado com sucesso [1]');
			}

			die('Falta de parametros [2]');

		}
		else
		{
			die('Finalizado com sucesso [0]');
		}

		die('Falta de parametros [1]');
	}

	die('Falta de parametros [0]');
}

die('Falta de parametros');