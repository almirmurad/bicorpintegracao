<?php
namespace src\contracts;

interface OmieManagerInterface{
    //BUSCA O ID DO CLIENTE OMIE
    public function clienteIdOmie(object $omie, string $contactCnpj);
    //BUSCA O VENDEDOR OMIE 
    public function vendedorIdOmie(object $omie, string $mailVendedor);
    //BUSCA ID DO PRODUTO NO OMIE
    public function buscaIdProductOmie(object $omie, string $idItem);
    //CRIA O PEDIDO NO OMIE 
    public function criaPedidoOmie(object $omie, string $idClienteOmie, object $deal, array $productsOrder, string $codVendedorOmie, string $notes, array $arrayRequestOrder, string $parcelamento);
}