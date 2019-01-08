<?php
@include_once('inc/config.php');
@include_once('inc/function.php');
@require_once ('inc/mercadopago.php');

$oConfig = array(
	'CLIENT_ID'     => 'YOUR_DATA',
	'CLIENT_SECRET' => 'YOUR_DATA',
	'INIT_POINT'    => /*'sandbox_init_point'*/ 'init_point'
);


$mp = new MP ($oConfig['CLIENT_ID'], $oConfig['CLIENT_SECRET']);

if( isset($_POST['item_id']) )
{
	$preference_data = array (
		"items" => array (
			array (
				"title"              => $_POST['item_id'],
				"description"        => $_POST['item_id'],
				"extra_part"         => $_POST['extra_part'],
				"quantity"           => 1,
				"currency_id"        => "BRL",
				"unit_price"         => (float)$_POST['amount']
			)
		),
		"external_reference" => $_POST['external_reference']
	);

	$preference = $mp->create_preference($preference_data);
	//print_r ($preference);

	header('location:' . $preference['response'][$oConfig['INIT_POINT']] );
	die();
}


if( isset($_GET['topic']) && $_GET['topic'] == 'payment' && isset($_GET['id']) )
{

	$payment_info = $mp->get_payment_info($_GET["id"]);

	if ($payment_info["status"] == 200)
	{

		$res = addPedido( $payment_info["response"]['collection'], 'mercadopago' );
		if($res == 1)
		{
			if( isAprovado( $payment_info["response"]['collection'], 'mercadopago' ))
			{
				if( isMonthCard( $payment_info["response"]['collection'], 'mercadopago') == 1 )
				{
					$res = insertMonthCard( $payment_info["response"]['collection'], 'mercadopago' );
				}
				else
				{
				$res = updateSaldo( $payment_info["response"]['collection'], 'mercadopago' );
				}

				if($res == 1)
				{
					die('Finalizado com sucesso [1]');
				}

			} else
			{
				die('Finalizado com sucesso');
			}
		}
	}
	die('Ocorreu um erro');
}

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"])) {
	//http_response_code(400);
	header('HTTP/1.1 400 Bad Request');
	return;
}