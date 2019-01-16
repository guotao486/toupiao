<?php

define('DIR_ROOT', dirname(__FILE__).'/');
define("OAPI_HOST", config('site.API_HOST'));

define("CORPID", config('site.CORPID'));
define("SECRET", config('site.SECRET'));
define("AGENTID", config('site.AGENTID'));//必填，在创建微应用的时候会分配
define("AGENTID2", config('site.AGENTID2'));//必填，在创建微应用的时候会分配

define("IS_SHOW", "me");//责任人评价 all 所有人  me 自己  no 拒绝查看