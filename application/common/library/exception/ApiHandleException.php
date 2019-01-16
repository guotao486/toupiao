<?php

namespace app\common\library\exception;


use think\exception\Handle;
class ApiHandleException extends Handle
{
    /**
     * http状态码
     * @var unknown
     */
    public $httpCode = 500;

    public function render(\Exception $e)
    {
        return show(0, $e->getMessage(), [], $this->httpCode);
    }


}
