<?php
/** 
 * 验证码类
 * @author 冷空气
 * @date 2015-03-20
 */
class Vcode{
    
    protected $width;   //生成的验证码图片 宽度 
    protected $height;  //生成的验证码图片 高度
    protected $size;    //生成的验证码图片 验证码文字的大小
    protected $length;  //生成的验证码图片 字符长度
    protected $type;    //生成的验证码类型 1.数字大小写字母 2.数字 3.大写字母 4.大小写字母 5.汉字
    protected $vcode;   //生成的验证码字符串
    protected $font;    //生成的验证码字体

    /**
     * 构造函数
     * @param int width 验证码图片宽度
     * @param int height 验证码图片高度
     * @param int size 验证码图片字体大小
     * @param int length 验证码图片文字个数
     * @param int length 验证码图片类型
     * @param int length 验证码图片字体
     * @param int length 验证码图片倾斜量
     */
    public function __construct($width = 200,$height = 50,$size = 20,$length = 4,$type = 1,$font = '',$angle = 10){
        $this->width    = $width;
        $this->height   = $height;
        $this->size     = $size;
        $this->length   = $length;
        $this->type     = $type;
        $this->font     = $font == '' ? $font : PUBLIC_PATH.'/ttf/KimsGirlType.ttf';
        $this->angle    = $angle;
    } 

    /**
     * 获取验证码 并放入$_SESSION['vcode']
     */
    public function getCode(){
        $vcode = $this->getStr();
        $_SESSION['vcode'] = $vcode;
        $this->getImg();
    }

    /**
     * 验证验证码是否正确
     */
    public static function checkCode($vcode){
        //不区分大小写比较
        if(strcasecmp($vcode,$_SESSION['vcode']) === 0){
            return true;
        }else{
            return false;
        }
    }

    //渲染图片
    private function getImg(){
        //1.创建画布
        $image  = imageCreateTrueColor($this->width,$this->height);

        //配色
        $white  = imageColorAllocate($image,255,255,255);
        $custom = imageColorAllocate($image,234,216,179);
        $red    = imageColorAllocate($image,255,0,0);
        $blue   = imageColorAllocate($image,0,0,255);
        $green  = imageColorAllocate($image,0,255,0);

        //2.开始绘画
        imageFill($image,0,0,$custom);

        //渲染验证码字符
        for($i=0;$i<$this->length;$i++){ 
            //指定编码集截取字符串
            $temp  = mb_substr($this->vcode,$i,1,'utf-8');
            $angle = rand(-$this->angle,$this->angle);
            $x     = $this->size/2 + ($this->size/2 + $this->size) * $i; 
            $y     = (($this->height - $this->size)/2) + $this->size;
            imageTtfText($image,$this->size,$angle,$x,$y,$red,$this->font,$temp);
        }
        
        //3.输出图像
        header('Content-type:image/jpeg');
        imageJpeg($image);
        
        //4.销毁资源
        imageDestroy($image);
    }

    //获取验证码字符
    public function getStr(){

        //要输出的字符串
        $str = '0123456789ABCDEFGHJKLMNPQRSTUVWXYabcdefghijkmnpqrstuvwxy';
        $str_cn = '这世间本是没有什么神仙的但自太古以来人类眼见周遭世界诸般奇异之事电闪鸣狂风暴雨又有天灾人祸伤亡无数哀鸿遍野决非人力所能为所能抵挡遂以为九天之上有诸般神灵九幽之下亦是阴魂归处阎罗殿堂于是神仙之说流传于世无数人类子民诚心叩拜向着自己臆想创造出的各种神明顶礼膜拜祈福诉苦香火鼎盛方今之世正道大昌邪魔退避中原大地山灵水秀人气鼎盛物产丰富为正派诸家牢牢占据其中尤以青云门天音寺和焚香谷为三大支柱是为领袖这个故事便是从青云门开始的';

        //判断要输出的类型
        //1.数字大小写字母 2.数字 3.大写字母 4.大小写字母 5.汉字
        switch($this->type){
            case 1: $start = 0; $end = 55; break;
            case 2: $start = 0; $end = 9; break;
            case 3: $start = 10; $end = 32; break;
            case 4: $start = 10; $end = 55; break;
            case 5: $str = $str_cn; $start = 0; $end = 210; break;
        }

        //字符串保存变量
        $temp = '';
        for($i=0;$i<$this->length;$i++){
            //随机字符串
            $rand = rand($start,$end);
        
            //指定编码集截取字符串
            $temp .= mb_substr($str,$rand,1,'utf-8');
        }
        $this->vcode = $temp;
        return $temp;
    }
}