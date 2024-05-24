<?php

namespace src\handlers;

use PDOException;
use src\exceptions\OrderControllerException;
use src\exceptions\PedidoDuplicadoException;
use src\models\Deal;
use src\models\Invoicing;
use src\models\Omieorder;
use src\models\User;

class OmieOrderHandler
{
   

    public static function newOmieOrder($json){
        $current = date('d/m/Y H:i:s');
        
        //decodifica o json de pedidos vindos do webhook
        $decoded = json_decode($json,true);

        if($decoded['topic'] === "VendaProduto.Incluida" && $decoded['event']['etapa'] == "10" ){

            $order = new Omieorder();
            $order->idOmie = $decoded['event']['idPedido'];
            $order->codCliente = $decoded['event']['idCliente'];
            //$order->codPedidoIntegracao = $decoded['event']['idPedido']; (não vem no webhook)
            $order->dataPrevisao = $decoded['event']['dataPrevisao']; 
            $order->numPedidoOmie = $decoded['event']['numeroPedido'];
            //$order->codClienteIntegracao = $decoded['event']['idPedido']; (não vem no webhook)
            $order->numContaCorrente = $decoded['event']['idContaCorrente'];
            $order->codVendedorOmie = $decoded['author']['userId'];
            //$order->idVendedorPloomes = $decoded['event']['idPedido']; (não vem no webhook)           
            
            try{

                $id = Self::saveOmieOrder($order);
                return $order;

            }catch(PDOException $e){
                echo $e->getMessage();
                throw new PedidoDuplicadoException('<br> Pedido Nº: '.$order->numPedidoOmie.' já cadastrado no omie '. $current, 1500);
            }
                        
            // echo"<pre>";
            // print_r($id);
            // exit;

            return ($id > 0) ? $order : false;

        }else{
            throw new OrderControllerException('<br> Havia um orçamento e não um pedido no webhook em '. $current, 1500);
        }
    }
    
    public static function saveOmieOrder($order)
    {   
        $id = Omieorder::insert(
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
                'created_at'=>date('Y-m-d H:i:s'),
             ]
        )->execute();

        return ($id > 0 )? $id : false;
    }
}