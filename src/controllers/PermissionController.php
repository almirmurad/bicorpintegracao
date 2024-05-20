<?php
namespace src\controllers;

use \core\Controller;
use src\handlers\LoginHandler;
use src\handlers\PermissionHandler;
use src\models\Deal;
use src\models\Permissions_group;

class PermissionController extends Controller {
    
    private $loggedUser;

    public function __construct(){
        $this->loggedUser = LoginHandler::checkLogin();
        if($this->loggedUser === false){
            $this->redirect('/login');
        }
        if( !in_array('permissions_view', $this->loggedUser->permission )){
            $this->redirect('/',['flash'=>$_SESSION['flash'] = "Usuário sem permissão para acessar esta area!"]);
        }
    }

    public function index() {

        $p = PermissionHandler::getAllGroups();
        $data['list'] = $p;
        $flash = '';
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }
    
        $data = [
            'pagina' => 'Permissões',
            'loggedUser'=>$this->loggedUser,
            'flash'=>$flash,
            'list'=> array()
        ];
        $this->render('gerenciador.pages.permissions', $data);
    }

}