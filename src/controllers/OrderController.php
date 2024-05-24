<?php
namespace src\controllers;

use core\Controller;
use src\exceptions\OrderControllerException;
use src\exceptions\PedidoDuplicadoException;
use src\handlers\LoginHandler;
use src\handlers\OmieOrderHandler;

class OrderController extends Controller {
    
    private $loggedUser;
    private $apiKey;
    private $baseApi;

    public function __construct()
    {
        if($_SERVER['REQUEST_METHOD'] !== "POST"){
            $this->loggedUser = LoginHandler::checkLogin();
            if ($this->loggedUser === false) {
                $this->redirect('/login');
            }
        }
        $this->apiKey = $_ENV['API_KEY'];
        $this->baseApi = $_ENV['BASE_API'];
   
    }

    public function index() {
        //$total = Deal::select('id')->count();        
        $data = [
            'pagina' => 'Pedidos',
            'loggedUser'=>$this->loggedUser,
            //'total'=>$total
        ];
        $this->render('gerenciador.pages.index', $data);
    }

    public function newOmieOrder(){

        $json = file_get_contents('php://input');

        ob_start();
        var_dump($json);
        $input = ob_get_contents();
        ob_end_clean();
        file_put_contents('./assets/all.log', $input . PHP_EOL, FILE_APPEND);
        exit;

        try{

            $response = json_encode(OmieOrderHandler::newOmieOrder($json));
            if ($response) {
                echo"<pre>";
                json_encode($response);
                //grava log
                //$decoded = json_decode($response, true);
                ob_start();
                var_dump($response);
                $input = ob_get_contents();
                ob_end_clean();
                file_put_contents('./assets/log.log', $input . PHP_EOL, FILE_APPEND);  

            }

        }catch(PedidoDuplicadoException $e){
            echo $e->getMessage();
        }catch(OrderControllerException $e){
            echo $e->getMessage();
        }finally{
            if (isset($e)){
                ob_start();
                echo $e->getMessage();
                $input = ob_get_contents();
                ob_end_clean();
                file_put_contents('./assets/log.log', $input . PHP_EOL, FILE_APPEND);
                exit; 
            }
            exit;
            //return print_r($response);
        }
            
    }

    public function deletedOrder(){
        $json = file_get_contents('php://input');
            //$decoded = json_decode($json, true);

            ob_start();
            var_dump($json);
            $input = ob_get_contents();
            ob_end_clean();

            file_put_contents('./assets/whkDelOrder.log', $input . PHP_EOL, FILE_APPEND);
            $pong = array("pong"=>true);
            $json = json_encode($pong);
            return print_r($json);
    }


}