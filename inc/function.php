<?php
function GetFileConfigText($filename) {
    $arrayCARD = array();
    //$filename = "../config_text/$filename";
    $fp = fopen($filename, "r");
    while ($line = fgets($fp)) {
        $arr = explode("|",$line);
        $arrayCARD[$arr[0]] = $arr[1];
    }
    fclose($fp);
    return $arrayCARD;
}

// function GETConfigText($filename) {
//     $arrayCARD = array();
//     $fp = fopen($filename, "r");
//     while ($line = fgets($fp)) {
//         $arr = explode("|",$line);
//         $content = substr($line,(strlen($arr[0]) + 1));
//         $arrayCARD[$arr[0]] = trim($content);
//     }
//     fclose($fp);
//     return $arrayCARD;
// }

function curlDownloadObj($Url)
{
    if (!function_exists('curl_init'))
    {
        die('cURL nao instalado!');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_REFERER, "YOUR_ENDPOINT");
    curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $output = curl_exec($ch);

    curl_close($ch);
    return xmlToObj( $output );
}

function xmlToObj( $xml )
{
    return json_decode(json_encode(simplexml_load_string($xml)));
}

function addSaldoIndicador($obj, $points, $porc = .5)
{
    global $dbWeb;

    $usuarioAtual = getPadrinho($obj->reference);
    if ( $usuarioAtual != 0 )
    {
        $points *= $porc;

        $sql = "UPDATE account SET point = (point + ". $points ." ),  total_point = (total_point + ". $points ." ) WHERE username = '".hash2user($usuarioAtual['indicado_por'])."' ";

        if ($dbWeb->query($sql) === TRUE) {

           $sql = "UPDATE account SET indicacao_compensada = 1 WHERE username = '".$obj->reference."'";

           if ($dbWeb->query($sql) === TRUE)
           {
            return 1;
        }
        else
        {
            return 0;
        }

    } else {
        return 0;
    }
}

}


function getPadrinho($username)
{
    global $dbWeb;
    $sql = "SELECT * FROM account WHERE username = '$username' AND indicado_por IS NOT NULL AND indicacao_compensada = 0 ";

    $result2 = $dbWeb->query($sql);
    if ($result2->num_rows > 0)
    {
        return $result2->fetch_assoc();
    }
    return 0;

}


function user2hash($str)
{
    return "CVT-" . strrev( bin2hex( $str ) );
}

function hash2user($str)
{
    return hex2bin( strrev( substr($str, 4, (strlen($str)) ) ) );
}


function addPedido($oTransacao, $origem)
{
    global $dbWeb;
    $time = time();
    $oConfig;

    switch ($origem) {
        case 'pagseguro':
        $qtd_total = 0;
        $pacotes_desc = array();
        $reference = $oTransacao->reference;

        if( !is_array( $oTransacao->items->item ) )
        {
            $qtd_total    = $oTransacao->items->item->quantity;
            $pacotes_desc = $oTransacao->items->item->id;
            //$reference    = $oTransacao->items->item->description;
        }
        else
        {
            foreach ($oTransacao->items->item as $key => $value)
            {
                $qtd_total     += $value->quantity;
                $pacotes_desc[] = $value->quantity . "x" . $value->id;
                //$reference      = $value->description;
            }
            $pacotes_desc = implode(",", $pacotes_desc);
        }

        $detalhesPedido =  $pacotes_desc;
        $qtdTotal       = 1;

        $oConfig = array(
            'usuario'          => getUserFromString($reference),
            'usuario_email'    => $oTransacao->sender->email,
            'valor_pago'       => (float)$oTransacao->grossAmount,
            'valor_descontado' => (float)$oTransacao->feeAmount,
            'pedidos'          => $detalhesPedido,
            'qtd'              => $qtdTotal,
            'status'           => $oTransacao->status,
            'aprovado'         => isAprovado($oTransacao, $origem),
            'criado_em'        => strtotime( $oTransacao->date ),
            'codigo_transacao' => $oTransacao->code,
            'recebido_em'      => $time,
            'origem'           => $origem,
            'servidor'         => getServerFromString($reference)
        );
        break;

        case 'mercadopago':
        $detalhesPedido = "1x" . $oTransacao['reason'];
        $qtdTotal       = 1;

        $oConfig = array(
            'usuario'          => getUserFromString($oTransacao['external_reference']),
            'usuario_email'    => $oTransacao['payer']['email'],
            'valor_pago'       => (float)$oTransacao['transaction_amount'],
            'valor_descontado' => (float)$oTransacao['coupon_fee'],
            'pedidos'          => $detalhesPedido,
            'qtd'              => $qtdTotal,
            'status'           => $oTransacao['status'],
            'aprovado'         => isAprovado($oTransacao, $origem),
            'criado_em'        => strtotime( $oTransacao['date_created'] ),
            'codigo_transacao' => $oTransacao['id'],
            'recebido_em'      => $time,
            'origem'           => $origem,
            'servidor'         => getServerFromString($oTransacao['external_reference'])
        );
        break;

        case 'paypal':
        //die( var_dump( explode("-", $oTransacao['item_number']) ) );
        $obj = explode("-", $oTransacao['item_number'] );
        $detalhesPedido = "1x" . (int)( $obj[1] );
        $qtdTotal       = (int)$oTransacao['quantity'];
        $dataCriacao    = explode(" ", $oTransacao['payment_date']);
        unset($dataCriacao[0]);
        unset($dataCriacao[5]);
        unset($dataCriacao[6]);
        $dataCriacao    =  strtotime( implode(' ', $dataCriacao) );

        $oConfig = array(
            'usuario'          => getUserFromString($oTransacao['custom']),
            'usuario_email'    => $oTransacao['payer_email'],
            'valor_pago'       => (float)$oTransacao['mc_gross'],
            'valor_descontado' => (float)$oTransacao['mc_fee'],
            'pedidos'          => $detalhesPedido,
            'qtd'              => $qtdTotal,
            'status'           => $oTransacao['payment_status'],
            'aprovado'         => isAprovado($oTransacao, $origem),
            'criado_em'        => $dataCriacao,
            'codigo_transacao' => $oTransacao['txn_id'],
            'recebido_em'      => $time,
            'origem'           => $origem,
            'servidor'         => getServerFromString($oTransacao['custom'])
        );
        break;

        case 'deposito':
        case 'permuta':
        $detalhesPedido = "1x" . (int)$oTransacao['saldo'];
        $qtdTotal       = 1;

        $oConfig = array(
            'usuario'          => $oTransacao['destino'],
            'usuario_email'    => '',
            'valor_pago'       => $oTransacao['saldo'],
            'valor_descontado' => 0,
            'pedidos'          => $detalhesPedido,
            'qtd'              => $qtdTotal,
            'status'           => 3,
            'aprovado'         => isAprovado($oTransacao, $origem),
            'criado_em'        => $time,
            'codigo_transacao' => $time,
            'recebido_em'      => $time,
            'origem'           => $origem,
            'servidor'         => 1
        );

        break;

        default:
        die('Origem desconhecida [1]');
        break;
    }
    $oInsert = array(
        'key' => array(),
        'val' => array()
    );

    foreach ($oConfig as $key => $val) {
        $oInsert['key'][] = $key;
        $oInsert['val'][] = "'".$val."'";
    }

    $sql = "INSERT INTO pedidos_compra (". implode(',', $oInsert['key'] ) .") VALUES (". implode(',', $oInsert['val'] ) .")";

    if ($dbWeb->query($sql) === TRUE) {
        return 1;
    } else {
        return 0;
    }

}

function isMonthCard( $oTransacao, $origem )
{
    $res = 0;
    switch ($origem)
    {
        case 'pagseguro':
        $pedido = "";
        if( !is_array( $oTransacao->items->item ) )
        {
            $pedido = $oTransacao->items->item->id;
        }
        else
        {
            foreach ($oTransacao->items->item as $key => $value)
            {
                $pedido = $value->id;
            }
        }
        $res = ($pedido == 'pluscard') ? 1 : 0;
        break;
        case 'mercadopago':
        $res = ($oTransacao['reason'] == "Plus Card") ? 1 : 0;
        break;
        case 'paypal':
        $res = ($oTransacao['item_number'] == 'pluscard') ? 1 : 0;
        break;
        case 'deposito':
        case 'permuta':
        $res = 0;
        break;
        default:
        die('Origem desconhecida [2]');
        break;
    }

    return $res;
}


function isJogada( $oTransacao, $origem )
{
    $res = 0;
    switch ($origem)
    {
        case 'pagseguro':
        $pedido = "";
        if( !is_array( $oTransacao->items->item ) )
        {
            $pedido = $oTransacao->items->item->id;
        }
        else
        {
            foreach ($oTransacao->items->item as $key => $value)
            {
                $pedido = $value->id;
            }
        }
        $pedido = explode('-', $pedido);
        $res = ($pedido[0] == 'LW') ? 1 : 0;
        break;
        case 'mercadopago':
        $pedido = explode(' ', $oTransacao['reason']);
        $res = ($pedido[ sizeof($pedido) - 1 ] == "LW") ? 1 : 0;
        break;
        case 'paypal':
        $pedido = explode('-', $oTransacao['item_number']);
        $res = ($pedido[0] == 'LW') ? 1 : 0;
        break;
        case 'deposito':
        case 'permuta':
        $res = 0;
        break;
        default:
        die('Origem desconhecida [2]');
        break;
    }

    return $res;
}


function isAprovado( $oTransacao, $origem )
{
    $res = 0;

    switch ($origem)
    {
        case 'pagseguro':
        $res = ($oTransacao->status == 3) ? 1 : 0;
        break;
        case 'mercadopago':
        $res = ($oTransacao['status'] == 'approved') ? 1 : 0;
        break;
        case 'paypal':
        $res = ($oTransacao['payment_status'] == 'Completed') ? 1 : 0;
        break;
        case 'deposito':
        case 'permuta':
        $res = 1;
        break;
        default:
        die('Origem desconhecida [3]');
        break;
    }

    return $res;
}

function insertMonthCard( $oTransacao, $origem )
{
    global $dbWeb;
    $args = array();
    $valor_pago = 0;
    $configWeb    = GetFileConfigText("YOUR_CONFIG_ENDPOINT");

    switch ($origem)
    {
        case 'pagseguro':
        $args = explode("|", $oTransacao->reference);
        $valor_pago = (int)$oTransacao->grossAmount;
        break;
        case 'mercadopago':
        $args = explode("|", $oTransacao['external_reference']);
        $valor_pago = (int)$oTransacao['transaction_amount'];
        break;
        case 'paypal':
        $args = explode("|", $oTransacao['custom']);
        $valor_pago =  (int)$oTransacao['mc_gross'];
        break;
        default:
        die('Origem desconhecida [4]');
        break;
    }

    if( sizeof($args) >= 3 && (int)$configWeb['monthcard'] == $valor_pago  )
    {
        $dbGame = RefreshDBGame( substr($args[0], 1) );

        $SDKLogin = 'TG';
        $roleID   = $args[2];
        $timeReg  = time();
        $timeEnd  = strtotime(date('Y-m-d', strtotime("+30 days")));
        $point    = $valor_pago;
        $username =  $args[1];
        $sql      = "INSERT INTO monthcard (roleId, timeReg, timeEnd, point, username) VALUES ('$roleID', '$timeReg', '$timeEnd', '$point', '$username')";

        if ($sql != "" AND $dbWeb->query($sql) === TRUE)
        {
            $QMQJUsername = $SDKLogin.$username;
            $gm = "-buyyueka $QMQJUsername $roleID";
            $sqlGM = "INSERT INTO t_gmmsg (msg) VALUES ('$gm')";
            if($dbGame->query($sqlGM) === TRUE)
            {
                return 1;
            }
        }
    }
    return 0;
}


function updateSaldo($obj, $origem) // Implementado em 2018-03-27 -> Entrega validando valor do pacote
{
    global $dbWeb;
    $pacotes    = GetFileConfigText("YOUR_CONFIG_ENDPOINT");
    $pointField = "point";

    $novosSv = array(
        "pacotes" => GetFileConfigText("YOUR_CONFIG_ENDPOINT"),
        "field"   => "point2"
    );

    $points        = 0;
    $username      = "";
    $multiplicador = 1;

    switch ($origem) {
        case 'pagseguro':
        $username = getUserFromString($obj->reference);

        $pacotes    = getServerFromString($obj->reference) == 1 ? $pacotes     : $novosSv['pacotes'];
        $pointField = getServerFromString($obj->reference) == 1 ?  $pointField : $novosSv['field'];

        if( !is_array( $obj->items->item ) )
        {
            //Pega indice do pacote pelo valor individual e multiplica pela quantidade
            $pacote = (int)$obj->items->item->amount;
            if ( !isset( $pacotes[ $pacote ] ) )
            {
                return 0;
            }
            $multiplicador = promocaoPacote( $pacote );
            $points = (int)$pacotes[ $pacote ] * (int)$obj->items->item->quantity *  $multiplicador;
        }
        else
        {
            foreach ($obj->items->item as $key => $value)
            {
                $pacote = (int) $value->amount;
                if ( !isset( $pacotes[ $pacote ] ) )
                {
                    return 0;
                }
                $multiplicador = promocaoPacote( $pacote );
                $points += (int)$pacotes[ $pacote ] * (int)$value->quantity *  $multiplicador;
            }
        }
        break;

        case 'mercadopago':
        $username = getUserFromString($obj['external_reference']);

        $pacotes    = getServerFromString($obj['external_reference']) == 1 ? $pacotes     : $novosSv['pacotes'];
        $pointField = getServerFromString($obj['external_reference']) == 1 ?  $pointField : $novosSv['field'];

        $multiplicador = promocaoPacote( (int)$obj['transaction_amount'] );
        $points   = $pacotes[ (int)$obj['transaction_amount'] ] *  $multiplicador;
        break;

        case 'paypal':
        $username = getUserFromString($obj['custom']);


        $pacotes    = getServerFromString($obj['custom']) == 1 ? $pacotes     : $novosSv['pacotes'];
        $pointField = getServerFromString($obj['custom']) == 1 ?  $pointField : $novosSv['field'];

        $multiplicador = promocaoPacote( (int)$obj['mc_gross'] );
        $points   = $pacotes[ (int)$obj['mc_gross'] ] *  $multiplicador;
        break;

        case 'deposito':
        case 'permuta':
        $pacotes    = $obj['servidor'] == 1 ? $pacotes     : $novosSv['pacotes'];
        $pointField = $obj['servidor'] == 1 ?  $pointField : $novosSv['field'];

        $username = $obj['destino'];
        $multiplicador =  promocaoPacote( (int)$obj['saldo'] );
        $points   = $pacotes[ (int)$obj['saldo'] ] *  $multiplicador;


        break;

        default:
        die('Origem desconhecida [5]');
        break;
    }

    if( isJogada( $obj, $origem ) )
    {

        $sql = "UPDATE account SET ".$pointField." = (".$pointField." + ". $points ." ),  total_".$pointField." = (total_".$pointField." + ". $points ." ) WHERE username = '".$username."'";

        if ($dbWeb->query($sql) === TRUE) {
            updateSaldoIndicador($username, $points, .5, $origem);
            return 1;
        } else {
            return 0;
        }
    }
    else
    {
        return entregaEmDiamantes( $obj, $origem, $points );
    }


}

function promocaoPacote( $pacoteValor )
{
    $res = 1;
    $time          = time();
    // $periodo = array(
    //     'inicio' => 1524798000, // 00h00 dia 27/04/18
    //     'fim'    => 1525143599  // 23h59 dia 30/04/18
    // );

    // if( ($time >= $periodo['inicio'] && $time <= $periodo['fim'] && $pacoteValor == 15) )
    // {
    //     $res = 2;
    // }

    return $res;

}

function updateSaldoIndicador($username, $points, $porc = .5, $origem)
{
    global $dbWeb;

    $usuarioAtual = getPadrinho($username);
    if ( $usuarioAtual != 0 )
    {
        $points *= $porc;

        $sql = "UPDATE account SET point = (point + ". $points ." ),  total_point = (total_point + ". $points ." ) WHERE username = '".hash2user($usuarioAtual['indicado_por'])."' ";

        if ($dbWeb->query($sql) === TRUE)
        {

           $sql = "UPDATE account SET indicacao_compensada = 1 WHERE username = '".$username."'";

           if ($dbWeb->query($sql) === TRUE)
           {
            return 1;
        }
        else
        {
            return 0;
        }

    }
    else
    {
        return 0;
    }
}

}

function getServerFromString( $str )
{
    $str = explode("|", $str);
    return ( sizeof($str) > 1 ) ?  substr($str[0], 1) : 1 ;
}

function getUserFromString( $str )
{
    $usrOriginal = $str;
    $str = explode("|", $str);
    return ( sizeof($str) > 1 ) ?  $str[1] : $usrOriginal;
}

function getRoleIdFromString( $str )
{
    $str = explode("|", $str);
    return ( sizeof($str) > 1 ) ?  $str[2] : 0 ;
}

function entregaEmDiamantes( $oTransacao, $origem, $total )
{
    $args = array();
    $valor_pago = 0;

    switch ($origem)
    {
        case 'pagseguro':
        $args = explode("|", $oTransacao->reference);
        $valor_pago = (int)$oTransacao->grossAmount;
        break;
        case 'mercadopago':
        $args = explode("|", $oTransacao['external_reference']);
        $valor_pago = (int)$oTransacao['transaction_amount'];
        break;
        case 'paypal':
        $args = explode("|", $oTransacao['custom']);
        $valor_pago =  (int)$oTransacao['mc_gross'];
        break;
        case 'deposito':
        case 'permuta':
        $args = array( 0 => 's'. $oTransacao["servidor"], 1 => $oTransacao["destino"]);
        $valor_pago =  (int)$oTransacao['saldo'];
        break;
        default:
        die('Origem desconhecida [6]');
        break;
    }
    $zoneid =  substr($args[0], 1);
    $dbGame = RefreshDBGame( $zoneid );

    date_default_timezone_set('America/Sao_Paulo');
    $datatp  = "'". date("Y-m-d H:i:s", strtotime('+22 hours')) ."'";
    $totaldm = $total / 10;


    $sqlEntrega = "INSERT INTO t_tempmoney (cc, uid, rid, addmoney, itemid, chargetime)
    SELECT
    CONCAT(
    SUBSTR( UPPER( MD5( CONCAT( 'jOU81>.fjoeanl3fw16d21f.*', SUBSTR( UPPER( MD5( CONCAT( 'jOU8l>.fjofw16d21f3s13e5.*', userid, 'YY', $totaldm, '.ean13', $datatp ) ) ), 25, 8 ), 'YY', userid, '3sl3e5.', $totaldm, '=', $datatp ) ) ) , 1, 24 ),
    SUBSTR( UPPER( MD5( CONCAT( 'jOU8l>.fjofw16d21f3s13e5.*', userid, 'YY', $totaldm, '.ean13', $datatp ) ) ), 25, 8 )
    ) as cc,
    userid as uid,
    rid,
    $totaldm AS addmoney,
    0 AS itemid,
    $datatp AS chargetime
    FROM t_roles
    WHERE username = '" . $args[1] . "' LIMIT 1";

    if ($dbGame->query($sqlEntrega))
    {
        $last_id = $dbGame->insert_id.time();

        $sqlO = "INSERT INTO t_order (order_no) VALUES ('$last_id')";
        if ($dbGame->query($sqlO) === TRUE)
        {
            $last_idO = $dbGame->insert_id.time();

            $SDKLogin  = 'TG';
            $zoneID    = $zoneid;
            $inputtime = date("Y-m-d H:i:s");
            $sign      = "Mastertoan_".time();
            $time      = time();
            $u         = $SDKLogin . $args[1];
            $moneyAdd  = $totaldm;
            @$rid       = $args[2];

            $sqlI = "INSERT INTO t_inputlog (order_no, zoneid, inputtime, result, sign, time, u, rid, amount, cporder_no) VALUES ('$last_idO','$zoneID', '$inputtime', 'success', '$sign', '$time', '$u', '$rid', '$moneyAdd', '$last_idO')";

            if ($dbGame->query($sqlI) === TRUE)
            {
                // $diamantes = $moneyAdd * 10;
                // $log = $quality."|".$diamantes."|".GetLang("Rút thành công")." ".VndDot($diamantes)." KC ".GetLang("tiêu hao")." ".VndDot($quality).GetLang("Xu") . "|". $zoneID;
                // _writelogMember($_SESSION["username"],$log,"transfermoney", "$rid");

                return 1;
            }
        }
    }
    return 0;
}

