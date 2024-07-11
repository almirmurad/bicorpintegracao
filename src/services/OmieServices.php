<?php

namespace src\services;

use src\contracts\OmieManagerInterface;
use src\functions\DiverseFunctions;

class OmieServices implements OmieManagerInterface{


    public function clienteCnpjOmie($order)
    {
        $jsonOmieIdCliente = [
            'app_key' => $order->appKey,
            'app_secret' => $order->appSecret,
            'call' => 'ConsultarCliente',
            'param' => [
                [
                    'codigo_cliente_omie'=>$order->codCliente
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
 
    //PEGA O ID DO CLIENTE DO OMIE
    public function clienteIdOmie($omie, $contactCnpj)
    {
        $jsonOmieIdCliente = [
            'app_key' => $omie->appKey,
            'app_secret' => $omie->appSecret,
            'call' => 'ListarClientes',
            'param' => [
                [
                    'clientesFiltro'=>['cnpj_cpf'=> $contactCnpj]
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
    public function vendedorIdOmie($omie, $mailVendedor)
    {

        $jsonOmieVendedor = [
            'app_key' => $omie->appKey,
            'app_secret' => $omie->appSecret,
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

    //BUSCA O ID DE UM PRODUTO BASEADO NO CODIGO DO PRODUTO NO PLOOMES
    public function buscaIdProductOmie($omie, $idItem)
    {
        $jsonId = [
            'app_key' => $omie->appKey,
            'app_secret' => $omie->appSecret,
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

    //CRIA PEDIDO NO OMIE
    public function criaPedidoOmie(object $omie, string $idClienteOmie, object $deal, array $productsOrder, string $codVendedorOmie, string $notes, array $arrayRequestOrder, string $parcelamento)
    {   
        //$det = [];//informações dos produtos da venda(array de arrays)
        //$ide=[];//array de informações do produto vai dentro do array det com por exemplo codigo_item_integracao(codigo do item no ploomes)
        //$produto = [];//array de informações do produto específico, codigo quantidade valor unitário. infos do item no omie. dentro de det
        //$parcela = []; //info de cada parcela individualmente data_vencimento, numero_parcela, percentual, valor (array de arrays) vai dentro de lista_parcelas
        
        // cabeçalho da requisição ($appKey,$appSecret, call(metodo))
        $top = [
            'app_key' =>   $omie->appKey,
            'app_secret' => $omie->appSecret,
            'call' => 'IncluirPedido',
            'param'=>[],
        ];
        
        // cabecalho
        $cabecalho = [];//cabeçalho do pedido (array)
        $cabecalho['codigo_cliente'] = $idClienteOmie;//int
        $cabecalho['codigo_pedido_integracao'] = $deal->lastOrderId;//string
        $cabecalho['data_previsao'] = DiverseFunctions::convertDate($deal->finishDate);//string
        $cabecalho['etapa'] = '10';//string
        $cabecalho['numero_pedido'] = $deal->lastOrderId;//string
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
        $informacoes_adicionais['codigo_conta_corrente'] = $omie->ncc;//int
        // $informacoes_adicionais['consumidor_final'] = 'S';//string
        // $informacoes_adicionais['enviar_email'] = 'N';//string
        $informacoes_adicionais['numero_pedido_cliente']=$deal->lastOrderId;
        $informacoes_adicionais['codVend']=$codVendedorOmie;
        
        //lista parcelas
        $lista_parcelas = [];//array de parcelas
        $lista_parcelas['parcela'] = DiverseFunctions::calculaParcelas( date('d-m-Y'),$parcelamento, $arrayRequestOrder['Amount']);
        
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

    // busca o pedido através do Id do OMIE
    public function consultaPedidoOmie(object $omie, int $idPedido){

        $array = [
                    'app_key'=>$omie->appKey,
                    'app_secret'=>$omie->appSecret,
                    'call'=>'ConsultarPedido',
                    'param'=>[
                            [
                                'codigo_pedido'=>$idPedido,
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

    //consulta nota fiscal no omie
    public function consultaNotaOmie(object $omie, int $idPedido){
        $array = [
            'app_key'=>$omie->appKey,
            'app_secret'=>$omie->appSecret,
            'call'=>'ConsultarNF',
            'param'=>[
                    [
                        'nIdPedido'=>$idPedido,
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
  
}