<?php
/**
 * 分页类
 * @author TTT
 * @date 2015-01-19
 */
class Page{
    protected $totalRows = 0;   //总数据条数
    protected $pageSize = 10;   //页大小
    protected $pageVar = 'p';   //分页中的get参数
    protected $page = 1;        //当前页数
    protected $totalPage = 1;   //总页数
    protected $url = '';        //当前页面地址

    public function __construct($total = 0,$pageSize = 10,$pageVar = 'p',$url = ''){

        $this->totalRows = $total;
        $this->pageSize = $pageSize;
        $this->pageVar = $pageVar;
        $this->page = !empty($_GET[$this->pageVar]) ? (int)$_GET[$this->pageVar] : 1;
        $this->totalPage = $this->totalPage();
        if(!empty($url)){
            $this->url = $url;
        }else{
            $this->pageUrl();
        }
        
    }

    private function pageUrl(){
        //获取当前页面地址
        $this->url = $_SERVER['PHP_SELF'];
        if(!empty($_GET)){                          //有get参数的情况
            $this->url .= '?';
            unset($_GET[$this->pageVar]);
            foreach ($_GET as $k => $v) {
                $this->url .= "$k=$v&";
            }
            $this->url .= "{$this->pageVar}=";
        }else{                                      //无get参数的情况
            $this->url .= "?{$this->pageVar}=";
        }
    }

    /**
     * 获取总页数
     */
    public function totalPage(){
        return ceil($this->totalRows / $this->pageSize);
    }

    /**
     * 获取分页 limit 的第一个参数
     */
    public function getStartLimit(){
        return ($this->page - 1) * $this->pageSize;
    }

    /**
     * 获取分页 limit 的第二个参数
     */
    public function getEndLimit(){
        return $this->pageSize;
    }

    /**
     * 获取分页输出的HTML代码
     */
    public function getHtml(){

        $bPage = (($this->page - 1) > 0) ? ($this->page - 1) : 1;   //上一页
        $fPage = (($this->page + 1) < $this->totalPage) ? ($this->page + 1) : $this->totalPage; //下一页

        $pageHtml  = '<div class="page">';
        $pageHtml  = '<span>第'.$this->page.'/'.$this->totalPage.'页</span> ';
        if($this->page <= 1){
            $pageHtml .= '<span class="firstPage">首页</span> ';
        }else{
            $pageHtml .= '<a href="'.$this->url.'" class="firstPage">首页</a> ';
            $pageHtml .= '<a href="'.$this->url.$bPage.'"><上一页</a> ';
        }
        
        for($i = 1;$i <= $this->totalPage;$i++){
            if($this->page == $i){
                $pageHtml .='<span class="currentPage">'.$i.'</span> ';
            }else{
                $pageHtml .='<a href="'.$this->url.$i.'">'.$i.'</a> ';
            }
        }
        
        if($this->page == $this->totalPage){
            $pageHtml .= '<span>末页</span>';
        }else{
            $pageHtml .= '<a href="'.$this->url.$fPage.'">下一页></a> ';
            $pageHtml .= '<a href="'.$this->url.$this->totalPage.'">末页</a>';
        }

        return $pageHtml;
    }
} 

/**
 * 使用方法
 * $page = new Page($user_total,10);
 * //limit 条件
 * $startLimit = $page->getStartLimit();   
 * $endLimit = $page->getEndLimit();
 * //输出的html
 * $html = $page->getHtml();
 */