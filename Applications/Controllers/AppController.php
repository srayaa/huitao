<?php
/*
app交互公共控制器
 */
class AppController extends Controller
{
	const DUIBA_AUTO_URL = 'http://www.duiba.com.cn/autoLogin/autologin?';
	const DUIBA_KEY = '3JaYVqyA2yXdvTKD14ybisvjzcT9';
	const DUIBA_SECRET = 'FTfdo5BFrq9Svto1KKb3Lzdsmvo';
    // const DUIBA_KEY = '4CgXMXbZYifpSWv1wHszsN9UWr2z';
    // const DUIBA_SECRET = '4MD6bsEmMoSNTzBA2RPi5oQH7AT7';
    const ALIDAYU_KEY = '23559394';
    const ALIDAYU_SECRET = '14ec3bb9c8d206eb00c97241cff58f60';
    const PERCENT = 0.2;
    const SHARE_URL = 'http://huitao321.com/share/share.html';
    static $aes = null;
    // static $aes = true;
    protected $param;
    public $status = 1;
    public function __construct()
    {
        if($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $this->dparam = json_decode(rawurldecode(file_get_contents('php://input')),true);
        }else{
            $this->dparam = $_GET;
        }
        //判断接收的数据是否AES加密
        if(!empty($this->dparam['secret'])){
            $this->dparam = aes_decode($this->dparam['content'],$this->dparam['secret']);
            self::$aes = true;
        }
        //status 等1 的情况下 才会去过滤
        if($this ->status == 1 && !empty($this->dparam) ) {
            $this ->dparam = $this->filter_arr($this ->dparam);
        }
    }

    //过滤非表字段的数据
    public function filter_field($arr=array(),$farr=array()){
        foreach ($arr as $k => $v) {
            if(in_array($k,$farr)){
                $_arr[$k] = $v;
            }
        }
        return $this->filter_arr($_arr);
    }


    //过滤空数据
    function filter_arr($arr=array()){
        $_arr = [];
        foreach ($arr as $k => $v) {
            if($v !== '' && $v !== null){
                $_arr[$k] = $v;
            }
        }
        return $_arr;
    }

}


