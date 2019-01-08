<?php
@include_once('inc/config.php');
@include_once('inc/function.php');

// $myfile = fopen("req-paypal.txt", "w") or die("Unable to open file!");
// fwrite($myfile, json_encode($_POST));
// fclose($myfile);

if( isset( $_POST['item_name'] ) && $_POST['item_name'] != "" )
{
	$res = addPedido( $_POST, 'paypal' );
	if($res == 1)
	{
		if( isAprovado( $_POST, 'paypal' ))
		{
			if( isMonthCard( $_POST, 'paypal' ) == 1 )
			{
				$res = insertMonthCard( $_POST, 'paypal' );
			}
			else
			{
				$res = updateSaldo( $_POST, 'paypal' );
			}

			if($res == 1)
			{
				die('Finalizado com sucesso [1]');
			}

		} else
		{
			die('Finalizado com sucesso [0]');
		}
	}
}

die('Houve um erro');