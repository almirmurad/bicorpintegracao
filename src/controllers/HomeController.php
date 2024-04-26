<?php
namespace src\controllers;

use \core\Controller;
use src\handlers\LoginHandler;
use src\models\Deal;

class HomeController extends Controller {
    
    private $loggedUser;

    public function __construct(){
        $this->loggedUser = LoginHandler::checkLogin();
        if($this->loggedUser === false){
            $this->redirect('/login');
        }   
    }

    public function index() {
        $total = Deal::select('id')->count();        
        $data = [
            'pagina' => 'Dashboard',
            'loggedUser'=>$this->loggedUser,
            'total'=>$total
        ];
        $this->render('gerenciador.pages.index', $data);
    }

}