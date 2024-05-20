<?php

namespace src\handlers;

use PDOException;
use src\exceptions\DealNaoEncontradoBDException;
use src\exceptions\EstagiodavendaNaoAlteradoException;
use src\exceptions\FaturamentoNaoCadastradoException;
use src\exceptions\InteracaoNaoAdicionadaException;
use src\exceptions\NotaFiscalNaoEncontradaException;
use src\exceptions\PedidoNaoEncontradoOmieException;
use src\exceptions\WebhookReadErrorException;
use src\functions\DiverseFunctions;
use src\models\Deal;
use src\models\Invoicing;
class InvoicingHandler
{
    //LÊ O WEBHOOK E COM A NOTA FATURADA
    public static function readInvoiceHook($json, $baseApi, $method, $apiKey)
    {   
        // Array de retorno
        $message = [];
        //decodifica o json em array
        $decoded = json_decode($json, true);
        //verifica se tem informações no array e se a nota está faturada (etapa 60)
        if(empty($decoded) && $decoded['event']['etapa'] != 60){
            throw new WebhookReadErrorException('Não foi possível ler o Webhook ou não existe nota fiscal emitida!',1020);
        }        
        //infos do webhook
        $invoicing = new Invoicing();
        $invoicing->authorId = $decoded['author']['userId'];//Id de quem faturou
        $invoicing->authorName = $decoded['author']['name'];//nome de quem faturou
        $invoicing->authorEmail = $decoded['author']['email'];//email de quem faturou
        $invoicing->appKey = $decoded['appKey'];//id do app que faturou (base de faturamento)
        $invoicing->etapa = $decoded['event']['etapa']; // etapa do processo 60 = faturado
        $invoicing->etapaDescr = $decoded['event']['etapaDescr']; // descrição da etapa 
        $invoicing->dataFaturado = $decoded['event']['dataFaturado']; // data do faturamento
        $invoicing->horaFaturado = $decoded['event']['horaFaturado']; // hora do faturamento
        $invoicing->idCliente = $decoded['event']['idCliente']; // Id do Cliente Omie
        $invoicing->idPedido = $decoded['event']['idPedido']; // Id do Pedido Omie
        $invoicing->numeroPedido = $decoded['event']['numeroPedido']; // Numero do pedido
        $invoicing->valorPedido = $decoded['event']['valorPedido']; // Valor Faturado
        //pega a chave secreta para a base de faturamento vinda no faturamento
        switch($invoicing->appKey){
            case 2337978328686:               
                $appSecret = $_ENV['SECRETS_MHL'];
                break;
                
            case 2335095664902:
                $appSecret = $_ENV['SECRETS_MPR'];
                break;
                
            case 2597402735928:
                $appSecret = $_ENV['SECRETS_MSC'];
                break;
            }
        // busca o pedido através id do pedido no omie para pegar o número do OrderId para buscar o Deal no banco
        ($pedidoOmie = Self::consultaPedidoOmie($invoicing->appKey, $appSecret, $invoicing->idPedido))?$idPedidoIntegracao = $pedidoOmie['pedido_venda_produto']['cabecalho']['codigo_pedido_integracao']: throw new PedidoNaoEncontradoOmieException('Pedido '.$invoicing->idPedido.' não encontrado no Omie ERP',1023);
        //$idPedidoIntegracao=402303406;
        // Busca o Deal salvo no banco com o número do pedido de integração para pegar os dados e montar o cabeçalho da mensagem
        $deal = Deal::select()->where('last_order_id', $idPedidoIntegracao)->one();
        //busca o cnpj do cliente para consultar o contact id no ploomes
        $cnpjClient = self::clienteIdOmie($invoicing->idCliente, $invoicing->appKey, $appSecret);
        
        // echo'<pre><br>';
        // echo'-----------------------------------------------------------<br>';
        // print_r($cnpjClient);
        // echo'-----------------------------------------------------------<br>';
        // print_r($deal);
        // echo'-----------------------------------------------------------<br>';
        // exit;
        
        try{                
            //consulta a nota fiscal no omie para retornar o numero da nota.            
            ($nfe = Self::consultaNotaOmie($invoicing->appKey, $appSecret, $invoicing->idPedido))?$invoicing->nNF = $nfe :throw new NotaFiscalNaoEncontradaException('Nota fiscal não encontrada para o pedido: '.$invoicing->idPedido, 1022);  
            try{
                //salva o faturamento no banco
                $idInvoicing = Self::saveInvoicing($invoicing);
                $message['saveInvoicing'] = 'Novo faturamento armazenado no banco id: '.$idInvoicing;

            }catch(PDOException $e){          
                throw new FaturamentoNaoCadastradoException('NOTA FISCAL NÚMERO '.intval($invoicing->nNF).' JÁ FOI CADASTRADA NA BASE DE DADOS E NÃO PODE SER REPITIDA!'.$e->getMessage(),1021);
            }
        }catch(NotaFiscalNaoEncontradaException $e){
            echo $e->getMessage();
        }
        if(!empty($deal)){
            $infoDeal = [
                'contactId' => $deal['contact_id'],
                'dealId'=> $deal['deal_id'],
            ];

        }elseif(empty($deal) && !empty($cnpjClient)){
            //busca o contact_id artravés do cnpj do cliente do omie
            $contactId = Self::consultaClientePloomesCnpj($cnpjClient, $baseApi, $method, $apiKey);

            $infoDeal = [
                'contactId' =>$contactId,
                'dealId'=> null,
            ];

        }
        else{

            //RETORNA excessão caso não tenha o $deal
            throw new DealNaoEncontradoBDException('Dados do pedido não encontrado na integração PLOOMES CRM X OMIE ERP. <br> A origem do pedido pode não ter sido o Ploomes CRM. Não foi possível enviar a mensagem ao Ploomes<br>',1024);
        }
        //consegui recuperar o Deal com o numero do pedido de integração então grava os dados em $infoDeal
        
        //monta a mensagem para retornar ao ploomes
        $msg = [
            'ContactId'=> $infoDeal['contactId'],
            'DealId'=> $infoDeal['dealId'],
            'TypeId'=> 1,
            'Title'=> 'Nota Fiscal emitida',
            'Content'=> 'Pedido faturado no Omie. NF número: '. intval($invoicing->nNF),
        ];

        //Cria interação no card específico 
        (InteractionHandler::createPloomesIteraction(json_encode($msg), $baseApi, $apiKey))? $message['addInteraction'] = 'Interação adicionada no card '.$deal['dealId'] : throw new InteracaoNaoAdicionadaException('Não foi possível adicionar a interação no card'.$deal['dealId'],1025);
        //muda a etapa da venda específica para NF-Emitida stage Id 40042597
        $stage = ['StageId'=> 40042597];
        $method = 'patch';
        (self::alterStageOrder(json_encode($stage), $idPedidoIntegracao, $baseApi, $method, $apiKey))? $message['alterStage'] = 'Estágio da venda alterado com sucesso': throw new EstagiodavendaNaoAlteradoException('Não foi possível alterar o estágio da venda',1026);
        return $message;
    }
    //CONSULTA PEDIDO NO OMIE
    public static function consultaPedidoOmie($appKey, $appSecret, $orderId )
    {   

        $array = [
            'app_key'=>$appKey,
            'app_secret'=>$appSecret,
            'call'=>'ConsultarPedido',
            'param'=>[
                    [
                        'codigo_pedido'=>$orderId,
                    ]
                ]
            ];

        $json = json_encode($array);
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://app.omie.com.br/api/v1/produtos/pedido/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        
        return json_decode($response, true);
    }
    //CONSULTA NOTA FISCAL NO OMIE
    public static function consultaNotaOmie($appKey, $appSecret, $orderId )
    {   
        $array = [
            'app_key'=>$appKey,
            'app_secret'=>$appSecret,
            'call'=>'ConsultarNF',
            'param'=>[
                    [
                        'nIdPedido'=>$orderId,
                    ]
                ]
            ];

        $json = json_encode($array);
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://app.omie.com.br/api/v1/produtos/nfconsultar/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        
        $nfe = json_decode($response, true);
        return $nfe['ide']['nNF'];
    }
    //SALVA NO BANCO DE DADOS AS INFORMAÇÕES DO DEAL
    public static function saveInvoicing($invoicing)
    {
        $id = Invoicing::insert(
            [
                'stage'=>$invoicing->etapa,
                'invoicing_date'=>$invoicing->dataFaturado,
                'invoicing_time'=>$invoicing->horaFaturado,
                'client_id'=>$invoicing->idCliente,
                'order_id'=>$invoicing->idPedido,
                'invoice_number'=>$invoicing->nNF,
                'order_number'=>$invoicing->numeroPedido,
                'order_amount'=>$invoicing->valorPedido,
                'user_id'=>$invoicing->authorId,
                'user_email'=>$invoicing->authorEmail,
                'user_name'=>$invoicing->authorName,
                'appkey'=>$invoicing->appKey,
                'created_at'=>date('Y-m-d H:i:s'),
             ]
        )->execute();

        if(empty($id)){
            return "Erro ao cadastrar Faturamenro no banco de dados.";
        }
        return $id;
    }
    //BUSCA CLIENTE NO PLOOMES PELO CNPJ
    public static function consultaClientePloomesCnpj($cnpjClient, $baseApi, $method, $apiKey)
    {
        $method='get';
        $headers = [
            'User-Key:' . $apiKey,
            'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseApi .'/Contacts?$filter=CNPJ+eq+'."'$cnpjClient'",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers

        ));

        $response = curl_exec($curl);
        $response =json_decode($response, true);
        
        curl_close($curl);

        return $response['value'][0]['Id'];
    }
    //ALTERA O ESTÁGIO DA VENDA NO PLOOMES
    public static function alterStageOrder($stage, $orderId, $baseApi, $method, $apiKey)
    {
        $headers = [
            'User-Key:' . $apiKey,
            'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseApi . 'Orders('.$orderId.')',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_POSTFIELDS =>$stage,
            CURLOPT_HTTPHEADER => $headers

        ));

        $response = curl_exec($curl);
        $response =json_decode($response, true);
        $stage = json_decode($stage,true);
        curl_close($curl);

       return ($response['stageid'] != $stage['StageId']) ? true :  false;
    }
    public static function clienteIdOmie($id, $appKey, $appSecret)
    {
        $jsonOmieIdCliente = [
            'app_key' => $appKey,
            'app_secret' => $appSecret,
            'call' => 'ConsultarCliente',
            'param' => [
                [
                    'codigo_cliente_omie'=>$id
                ]
            ]
                ];

        $jsonCnpj = json_encode($jsonOmieIdCliente);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://app.omie.com.br/api/v1/geral/clientes/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonCnpj,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $cliente = json_decode($response, true);
        $cnpj = DiverseFunctions::limpa_cpf_cnpj($cliente['cnpj_cpf']);

        return $cnpj;
    }
}