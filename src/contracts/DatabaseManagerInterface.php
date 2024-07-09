<?php

namespace src\contracts;

interface DatabaseManagerInterface{
    //salva um deal na base de dados
    public function saveDeal(object $deal):string;
    //salva um webhook na base de dados
    public function saveWebhook(object $webhook):string;
    //deleta um Deal da base de dados
    public function deleteDeal(int $id):int;
}