<?php
namespace src\controllers;

use \core\Controller;
use src\handlers\InteractionHandler;
use src\handlers\LoginHandler;

class InteractionController extends Controller{
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
        $data = [
            'pagina' => 'Interactions',
            'loggedUser'=>$this->loggedUser,

        ];
        $this->render('gerenciador.pages.index', $data);
    }

    public function createInteraction(){
        $json = file_get_contents('php://input');

        $apiKey = $this->apiKey;
        $baseApi = $this->baseApi;

        $response = InteractionHandler::createPloomesIteraction($json, $baseApi, $apiKey);

        if ($response) {
            echo"<pre>";
            //json_encode($response);
            print_r($response);
            //grava log
            //$decoded = json_decode($response, true);
            ob_start();
            var_dump($response);
            $input = ob_get_contents();
            ob_end_clean();
            file_put_contents('./assets/log.log', $input . PHP_EOL, FILE_APPEND);
            exit;            
        } else {
            $error = "Erro ao ler dados do webhook";
            echo $error;
            exit;
        }


    }



}