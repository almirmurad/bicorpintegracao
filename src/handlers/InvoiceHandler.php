<?php

namespace src\handlers;

use PDOException;
use src\exceptions\DealNaoEncontradoBDException;
use src\exceptions\EstagiodavendaNaoAlteradoException;
use src\exceptions\FaturamentoNaoCadastradoException;
use src\exceptions\InteracaoNaoAdicionadaException;
use src\exceptions\NotaFiscalNaoCadastradaException;
use src\exceptions\NotaFiscalNaoCanceladaException;
use src\exceptions\NotaFiscalNaoEncontradaException;
use src\exceptions\PedidoNaoEncontradoOmieException;
use src\exceptions\WebhookReadErrorException;
use src\functions\DiverseFunctions;
use src\models\Deal;
use src\models\Homologacao_invoicing;
use src\models\Manospr_invoicing;
use src\models\Manossc_invoicing;

class InvoiceHandler
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
        
        //pega a chave secreta para a base de faturamento vinda no faturamento
        switch($decoded['appKey']){
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
        // busca o pedido através id do pedido no omie retorna exceção se não encontrat 
        if(!$pedidoOmie = Self::consultaPedidoOmie($decoded['appKey'], $appSecret, $decoded['event']['idPedido'])){throw new PedidoNaoEncontradoOmieException('Pedido '.$decoded['event']['idPedido'].' não encontrado no Omie ERP',1023);}
        //verifica se existe o codigo de integração no pedido do  omie que é o OrderId para buscar o Deal no banco ou retorna nulo
        $idPedidoIntegracao = (isset($pedidoOmie['pedido_venda_produto']['cabecalho']['codigo_pedido_integracao']))? $pedidoOmie['pedido_venda_produto']['cabecalho']['codigo_pedido_integracao']: null;
        //$idPedidoIntegracao=402303406;
        // Busca o Deal salvo no banco com o número do pedido de integração para pegar os dados e montar o cabeçalho da mensagem, caso não encontre retorna nulo e segue  
        $deal = Deal::select()->where('last_order_id', $idPedidoIntegracao)->one();
        //busca o cnpj do cliente para consultar o contact id no ploomes
        $cnpjClient = self::clienteIdOmie($decoded['event']['idCliente'], $decoded['appKey'], $appSecret);
 
        try{                
            //consulta a nota fiscal no omie para retornar o numero da nota.            
            ($nfe = Self::consultaNotaOmie($decoded['appKey'], $appSecret, $decoded['event']['idPedido']))?? throw new NotaFiscalNaoEncontradaException('Nota fiscal não encontrada para o pedido: '.$decoded['event']['idPedido'], 1022);  

            //VERIFICA PARA QUAL BASE DE NOTAS FISCAIS SERÁ ENVIADO A NOTA

            switch($decoded['appKey']){
                case 2337978328686: //MHL            
                    
                    //Monta o objeto com infos do webhook
                    $invoicing = new Homologacao_invoicing();
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
                    $invoicing->nNF = $nfe;

                    try{
                        //salva o faturamento no banco MHL
                        $idInvoicing = HomologacaoInvoicingHandler::saveHomologacaoInvoicing($invoicing);
                        $message['saveInvoicing'] = 'Novo faturamento armazenado na base de Homologação id: '.$idInvoicing;
                        
                    }catch(PDOException $e){          
                        throw new FaturamentoNaoCadastradoException('NOTA FISCAL NÚMERO '.intval($nfe).' JÁ FOI CADASTRADA NA BASE DE DADOS E NÃO PODE SER REPITIDA!'.$e->getMessage(),1021);
                    }


                    break;
                    
                case 2335095664902: // MPR

                    //Monta o objeto com infos do webhook
                    $invoicing = new Manospr_invoicing();
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
                    $invoicing->nNF = $nfe;

                    try{
                        //salva o faturamento no banco MPR
                        $idInvoicing = ManosPrInvoicingHandler::saveManosPrInvoicing($invoicing);
                        $message['saveInvoicing'] = 'Novo faturamento armazenado na base de Manos-PR id: '.$idInvoicing;
                    }catch(PDOException $e){          
                        throw new FaturamentoNaoCadastradoException('NOTA FISCAL NÚMERO '.intval($nfe).' JÁ FOI CADASTRADA NA BASE DE DADOS E NÃO PODE SER REPITIDA!'.$e->getMessage(),1021);
                    }


                    break;
                    
                case 2597402735928: // MSC
                    
                    //Monta o objeto com infos do webhook
                    $invoicing = new Manossc_invoicing();
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
                    $invoicing->nNF = $nfe;

                    try{
                        //salva o faturamento no banco MSC
                        $idInvoicing = ManosScInvoicingHandler::saveManosScInvoicing($invoicing);
                        $message['saveInvoicing'] = 'Novo faturamento armazenado na base de Manos-SC id: '.$idInvoicing;
                    }catch(PDOException $e){          
                        throw new FaturamentoNaoCadastradoException('NOTA FISCAL NÚMERO '.intval($nfe).' JÁ FOI CADASTRADA NA BASE DE DADOS E NÃO PODE SER REPITIDA!'.$e->getMessage(),1021);
                    }



                    break;
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
            'Content'=> 'Nota Fiscal ('. intval($nfe).') emitida no Omie ERP.',
        ];


        //Cria interação no card específico 
        (InteractionHandler::createPloomesIteraction(json_encode($msg), $baseApi, $apiKey))? $message['addInteraction'] = 'Interação adicionada no card ' : throw new InteracaoNaoAdicionadaException('Não foi possível adicionar a interação no card'.$deal['dealId'],1025);
        //muda a etapa da venda específica para NF-Emitida stage Id 40042597
        $stage = ['StageId'=> 40042597];
        $method = 'patch';
        (self::alterStageOrder(json_encode($stage), $idPedidoIntegracao, $baseApi, $method, $apiKey))? $message['alterStage'] = 'Estágio da venda alterado com sucesso': throw new EstagiodavendaNaoAlteradoException('Não foi possível alterar o estágio da venda. Possivelmente a venda foi criada direto no Omie',1026);

        return $message;
    }

    //RECEBE O WEBHOOK DE NOTA CANCELADA, CANCELA NO BANCO E ENVIA INTERAÇÃO NO PLOOMES CRM
    public static function isDeletedInvoice($json, $baseApi, $method, $apiKey)
    {

        $message = [];
        $current = date('d/m/Y H:i:s');
        $decoded = json_decode($json, true);

        if( $decoded['topic'] !== "NFe.NotaCancelada" && $decoded['event']['acao'] !== 'cancelada'){
            throw new WebhookReadErrorException('Não foi possível ler o Webhook ou não existe nota fiscal cancelada!',1020);
        }
            
           //VERIFICA PARA QUAL BASE DE NOTAS FISCAIS SERÁ EDITADA A NOTA
           switch($decoded['appKey'])
           {
            case 2337978328686: //MHL    
                
                try{
                    $id = HomologacaoInvoicingHandler::isIssetInvoice($decoded['event']['id_pedido']);
                    if(is_string($id)){
                        throw new NotaFiscalNaoCadastradaException('Erro ao consultar a base de dados de Manos Homologação. Erro: '.$id. 'em '.$current, 1030);
                        }elseif(empty($id)){
                        throw new NotaFiscalNaoCadastradaException('Nota fiscal não cadastrada na base de dados de notas de Manos-PR , ou já foi cancelada em '.$current, 1030);
                    }else{$message['invoice']['issetInvoice'] = 'Nota fiscal do pedido '. $decoded['event']['id_pedido'] .'encontrada na base em '.$current;
                    }

                }catch(NotaFiscalNaoCadastradaException $e){
                    //$message['invoice']['error']['notRegistered'] =;
                    throw new NotaFiscalNaoCadastradaException( $e->getMessage());
                }
                        
                //altera a nota fiscal no banco para cancelada
                try{
                    //Altera a nota para cancelada no banco MHL
                    $altera = HomologacaoInvoicingHandler::alterHomologacaoInvoice($decoded['event']['id_pedido']);

                    if(is_string($altera)){
                        throw new NotaFiscalNaoCanceladaException('Erro ao consultar a base de dados de Manos Homologação. Erro: '.$altera. 'em '.$current, 1030);                     
                    }

                    $message['invoice']['iscanceled'] = 'Nota fiscal do pedido '. $decoded['event']['id_pedido'] .'cancelada com sucesso em '.$current;
                    
                }catch(NotaFiscalNaoCanceladaException $e){          
                    throw new NotaFiscalNaoCanceladaException($e->getMessage(), 1031);
                }

                break;
                
            case 2335095664902: // MPR
                
                try{
                    $id = ManosPrInvoicingHandler::isIssetInvoice($decoded['event']['id_pedido']);
                    if(is_string($id)){
                        throw new NotaFiscalNaoCadastradaException('Erro ao consultar a base de dados de Manos-PR. Erro: '.$id. 'em '.$current, 1030);
                        }elseif(empty($id)){
                        throw new NotaFiscalNaoCadastradaException('Nota fiscal não cadastrada na base de dados de notas de Manos-PR , ou já foi cancelada em '.$current, 1030);
                    }else{$message['invoice']['issetInvoice'] = 'Nota fiscal do pedido '. $decoded['event']['id_pedido'] .'encontrada na base em '.$current;
                    }

                }catch(NotaFiscalNaoCadastradaException $e){
                    //$message['invoice']['error']['notRegistered'] =;
                    throw new NotaFiscalNaoCadastradaException( $e->getMessage());
                }
                        
                //altera a nota fiscal no banco para cancelada
                try{
                    //Altera a nota para cancelada no banco MHL
                    $altera = ManosPrInvoicingHandler::alterManosPrInvoiceHandler($decoded['event']['id_pedido']);

                    if(is_string($altera)){
                        throw new NotaFiscalNaoCanceladaException('Erro ao consultar a base de dados de Manos Homologação. Erro: '.$altera. 'em '.$current, 1030);                     
                    }

                    $message['invoice']['iscanceled'] = 'Nota fiscal do pedido '. $decoded['event']['id_pedido'] .'cancelada com sucesso em '.$current;
                    
                }catch(NotaFiscalNaoCanceladaException $e){          
                    throw new NotaFiscalNaoCanceladaException($e->getMessage(), 1031);
                }

            break;
                
            case 2597402735928: // MSC
                
                try{
                    $id = ManosScInvoicingHandler::isIssetInvoice($decoded['event']['id_pedido']);
                    if(is_string($id)){
                        throw new NotaFiscalNaoCadastradaException('Erro ao consultar a base de dados de Manos-SC. Erro: '.$id. 'em '.$current, 1030);
                        }elseif(empty($id)){
                        throw new NotaFiscalNaoCadastradaException('Nota fiscal não cadastrada na base de dados de notas de Manos-SC , ou já foi cancelada em '.$current, 1030);
                    }else{$message['invoice']['issetInvoice'] = 'Nota fiscal do pedido '. $decoded['event']['id_pedido'] .'encontrada na base em '.$current;
                    }

                }catch(NotaFiscalNaoCadastradaException $e){
                    //$message['invoice']['error']['notRegistered'] =;
                    throw new NotaFiscalNaoCadastradaException( $e->getMessage());
                }
                        
                //altera a nota fiscal no banco para cancelada
                try{
                    //Altera a nota para cancelada no banco MHL
                    $altera = ManosScInvoicingHandler::alterManosScInvoice($decoded['event']['id_pedido']);

                    if(is_string($altera)){
                        throw new NotaFiscalNaoCanceladaException('Erro ao consultar a base de dados de Manos-SC. Erro: '.$altera. 'em '.$current, 1030);                     
                    }

                    $message['invoice']['iscanceled'] = 'Nota fiscal do pedido '. $decoded['event']['id_pedido'] .' cancelada com sucesso em '.$current;
                    
                }catch(NotaFiscalNaoCanceladaException $e){          
                    throw new NotaFiscalNaoCanceladaException($e->getMessage(), 1031);
                }

                break;
            }

            //busca o contact id do cliente no P`loomes CRM através do cnpj do cliente no Omie ERP
            $contactId = Self::consultaClientePloomesCnpj($decoded['event']['empresa_cnpj'],$baseApi, $method, $apiKey);
            //monta a mensadem para atualizar o card do ploomes
            $msg=[
                'ContactId' => $contactId,
                'Content' => 'Nota fiscal '.intval($decoded['event']['numero_nf']).' cancelada no Omie ERP em: '.$current,
                'Title' => 'Nota Fiscal Cancelada no Omie ERP'
            ];

            //cria uma interação no card
            (InteractionHandler::createPloomesIteraction(json_encode($msg), $baseApi, $apiKey))?$message['interactionMessage'] = 'Integração de cancelamento de nota fiscal concluída com sucesso!<br> Nota Fiscal: '.intval($decoded['event']['numero_nf']).' foi cancelada no Omie ERP e interação criada no cliente id: '.$contactId.' em: '.$current : throw new InteracaoNaoAdicionadaException('Não foi possível gravar a mensagem de nota cancelada no Ploomes CRM',1032);

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

       return ($response['value'][0]['StageId'] === $stage['StageId']) ? true :  false;
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