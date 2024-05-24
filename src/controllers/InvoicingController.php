<?php
namespace src\controllers;

use \core\Controller;
use src\exceptions\WebhookReadErrorException;
use src\exceptions\DealNaoEncontradoBDException;
use src\exceptions\EstagiodavendaNaoAlteradoException;
use src\exceptions\FaturamentoNaoCadastradoException;
use src\exceptions\InteracaoNaoAdicionadaException;
use src\exceptions\NotaFiscalNaoEncontradaException;
use src\exceptions\PedidoNaoEncontradoOmieException;
use src\handlers\LoginHandler;
use src\handlers\InvoicingHandler;
use src\models\Deal;



class InvoicingController extends Controller {
    
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

    public function invoiceIssue()
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
            $response = InvoicingHandler::readInvoiceHook($json, $baseApi, $method, $apiKey);
            
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
        catch(DealNaoEncontradoBDException $e){
            echo '<pre>';
            print $e->getMessage();           
        }
        catch(EstagiodavendaNaoAlteradoException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(FaturamentoNaoCadastradoException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(InteracaoNaoAdicionadaException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(NotaFiscalNaoEncontradaException $e){
            echo '<pre>';
            print $e->getMessage();
        }
        catch(PedidoNaoEncontradoOmieException $e){
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
        
    }

    public function deletedInvoice(){
        $json = file_get_contents('php://input');
            //$decoded = json_decode($json, true);

            ob_start();
            var_dump($json);
            $input = ob_get_contents();
            ob_end_clean();

            file_put_contents('./assets/wbhkDelInv.log', $input . PHP_EOL, FILE_APPEND);
            $pong = array("pong"=>true);
            $json = json_encode($pong);
            return print_r($json);
    }

    public function totalInvoices(){

        $total = InvoicingHandler::totalInvoices();

        echo $total;
    }

}