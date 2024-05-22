<?php

namespace src\handlers;

use src\models\Deal;
use src\models\Invoicing;
use src\models\User;

class DashboardHandler
{
    
    public static function getAllTotals()
    {   
        $t =[];

        $t['totalDeals']    = Deal::select()->count();
        $t['totalInvoices'] = Invoicing::select()->count();
        $t['totalUsers']    = User::select()->count();

        $totals = json_encode($t);

        return $totals;
    }
}