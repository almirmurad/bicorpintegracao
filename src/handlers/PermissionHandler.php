<?php
namespace src\handlers;

use PDO;
use src\models\Permissions_group;
use src\models\User;
use src\models\Permissions_item;
use src\models\Permissions_link;

class PermissionHandler {

    public static function getPermissions($idPermission){
        $permissionsList = [];

        $data = Permissions_link::select('id_permission_item')->where('id_permission_group', $idPermission)->get();

        foreach($data as $dataItem){
           
            $permissionsItemId[] = $dataItem['id_permission_item'];
            
        }

        $data = Permissions_item::select('slug')->whereIn('id', $permissionsItemId)->get();

        foreach($data as $dataSlug){
            $permissionsList[] = $dataSlug['slug'];
        }

        return $permissionsList;
    }

    public static function getAllGroups(){
        $array = [];

        $permissions = Permissions_group::select()->get();

        foreach($permissions as $p ){
            $array[]=$p['name'];
        }
        // echo'<pre>';
        // $arrayIds =[];
        
        // $id = Permissions_group::select('id')->execute();
        // foreach($id as $itemId ){
        //     $arrayIds[]=$itemId['id'];
        // }
        // $join = User::select()->join('permissions_groups','permissions_groups.id', '=','id_permission')->count();
        // print_r($join);
        // exit;
        // $uq = User::select()->where('id_permission',$arrayIds)->count();
        // print_r($uq);
        // exit;
        
        // $array = Permissions_group::select(['total'=>function($t){
        //     $t->(User::select()->where('id_permission','Permissions_group.id'))
        // }]
                                    
        //                             )
        //     ->gecountt();

            // print_r($array);
            // exit;

        return $array;
    }
    
}
