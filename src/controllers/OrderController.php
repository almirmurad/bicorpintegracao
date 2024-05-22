<?php
namespace src\controllers;

use core\Controller;
use src\handlers\LoginHandler;

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

    public function newOrder(){

        $json = file_get_contents('php://input');
            //$decoded = json_decode($json, true);

            ob_start();
            var_dump($json);
            $input = ob_get_contents();
            ob_end_clean();

            file_put_contents('./assets/whkNewOrder.log', $input . PHP_EOL, FILE_APPEND);
            $pong = array("pong"=>true);
            $json = json_encode($pong);
            return print_r($json);

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