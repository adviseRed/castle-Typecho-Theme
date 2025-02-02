<?php
/**
 * Functions
 * Version 0.3.5
 * Author ohmyga( https://ohmyga.cn/ )
 * 2019/08/30
 **/
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

define("THEME_NAME", "Castle");
define("CASTLE_VERSION", "0.3.5");

require_once("libs/setting.php");
require_once("libs/owo.php");

//错误是什么？
error_reporting(0);

//设置时区
date_default_timezone_set("Asia/Shanghai");

/* 文章or页面类型 */
function themeFields($layout) {
?>
<style>#custom-field input{ width:100%; }textarea{ height: 180px; width: 100%;}</style>
<?php
 $wzimg = new Typecho_Widget_Helper_Form_Element_Text('wzimg', NULL, NULL, _t('文章/独立页面封面图'), _t('如果不填将显示随机封面图'));
 $layout->addItem($wzimg);
 
 $des = new Typecho_Widget_Helper_Form_Element_Text('des', NULL, NULL, _t('文章/独立页面摘要'), _t('简介摘要，如不填将自动摘取前100字'));
 $layout->addItem($des);
 
 preg_match('/write-post.php/', $_SERVER['SCRIPT_NAME'], $post);
 preg_match('/write-page.php/', $_SERVER['SCRIPT_NAME'], $page);
 
 if(@$post[0] == 'write-post.php'){
  $PostType = new Typecho_Widget_Helper_Form_Element_Select('PostType',
   array(
	'post'=>'文章',
	'nopic'=>'无图',
	'dynamic'=>'日常'
   ),
  'post','文章类型','设置发表的文章的类型(仅对文章有效)');
  $layout->addItem($PostType);
 }elseif(@$page[0] == 'write-page.php'){
  $PageType = new Typecho_Widget_Helper_Form_Element_Select('PageType',
   array(
	'page'=>'默认',
	'links'=>'友链',
	'bangumi'=>'追番'
   ),
  'page','独立页面类型','请根据页面模板选择类型，普通页面保持默认即可(仅对页面有效)');
  $layout->addItem($PageType);
 }
 
 $PSetting = new Typecho_Widget_Helper_Form_Element_Textarea('PSetting', NULL, NULL,
 '高级设置', '文章/独立页高级设置，如果不懂此有何用请勿填写。');
 $layout->addItem($PSetting);
}

function themeInit($comment) {
 Helper::options()->commentsAntiSpam = false; //关闭评论反垃圾(否则与PJAX冲突)
 Helper::options()->commentsHTMLTagAllowed = '<a href=""> <img src=""> <img src="" class=""> <code> <del>'; //评论允许使用的标签
 Helper::options()->commentsMarkdown = true; //启用评论可使用MarkDown语法
 Helper::options()->commentsCheckReferer = false; //关闭检查评论来源URL与文章链接是否一致判断(否则会无法评论)
 Helper::options()->commentsPageBreak = true; //是否开启评论分页
 Helper::options()->commentsPageSize = 5; //评论每页显示条数
 Helper::options()->commentsPageDisplay = 'first'; //默认显示第一页
 Helper::options()->commentsOrder = 'DESC'; //将较新的评论展示在第一页
 Helper::options()->commentsMaxNestingLevels = 9999; //最大回复层数

 /* AJAX获取评论者Gravatar头像 */
 if(@$_GET["action"] == 'ajax_avatar_get' && 'GET' == $_SERVER['REQUEST_METHOD'] ) {
   $host = 'https://cdn.v2ex.com/gravatar/';
   $email = strtolower($_GET['email']);
   $hash = md5($email);
   $qq = str_replace('@qq.com','',$email);
   $sjtx = 'mm';
   if(strstr($email,"qq.com") && is_numeric($qq) && strlen($qq) < 11 && strlen($qq) > 4) {
    $avatar = QQHeadimg($qq);
   }else{
    $avatar = $host.$hash.'?d='.$sjtx;
   }
   echo $avatar; 
   die();
 }elseif(@$_GET["action"] == 'bangumi' && 'GET' == $_SERVER['REQUEST_METHOD'] ) {
   header('Content-type: application/json');
   $bangumiID = $_GET['bgmID'];
   $getKEY = $_GET['auth'];
   if(Helper::options()->apipass) {
    $setKEY = Helper::options()->apipass;
   }else{
    $setKEY = 'babff6a3d9521693097debb2f0063a2f';
   }
   if (!empty($getKEY)) {
   if (!empty($bangumiID)) {
   if ($getKEY == $setKEY) {
    if (extension_loaded('openssl')) {
     $get_json = file_get_contents("https://api.bgm.tv/user/".$bangumiID."/collection?cat=watching");
	}else{
     $get_json = file_get_contents("http://api.bgm.tv/user/".$bangumiID."/collection?cat=watching");
	}
	$dejson = json_decode($get_json, true);
	foreach($dejson as $bangumi) {
     if ($bangumi['subject']['type'] == 2) {
      if ($bangumi['subject']['eps_count'] == null){
       $eps_count = '总集数未知';
       $bfb = 0;
      }else{
       $eps_count = $bangumi['subject']['eps_count'];
       $bfb = 100/$eps_count*$bangumi['ep_status'];
      }
      $array[] = array('name'=>$bangumi['name'], 'CNname'=>$bangumi['subject']['name_cn'], 'img'=>$bangumi['subject']['images']['large'], 'url'=>$bangumi['subject']['url'], 'status'=>$bangumi['ep_status'], 'count'=>$eps_count, 'percentage'=>$bfb);
	 }
    }
	echo preg_replace('/http/', 'https', json_encode($array, true));
   }else{
    header('HTTP/1.1 403 Forbidden');
    $error_json = array('status'=>'403');
	$output = json_encode($error_json);
	echo $output;
   }
   }else{
    header('HTTP/1.1 404 Not Found');
    $error_json = array('status'=>'404');
    $output = json_encode($error_json);
	echo $output;
   }
   }else{
    header('HTTP/1.1 403 Forbidden');
    $error_json = array('status'=>'403');
	$output = json_encode($error_json);
	echo $output;
   }
   die();
  }elseif(@$_GET["action"] == 'QQ_headimg' && 'GET' == $_SERVER['REQUEST_METHOD'] ) {
   $qqNum = base64_decode($_GET['content'], true);
   if (extension_loaded('openssl')) {
     $qqGet = file_get_contents("https://q.qlogo.cn/g?b=qq&nk=".$qqNum."&s=100");
	}else{
     $qqGet = file_get_contents("http://q.qlogo.cn/g?b=qq&nk=".$qqNum."&s=100");
	}
   header('Content-type: image/jpeg');
   echo $qqGet;
   die();
  }else{
   return;
 }
}

/* 文章阅读次数(含Cookie) */
function PostView($moe) {
 $cid    = $moe->cid;
 $db     = Typecho_Db::get();
 $prefix = $db->getPrefix();
 if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
  $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `views` INT(10) DEFAULT 0;');
  echo 0;
  return;
 }
   
 $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
 if ($moe->is('single')) {
  $views = Typecho_Cookie::get('extend_contents_views');
  if(empty($views)){
   $views = array();
  }else{
   $views = explode(',', $views);
  }
  if(!in_array($cid,$views)){
   $db->query($db->update('table.contents')->rows(array('views' => (int) $row['views'] + 1))->where('cid = ?', $cid));
   array_push($views, $cid);
   $views = implode(',', $views);
   Typecho_Cookie::set('extend_contents_views', $views);
  }
 }
 return $row['views'];
}

/* 文章or页面高级设置 */
function PSetting($moe, $type) {
 $setting = $moe->fields->PSetting;
 if (json_decode($setting) == null) {
  $output = null;
 } else {
  $data = json_decode($setting, true);
  $output = $data[''.$type.''];
 }
 return $output;
}

/* 高级设置 */
function adSetting($a, $b, $c=NULL, $moe=NULL){
 $data = Helper::options()->advancedSetting;
 $set = json_decode($data, true);
 if (!empty($a) && !empty($b) && !empty($c)) {
  $output = $set[$a][$b][$c];
 }elseif (!empty($a) && !empty($b)) {
  $output = $set[$a][$b];
 }elseif (!empty($a)) {
  $output = $set[$a];
 }
 return $output;
}

/* 随机封面图 */
function randPic() {
 $setting = Helper::options()->randimg;
 $rand = rand(0,99); //防止只获取一张图
 if ($setting == 'api.ohmyga.cn') {
  $output = 'https://api.ohmyga.cn/wallpaper/?rand='.$rand;
 }elseif ($setting == 'local') {
  $openfile = glob(Helper::options()->themeFile(getTheme(), "random/*.jpg"), GLOB_BRACE);
  $img = array_rand($openfile);
  preg_match('/\/random\/(.*).jpg/', $openfile[$img], $out);
  $output = Helper::options()->siteUrl.'usr/themes/'.getTheme().'/random/'.$out[1].'.jpg';
 }elseif ($setting == 'cdn'){
  $output = adSetting('randPic', 'url').'?rand='.$rand;
 }elseif ($setting == 'cdnno'){
  $output = adSetting('randPic', 'url');
 }
 return $output;
}

/* 获取主题名称 */
function getTheme() {
 static $themeName = NULL;
 if ($themeName === NULL) {
  $db = Typecho_Db::get();
  $query = $db->select('value')->from('table.options')->where('name = ?', 'theme');
  $result = $db->fetchAll($query);
  $themeName = $result[0]["value"];
 }
 return $themeName;
}

/* 主题版本 */
function themeVer($type) {
 if ($type == 'current') {
  $ver = CASTLE_VERSION;
 }
 return $ver;
}

/* 读取语言配置文件 */
function lang($type, $name){
 $file = Helper::options()->lang;
 $json_string = file_get_contents($file, true);
 $data = json_decode($json_string, true);
 $output = $data['0'][''.$type.''][''.$name.''];
 return $output;
}

/* 获取主题静态文件引用源 */
function themeResource($content) {
 $setting = Helper::options()->themeResource;
 
 if ($setting == 'local') {
  $output = Helper::options()->themeUrl.'/'.$content;
 }elseif ($setting == 'jsdelivr') {
  $output = 'https://cdn.jsdelivr.net/gh/ohmyga233/castle-Typecho-Theme@'.themeVer('current').'/'.$content;
 }elseif ($setting == 'cdn') {
  $output = adSetting('resource', 'url').$content;
 }

 return $output;
}

/* 代码高亮静态文件源（Css */
function highlightResource() {
 $setting = Helper::options()->themeResource;
 $hls = Helper::options()->hls;
 
 $hltd = Helper::options()->themeFile(getTheme(), "libs/highlight/default.min.css");
 if (file_exists($hltd)) {
  if ($hls == 'default.min.css') {
   $output = Helper::options()->themeUrl.'/libs/highlight/default.min.css';
  }elseif ($hls == 'jsdelivr') {
   $output = 'https://cdn.jsdelivr.net/gh/ohmyga233/castle-Typecho-Theme@'.themeVer('current').'/libs/highlight/default.min.css';
  }else{
   $output = Helper::options()->themeUrl.'/others/css/highlight/'.$hls;
  }
 }else{
  $output = 'https://cdn.jsdelivr.net/gh/ohmyga233/castle-Typecho-Theme@'.themeVer('current').'/libs/highlight/default.min.css';
 }

 return $output;
}

/* 获取Gravatr头像 */
function gravatar($email, $size){
 $urlSetting = 'https://'.Helper::options()->gravatar_url.'/';
 if (!empty($urlSetting)) {
  $url = $urlSetting;
 }else{
  $url = 'https://cdn.v2ex.com/gravatar/';
 }
 
 $host = $url;
 $hash = md5(strtolower($email));
 $output = $host.$hash.'?s='.$size;
 
 return $output;
}

/* 获取站点头像 */
function siteHeadimg($type, $moe=NULL) {
 $setting = Helper::options()->headimg;
 
 if ($type == 'ico') {
  if (!empty($setting)) {
   $headimg = $setting;
  }else{
   $headimg = themeResource('others/img/headimg.png');
  }
 }elseif ($type == 'pauthor') {
  if (!empty($setting)) {
   $headimg = $setting;
  }else{
   $headimg = gravatar($moe->author->mail, '100');
  }
 }
 return $headimg;
}

/* 获取评论者头像 */
function userHeadimg($moe=NULL) {
 $host = Helper::options()->gravatar_url;
 $hash = md5(strtolower($moe->mail));
 $email = strtolower($moe->mail);
 $qq = str_replace('@qq.com','',$email);
 if(strstr($email,"qq.com") && is_numeric($qq) && strlen($qq) < 11 && strlen($qq) > 4) {
  $avatar = QQHeadimg($qq);
 }else{
  $avatar = 'https://'.$host.'/'.$hash.'?s=640';
 }
 
 return $avatar;
}

/* 获取QQ头像 */
function QQHeadimg($qq) {
 $set = Helper::options()->qqheadimg;
 if (!empty($set)) {
  $setting = $set;
 }else{
  $setting = '0';
 }
 
 if (extension_loaded('openssl')) {
  $osStatus = 'https';
 }else{
  $osStatus = 'http';
 }
 
 if ($setting == 0) {
  $output = "https://q.qlogo.cn/g?b=qq&nk=".$qq."&s=100";
 }elseif ($setting == 1) {
  $output = Helper::options()->siteUrl.'?action=QQ_headimg&content='.base64_encode($qq);
 }elseif ($setting == 2) {
  $url = '://ptlogin2.qq.com/getface?&imgtype=1&uin=';
  $qquser = file_get_contents($osStatus.$url.$qq);
  $str1 = explode('&k=', $qquser);
  $str2 = explode('&s=', $str1[1]);
  $k = $str2[0];
  $output = 'https://q.qlogo.cn/g?b=qq&k='.$k.'&s=100';
 }
 
 return $output;
}

/* 主题整体色 */
function tcs() {
 $setting = Helper::options()->tcs;
 
 if ($setting == 0) {
 }elseif($setting == 1) {
  @$cookie = $_COOKIE["nightSwitch"];
  if (!empty($cookie)) {
   if ($cookie == 'off') {
   }else{
    setcookie('nightSwitch', 'open', NULL, '/');
   }
  }else{
   setcookie('nightSwitch', 'open', NULL, '/');
  }
 }
}

/* 显示上一篇 */
function thePrev($widget, $default = NULL) {
 $db = Typecho_Db::get();
 $sql = $db->select()->from('table.contents')
  ->where('table.contents.created < ?', $widget->created)
  ->where('table.contents.status = ?', 'publish')
  ->where('table.contents.type = ?', $widget->type)
  ->where('table.contents.password IS NULL')
  ->order('table.contents.created', Typecho_Db::SORT_DESC)
  ->limit(1);
 $content = $db->fetchRow($sql);
 if ($content) {
  $content = $widget->filter($content);
  $link = '
      <a href="'.$content['permalink'].'" class="mdui-ripple mdui-col-xs-2 mdui-col-sm-6 moe-nav-left">
       <div class="moe-nav-text">
        <i class="mdui-icon material-icons">arrow_back</i>
        <span class="moe-nav-direction mdui-hidden-xs-down">'.lang('post', 'prev').'</span>
        <div class="moe-nav-chapter mdui-hidden-xs-down">'.$content['title'].'</div>
       </div>
      </a>';
  echo $link;
 } else {
  echo $default;
 }
}

/* 显示下一篇 */
function theNext($widget, $default = NULL) {
 $db = Typecho_Db::get();
 $sql = $db->select()->from('table.contents')
  ->where('table.contents.created > ?', $widget->created)
  ->where('table.contents.status = ?', 'publish')
  ->where('table.contents.type = ?', $widget->type)
  ->where('table.contents.password IS NULL')
  ->order('table.contents.created', Typecho_Db::SORT_ASC)
  ->limit(1);
 $content = $db->fetchRow($sql);
 if ($content) {
  $content = $widget->filter($content);
  $link = '
      <a href="'.$content['permalink'].'" class="mdui-ripple mdui-col-xs-10 mdui-col-sm-6 moe-nav-right">
       <div class="moe-nav-text">
        <i class="mdui-icon material-icons">arrow_forward</i>
        <span class="moe-nav-direction">'.lang('post', 'next').'</span>
        <div class="moe-nav-chapter">'.$content['title'].'</div>
       </div>
      </a>';
  echo $link;
 } else {
  echo $default;
 }
}

/* Tag随机颜色 */
function randColor() {
 $before = 'mdui-text-color-';
 $randNum1 = rand(0,18);
 $colorArray = array('red','pink','purple','deep-purple','indigo','blue','light-blue','cyan','teal','green','light-green','lime','yellow','amber','orange','deep-orange','brown','grey','blue-grey');
 $output = $before.$colorArray[$randNum1];
 return $output;
}

/* Tag字体大小 */
function fTag($t, $moe=NULL) {
 $num = 14;
 $count = $moe->count;
 if ($count <= 10){
  $output = $num+$count;
 }else{
  $output = $num+$count/2;
 }
 return $output;
}

/* 解析文章/页面作者 */
function Jauthor($moe=NULL) {
 $json = json_encode($moe->author);
 $jsonde = json_decode($json, true);
 $output = $jsonde['stack'][0]['name'];
 return $output;
}

/* 输出文章/页面版权 */
function Pcopy($type, $moe=NULL) {
 if ($type == 'post') {
  $copyText = lang('post', 'copy');
 }elseif ($type == 'page'){
  $copyText = lang('page', 'copy');
 }else{}
 $Tauthor = preg_replace('/%author/', Jauthor($moe), $copyText);
 $Ttime = preg_replace('/%time/', date('Y-m-d H:i:s', $moe->modified), $Tauthor);
 $Tlink = preg_replace('/%link/', $moe->permalink, $Ttime);
 $output = $Tlink;
 return $output;
}

function DrawerMenu() {
 $data = Helper::options()->sidebar;
 if (!empty($data)) {
  $json = json_decode($data, true);
  foreach($json as $i) {
   if ($i['type'] == '0') {
    echo '<a href="'.$i['link'].'" class="mdui-list-item mdui-ripple" mdui-drawer-close>
     <i class="mdui-icon material-icons mdui-list-item-icon">'.$i['icon'].'</i>
     <div class="mdui-list-item-content">'.$i['name'].'</div>
    </a>';
   }elseif ($i['type'] == '1') {
	echo '<li class="mdui-collapse-item">
     <div class="mdui-collapse-item-header mdui-list-item mdui-ripple">
      <i class="mdui-icon material-icons mdui-list-item-icon">'.$i['icon'].'</i>
      <div class="mdui-list-item-content">'.$i['name'].'</div>
      <i class="mdui-icon material-icons mdui-list-item-icon mdui-collapse-item-arrow">keyboard_arrow_down</i>
     </div>
     <ul class="mdui-collapse-item-body mdui-list mdui-list-dense">';
	  Typecho_Widget::widget('Widget_Contents_Post_Date', 'type=month&format='.lang('sidebar', 'time').'')->parse('<a href="{permalink}" class="mdui-list-item mdui-ripple" mdui-drawer-close>{date} &nbsp; <span class="moe-cl-a">{count}</span></a>');
	echo '</ul>
    </li>';
   }elseif ($i['type'] == '2') {
	echo '<li class="mdui-collapse-item">
     <div class="mdui-collapse-item-header mdui-list-item mdui-ripple">
      <i class="mdui-icon material-icons mdui-list-item-icon">'.$i['icon'].'</i>
      <div class="mdui-list-item-content">'.$i['name'].'</div>
      <i class="mdui-icon material-icons mdui-list-item-icon mdui-collapse-item-arrow">keyboard_arrow_down</i>
     </div>
     <ul class="mdui-collapse-item-body mdui-list mdui-list-dense">';
	  Typecho_Widget::widget('Widget_Metas_Category_List')->parse('<a href="{permalink}" class="mdui-list-item mdui-ripple" mdui-drawer-close>{name} &nbsp; <span class="moe-cl-a">{count}</span></a>');
	echo '</ul>
    </li>';
   }elseif ($i['type'] == '3') {
    Typecho_Widget::widget('Widget_Contents_Page_List')->to($pages);
	echo '<li class="mdui-collapse-item">
     <div class="mdui-collapse-item-header mdui-list-item mdui-ripple">
      <i class="mdui-icon material-icons mdui-list-item-icon">'.$i['icon'].'</i>
      <div class="mdui-list-item-content">'.$i['name'].'</div>
      <i class="mdui-icon material-icons mdui-list-item-icon mdui-collapse-item-arrow">keyboard_arrow_down</i>
     </div>
     <ul class="mdui-collapse-item-body mdui-list mdui-list-dense">';
	 while($pages->next()){
	  echo '<a class="mdui-list-item mdui-ripple" href="'.$pages->permalink.'" mdui-drawer-close>'.$pages->title.'</a>';
	 }
	echo '</ul>
    </li>';
   }elseif ($i['type'] == '4') {
     echo '<li class="mdui-collapse-item">
     <div class="mdui-collapse-item-header mdui-list-item mdui-ripple">
      <i class="mdui-icon material-icons mdui-list-item-icon">'.$i['icon'].'</i>
      <div class="mdui-list-item-content">'.$i['name'].'</div>
      <i class="mdui-icon material-icons mdui-list-item-icon mdui-collapse-item-arrow">keyboard_arrow_down</i>
     </div>
     <ul class="mdui-collapse-item-body mdui-list mdui-list-dense">';
	 foreach($i['list'] as $ii){ echo '<a class="mdui-list-item mdui-ripple" href="'.$ii['link'].'" mdui-drawer-close>'.$ii['name'].'</a>'; }
	echo '</ul>
    </li>';
   }elseif ($i['type'] == '5') {
    echo '<div class="mdui-divider"></div>';
   }elseif ($i['type'] == '6') {
	echo '<a href="'.Helper::options()->feedUrl.'" target="_blank" class="mdui-list-item mdui-ripple" mdui-drawer-close>
     <i class="mdui-icon material-icons mdui-list-item-icon">rss_feed</i>
     <div class="mdui-list-item-content">RSS订阅</div>
    </a>';
   }elseif ($i['type'] == '7') {
    Typecho_Widget::widget('Widget_Stat')->to($stat);
    if ($i['tes'] == '1') {
      echo '<li class="mdui-list-item mdui-ripple" disabled>
     <div class="mdui-list-item-content">'.lang('sidebar', 'postAllNumber').'</div>
     <div class="mdui-list mdui-float-right">
      <span class="moe-sidebar-count">'.$stat->publishedPostsNum.'</span>
     </div>
    </li>';
    }elseif ($i['tes'] == '2') {
     echo '<li class="mdui-list-item mdui-ripple" disabled>
     <div class="mdui-list-item-content">'.lang('sidebar', 'pageAllNumber').'</div>
     <div class="mdui-list mdui-float-right">
      <span class="moe-sidebar-count">'.$stat->publishedPagesNum.'</span>
     </div>
    </li>';
	}elseif ($i['tes'] == '3') {
     echo '<li class="mdui-list-item mdui-ripple" disabled>
     <div class="mdui-list-item-content">'.lang('sidebar', 'categoriesAllNumber').'</div>
     <div class="mdui-list mdui-float-right">
      <span class="moe-sidebar-count">'.$stat->categoriesNum.'</span>
     </div>
    </li>';
	}elseif ($i['tes'] == '4') {
     echo '<li class="mdui-list-item mdui-ripple" disabled>
     <div class="mdui-list-item-content">'.lang('sidebar', 'commentAllNumber').'</div>
     <div class="mdui-list mdui-float-right">
      <span class="moe-sidebar-count">'.$stat->publishedCommentsNum.'</span>
     </div>
    </li>';
	}
   }
  }
 }
}

/* 解析底部社交信息 */
function FooterSocial() {
 $data = Helper::options()->social;
 if (!empty($data)) {
  $json = json_decode($data, true);
  foreach($json as $i) {
   echo '<a href="'.$i['link'].'" target="_blank"><button class="mdui-btn mdui-btn-icon mdui-ripple moe-footer-i" mdui-tooltip="{content: \''.$i['name'].'\'}">'.$i['icon'].'</button></a>';
  }
 }
}

/* 文章or独立页分享*/
function Pshare($t,$moe=NULL) {
 $wzimg = '';
 if ($t == 'post'){
  $Pt = 'post';
 }elseif($t == 'page'){
  $Pt = 'page';
 }
 $qq = '<li class="mdui-menu-item">
        <a href="http://connect.qq.com/widget/shareqq/index.html?site='.Helper::options()->title.'&amp;title='.$moe->title.'&amp;summary='.$moe->title.'&amp;pics='.$wzimg.'&amp;url='.$moe->permalink.'" target="_blank" class="mdui-ripple">
         <strong>'.lang($Pt,'shareQQ').'</strong>
        </a>
       </li>';

 $weibo = '<li class="mdui-menu-item">
        <a href="" class="mdui-ripple">
         <strong>'.lang($Pt,'shareWB').'</strong>
        </a>
       </li>';

 $facebook = '<li class="mdui-menu-item">
        <a href="http://www.facebook.com/sharer/sharer.php?u='.$moe->permalink.'" target="_blank" class="mdui-ripple">
         <strong>'.lang($Pt,'shareFB').'</strong>
        </a>
       </li>';

 $twitter = '<li class="mdui-menu-item">
        <a href="https://twitter.com/intent/tweet?text='.$moe->title.';url='.$moe->permalink.';via='.Jauthor($moe).'" target="_blank" class="mdui-ripple">
         <strong>'.lang($Pt,'shareTW').'</strong>
        </a>
       </li>';
 return $qq.$weibo.$twitter.$facebook;
}

/* Original Author 熊猫小A (https://blog.imalan.cn/) */
/* 解析表情、灯箱，获取第一管理员邮箱、名称 */
class Castle {
 public static function getAdminScreenName(){
  $db = Typecho_Db::get();
  $name = $db->fetchRow($db->select()->from('table.users')->where('uid = ?', 1))['screenName'];
  return $name;
 }

 public static function getAdminMail(){
  $db = Typecho_Db::get();
  $mail = $db->fetchRow($db->select()->from('table.users')->where('uid = ?', 1))['mail'];
  return $mail;
}

 static public function parseAll($content){
  $new  = self::parseBiaoQing(self::parseFancyBox(self::parseRuby(self::parseDel((self::parseTable($content))))));
  return $new;
 }

 static public function parseBiaoQing($content){
  $content = preg_replace_callback('/\:\s*(a|bishi|bugaoxing|guai|haha|han|hehe|heixian|huaji|huanxin|jingku|jingya|landeli|lei|mianqiang|nidongde|pen|shuijiao|suanshuang|taikaixin|tushe|wabi|weiqu|what|what|wuzuixiao|xiaoguai|xiaohonglian|xiaoniao|xiaoyan|xili|yamaidei|yinxian|yiwen|zhenbang|aixin|xinsui|bianbian|caihong|damuzhi|dangao|dengpao|honglingjin|lazhu|liwu|meigui|OK|shafa|shouzhi|taiyang|xingxingyueliang|yaowan|yinyue)\s*\:/is',
   array('Castle', 'parsePaopaoBiaoqingCallback'), $content);
  $content = preg_replace_callback('/\:\s*(huaji1|huaji2|huaji3|huaji4|huaji5|huaji6|huaji7|huaji8|huaji9|huaji10|huaji11|huaji12|huaji13|huaji14|huaji15|huaji16|huaji17|huaji18|huaji19|huaji20|huaji21|huaji22|huaji23|huaji24|huaji25|huaji26|huaji27)\s*\:/is',
   array('Castle', 'parseHuajibiaoqingCallback'), $content);
  $content = preg_replace_callback('/\:\s*(qwq1|qwq2|qwq3|qwq4|qwq5|qwq6|qwq7|qwq8|qwq9|qwq10|qwq11|qwq12|qwq13|qwq14|qwq15|qwq16|qwq17|qwq18|qwq19|qwq20|qwq21|qwq22|qwq23|qwq24|qwq25|qwq26)\s*\:/is',
   array('Castle', 'parseqwqbiaoqingCallback'), $content);
  return $content;
 }

 private static function parsePaopaoBiaoqingCallback($match){
  return '<img class="moe-owo-img-tieba" src="'.Helper::options()->themeUrl.'/others/img/OwO/tieba/'.$match[1].'.png">';
 }
	
 private static function parseHuajibiaoqingCallback($match){
  return '<img class="moe-owo-img-hj" src="'.Helper::options()->themeUrl.'/others/img/OwO/huaji/'.$match[1].'.gif">';
 }
 
 private static function parseqwqbiaoqingCallback($match){
  return '<img class="moe-owo-img-qwq" src="'.Helper::options()->themeUrl.'/others/img/OwO/qwq/'.$match[1].'.png">';
 }

 static public function parseFancyBox($content){
  $reg = '/<img(.*?)src="(.*?)"(.*?)>/s';
  $rp = '<a data-fancybox="images" href="${2}"><img${1}src="'.themeResource('others/img/loading.gif').'"${3}data-original="${2}" class="mdui-shadow-3 moe-p-pic"></a>';
  $new = preg_replace($reg,$rp,$content);
  return $new;
}

 static public function parseRuby($string){
  $reg='/\{\{(.*?):(.*?)\}\}/s';
  $rp='<ruby>${1}<rp>(</rp><rt>${2}</rt><rp>)</rp></ruby>';
  $new=preg_replace($reg,$rp,$string);
  return $new;
 }

 static public function parseDel($string){
  $reg='/\[del\](.*?)\[\/del\]/s';
  $rp='<span class="moe-del" title="'.lang('short', 'del').'">${1}</span>';
  $new=preg_replace($reg,$rp,$string);
  return $new;
 }
 
 static public function parseTable($content){
  $reg = '/<table>(.*?)<\/table>/s';
  $rp = '<div class="mdui-table-fluid"><table class="mdui-table mdui-table-hoverable">${1}</table></div>';
  $new = preg_replace($reg,$rp,$content);
  return $new;
 }

 static public function commentsReply($comment) {
  $db = Typecho_Db::get();
  $parentID = $db->fetchRow($db->select('parent')->from('table.comments')->where('coid = ?', $comment->coid));
  $parentID=$parentID['parent'];
  if($parentID=='0'){
   return '';
  }else {
   $author=$db->fetchRow($db->select()->from('table.comments')->where('coid = ?', $parentID));
   if (!array_key_exists('author', $author) || empty($author['author']))
   $author['author'] = '已删除的评论';
   return '<span class="moe-comments-reply-name">@'.$author['author'].'</span>';
  }
 }
 
 public static function exportHead($moe){
  $author = $moe->author->screenName;
  if($moe->is("index")) {
   $description = Helper::options()->description;
   $type = 'website';
  }elseif ($moe->is("post") || $moe->is("page")) {
   if($moe->fields->des && $moe->fields->des!=''){
    $description = $moe->fields->des;
   }else{
    $description = Typecho_Common::subStr(strip_tags($moe->excerpt), 0, 100, "...");
   }
   $type='article';
  }else{
   $description = Helper::options()->description;
   $type='archive';
  }
  $wzimg = $moe->fields->wzimg;
  if(!empty($wzimg)){
   $img = $moe->fields->wzimg;
  }elseif ($moe->is("index")) {
   $img = siteHeadimg('ico');
  }else{
   $img = randPic();
  }
  echo '  <meta name="description" content="'.$description.'" />
  <meta property="og:title" content="';
  $moe->archiveTitle(array(
   'category'  =>  '分类 %s 下的文章',
   'search'    =>  '包含关键字 %s 的文章',
   'tag'       =>  '标签 %s 下的文章',
   'author'    =>  '%s 发布的文章'
  ), '', ' - ');
  echo Helper::options()->title();
  echo '" />
  <meta name="author" content="'.$author.'" />
  <meta property="og:site_name" content="'.Helper::options()->title.'" />
  <meta property="og:type" content="'.$type.'" />
  <meta property="og:description" content="'.$description.'" />
  <meta property="og:url" content="'.$moe->permalink.'" />
  <meta property="og:image" content="'.$img.'" />
  <meta property="article:published_time" content="'.date('c', $moe->created).'" />
  <meta property="article:modified_time" content="'.date('c', $moe->modified).'" />
  <meta name="twitter:title" content="';
  $moe->archiveTitle(array(
   'category'  =>  '分类 %s 下的文章',
   'search'    =>  '包含关键字 %s 的文章',
   'tag'       =>  '标签 %s 下的文章',
   'author'    =>  '%s 发布的文章'
  ), '', ' - ');
  echo Helper::options()->title();
  echo "\" />
  <meta name=\"twitter:description\" content=\"".$description."\" />
  <meta name=\"twitter:card\" content=\"summary_large_image\" />
  <meta name=\"twitter:image\" content=\"".$img."\" />\n";
 }
}

/* 获取浏览器信息 */
function getBrowser($agent) {
 if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {
  $name = lang('ua', 'ie');
  $icon = 'icon-IE';
 }elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {
  $name = lang('ua', 'firefox');
  $icon = 'icon-firefox';
 }elseif (preg_match('/Maxthon([\d]*)\/([^\s]+)/i', $agent, $regs)) {
  $name = lang('ua', 'aoyou');
  $icon = 'icon-Aoyou_Browser';
 }elseif (preg_match('#SE 2([a-zA-Z0-9.]+)#i', $agent, $regs)) {
  $name = lang('ua', 'sougou');
  $icon = 'icon-Sougou_Browser';
 }elseif (preg_match('#360([a-zA-Z0-9.]+)#i', $agent, $regs)) {
  $name = lang('ua', '360');
  $icon = 'icon-360_Browser';
 }elseif (preg_match('/Edge([\d]*)\/([^\s]+)/i', $agent, $regs)) {
  $name = lang('ua', 'edge');
  $icon = 'icon-edge';
 }elseif (preg_match('/QQ/i', $agent, $regs)||preg_match('/QQBrowser\/([^\s]+)/i', $agent, $regs)) {
  $name = lang('ua', 'qq');
  $icon = 'icon-QQBrowser';
 }elseif (preg_match('/UC/i', $agent)) {
  $name = lang('ua', 'uc');
  $icon = 'icon-UC_Browser';
 }elseif (preg_match('/UBrowser/i', $agent, $regs)) {
  $name = lang('ua', 'ub');
  $icon = 'icon-UC_Browser';
 }elseif (preg_match('/MicroMesseng/i', $agent, $regs)) {
  $name = lang('ua', 'wechat');
  $icon = 'icon-wechat';
 }elseif (preg_match('/WeiBo/i', $agent, $regs)) {
  $name = lang('ua', 'weibo');
  $icon = 'icon-weibo';
 }elseif (preg_match('/BIDU/i', $agent, $regs)) {
  $name = lang('ua', 'baidu');
  $icon = 'icon-Baidu_Browser';
 }elseif (preg_match('/LBBROWSER/i', $agent, $regs)) {
  $name = lang('ua', 'lb');
  $icon = 'icon-LBBROWSER';
 }elseif (preg_match('/TheWorld/i', $agent, $regs)) {
  $name = lang('ua', 'tw');
  $icon = 'icon-TheWorld_Browser';
 }elseif (preg_match('/XiaoMi/i', $agent, $regs)) {
  $name = lang('ua', 'xiaomi');
  $icon = 'icon-xiaomi';
 }elseif (preg_match('/2345Explorer/i', $agent, $regs)) {
  $name = lang('ua', '2345');
  $icon = 'icon-2345_Browser';
 }elseif (preg_match('/YaBrowser/i', $agent, $regs)) {
  $name = lang('ua', 'yandex');
  $icon = 'icon-Yandex_Browser';
 }elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)) {
  $name = lang('ua', 'opera');
  $icon = 'icon-Opera_Browser';
 }elseif (preg_match('/Thunder/i', $agent, $regs)) {
  $name = lang('ua', 'xunlie');
  $icon = 'icon-xunlei';
 }elseif (preg_match('/Chrome([\d]*)\/([^\s]+)/i', $agent, $regs)) {
  $name = lang('ua', 'chrome');
  $icon = 'icon-chrome';
 }elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs)) {
  $name = lang('ua', 'safari');
  $icon = 'icon-safari';
 }else{
  $name = lang('ua', 'other');
  $icon = 'icon-Browser';
 }
 echo '<i class="iconfont '.$icon.' moe-comments-ua" mdui-tooltip="{content: \''.$name.'\'}"></i>';
}

/* 获取操作系统 */
function getOs($agent) {
 if (preg_match('/win/i', $agent)) {
  if (preg_match('/nt 5.1/i', $agent)) {
   $name = lang('os', 'windows xp');
   $icon = 'icon-windows_old';
  }elseif (preg_match('/nt 6.0/i', $agent)) {
   $name = lang('os', 'windows vista');
   $icon = 'icon-windows_old';
  }elseif (preg_match('/nt 6.1/i', $agent)) {
   $name = lang('os', 'windows 7');
   $icon = 'icon-windows_old';
  }elseif (preg_match('/nt 6.2/i', $agent)) {
   $name = lang('os', 'windows 8');
   $icon = 'icon-windows';
  }elseif (preg_match('/nt 6.3/i', $agent)) {
   $name = lang('os', 'windows 8.1');
   $icon = 'icon-windows';
  }elseif (preg_match('/nt 10.0/i', $agent)) {
   $name = lang('os', 'windows 10');
   $icon = 'icon-windows';
  }else{
   $name = lang('os', 'windows xp');
   $icon = 'icon-windows';
  }
 }elseif (preg_match('/android/i', $agent)) {
  if (preg_match('/android 5/i', $agent)) {
   $name = lang('os', 'android l');
   $icon = 'icon-android';
  }elseif (preg_match('/android 6/i', $agent)) {
   $name = lang('os', 'android m');
   $icon = 'icon-android';
  }elseif (preg_match('/android 7/i', $agent)) {
   $name = lang('os', 'android n');
   $icon = 'icon-android';
  }elseif (preg_match('/android 8/i', $agent)) {
   $name = lang('os', 'android o');
   $icon = 'icon-android';
  }elseif (preg_match('/android 9/i', $agent)) {
   $name = lang('os', 'android p');
   $icon = 'icon-android';
  }else{
   $name = lang('os', 'android');
   $icon = 'icon-android';
  }
 }elseif (preg_match('/linux/i', $agent)) {
  $name = lang('os', 'linux');
  $icon = 'icon-linux';
 }elseif (preg_match('/iPhone/i', $agent)) {
  $name = lang('os', 'iphone');
  $icon = 'icon-ios';
 }elseif (preg_match('/iPad/i', $agent)) {
  $name = lang('os', 'ipad');
  $icon = 'icon-ios';
 }elseif (preg_match('/mac/i', $agent)) {
  $name = lang('os', 'mac os');
  $icon = 'icon-osx';
 }else{
  $name = lang('os', 'other');
  $icon = 'icon-os';
 }
 echo '<i class="iconfont '.$icon.' moe-comments-ua" mdui-tooltip="{content: \''.$name.'\'}"></i>';
}

/* HTML压缩 */
function compressHtml($html_source) {
 $chunks = preg_split('/(<!--<nocompress>-->.*?<!--<\/nocompress>-->|<nocompress>.*?<\/nocompress>|<pre.*?\/pre>|<textarea.*?\/textarea>|<script.*?\/script>)/msi', $html_source, -1, PREG_SPLIT_DELIM_CAPTURE);
 $compress = '';
 foreach ($chunks as $c) {
  if (strtolower(substr($c, 0, 19)) == '<!--<nocompress>-->') {
   $c = substr($c, 19, strlen($c) - 19 - 20);
   $compress .= $c;
   continue;
  }else if (strtolower(substr($c, 0, 12)) == '<nocompress>') {
   $c = substr($c, 12, strlen($c) - 12 - 13);
   $compress .= $c;
   continue;
  }elseif (strtolower(substr($c, 0, 4)) == '<pre' || strtolower(substr($c, 0, 9)) == '<textarea') {
   $compress .= $c;
   continue;
  }elseif (strtolower(substr($c, 0, 7)) == '<script' && strpos($c, '//') != false && (strpos($c, "\r") !== false || strpos($c, "\n") !== false)) {
   $tmps = preg_split('/(\r|\n)/ms', $c, -1, PREG_SPLIT_NO_EMPTY);
   $c = '';
   foreach ($tmps as $tmp) {
    if (strpos($tmp, '//') !== false) {
     if (substr(trim($tmp), 0, 2) == '//') {
      continue;
     }
     $chars = preg_split('//', $tmp, -1, PREG_SPLIT_NO_EMPTY);
     $is_quot = $is_apos = false;
     foreach ($chars as $key => $char) {
      if ($char == '"' && $chars[$key - 1] != '\\' && !$is_apos) {
       $is_quot = !$is_quot;
      }elseif ($char == '\'' && $chars[$key - 1] != '\\' && !$is_quot) {
       $is_apos = !$is_apos;
      }elseif ($char == '/' && $chars[$key + 1] == '/' && !$is_quot && !$is_apos) {
       $tmp = substr($tmp, 0, $key);
       break;
      }
     }
    }
    $c .= $tmp;
   }
  }
  $c = preg_replace('/[\\n\\r\\t]+/', ' ', $c);
  $c = preg_replace('/\\s{2,}/', ' ', $c);
  $c = preg_replace('/>\\s</', '> <', $c);
  $c = preg_replace('/\\/\\*.*?\\*\\//i', '', $c);
  $c = preg_replace('/<!--[^!]*-->/', '', $c);
  $compress .= $c;
 }
 return $compress;
}
