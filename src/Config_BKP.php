<?php
namespace src;

class Config {
    
    const BASE_DIR = '/bicorpIntegracao/public';

    const DB_DRIVER = 'mysql';
    const DB_HOST = 'localhost';
    const DB_DATABASE = 'integracao';
    CONST DB_USER = 'root';
    const DB_PASS = '';
    const BASE_API = 'https://public-api2.ploomes.com/';
    const API_KEY ='1BD75B9E40815E638BFAB940A15B8E4072BB95150D37EF528EC4DC343682B502F10EFFD44F7D20DEA11811D6CBF3C8596F0DFA819AA3231C1C954C2286EB3051';
    //fielKey : 1BD75B9E40815E638BFAB940A15B8E4072BB95150D37EF528EC4DC343682B502F10EFFD44F7D20DEA11811D6CBF3C8596F0DFA819AA3231C1C954C2286EB3051
    //bicorpKey : 386445B648D4A40ABF83702EFAE0E08DC6BD4CFA6D5FC55080527C11CF0BAA7F14C3EBB123A8BA44B439CD3334A3A22469B86F3BF3C0E59C99BB9C711D685082
    const SECRETS_KEYS = array('ManosPR'=>'ebc9a6d49972b6463eefca22c32d1332','ManosSC'=>'0cb50ae1518fd242c61fdf3f2ef1ad4a','ManosHomologacao'=>'632c1d9960c35fb33f47db214d904754');
    const NUM_CC = array('ManosPR'=>3236906075, 'ManosSC'=>2781311195, 'ManosHomologacao'=>4162600091);
    const APP_KEY = array('ManosPR'=>'2335095664902','ManosSC'=>'2597402735928','ManosHomologacao'=>'2337978328686');
    //https://app10.ploomes.com/hometutorial

    const ERROR_CONTROLLER = 'ErrorController';
    const DEFAULT_ACTION = 'index';
}