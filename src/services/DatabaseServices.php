<?php

namespace src\services;

use PDOException;
use src\contracts\DatabaseManagerInterface;
use src\models\Deal;
use src\models\Homologacao_invoicing;
use src\models\Homologacao_order;
use src\models\Manospr_invoicing;
use src\models\Manospr_order;
use src\models\Manossc_invoicing;
use src\models\Manossc_order;
use src\models\Webhook;

class DatabaseServices implements DatabaseManagerInterface{
    //SALVA NO BANCO DE DADOS AS INFORMAÇÕES DO WEBHOOK
    public function saveWebhook(object $webhook):string
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
    public function saveDeal(object $deal):string
    {

        try{
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
        }catch(PDOException $e){
            // if ($e->getCode() == 2006) {
            //     // Tentar reconectar
            //     $id = Deal::insert(
            //         [
            //             'billing_basis'=>$deal->baseFaturamento,
            //             'billing_basis_title'=>$deal->baseFaturamentoTitle,
            //             'deal_id'=>$deal->id,
            //             'omie_order_id' => $deal->omieOrderId,
            //             'contact_id'=>$deal->contactId,
            //             'person_id'=>$deal->personId,
            //             'pipeline_id'=>$deal->pipelineId,
            //             'stage_id'=>$deal->stageId,
            //             'status_id'=>$deal->statusId,
            //             'won_quote_id'=>$deal->wonQuoteId,
            //             'create_date'=>$deal->createDate,
            //             'last_order_id'=>$deal->lastOrderId,
            //             'creator_id'=>$deal->creatorId,
            //             'webhook_id'=>$deal->webhookId,
            //             'created_at'=>date('Y-m-d H:i:s'),
            //          ]
            //     )->execute();
                
            // } else {
            //     throw $e;
            // }
            print_r($e->getMessage());
        }

        if(empty($id)){
            return "Erro ao cadastrar Venda no banco de dados.";
        }
        return $id;
    }
    //BUSCA UM DEAL PELO LASTORDER ID
    public function getDealByLastOrderId(int $idPedidoIntegracao)
    {
       $deal = Deal::select()->where('last_order_id', $idPedidoIntegracao)->one();
      
       return $deal;
    }
    //deleta um deal da base de dados
    public function deleteDeal(int $id): int
    {
        $delete = Deal::delete()->where('deal_id', $id)->execute();
        $total = $delete->rowCount();

        return $total;
    }

    //SALVA UMA ORDER NO BANCO DE DADOS
    public function saveOrder(object $order):int
    {   
        switch($order->target){
            case 'MHL':
                $database = new Homologacao_order();
                break;
            case 'MPR':
                $database = new Manospr_order();
                break;
            case 'MSC':
                $database = new Manossc_order();
                break;
        }
        
        $id = $database::insert(
            [
                'id_omie'=>$order->idOmie,
                'cod_cliente'=>$order->codCliente,
                'cod_pedido_integracao'=>$order->codPedidoIntegracao ?? null,
                'num_pedido_omie'=>$order->numPedidoOmie,
                'cod_cliente_integracao'=>$order->codClienteIntegracao ?? null,
                'data_previsao'=>$order->dataPrevisao,
                'num_conta_corrente'=>$order->numContaCorrente,
                'cod_vendedor_omie'=>$order->codVendedorOmie,
                'id_vendedor_ploomes'=>$order->idVendedorPloomes ?? null,   
                'app_key'=>$order->appKey ?? null,             
                'created_at'=>date('Y-m-d H:i:s'),
            ]
        )->execute();

        return ($id > 0 ) ? $id : false;
    }
    //VERIFICAR SE EXISTE A ORDEM NA BASE DE DADOS
    public function isIssetOrder(int $orderNumber, string $target){

        switch($target){
            case 'MHL':
                $database = new Homologacao_order();
                break;
            case 'MPR':
                $database = new Manospr_order();
                break;
            case 'MSC':
                $database = new Manossc_order();
                break;
        }

        try{

        $id = $database::select('id')
                ->where('id_omie',$orderNumber)
                ->one(); 

        return $id;

        }catch(PDOException $e){
            return $e->getMessage();
        }

    }
    //EXCLUI A ORDEM DA BASE DE DADOS
    public function excluiOrder(int $orderNumber, string $target):bool
    {

        switch($target){
            case 'MHL':
                $database = new Homologacao_order();
                break;
            case 'MPR':
                $database = new Manospr_order();
                break;
            case 'MSC':
                $database = new Manossc_order();
                break;
        }

        try{
            $database::delete()
            ->where('id_omie', $orderNumber)
            ->execute();
    
        }catch(PDOException $e){
                return $e->getMessage();
        }
            
            return true;
    }
    //ALTERA A ORDEM PARA CANCELADA
    public static function alterOrder(int $orderNumber, string $target):bool 
    {
        switch($target){
            case 'MHL':
                $database = new Homologacao_order();
                break;
            case 'MPR':
                $database = new Manospr_order();
                break;
            case 'MSC':
                $database = new Manossc_order();
                break;
        }

        try{

            $database::update()
                ->set('is_canceled', 1)
                ->set('updated_at', date('Y-m-d H:i:s'))
                ->where('id_omie',$orderNumber)
                ->execute();           

        }catch(PDOException $e){
            return $e->getMessage();
        }
        
        return true;
    }

    //SALVA A NOTA FISCAL NO BANCO DE DADOS
    public function saveInvoicing(object $invoicing){

        switch($invoicing->target){
            case 'MHL':
                $database = new Homologacao_invoicing();
                break;
            case 'MPR':
                $database = new Manospr_invoicing();
                break;
            case 'MSC':
                $database = new Manossc_invoicing();
                break;
        }

        try{

            $id = $database::insert(
                [
                    'stage'=>$invoicing->etapa,
                    'invoicing_date'=>$invoicing->dataFaturado,
                    'invoicing_time'=>$invoicing->horaFaturado,
                    'client_id'=>$invoicing->idCliente,
                    'order_id'=>$invoicing->idPedido,
                    'invoice_number'=>$invoicing->nNF,
                    'ord_number'=>$invoicing->numeroPedido,
                    'order_amount'=>$invoicing->valorPedido,
                    'user_id'=>$invoicing->authorId,
                    'user_email'=>$invoicing->authorEmail,
                    'user_name'=>$invoicing->authorName,
                    'appkey'=>$invoicing->appKey,
                    'created_at'=>date('Y-m-d H:i:s'),
                 ]
            )->execute();

            return $id;

        }catch(PDOException $e){
            return 'Erro ao cadastrar Faturamenro no banco de dados! - '. $e->getMessage();
        }
        
    }

    public function isIssetInvoice(object $omie, int $orderNumber):int|string|null
    {
        switch($omie->target){
            case 'MHL':
                $database = new Homologacao_invoicing();
                break;
            case 'MPR':
                $database = new Manospr_invoicing();
                break;
            case 'MSC':
                $database = new Manossc_invoicing();
                break;
        }
        try{

            $id = $database::select('id')
                    ->where('order_id',$orderNumber)
                    ->where('is_canceled',0)
                    ->one();       
            return (!$id)?$id: $id['id'];
                    
        }catch(PDOException $e){
            return $e->getMessage();
        }

    }

    public function alterInvoice(object $omie, int $orderNumber):string|bool
    {
        switch($omie->target){
            case 'MHL':
                $database = new Homologacao_invoicing();
                break;
            case 'MPR':
                $database = new Manospr_invoicing();
                break;
            case 'MSC':
                $database = new Manossc_invoicing();
                break;
        }

        try{

            $database::update()
                ->set('is_canceled', 1)
                ->set('updated_at', date('Y-m-d H:i:s'))
                ->where('order_id',$orderNumber)
                ->execute();

            return true;

        }catch(PDOException $e){
            return $e->getMessage();
        }
    }


}