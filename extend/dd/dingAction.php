<?php
namespace dd;

use dd\util\Log;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/19
 * Time: 10:20
 */
class dingAction
{
    use \traits\controller\Jump;
    //根据code获得用户身份
    public static function getUserInfo($code)
    {
        $userInfo = User::getUserInfo(Auth::getAccessToken(), $code);
        Log::i("[USERINFO]".json_encode($userInfo));
        return $userInfo;
    }

    //
    public static function getUser($userid)
    {
        $userInfo = User::get_user_info(Auth::getAccessToken(),$userid);
        Log::i("[USERINFO]".json_encode($userInfo));
        return $userInfo;
    }

    //用户部门
    public static function getDept($id)
    {

        $deptInfo = Department::getDept(Auth::getAccessToken(), $id);
        Log::i("[DEPTINFO]".json_encode($deptInfo));
        return $deptInfo;
    }

    public static function sendToConversation($sender,$cid,$content)
    {
        $option = array(
            "sender"=>$sender,
            "cid"=>$cid,
            "msgtype"=>"text",
            "text"=>array("content"=>$content)
        );
        $response = Message::sendToConversation(Auth::getAccessToken(),$option);
        Log::i("[sendToConversation]".json_encode($response));
        return $response;
    }
}