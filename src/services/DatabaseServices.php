<?php

namespace src\services;

use PDOException;
use src\contracts\DatabaseManagerInterface;
use src\models\Deal;
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
        //deleta um deal da base de dados
        public function deleteDeal(int $id): int
        {
            $delete = Deal::delete()->where('deal_id', $id)->execute();
            $total = $delete->rowCount();

            return $total;
        }

}