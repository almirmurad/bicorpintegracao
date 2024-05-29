<?php

namespace src\handlers;

use src\models\Manossc_order;

class ManosScOrderHandler
{
    
    public static function saveManosScOrder($order)
    {   
        $id = Manossc_order::insert(
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
}