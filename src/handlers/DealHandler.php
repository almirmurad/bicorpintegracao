<?php

namespace src\handlers;

use PDOException;
use src\exceptions\BaseFaturamentoInexistenteException;
use src\exceptions\ClienteInexistenteException;
use src\exceptions\CnpjClienteInexistenteException;
use src\exceptions\DealNaoEncontradoBDException;
use src\exceptions\DealNaoExcluidoBDException;
use src\exceptions\EmailVendedorNaoExistenteException;
use src\exceptions\InteracaoNaoAdicionadaException;
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
        $current = date('d/m/Y H:i:s');
        $message = [];
        $decoded = json_decode($json, true);
        //infos do webhook
        $webhook = new Webhook();
        $webhook->action = $decoded['Action']; // Ação 
        $webhook->entity = $decoded['Entity']; // Entidade
        $webhook->secondaryEntityId = $decoded['SecondaryEntityId']; // Entidade Secundária
        $webhook->accountId = $decoded['AccountId']; // Conta associada
        $webhook->actionUserId = $decoded['ActionUserId']; // Id do Usuário
        $webhook->webhookId = $decoded['WebhookId']; // Id do Webhook
        $webhook->webhookCreatorId = $decoded['WebhookCreatorId']; // Id do usuário que criou o webhook
        
        if (isset($decoded['Action']) && $decoded['Action'] == "Win" && !empty($decoded['New']['LastOrderId'])) 
        {    
            //salva o hook no banco
            $idWebhook = Self::saveWebhook($webhook);
            $message['webhookMessage'] ='Novo webhook criado id = '.$webhook->webhookId . ' em: '. $current;
            /************************************************************
            *                   Other Properties                        *
            *                                                           *
            * No webhook do Card pegamos os campos de Other Properies   *
            * para encontrar a chave da base de faturamento do Omie     *
            *                                                           *
            *************************************************************/
            $prop = [];
            foreach ($decoded['New']['OtherProperties'] as $key => $op) {
                $prop[$key] = $op;
            }
            //cria objeto deal
            $deal = new Deal();
            //infos do Deal new
            $deal->attachmentsItems = $decoded['New']['AttachmentsItems'];
            $deal->collaboratingUsers = (isset($decoded['New']['CollaboratingUsers'][0]['UserId'])) ? $decoded['New']['CollaboratingUsers'][0]['UserId'] : 'Não definido'; // Usuários colaboradores
            $deal->contacts = $decoded['New']['Contacts']; // Contatos relacionados
            $deal->contactsProducts = $decoded['New']['ContactsProducts']; // Produtos de cliente
            // Base de Faturamento
            $deal->baseFaturamento = (isset($prop['deal_A965E8F5-EF81-4CF3-939D-1D7FE6F1556C']) && !empty($prop['deal_A965E8F5-EF81-4CF3-939D-1D7FE6F1556C'])) ? $prop['deal_A965E8F5-EF81-4CF3-939D-1D7FE6F1556C'] : throw new BaseFaturamentoInexistenteException('Base de faturamento inexistente para o card id: '.$decoded['New']['Id'].' e pedido de venda Ploomes CRM: '.$decoded['New']['LastOrderId'].'em'.$current,1001);
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
            (!empty($contactCnpj = Self::contactCnpj($deal->contactId, $baseApi, $method,  $apiKey))) ? $contactCnpj : throw new CnpjClienteInexistenteException('Cliente não informado ou não cadastrado no Omie ERP. Id do card Ploomes CRM: '.$decoded['New']['Id'].' e pedido de venda Ploomes CRM: '.$decoded['New']['LastOrderId'].'em'.$current,1002); //cnpj do cliente
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
            throw new EmailVendedorNaoExistenteException('Não foi encontrado o email deste vendedor. Id do card Ploomes CRM: '.$decoded['New']['Id'].' e pedido de venda Ploomes CRM: '.$decoded['New']['LastOrderId'].'em'.$current, 1003);
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

            /**************************************************** 
            *        Encontra a base de faturamento             *
            *                                                   *
            * NO webhook do Card pegamos os dados do checklist  * 
            * para encontrar a base de faturamento do Omie      *
            *                                                   *
            *****************************************************/
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
            /**************************************************** 
            *        busca dados da venda no ploomes            *
            *                                                   *
            * Na venda encontramos o array de itens da venda    * 
            * Montamos o det (array de items da venda no omie)  *
            *                                                   *
            *****************************************************/
            (!empty($arrayRequestOrder = Self::requestOrder($deal->lastOrderId, $baseApi, $method, $apiKey))) ? $arrayRequestOrder : throw new PedidoInexistenteException('Venda Id: '.$deal->lastOrderId.' não encontrada no Ploomes. Card id: '.$deal->id.' em: '.$current,1004 );
            //array de produtos da venda
            $productsRequestOrder = $arrayRequestOrder[0]->Products;
            //Array de detalhes do item da venda
            $det = [];
            $productsOrder = [];
            foreach ($productsRequestOrder as $prdItem) { 
                
                $det['ide'] = [];
                $det['ide']['codigo_item_integracao'] = $prdItem->Id;
                $det['produto'] = [];
                $idPrd = $prdItem->Product->Code;
                //encontra o id do produto no omie atraves do Code do ploomes
                (!empty($idProductOmie = Self::buscaIdProductOmie($appKey, $appSecret, $idPrd))) ? $idProductOmie : throw new ProdutoInexistenteException('Id do Produto inexistente no Omie ERP. Id do card Ploomes CRM: '.$deal->id.' e pedido de venda Ploomes CRM: '.$deal->lastOrderId.'em'.$current,1005);
                $det['produto']['codigo_produto'] = $idProductOmie;
                $det['produto']['quantidade'] = $prdItem->Quantity;
                $det['produto']['tipo_desconto'] = 'P';
                $det['produto']['valor_desconto'] = number_format($prdItem->Discount, 2, ',', '.');
                $det['produto']['valor_unitario'] = $prdItem->UnitPrice;

                $productsOrder[] = $det;
            }
            /****************************************************************
            *                          Request Quote                        *
            *                                                               *
            * Busca os dados da proposta (quote) para pegar a observação    *
            * e o parcelamento escolhido em campos personalizados do ploomes*
            *                                                               *
            *****************************************************************/
            $quote = Self::requestQuote($deal->lastQuoteId, $baseApi, $method, $apiKey);
            //busca Observação da Proposta (Quote)
            ($notes = strip_tags($quote['value'][0]['Notes']))? $notes : $notes='Venda à Vista!';
            //busca Parcelamento na Proposta (Quote)
            $texto = $quote['value'][0]['OtherProperties'][0]['ObjectValueName'];
            //verifica se exixtema parcelas ou se é a vista
            if($texto !== "a vista" ){
                $parcelamento = $texto;
            }else{
                $parcelamento = 0;
            }
            //installments é o parcelamento padrão do ploomes
            //print_r(count($quote['value'][0]['Installments']));

            //pega o id do cliente do Omie através do CNPJ do contact do ploomes           
            (!empty($idClienteOmie = Self::clienteIdOmie($contactCnpj, $appKey, $appSecret))) ? $idClienteOmie : throw new ClienteInexistenteException('Id do cliente não encontrado no Omie ERP! Id do card Ploomes CRM: '.$deal->id.' e pedido de venda Ploomes CRM: '.$deal->lastOrderId.' em: '.$current,1006);
            //pega o id do cliente do Omie através do CNPJ do contact do ploomes           
            (!empty($codVendedorOmie = Self::vendedorIdOmie($mailVendedor, $appKey, $appSecret))) ? $codVendedorOmie : throw new VendedorInexistenteException('Id do vendedor não encontrado no Omie ERP!Id do card Ploomes CRM: '.$deal->id.' e pedido de venda Ploomes CRM: '.$deal->lastOrderId.' em: '.$current,1007);
            //inclui o pedido no omie

            /****************************************************************
            *                     Cria Pedido no Omie                       *
            *                                                               *
            * Cria um pedido de venda no omie. Obrigatório enviar:          *
            * chave app do omie, chave secreta do omie, id do cliente omie, *
            * data de previsão(finish date), id pedido integração (id pedido* 
            * no ploomes), array de produtos($prodcutsOrder), numero conta  *    
            * corrente do Omie ($ncc), id do vendedor omie($codVendedorOmie)*
            * Total do pedido e Array de parcelamento                       *
            *                                                               *
            *****************************************************************/
            $incluiPedidoOmie = Self::criaPedidoOmie($appKey, $appSecret, $idClienteOmie, $deal->finishDate, $deal->lastOrderId, $productsOrder, $ncc, $codVendedorOmie, $notes, $arrayRequestOrder, $parcelamento  );
            //verifica se criou o pedido no omie
            if ($incluiPedidoOmie) {
                //se no pedido existir faulstring, então deu erro na inclusão
                if(isset($incluiPedidoOmie->faultstring)){
                    throw new PedidoRejeitadoException($incluiPedidoOmie->faultstring,1008);
                }
                $message['returnPedidoOmie'] ='Pedido criado no Omie via BICORP INTEGRAÇÃO pedido numero: '.intval($incluiPedidoOmie->numero_pedido);
                //inclui o id do pedido no omie na tabela deal
                if($incluiPedidoOmie->codigo_pedido){
                    //salva um deal no banco
                    $deal->omieOrderId = $incluiPedidoOmie->codigo_pedido;
                    $dealCreatedId = Self::saveDeal($deal);   
                    $message['dealMessage'] ='Id do Deal no Banco de Dados: '.$dealCreatedId;  
                }
                //monta a mensadem para atualizar o card do ploomes
                $msg=[
                    'ContactId' => $deal->contactId,
                    'DealId' => $deal->id,
                    'Content' => 'Venda('.intval($incluiPedidoOmie->numero_pedido).') criada no OMIE via API BICORP na base '.$deal->baseFaturamentoTitle.'.',
                    'Title' => 'Pedido Criado'
                ];
                //cria uma interação no card
                (InteractionHandler::createPloomesIteraction(json_encode($msg), $baseApi, $apiKey))?$message['interactionMessage'] = 'Integração concluída com sucesso!<br> Pedido Ploomes: '.$deal->lastOrderId.' card nº: '.$deal->id.' e client id: '.$deal->contactId.' gravados no Omie ERP com o numero: '.intval($incluiPedidoOmie->numero_pedido).' e mensagem enviada com sucesso em: '.$current : throw new InteracaoNaoAdicionadaException('Não foi possível gravar a mensagem na venda',1010);
            }else{
                $message['returnPedidoOmie'] ='Não foi possível gravar o pedido no Omie!';
            }           
            
            return $message;
        } else {
            throw new WebhookReadErrorException('Não era um Card Ganho ou não havia venda na proposta do card Nº '.$decoded['New']['Id'].' data '.$current ,1009);
        }                
    }

    //LÊ O WEBHOOK DE CARD EXCLUIDO
    public static function deletedDealHook($json){
        $current = date('d/m/Y H:i:s');
        $message = [];

        $decoded = json_decode($json, true);
        //verifica se o webhook é de card excluido
        if($decoded['Action'] !== 'Delete' && $decoded['Deals']) {
            throw new WebhookReadErrorException('Não havia um card deletado no webhook - '.$current . PHP_EOL, 1010);
        }
        //Exclui o Deal da base de dados 
        try{
            $delete = Deal::delete()->where('deal_id', $decoded['Old']['Id'])->execute();
            $total = $delete->rowCount();
            ($total > 0)?
            $message ['deal']['deleted'] = 'Proposta (total = '.$total.') excluída da base de dados do sistema de integração - '.$current . PHP_EOL:$message ['deal']['notdeleted'] = 'Proposta não encontrada na base de dados da integração ou já foi deletada. - '.$current . PHP_EOL;
            
        }
        catch(PDOException $e)
        {
            throw new DealNaoExcluidoBDException('Erro ao consultar a base de dados do sistema de integração. Erro: '. $e->getMessage() .' - '. $current . PHP_EOL, 1012);
        }

        return $message;

    }

    //CRIA PEDIDO NO OMIE
    public static function criaPedidoOmie($appKey, $appSecret, $idClienteOmie, $finishDate, $lastOrderId, $productsOrder, $ncc, $codVendedorOmie, $notes, $arrayRequestOrder, $parcelamento)
    {   
        //$det = [];//informações dos produtos da venda(array de arrays)
        //$ide=[];//array de informações do produto vai dentro do array det com por exemplo codigo_item_integracao(codigo do item no ploomes)
        //$produto = [];//array de informações do produto específico, codigo quantidade valor unitário. infos do item no omie. dentro de det
        //$parcela = []; //info de cada parcela individualmente data_vencimento, numero_parcela, percentual, valor (array de arrays) vai dentro de lista_parcelas
        
        // cabeçalho da requisição ($appKey,$appSecret, call(metodo))
        $top = [
            'app_key' =>   $appKey,
            'app_secret' => $appSecret,
            'call' => 'IncluirPedido',
            'param'=>[],
        ];
        
        // cabecalho
        $cabecalho = [];//cabeçalho do pedido (array)
        $cabecalho['codigo_cliente'] = $idClienteOmie;//int
        $cabecalho['codigo_pedido_integracao'] = $lastOrderId;//string
        $cabecalho['data_previsao'] = DiverseFunctions::convertDate($finishDate);//string
        $cabecalho['etapa'] = '10';//string
        $cabecalho['numero_pedido'] = $lastOrderId;//string
        $cabecalho['codigo_parcela'] = '999';//string
        $cabecalho['origem_pedido'] = 'API';//string
        //$cabecalho['quantidade_itens'] = 1;//int
        // $cabecalho['codigo_cenario_impostos'] = 12315456498798;//int
        
        //ide primeiro pois vai dentro de det
        // $ide['codigo_item_integracao'] = $productsOrder['id_item'];//codigo do item da integração específica;//string
        
        //produto antes pois vai dentro de det
        // $produto['codigo_produto'] = 3342938591;
        // $produto['quantidade'] = 1;//int
        // $produto['valor_unitario'] = 200;
        
        //det array com ide e produto
        // $det['ide'] = $ide;
        // $det['produto'] = $produto;
        
        //frete
        $frete = [];//array com infos do frete, por exemplo, modailidade;
        $frete['modalidade'] = '9';//string
        
        
        //informações adicionais
        $informacoes_adicionais = []; //informações adicionais por exemplo codigo_categoria = 1.01.03, codigo_conta_corrente = 123456789
        $informacoes_adicionais['codigo_categoria'] = '1.01.01';//string
        $informacoes_adicionais['codigo_conta_corrente'] = $ncc;//int
        // $informacoes_adicionais['consumidor_final'] = 'S';//string
        // $informacoes_adicionais['enviar_email'] = 'N';//string
        $informacoes_adicionais['numero_pedido_cliente']=$lastOrderId;
        $informacoes_adicionais['codVend']=$codVendedorOmie;
        
        //lista parcelas
        $lista_parcelas = [];//array de parcelas
        $lista_parcelas['parcela'] = DiverseFunctions::calculaParcelas( date('d-m-Y'),$parcelamento, $arrayRequestOrder[0]->Amount);
        
        //observbacoes
        $observacoes =[];
        $observacoes['obs_venda'] = $notes;
       
        //exemplo de parcelsa
        //$totalParcelas = "10/15/20";
        $newPedido = [];//array que engloba tudo
        $newPedido['cabecalho'] = $cabecalho;
        $newPedido['det'] = $productsOrder;
        $newPedido['frete'] = $frete;
        $newPedido['informacoes_adicionais'] = $informacoes_adicionais;
        $newPedido['lista_parcelas'] = $lista_parcelas;
        $newPedido['observacoes'] = $observacoes;
        $top['param'][]= $newPedido;

        $jsonPedido = json_encode($top, JSON_UNESCAPED_UNICODE);
        // echo'<pre>';
        // print_r($jsonPedido);
        // exit;

        //aqui está o json original
        // $jsonOmie = [
        //     'app_key' => $appKey,
        //     'app_secret' => $appSecret,
        //     'call' => 'IncluirPedido',
        //     'param' => [
        //         [

        //             'cabecalho' => [
        //                 'codigo_cliente' => $idClienteOmie, //Id do cliente do Omie retornado da função que busca no omie pelo cnpj
        //                 'data_previsao' => DiverseFunctions::convertDate($finishDate), //obrigatorio
        //                 'codigo_pedido_integracao' =>$lastOrderId, //codigo que busca pela integração específica
        //                 'numero_pedido' => $lastOrderId,
        //                 'origem_pedido' => 'API',
        //                 'etapa' => '10', //obrigatorio
        //                 //'qtde_parcela'=>2
        //                 //'codigo_parcela' =>'999' aceita parcelas customizadas precisa indicar junto ao fim estrutura 'lista_parcela' e a tag qtde_parcela
        //             ],
        //             'det'=>$productsOrder,
        //             // 'det' => [
        //             //     [
        //             //         'ide' => [
        //             //             'codigo_item_integracao' => $productsOrder['id_item'],//codigo do item da integração específica
        //             //         ],
        //             //         'produto' => $productsOrder,//integrado pelo codigo_produto_integracao deve ser igual ao id do ploomes porém é diferente pra cada base no omie
        //             //     ]
        //             // ],
        //             'frete' => [
        //                 'modalidade' => '9',
        //             ],
        //             'informacoes_adicionais' => [
        //                 'codigo_conta_corrente' => $ncc,
        //                 'codigo_categoria' => '1.01.01', //obrigatorio
        //                 'numero_pedido_cliente'=>$lastOrderId,
        //                 'codVend' => $codVendedorOmie,
        //             ],
        //             // 'lista_parcelas'=>[
        //             //     'parcela'=>[
        //             //         [
        //             //             'data_vencimento' => '26/04/2024',
        //             //             'numero_parcela' => 1,
        //             //             'percentual' => 50,
        //             //             'valor' => 100
        //             //         ],
        //             //         [
        //             //             'data_vencimento' => '09/09/2024',
        //             //             'numero_parcela' => 2,
        //             //             'percentual' => 50,
        //             //             'valor' => 100   
        //             //         ]
        //             //     ]
        //             //         ],
        //             'observacoes'=> [
        //                 'obs_venda' => $notes,
        //             ]
        //         ]
        //     ],
        // ];
          
       // $jsonOmie = json_encode($jsonOmie,JSON_UNESCAPED_UNICODE);

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
            CURLOPT_POSTFIELDS => $jsonPedido,
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
    //ENCONTRA A PROPOSTA NO PLOOMES
    public static function requestQuote($quoteId, $baseApi, $method, $apiKey)
    {
        /**
         * {{server}}/Orders?$filter=Id+eq+402118677&$expand=Products($select=Product,Quantity;$expand=Parts($expand=Product($select=Code),OtherProperties),Product($select=Code),)&$orderby=Id
         */
        $query = 'Quotes?$expand=Installments,OtherProperties,Products($select=Id,Discount),Approvals($select=Id),ExternalComments($select=Id),Comments($select=Id),Template,Deal($expand=Pipeline($expand=Icon,Gender,WinButton,WinVerb,LoseButton,LoseVerb),Stage,Contact($expand=Phones;$select=Name,TypeId,Phones),Person($expand=Phones;$select=Name,TypeId,Phones),OtherProperties),Pages&$filter=Id+eq+'.$quoteId.'&preload=true';
        $headers = [
            'User-Key:' . $apiKey,
            'Content-Type: application/json',
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseApi . $query,
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
        $quote = json_decode($response, true);

        return $quote;
        // $notes = $notes['value'][0]['Notes'];
        
        // return (empty($notes)) ? false: $notes;

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
                'omie_order_id' => $deal->omieOrderId,
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