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
use src\models\Omie;
use src\services\DatabaseServices;
use src\services\OmieServices;
use src\services\PloomesServices;

class DealHandler
{
    private $ploomesServices;
    private $omieServices;
    private $databaseServices;

    public function __construct(PloomesServices $ploomesServices, OmieServices $omieServices, DatabaseServices $databaseServices)
    {
        $this->ploomesServices = $ploomesServices;
        $this->omieServices = $omieServices;
        $this->databaseServices = $databaseServices;
    }

    //LÊ O WEBHOOK E CRIA O PEDIDO
    public function readDealHook($json)
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
            //cria objeto deal
            $deal = new Deal();
            //salva o hook no banco
            $idWebhookBd = $this->databaseServices->saveWebhook($webhook);
            $deal->idWebhookBd = $idWebhookBd;
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
            //infos do Deal new
            //$deal->attachmentsItems = $decoded['New']['AttachmentsItems'];
            //$deal->collaboratingUsers = (isset($decoded['New']['CollaboratingUsers'][0]['UserId'])) ? $decoded['New']['CollaboratingUsers'][0]['UserId'] : 'Não definido'; // Usuários colaboradores
            //$deal->contacts = $decoded['New']['Contacts']; // Contatos relacionados
            //$deal->contactsProducts = $decoded['New']['ContactsProducts']; // Produtos de cliente
            // Base de Faturamento
            $deal->baseFaturamento = (isset($prop['deal_A965E8F5-EF81-4CF3-939D-1D7FE6F1556C']) && !empty($prop['deal_A965E8F5-EF81-4CF3-939D-1D7FE6F1556C'])) ? $prop['deal_A965E8F5-EF81-4CF3-939D-1D7FE6F1556C'] : throw new BaseFaturamentoInexistenteException('Base de faturamento inexistente para o card id: '.$decoded['New']['Id'].' e pedido de venda Ploomes CRM: '.$decoded['New']['LastOrderId'].'em'.$current,1001);
            // Fim de $prop Outras Propriedades //
            //$deal->products = $decoded['New']['Products']; //Produtos relacionados
            //$products = $deal->products; //Produtos relacionados
            // $produtos = [];
            // foreach ($products as $prdt) {
            //     $produtos['codigo_produto'] = $prdt['ProductId'];
            // }
            // $produtos['codigo_produto'] = '3448900782';
            // $produtos['quantidade'] = '1';
            // $produtos['valor_unitario'] = 150;
            // $deal->products = $produtos; //Produtos relacionados
            //$deal->tags = (isset($decoded['New']['Tags'][0]['TagId'])) ? $decoded['New']['Tags'][0]['TagId'] : 'Não definido'; //Marcadores
            $deal->id = $decoded['New']['Id']; //Id do Deal
            $deal->title = $decoded['New']['Title']; // Título do Deal
            $deal->contactId = $decoded['New']['ContactId']; // Contatos relacionados
            // Busca o CNPJ do contato 
            (!empty($contactCnpj = $this->ploomesServices->contactCnpj($deal))) ? $contactCnpj : throw new CnpjClienteInexistenteException('Cliente não informado ou não cadastrado no Omie ERP. Id do card Ploomes CRM: '.$decoded['New']['Id'].' e pedido de venda Ploomes CRM: '.$decoded['New']['LastOrderId'].'em'.$current,1002); //cnpj do cliente
            $deal->contactName = $decoded['New']['ContactName']; // Nome do Contato no Deal
            $deal->personId = $decoded['New']['PersonId']; // Id do Contato
            // $deal->personName = $decoded['New']['PersonName']; // Nome do contato
            $deal->pipelineId = $decoded['New']['PipelineId']; // Funil
            $deal->stageId = $decoded['New']['StageId']; // Estágio
            $deal->statusId = $decoded['New']['StatusId']; // Situação
            // $deal->firstTaskId = $decoded['New']['FirstTaskId'];
            // $deal->firstTaskDate = $decoded['New']['FirstTaskDate'];
            // $deal->firstTaskNoTime = $decoded['New']['FirstTaskNoTime'];
            // $deal->hasScheduledTasks = $decoded['New']['HasScheduledTasks']; //Possui tarefas agendadas
            // $deal->tasksOrdination = $decoded['New']['TasksOrdination'];
            // $deal->contactProductId = $decoded['New']['ContactProductId']; // Produtos de cliente
            $deal->lastQuoteId = $decoded['New']['LastQuoteId']; // Proposta
            // $deal->isLastQuoteApproved = $decoded['New']['IsLastQuoteApproved']; // Proposta aprovada
            $deal->wonQuoteId = $decoded['New']['WonQuoteId']; // Proposta ganha
            $deal->wonQuote = $decoded['New']['WonQuote']; // Proposta ganha
            $deal->lastStageId = $decoded['New']['LastStageId'];
            // $deal->lossReasonId = $decoded['New']['LossReasonId']; // Motivo de perda
            // $deal->originId = $decoded['New']['OriginId']; // Origem
            $deal->ownerId = $decoded['New']['OwnerId']; // Responsável
            (!empty($mailVendedor = $this->ploomesServices->ownerMail($deal)))?$mailVendedor:
            throw new EmailVendedorNaoExistenteException('Não foi encontrado o email deste vendedor. Id do card Ploomes CRM: '.$decoded['New']['Id'].' e pedido de venda Ploomes CRM: '.$decoded['New']['LastOrderId'].'em'.$current, 1003);
            // $deal->startDate = $decoded['New']['StartDate']; // Início
            $deal->finishDate = $decoded['New']['FinishDate']; // Término
            // $deal->currencyId = $decoded['New']['CurrencyId']; // Moeda
            $deal->amount = $decoded['New']['Amount']; // Valor
            // $deal->startCurrencyId = $decoded['New']['StartCurrencyId'];
            // $deal->startAmount = $decoded['New']['StartAmount'];
            // $deal->read = $decoded['New']['Read'];
            // $deal->lastInteractionRecordId = $decoded['New']['LastInteractionRecordId']; // Último contato
            $deal->lastOrderId = $decoded['New']['LastOrderId']; // Última venda
            //$deal->lastOrderIdOld = $decoded['Old']['LastOrderId']; // Última venda
            // $deal->daysInStage = $decoded['New']['DaysInStage']; // Dias no estágio
            // $deal->hoursInStage = $decoded['New']['HoursInStage']; // Horas no estágio
            // $deal->length = $decoded['New']['Length']; // Duração
            // $deal->createImportId = $decoded['New']['CreateImportId'];
            // $deal->updateImportId = $decoded['New']['UpdateImportId'];
            // $deal->leadId = $decoded['New']['LeadId']; // Lead origem
            // $deal->originDealId = $decoded['New']['OriginDealId']; // Negócio origem
            // $deal->reevId = $decoded['New']['ReevId'];
            $deal->creatorId = $decoded['New']['CreatorId']; // Criador
            $deal->updaterId = $decoded['New']['UpdaterId']; // Último atualizador
            $deal->createDate = $decoded['New']['CreateDate']; // Data de criação
            // $deal->lastUpdateDate = $decoded['New']['LastUpdateDate']; // Data da última atualizaçãop
            // $deal->lastDocumentId = $decoded['New']['LastDocumentId']; // Último documento
            // $deal->dealNumber = $decoded['New']['DealNumber']; // Número
            // $deal->importationIdCreate = $decoded['New']['ImportationIdCreate']; // Id da importação de criação (novo)
            // $deal->importationIdUpdate = $decoded['New']['ImportationIdUpdate']; // Id da importação de atualização (novo)
            // $deal->publicFormIdCreate = $decoded['New']['PublicFormIdCreate']; // Id do formulário externo de criação
            // $deal->publicFormIdUpdate = $decoded['New']['PublicFormIdUpdate']; // Id do formulário externo de atualização
            $deal->webhookId = $webhook->webhookId; //inclui o id do webhook no deal
            
            /**************************************************** 
            *        Encontra a base de faturamento             *
            *                                                   *
            * NO webhook do Card pegamos os dados do checklist  * 
            * para encontrar a base de faturamento do Omie      *
            *                                                   *
            *****************************************************/
            $omie = new Omie();
            switch ($deal->baseFaturamento) {
                case 404096111:
                    $deal->baseFaturamentoTitle = 'Manos PR';
                    $omie->baseFaturamentoTitle = 'Manos PR'; 
                    $omie->ncc = $_ENV['NCC_MPR'];
                    $omie->appSecret = $_ENV['SECRETS_MPR'];
                    $omie->appKey = $_ENV['APPK_MPR'];
                    break;

                case 404096110:
                    $deal->baseFaturamentoTitle = 'Manos SC';
                    $omie->baseFaturamentoTitle = 'Manos SC';
                    $omie->ncc = $_ENV['NCC_MSC'];
                    $omie->appSecret = $_ENV['SECRETS_MSC'];
                    $omie->appKey = $_ENV['APPK_MSC'];
                    break;

                case 404096109:
                    $deal->baseFaturamentoTitle = 'Manos Homologação';
                    $omie->baseFaturamentoTitle = 'Manos Homologação';
                    $omie->ncc = $_ENV['NCC_MHL'];
                    $omie->appSecret = $_ENV['SECRETS_MHL'];
                    $omie->appKey = $_ENV['APPK_MHL'];
                    break;
            }
            /**************************************************** 
            *        busca dados da venda no ploomes            *
            *                                                   *
            * Na venda encontramos o array de itens da venda    * 
            * Montamos o det (array de items da venda no omie)  *
            *                                                   *
            *****************************************************/
            (!empty($arrayRequestOrder = $this->ploomesServices->requestOrder($deal))) ? $arrayRequestOrder : throw new PedidoInexistenteException('Venda Id: '.$deal->lastOrderId.' não encontrada no Ploomes. Card id: '.$deal->id.' em: '.$current,1004 );

            //array de produtos da venda
            $productsRequestOrder = $arrayRequestOrder['Products'];
            // print_r($arrayRequestOrder);
            // exit;
            //Array de detalhes do item da venda
            $det = [];
            $productsOrder = [];
            foreach ($productsRequestOrder as $prdItem) { 
                
                $det['ide'] = [];
                $det['ide']['codigo_item_integracao'] = $prdItem['Id'];
                $det['produto'] = [];
                $idPrd = $prdItem['Product']['OtherProperties'][0]['StringValue'];
                
                //encontra o id do produto no omie atraves do Code do ploomes
                //  (!empty($idProductOmie = $this->omieServices->buscaIdProductOmie($omie, $idPrd))) ? $idProductOmie : throw new ProdutoInexistenteException('Id do Produto inexistente no Omie ERP. Id do card Ploomes CRM: '.$deal->id.' e pedido de venda Ploomes CRM: '.$deal->lastOrderId.'em'.$current,1005);
                $det['produto']['codigo_produto'] = $idPrd;//mudei aqui de $idproductOmie para $idPrd
                $det['produto']['quantidade'] = $prdItem['Quantity'];
                $det['produto']['tipo_desconto'] = 'P';
                $det['produto']['valor_desconto'] = number_format($prdItem['Discount'], 2, ',', '.');
                $det['produto']['valor_unitario'] = $prdItem['UnitPrice'];

                $productsOrder[] = $det;
            }
            /****************************************************************
            *                          Request Quote                        *
            *                                                               *
            * Busca os dados da proposta (quote) para pegar a observação    *
            * e o parcelamento escolhido em campos personalizados do ploomes*
            *                                                               *
            *****************************************************************/
            $quote = $this->ploomesServices->requestQuote($deal);
            //busca Observação da Proposta (Quote)
            ($notes = strip_tags($quote['Notes']))? $notes : $notes='Venda à Vista!';
            //busca Parcelamento na Proposta (Quote)
            $texto = $quote['OtherProperties'][0]['ObjectValueName'];
            //verifica se exixtema parcelas ou se é a vista
            if($texto !== "a vista" ){
                $parcelamento = $texto;
            }else{
                $parcelamento = 0;
            }
            //installments é o parcelamento padrão do ploomes
            //print_r(count($quote['value'][0]['Installments']));

            //pega o id do cliente do Omie através do CNPJ do contact do ploomes           
            (!empty($idClienteOmie = $this->omieServices->clienteIdOmie($omie, $contactCnpj))) ? $idClienteOmie : throw new ClienteInexistenteException('Id do cliente não encontrado no Omie ERP! Id do card Ploomes CRM: '.$deal->id.' e pedido de venda Ploomes CRM: '.$deal->lastOrderId.' em: '.$current,1006);
            //pega o id do cliente do Omie através do CNPJ do contact do ploomes           
            (!empty($codVendedorOmie = $this->omieServices->vendedorIdOmie($omie, $mailVendedor))) ? $codVendedorOmie : throw new VendedorInexistenteException('Id do vendedor não encontrado no Omie ERP!Id do card Ploomes CRM: '.$deal->id.' e pedido de venda Ploomes CRM: '.$deal->lastOrderId.' em: '.$current,1007);
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
            $incluiPedidoOmie = $this->omieServices->criaPedidoOmie($omie, $idClienteOmie, $deal, $productsOrder, $codVendedorOmie, $notes, $arrayRequestOrder, $parcelamento);
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
                ($this->ploomesServices->createPloomesIteraction(json_encode($msg)))?$message['interactionMessage'] = 'Integração concluída com sucesso!<br> Pedido Ploomes: '.$deal->lastOrderId.' card nº: '.$deal->id.' e client id: '.$deal->contactId.' gravados no Omie ERP com o numero: '.intval($incluiPedidoOmie->numero_pedido).' e mensagem enviada com sucesso em: '.$current : throw new InteracaoNaoAdicionadaException('Não foi possível gravar a mensagem na venda',1010);
            }else{
                $message['returnPedidoOmie'] ='Não foi possível gravar o pedido no Omie!';
            }           
            
            return $message;
        } else {
            throw new WebhookReadErrorException('Não era um Card Ganho ou não havia venda na proposta do card Nº '.$decoded['New']['Id'].' data '.$current ,1009);
        }                
    }

    //LÊ O WEBHOOK DE CARD EXCLUIDO
    public function deletedDealHook($json)
    {

        $current = date('d/m/Y H:i:s');
        $message = [];

        $decoded = json_decode($json, true);
        //verifica se o webhook é de card excluido
        if($decoded['Entity'] !== 'Deals' && $decoded['Action'] !== 'Delete'  ) {
            throw new WebhookReadErrorException('Não havia um card deletado no webhook - '.$current . PHP_EOL, 1010);
        }
        //Exclui o Deal da base de dados 
        try{
            
            $total = $this->databaseServices->deleteDeal($decoded['Old']['Id']);
            
            ($total > 0)?
            $message ['deal']['deleted'] = 'Proposta (total = '.$total.') excluída da base de dados do sistema de integração - '.$current . PHP_EOL:$message ['deal']['notdeleted'] = 'Proposta não encontrada na base de dados da integração ou já foi deletada. - '.$current . PHP_EOL;
            
        }
        catch(PDOException $e)
        {
            throw new DealNaoExcluidoBDException('Erro ao consultar a base de dados do sistema de integração. Erro: '. $e->getMessage() .' - '. $current . PHP_EOL, 1012);
        }

        return $message;

    }

    
}