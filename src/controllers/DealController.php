<?php
namespace src\controllers;

use \core\Controller;
use src\handlers\DealHandler;
use src\handlers\LoginHandler;
use src\models\Deal;

class DealController extends Controller {
    
    private $loggedUser;
    private $apiKey;
    private $baseApi;
    // private $appKey;
    // private $secrets;
    // private $ncc;

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
        // $this->appKey = $_ENV['APP_KEY'];
        // $this->secrets = $_ENV['SECRETS_KEYS'];
        // $this->ncc = $_ENV['NUM_CC'];
    }

    public function index() {
        $total = Deal::select('id')->count();        
        $data = [
            'pagina' => 'Deals',
            'loggedUser'=>$this->loggedUser,
            'total'=>$total
        ];
        $this->render('gerenciador.pages.index', $data);
    }

    public function winDeal()
    {
        $json = file_get_contents('php://input');

        $apiKey = $this->apiKey;
        $baseApi = $this->baseApi;
        $method = 'GET';
        // $appKey = $this->appKey;
        // $secrets = $this->secrets;
        // $ncc = $this->ncc;

        $response = DealHandler::readDealHook($json, $baseApi, $method, $apiKey);

        if ($response) {
            echo"<pre>";
            json_encode($response);
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