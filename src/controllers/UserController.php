<?php

namespace src\controllers;

use \core\Controller;
use src\handlers\IntegraHandler;
use src\handlers\LoginHandler;
use src\handlers\UserHandler;
use src\models\User;

class UserController extends Controller
{

    private $loggedUser;

    public function __construct(){
        if($_SERVER['REQUEST_METHOD'] !== "POST"){
            $this->loggedUser = LoginHandler::checkLogin();
            if ($this->loggedUser === false) {
                $this->redirect('/login');
            }
        }
    }

    public function listUsers(){
       
        $flash = '';
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }

        $users = UserHandler::listAllUsers();

        $this->render('gerenciador.pages.users', [
            'pagina' => 'Lista de usuários',
            'users'=>$users,
            'loggedUser' => $this->loggedUser,
            'flash' => $flash
        ]);
    }

    public function addUser(){

        $flash = '';
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }
        $this->render('gerenciador.pages.addUser', [
            'pagina' => 'Cadastrar novo usuario',
            'loggedUser' => $this->loggedUser,
            'flash' => $flash
        ]); 
    }

    public function addUserAction(){
        

        $name   = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
        $mail   = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $pass   = filter_input(INPUT_POST, 'pass',FILTER_SANITIZE_SPECIAL_CHARS);
        $rpass   = filter_input(INPUT_POST, 'rpass',FILTER_SANITIZE_SPECIAL_CHARS);
        //$avatar = filter_input(INPUT_POST, 'avatar', FILTER_SANITIZE_SPECIAL_CHARS);
        $active = filter_input(INPUT_POST, 'active', FILTER_VALIDATE_INT);
        $type   = filter_input(INPUT_POST, 'type', FILTER_VALIDATE_INT);
        ($type) == 1 ? $id_permission = 1 : $id_permission = 2;
        // echo $id_permission;
        // exit;


        if($name && $mail && $pass && $rpass && $active && $type && $id_permission){
            if((!empty($pass) || !empty($rpass)) && $pass === $rpass){

                if(LoginHandler::emailExists($mail) === false){

                    //UPLOAD DA FOTO DE CAPA
                    $fotosNames = [];
                    if(!empty($_FILES['avatar']['tmp_name'])){
                        
                        foreach($_FILES as $img){
                        
                            if(isset ($img['type'])){
                                    if(in_array($img['type'],['image/jpeg', 'image/jpg', 'image/png'])){
                                        $fotosNames[] = $img;
                                    }
                            } 
                        }
                        
                        //verifica se existe a pasta imagens específica para pacotes 
                        $newNameAvatar = md5(time().rand(0,999).rand(0,999)).'.jpg';

                        $idCreate = UserHandler::addUser($name, $mail, $pass, $newNameAvatar, $active, $id_permission, $type);
                        //$token = LoginHandler::addUser($name, $mail, $pass, $newNameAvatar, $active, $id_permission, $type);
                        //$_SESSION['token'] = $token;
                        
                        $caminhoBase ="assets/uploads";
                        if(!is_dir($caminhoBase)){
                            //se não não existir cria
                            mkdir($caminhoBase, 0777);
                        }
                        $fimages = $caminhoBase.'/images';
                        if(!is_dir($fimages)){
                            //se não não existir cria
                            mkdir($fimages, 0777);
                        }
                        $fusers = $fimages.'/users';
                        if(!is_dir($fusers)){
                            //se não não existir cria
                            mkdir($fusers, 0777);
                        }
                        $fiduser = $fusers."/$idCreate";
                        if(!is_dir($fiduser)){
                            //se não não existir cria
                            mkdir($fiduser, 0777);
                        }
                        $caminho = $fiduser.'/avatars';
                        if(!is_dir($caminho)){
                            //se não não existir cria
                            mkdir($caminho, 0777);
                        }

                        move_uploaded_file($fotosNames[0]['tmp_name'], $caminho.'/'.$newNameAvatar);
                        
                        $this->redirect('/users');
                    }

                }else{
                    $_SESSION['flash'] = "Email já cadastrado";
                    $this->redirect('/addUser');
                }

            }else{
                $_SESSION['flash'] = "Senha e repita a senha não conferem";
                $this->redirect('/addUser');
            }
        }else{
            $_SESSION['flash'] = "Preencha todos os campos";
            $this->redirect('/addUser');
        }
    }

    public function delUser($id) {
        $del = UserHandler::delUser($id);
        if (!$del) {
            $_SESSION['flash'] = "Erro ao excluir Usuário!";
            $this->redirect('/users');
        }
            $_SESSION['flash'] = "Usuário excluido com sucesso!";
            $this->redirect('/users');
    }



}
