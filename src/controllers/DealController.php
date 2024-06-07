<?php
namespace src\controllers;

use \core\Controller;

use src\handlers\DealHandler;
use src\handlers\LoginHandler;
use src\exceptions\WebhookReadErrorException;
use src\exceptions\BaseFaturamentoInexistenteException;
use src\exceptions\ClienteInexistenteException;
use src\exceptions\CnpjClienteInexistenteException;
use src\exceptions\DealNaoExcluidoBDException;
use src\exceptions\PedidoInexistenteException;
use src\exceptions\PedidoRejeitadoException;
use src\exceptions\ProdutoInexistenteException;
use src\exceptions\VendedorInexistenteException;
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

        ob_start();
        var_dump($json);
        $input = ob_get_contents();
        ob_end_clean();
        file_put_contents('./assets/all.log', $input . PHP_EOL, FILE_APPEND);

        $apiKey = $this->apiKey;
        $baseApi = $this->baseApi;
        $method = 'GET';
        // $appKey = $this->appKey;
        // $secrets = $this->secrets;
        // $ncc = $this->ncc;
        try{
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
            }
        }catch(WebhookReadErrorException $e){
            echo '<pre>';
            print $e->getMessage();           
        }
        catch(BaseFaturamentoInexistenteException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(CnpjClienteInexistenteException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(PedidoInexistenteException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(ProdutoInexistenteException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(ClienteInexistenteException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(VendedorInexistenteException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(PedidoRejeitadoException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        finally{
            ob_start();
            var_dump($e->getMessage());
            $input = ob_get_contents();
            ob_end_clean();
            file_put_contents('./assets/log.log', $input . PHP_EOL . date('d/m/Y H:i:s'), FILE_APPEND);
            exit; 
        }
        
    }


    public function deletedDeal()
    {
        $json = file_get_contents('php://input');

        try{
            $response = DealHandler::deletedDealHook($json);
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
                file_put_contents('./assets/log.log', $input . PHP_EOL . date('d/m/Y H:i:s'), FILE_APPEND);
                exit;            
            }
        }catch(WebhookReadErrorException $e){
            echo '<pre>';
            print $e->getMessage();           
        }
        catch(BaseFaturamentoInexistenteException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(DealNaoExcluidoBDException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        finally{
            ob_start();
            var_dump($e->getMessage());
            $input = ob_get_contents();
            ob_end_clean();
            file_put_contents('./assets/log.log', $input . PHP_EOL, FILE_APPEND);
            exit; 
        }

        $ping = json_encode([
            'pong' => 'true',
            'message' => $response,
        ]);

        return print_r($ping);
        
    }

}