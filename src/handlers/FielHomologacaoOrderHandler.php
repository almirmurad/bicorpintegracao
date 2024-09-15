<?php

namespace src\handlers;

use PDOException;
use src\models\FielHomologacao_order;

class FielHomologacaoOrderHandler
{
    
    public static function saveFielHomologacaoOrder($order)
    {   
        $id = FielHomologacao_order::insert(
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

        return ($id > 0 )? $id : false;
    }
    
    public static function isIssetOrder($orderNumber){

        try{

        $id = FielHomologacao_order::select('id')
                ->where('id_omie',$orderNumber)
                ->execute();       
        
        return $id;
                    

        }catch(PDOException $e){
            return $e->getMessage();
        }

    }

    public static function alterFielHomologacaoOrder($orderNumber){


        try{

            FielHomologacao_order::update()
                ->set('is_canceled', 1)
                ->set('updated_at', date('Y-m-d H:i:s'))
                ->where('id_omie',$orderNumber)
                ->execute();           

        }catch(PDOException $e){
            return $e->getMessage();
        }
        
        return true;
    }

    public static function excluiFielHomologacaoOrder($orderNumber){
       try{
        FielHomologacao_order::delete()
        ->where('id_omie', $orderNumber)
        ->execute();

       }catch(PDOException $e){
            return $e->getMessage();
       }
        
        return true;
    }
}