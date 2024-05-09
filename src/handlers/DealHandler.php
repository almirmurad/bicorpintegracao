<?php

namespace src\handlers;

use src\exceptions\BaseFaturamentoInexistenteException;
use src\exceptions\ClienteInexistenteException;
use src\exceptions\CnpjClienteInexistenteException;
use src\exceptions\EmailVendedorNaoExistenteException;
use src\exceptions\PedidoInexistenteException;
use src\exceptions\PedidoRejeitadoException;
use src\exceptions\ProdutoInexistenteException;
use src\exceptions\VendedorInexistenteException;
use src\exceptions\WebhookReadErrorException;
use src\models\Deal;
use src\models\Webhook;
use src\functions\DiverseFunctions;

class DealHandler
{
    //LÊ O WEBHOOK E CRIA O PEDIDO
    public static function readDealHook($json, $baseApi, $method, $apiKey)
    {   
        $message = [];
        $decoded = json_decode($json, true);
        // echo"<pre>";
        // print_r($decoded);
        // exit;

        //infos do webhook
        $webhook = new Webhook();
        $webhook->action = $decoded['Action']; // Ação 
        $webhook->entity = $decoded['Entity']; // Entidade
        $webhook->secondaryEntityId = $decoded['SecondaryEntityId']; // Entidade Secundária
        $webhook->accountId = $decoded['AccountId']; // Conta associada
        $webhook->actionUserId = $decoded['ActionUserId']; // Id do Usuário
        $webhook->webhookId = $decoded['WebhookId']; // Id do Webhook
        $webhook->webhookCreatorId = $decoded['WebhookCreatorId']; // Id do usuário que criou o webhook
        
        //salva o hook no banco
        $idWebhook = Self::saveWebhook($webhook);
        $message['webhookMessage'] ='Novo webhook criado id = '.$idWebhook;
        
        $prop = [];
        foreach ($decoded['New']['OtherProperties'] as $key => $op) {
            $prop[$key] = $op;
        }

        if (isset($decoded['Action']) && $decoded['Action'] == "Win" && !empty($decoded['New']['LastOrderId'])) {

            $deal = new Deal();
            
            //infos do Deal new
            $deal->attachmentsItems = $decoded['New']['AttachmentsItems'];
            $deal->collaboratingUsers = (isset($decoded['New']['CollaboratingUsers'][0]['UserId'])) ? $decoded['New']['CollaboratingUsers'][0]['UserId'] : 'Não definido'; // Usuários colaboradores
            $deal->contacts = $decoded['New']['Contacts']; // Contatos relacionados
            $deal->contactsProducts = $decoded['New']['ContactsProducts']; // Produtos de cliente
            // Base de Faturamento
            $deal->baseFaturamento = (isset($prop['deal_A965E8F5-EF81-4CF3-939D-1D7FE6F1556C']) && !empty($prop['deal_A965E8F5-EF81-4CF3-939D-1D7FE6F1556C'])) ? $prop['deal_A965E8F5-EF81-4CF3-939D-1D7FE6F1556C'] : throw new BaseFaturamentoInexistenteException('Base de faturamento inexistente para o webhook Id: '.$webhook->webhookId.' ',1001);

            // Fim de $prop Outras Propriedades //
            $deal->products = $decoded['New']['Products']; //Produtos relacionados
            $products = $deal->products; //Produtos relacionados
            // $produtos = [];
            // foreach ($products as $prdt) {
            //     $produtos['codigo_produto'] = $prdt['ProductId'];
            // }
            // $produtos['codigo_produto'] = '3448900782';
            // $produtos['quantidade'] = '1';
            // $produtos['valor_unitario'] = 150;
            // $deal->products = $produtos; //Produtos relacionados
            $deal->tags = (isset($decoded['New']['Tags'][0]['TagId'])) ? $decoded['New']['Tags'][0]['TagId'] : 'Não definido'; //Marcadores
            $deal->id = $decoded['New']['Id']; //Id do Deal
            $deal->title = $decoded['New']['Title']; // Título do Deal
            $deal->contactId = $decoded['New']['ContactId']; // Contatos relacionados
            // Busca o CNPJ do contato 
            (!empty($contactCnpj = Self::contactCnpj($deal->contactId, $baseApi, $method,  $apiKey))) ? $contactCnpj : throw new CnpjClienteInexistenteException('Cliente não informado ou não cadastrado no Omie ERP webhookId: '.$webhook->webhookId.'',1002); //cnpj do cliente
            $deal->contactName = $decoded['New']['ContactName']; // Nome do Contato no Deal
            $deal->personId = $decoded['New']['PersonId']; // Id do Contato
            $deal->personName = $decoded['New']['PersonName']; // Nome do contato
            $deal->pipelineId = $decoded['New']['PipelineId']; // Funil
            $deal->stageId = $decoded['New']['StageId']; // Estágio
            $deal->statusId = $decoded['New']['StatusId']; // Situação
            $deal->firstTaskId = $decoded['New']['FirstTaskId'];
            $deal->firstTaskDate = $decoded['New']['FirstTaskDate'];
            $deal->firstTaskNoTime = $decoded['New']['FirstTaskNoTime'];
            $deal->hasScheduledTasks = $decoded['New']['HasScheduledTasks']; //Possui tarefas agendadas
            $deal->tasksOrdination = $decoded['New']['TasksOrdination'];
            $deal->contactProductId = $decoded['New']['ContactProductId']; // Produtos de cliente
            $deal->lastQuoteId = $decoded['New']['LastQuoteId']; // Proposta
            $deal->isLastQuoteApproved = $decoded['New']['IsLastQuoteApproved']; // Proposta aprovada
            $deal->wonQuoteId = $decoded['New']['WonQuoteId']; // Proposta ganha
            $deal->wonQuote = $decoded['New']['WonQuote']; // Proposta ganha
            $deal->lastStageId = $decoded['New']['LastStageId'];
            $deal->lossReasonId = $decoded['New']['LossReasonId']; // Motivo de perda
            $deal->originId = $decoded['New']['OriginId']; // Origem
            $deal->ownerId = $decoded['New']['OwnerId']; // Responsável
            (!empty($mailVendedor = Self::ownerMail($deal->ownerId, $baseApi, $method,  $apiKey)))?$mailVendedor:
            throw new EmailVendedorNaoExistenteException('Não foi encontrado o email deste vendedor. Webhook'.$webhook->webhookId.'', 1003);
            $deal->startDate = $decoded['New']['StartDate']; // Início
            $deal->finishDate = $decoded['New']['FinishDate']; // Término
            $deal->currencyId = $decoded['New']['CurrencyId']; // Moeda
            $deal->amount = $decoded['New']['Amount']; // Valor
            $deal->startCurrencyId = $decoded['New']['StartCurrencyId'];
            $deal->startAmount = $decoded['New']['StartAmount'];
            $deal->read = $decoded['New']['Read'];
            $deal->lastInteractionRecordId = $decoded['New']['LastInteractionRecordId']; // Último contato
            $deal->lastOrderId = $decoded['New']['LastOrderId']; // Última venda
            //$deal->lastOrderIdOld = $decoded['Old']['LastOrderId']; // Última venda
            $deal->daysInStage = $decoded['New']['DaysInStage']; // Dias no estágio
            $deal->hoursInStage = $decoded['New']['HoursInStage']; // Horas no estágio
            $deal->length = $decoded['New']['Length']; // Duração
            $deal->createImportId = $decoded['New']['CreateImportId'];
            $deal->updateImportId = $decoded['New']['UpdateImportId'];
            $deal->leadId = $decoded['New']['LeadId']; // Lead origem
            $deal->originDealId = $decoded['New']['OriginDealId']; // Negócio origem
            $deal->reevId = $decoded['New']['ReevId'];
            $deal->creatorId = $decoded['New']['CreatorId']; // Criador
            $deal->updaterId = $decoded['New']['UpdaterId']; // Último atualizador
            $deal->createDate = $decoded['New']['CreateDate']; // Data de criação
            $deal->lastUpdateDate = $decoded['New']['LastUpdateDate']; // Data da última atualizaçãop
            $deal->lastDocumentId = $decoded['New']['LastDocumentId']; // Último documento
            $deal->dealNumber = $decoded['New']['DealNumber']; // Número
            $deal->importationIdCreate = $decoded['New']['ImportationIdCreate']; // Id da importação de criação (novo)
            $deal->importationIdUpdate = $decoded['New']['ImportationIdUpdate']; // Id da importação de atualização (novo)
            $deal->publicFormIdCreate = $decoded['New']['PublicFormIdCreate']; // Id do formulário externo de criação
            $deal->publicFormIdUpdate = $decoded['New']['PublicFormIdUpdate']; // Id do formulário externo de atualização
            $deal->webhookId = $webhook->webhookId; //inclui o id do webhook no deal
            //Encontra a base de faturamento
            // $deal->baseFaturamento = 404096109;
            switch ($deal->baseFaturamento) {
                case 404096111:
                    $deal->baseFaturamentoTitle = 'Manos PR';
                    $ncc = $_ENV['NCC_MPR'];
                    $appSecret = $_ENV['SECRETS_MPR'];
                    $appKey = $_ENV['APPK_MPR'];
                    break;

                case 404096110:
                    $deal->baseFaturamentoTitle = 'Manos SC';
                    $ncc = $_ENV['NCC_MSC'];
                    $appSecret = $_ENV['SECRETS_MSC'];
                    $appKey = $_ENV['APPK_MSC'];
                    break;

                case 404096109:
                    $deal->baseFaturamentoTitle = 'Manos Homologação';
                    $ncc = $_ENV['NCC_MHL'];
                    $appSecret = $_ENV['SECRETS_MHL'];
                    $appKey = $_ENV['APPK_MHL'];
                    break;
            }
            
            //busca a venda no ploomes
             (!empty($arrayRequestOrder = Self::requestOrder($deal->lastOrderId, $baseApi, $method, $apiKey))) ? $arrayRequestOrder : throw new PedidoInexistenteException('Venda Id: '.$deal->lastOrderId.' não encontrada no Ploomes webhook id: '.$webhook->webhookId.'',1004 );

            //  echo 'chegou em order<br>';
            //  print_r($arrayRequestOrder);
             
            //  exit;

            //array de produtos da venda
            $productsRequestOrder = $arrayRequestOrder[0]->Products;
            // echo 'Products request<br>';
            //  print_r($productsRequestOrder[0]);
             
            //  exit;


            $det = [];
           
            $productsOrder = [];
            foreach ($productsRequestOrder as $prdItem) { 
            // echo'<pre>';
            // print_r($prdItem);
            // exit;
                
                $det['ide'] = [];
                $det['ide']['codigo_item_integracao'] = $prdItem->Id;
                $det['produto'] = [];
                $idPrd = $prdItem->Product->Code;
                //encontra o id do produto no omie atraves do Code do ploomes
                (!empty($idProductOmie = Self::buscaIdProductOmie($appKey, $appSecret, $idPrd))) ? $idProductOmie : throw new ProdutoInexistenteException('Id do Produto inexistente no Omie webhookId: '.$webhook->webhookId.'',1005);
                $det['produto']['codigo_produto'] = $idProductOmie;
                $det['produto']['quantidade'] = $prdItem->Quantity;
                $det['produto']['tipo_desconto'] = 'P';
                $det['produto']['valor_desconto'] = number_format($prdItem->Discount, 2, ',', '.');
                $det['produto']['valor_unitario'] = $prdItem->UnitPrice;

                $productsOrder[] = $det;
            }
               
            //busca Observação da Proposta (Quote)
            $notes = strip_tags(Self::requestQuote($deal->lastQuoteId, $baseApi, $method, $apiKey));
            // echo'<pre>';
            // print_r($notes);
            // exit;
            
            //salva um deal no banco
            $dealCreatedId = Self::saveDeal($deal);   
            $message['dealMessage'] ='Id do Deal no Banco de Dados: '.$dealCreatedId;     
            //pega o id do cliente do Omie através do CNPJ do contact do ploomes           
            (!empty($idClienteOmie = Self::clienteIdOmie($contactCnpj, $appKey, $appSecret))) ? $idClienteOmie : throw new ClienteInexistenteException('Id do cliente não encontrado no Omie ERP! WebhookId: '.$webhook->webhookId.'',1006);
            //pega o id do cliente do Omie através do CNPJ do contact do ploomes           
            (!empty($codVendedorOmie = Self::vendedorIdOmie($mailVendedor, $appKey, $appSecret))) ? $codVendedorOmie : throw new VendedorInexistenteException('Id do vendedor não encontrado no Omie ERP! WebhookId: '.$webhook->webhookId.'',1007);
            //inclui o pedido no omie
            $incluiPedidoOmie = Self::criaPedidoOmie($appKey, $appSecret, $idClienteOmie, $deal->finishDate, $deal->lastOrderId, $productsOrder, $ncc, $codVendedorOmie, $notes);

            if ($incluiPedidoOmie) {

                if(isset($incluiPedidoOmie->faultstring)){
                    throw new PedidoRejeitadoException($incluiPedidoOmie->faultstring,1008);
                }
                
                $message['returnPedidoOmie'] ='Pedido criado no Omie via BICORP INTEGRAÇÃO pedido numero: '.intval($incluiPedidoOmie->numero_pedido);
            
                $msg=[
                    'ContactId' => $deal->contactId,
                    'DealId' => $deal->id,
                    'Content' => 'Venda('.intval($incluiPedidoOmie->numero_pedido).') criada no OMIE via API BICORP na base '.$deal->baseFaturamentoTitle.'.',
                    'Title' => 'Pedido Criado'
                ];
                //cria uma interação no card
                InteractionHandler::createPloomesIteraction(json_encode($msg), $baseApi, $apiKey);
            }            
            //IntegraHandler::montaTable($deal, $prop);//monta a tabela html pra retornar a view ("legado").
            return $message;
        } else {
            throw new WebhookReadErrorException('Não era um Card Ganho ou não havia venda na proposta do webhook Id '.$webhook->webhookId.'',1009);
        }                
    }
    //CRIA PEDIDO NO OMIE
    public static function criaPedidoOmie($appKey, $appSecret, $idClienteOmie, $finishDate, $lastOrderId, $productsOrder, $ncc, $codVendedorOmie, $notes)
    {   
        // echo'<pre>';
        // $prd = json_encode($productsOrder);
        // print_r($prd);
        // exit;
        $jsonOmie = [
            'app_key' => $appKey,
            'app_secret' => $appSecret,
            'call' => 'IncluirPedido',
            'param' => [
                [
                    'cabecalho' => [
                        'codigo_cliente' => $idClienteOmie, //Id do cliente do Omie retornado da função que busca no omie pelo cnpj
                        'data_previsao' => DiverseFunctions::convertDate($finishDate), //obrigatorio
                        'codigo_pedido_integracao' =>$lastOrderId, //codigo que busca pela integração específica
                        'numero_pedido' => $lastOrderId,
                        'origem_pedido' => 'API',
                        'etapa' => '10', //obrigatorio
                        //'qtde_parcela'=>2
                        //'codigo_parcela' =>'999' aceita parcelas customizadas precisa indicar junto ao fim estrutura 'lista_parcela' e a tag qtde_parcela
                    ],
                    'det'=>$productsOrder,
                    // 'det' => [
                    //     [
                    //         'ide' => [
                    //             'codigo_item_integracao' => $productsOrder['id_item'],//codigo do item da integração específica
                    //         ],
                    //         'produto' => $productsOrder,//integrado pelo codigo_produto_integracao deve ser igual ao id do ploomes porém é diferente pra cada base no omie
                    //     ]
                    // ],
                    'frete' => [
                        'modalidade' => '9',
                    ],
                    'informacoes_adicionais' => [
                        'codigo_conta_corrente' => $ncc,
                        'codigo_categoria' => '1.01.01', //obrigatorio
                        'numero_pedido_cliente'=>$numPedido,
                        'codVend' => $codVendedorOmie,
                    ],
                    // 'lista_parcelas'=>[
                    //     'parcela'=>[
                    //         [
                    //             'data_vencimento' => '26/04/2024',
                    //             'numero_parcela' => 1,
                    //             'percentual' => 50,
                    //             'valor' => 100
                    //         ],
                    //         [
                    //             'data_vencimento' => '09/09/2024',
                    //             'numero_parcela' => 2,
                    //             'percentual' => 50,
                    //             'valor' => 100   
                    //         ]
                    //     ]
                    //         ],
                    'observacoes'=> [
                        'obs_venda' => $notes,
                    ]
                ]
            ],
        ];
       
        // echo"<pre>";            
        $jsonOmie = json_encode($jsonOmie);
        // print_r($jsonOmie);
        // exit;
        // echo'<pre>';
        // print_r($json);
        // exit;
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
            CURLOPT_POSTFIELDS => $jsonOmie,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }
    //BUSCA O ID DE UM PRODUTO BASEADO NO CODIGO DO PRODUTO NO PLOOMES
    public static function buscaIdProductOmie($appKey, $appSecret, $idItem)
    {
        $jsonId = [
            'app_key' => $appKey,
            'app_secret' => $appSecret,
            'call' => 'ConsultarProduto',
            'param' => [
                [
                    'codigo'=>$idItem
                ]
            ],
        ];

        $jsonId = json_encode($jsonId);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://app.omie.com.br/api/v1/geral/produtos/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonId,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $item = json_decode($response);
        
        $id = $item->codigo_produto;
        
        return $id;

    }
    //ENCONTRA A VENDA NO PLOOMES
    public static function requestOrder($orderId, $baseApi, $method, $apiKey)
    {
        /**
         * {{server}}/Orders?$filter=Id+eq+402118677&$expand=Products($select=Product,Quantity;$expand=Parts($expand=Product($select=Code),OtherProperties),Product($select=Code),)&$orderby=Id
         */

        $headers = [
            'User-Key:' . $apiKey,
            'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseApi . 'Orders?$filter=Id+eq+' . $orderId . '&$expand=Products($select=Product,Discount,Quantity,Id,UnitPrice;$expand=Parts($expand=Product($select=Code),OtherProperties),Product($select=Code,Id),)&$orderby=Id',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers

        ));

        $response = json_decode(curl_exec($curl));
       
        curl_close($curl);
        $order = (empty($response->value)) ? Null : $response->value; 
      
        return $order;
    }
    //ENCONTRA A VENDA NO PLOOMES
    public static function requestQuote($quoteId, $baseApi, $method, $apiKey)
    {
        /**
         * {{server}}/Orders?$filter=Id+eq+402118677&$expand=Products($select=Product,Quantity;$expand=Parts($expand=Product($select=Code),OtherProperties),Product($select=Code),)&$orderby=Id
         */

        $headers = [
            'User-Key:' . $apiKey,
            'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseApi . 'Quotes?$filter=Id+eq+'.$quoteId.'&$select=Notes',
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

        curl_close($curl);
        $notes = json_decode($response, true);
        $notes = $notes['value'][0]['Notes'];
        // echo'<pre>';
        // print_r($notes['value'][0]['Notes']);
        // exit;

        return $notes;
    }
    //ENCONTRA O CNPJ DO CLIENTE NO PLOOMES
    public static function contactCnpj($contactId, $baseApi, $method,  $apiKey)
    {

        $headers = [
            'User-Key:' . $apiKey,
            'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseApi . 'Contacts?$filter=Id+eq+' . $contactId . '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers

        ));

        $responseCnpj = curl_exec($curl);

        curl_close($curl);

        $responseCnpj = json_decode($responseCnpj);

        $response = (!empty($responseCnpj->value[0])) ? $responseCnpj->value[0]->CNPJ : NULL;

        return $response;
    }
     //ENCONTRA O EMAIL DO VENDEDOR NO PLOOMES
     public static function ownerMail($ownerId, $baseApi, $method,  $apiKey)
     {
 
         $headers = [
             'User-Key:' . $apiKey,
             'Content-Type: application/json',
         ];
 
         $curl = curl_init();
 
         curl_setopt_array($curl, array(
             CURLOPT_URL => $baseApi . 'Users?$filter=Id+eq+' . $ownerId . '',
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_ENCODING => '',
             CURLOPT_MAXREDIRS => 10,
             CURLOPT_TIMEOUT => 0,
             CURLOPT_FOLLOWLOCATION => true,
             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
             CURLOPT_CUSTOMREQUEST => strtoupper($method),
             CURLOPT_HTTPHEADER => $headers
 
         ));
 
         $responseCnpj = curl_exec($curl);
 
         curl_close($curl);
 
         $responseCnpj = json_decode($responseCnpj);
 
         $response = $responseCnpj->value[0]->Email;
         
         return $response;
     }
    //MONTA TABELA EM HTML PRA ENVIAR A VIEW
    public static function montaTable($deal, $prop)
    {

        $html = "<table class='table-content' >";

        $html .= "<thead>";
        $html .= "<tr>";
        $html .= "<th>";
        $html .= "Action";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Entity";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "SecondaryEntityId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "AccountId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ActionUserId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "WebhookId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "WebhookCreatorId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "AttachmentsItems";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CollaboratingUsers";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Base Faturamento";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "TagId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "id";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Title";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ContactId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ContactName";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PersonId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PersonName";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PipelineId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StageId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StatusId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "FirstTaskId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "FirstTaskDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "FirstTaskNoTime";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "HasScheduledTasks";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "TasksOrdination";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ContactProductId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastQuoteId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "IsLastQuoteApproved";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "WonQuoteId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "WonQuote";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastStageId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LossReasonId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "OriginId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "OwnerId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StartDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "FinishDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CurrencyId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Amount";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StartCurrencyId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "StartAmount";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Read";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastInteractionRecordId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastOrderId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "DaysInStage";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "HoursInStage";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "Length";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CreateImportId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "UpdateImportId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LeadId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "OriginDealId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ReevId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CreatorId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "UpdaterId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "CreateDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastUpdateDate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "LastDocumentId";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "DealNumber";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ImportationIdCreate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "ImportationIdUpdate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PublicFormIdCreate";
        $html .= "</th>";
        $html .= "<th>";
        $html .= "PublicFormIdUpdate";
        $html .= "</th>";
        $html .= "</tr>";
        $html .= "</thead>";

        $html .= "<tbody>";
        $html .= "<tr>";

        $html .= "<td>";
        $html .= $deal->action;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->entity;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->secondaryEntityId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->accountId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->actionUserId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->webhookId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->webhookCreatorId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->attachmentsItems ? $deal->attachmentsItems : '';
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->collaboratingUsers ? $deal->collaboratingUsers : '';
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->baseFaturamento;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->tags;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->id;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->title;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->contactId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->contactName;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->personId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->personName;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->pipelineId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->stageId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->statusId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->firstTaskId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->firstTaskDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->firstTaskNoTime;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->hasScheduledTasks;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->tasksOrdination;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->contactProductId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastQuoteId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->isLastQuoteApproved;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->wonQuoteId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->wonQuote;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastStageId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lossReasonId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->originId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->ownerId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->startDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->finishDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->currencyId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= "R$ " . number_format($deal->amount, 2, ',', '.');
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->startCurrencyId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= "R$ " . number_format($deal->startAmount, 2, ',', '.');
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->read;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastInteractionRecordId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastOrderId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->daysInStage;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->hoursInStage;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->length;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->createImportId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->updateImportId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->leadId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->originDealId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->reevId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->creatorId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->updaterId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->createDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= DiverseFunctions::convertDate($deal->lastUpdateDate);
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->lastDocumentId;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->dealNumber;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->importationIdCreate;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->importationIdUpdate;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->publicFormIdCreate;
        $html .= "</td>";

        $html .= "<td>";
        $html .= $deal->publicFormIdUpdate;
        $html .= "</td>";

        $html .= "</tr>";
        $html .= "</tbody>";

        $html .= "</table>";
        //OTHER Properties
        if (!empty($prop)) {
            $html .= "<br/>";
            $html .= "<h2>Outras Propriedades</h2>";
            $html .= "<table class='table-content' border='1px'>";

            $html .= "<thead>";
            $html .= "<tr>";
            foreach ($prop as $key => $item) {
                $html .= "<th>";
                $html .= $key;
                $html .= "</th>";
            }
            $html .= "</tr>";
            $html .= "</thead>";
            $html .= "<tbody>";
            $html .= "<tr>";
            foreach ($prop as $itemProps) {
                $html .= "<td>";
                $html .= $itemProps;
                $html .= "</td>";
            }
            $html .= "</tr>";
            $html .= "</tbody>";
            $html .= "</table>";
        }

        if (!empty($deal->products)) {
        }
        return $html;
    }
    //PEGA O ID DO CLIENTE DO OMIE
    public static function clienteIdOmie($cnpj, $appKey, $appSecret)
    {
        $jsonOmieIdCliente = [
            'app_key' => $appKey,
            'app_secret' => $appSecret,
            'call' => 'ListarClientes',
            'param' => [
                [
                    'clientesFiltro'=>['cnpj_cpf'=> $cnpj]
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

        $cliente = json_decode($response);
        $idClienteOmie = $cliente->clientes_cadastro[0]->codigo_cliente_omie;

        return $idClienteOmie;
    }
    //PEGA O ID DO vendedor DO OMIE
    public static function vendedorIdOmie($mailVendedor, $appKey, $appSecret)
    {

        $jsonOmieVendedor = [
            'app_key' => $appKey,
            'app_secret' => $appSecret,
            'call' => 'ListarVendedores',
            'param' => [
                [
                    'filtrar_por_email'=>$mailVendedor
                ]
            ]
                ];

        $jsonVendedor = json_encode($jsonOmieVendedor);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://app.omie.com.br/api/v1/geral/vendedores/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonVendedor,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
       
        $vendedor = json_decode($response);
        $codigoVendedor = '';
        $arrayVendedores = $vendedor->cadastro;
        if(count($arrayVendedores) > 1){
            foreach($arrayVendedores as $itArrVend){
            
                if($itArrVend->inativo && $itArrVend->inativo === 'N'){
                    $codigoVendedor = $itArrVend->codigo;
                }
            }
        }else{
            foreach($arrayVendedores as $itArrVend){
                    $codigoVendedor = $itArrVend->codigo;
            }
        }
        
        // echo'<pre>';
        // print_r($codigoVendedor);
        // exit;
        return $codigoVendedor;
    }
    //SALVA NO BANCO DE DADOS AS INFORMAÇÕES DO WEBHOOK
    public static function saveWebhook($webhook)
    {   

       $id = Webhook::insert(
                    [
                        'action'=>$webhook->action,
                        'entity'=>$webhook->entity,
                        'secondary_entity'=>$webhook->secondaryEntityId,
                        'account_id'=>$webhook->accountId,
                        'account_user_id'=>$webhook->actionUserId,
                        'webhook_id'=>$webhook->webhookId,
                        'webhook_creator_id'=>$webhook->webhookCreatorId,
                        'created_at'=>date("Y-m-d H:i:s"),
                     ]
                )->execute();

                if(empty($id)){
                    return "Erro ao cadastrar webhook no banco de dados.";
                }
                return 'Id do cadastro do webhook no banco: '.$id;
    }
    //SALVA NO BANCO DE DADOS AS INFORMAÇÕES DO DEAL
    public static function saveDeal($deal)
    {
        $id = Deal::insert(
            [
                'billing_basis'=>$deal->baseFaturamento,
                'billing_basis_title'=>$deal->baseFaturamentoTitle,
                'deal_id'=>$deal->id,
                'contact_id'=>$deal->contactId,
                'person_id'=>$deal->personId,
                'pipeline_id'=>$deal->pipelineId,
                'stage_id'=>$deal->stageId,
                'status_id'=>$deal->statusId,
                'won_quote_id'=>$deal->wonQuoteId,
                'create_date'=>$deal->createDate,
                'last_order_id'=>$deal->lastOrderId,
                'creator_id'=>$deal->creatorId,
                'webhook_id'=>$deal->webhookId,
                'created_at'=>date('Y-m-d H:i:s'),
             ]
        )->execute();

        if(empty($id)){
            return "Erro ao cadastrar Venda no banco de dados.";
        }
        return $id;
    }
    
}