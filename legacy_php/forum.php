<?php
include("datatable.php");
include("uploadfile.php");
include("captcha.php");

class Forum
{
    // property declaration
    public $u=false, $context, $error="", $baseurl;
    protected $db, $topic, $post, $user, $image;

    public function __construct() {
      // Tworzenie obiektu klasy PDO - baza danych SQLite
      // Zobacz: http://www.sqlitetutorial.net/sqlite-php
      try{ 
         $this->db = new PDO('sqlite:'.dirname(__FILE__).'/db.sq3'); 
         $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      }
      catch(PDOException $e){ echo $e->getMessage().": ".$e->getCode();  exit; }
      
      // Model danych - tablice bazy danych
      $this->topic = new Datatable( $this->db, "topic", array("topic","topic_body","date","userid","topicid"), "topicid" );
      $this->post  = new Datatable( $this->db, "post", array("post", "userid","topicid","date","postid"), "postid" );
      $this->user  = new Datatable( $this->db, "user", array("userid", "username","userlevel","pass"), "userid", false );
      $this->image = new Uploadfile( $this->db, "image", array("userid","postid","topicid","name","sufix","title","date","id"), "id" );
      
      // komponenty
      $this->captcha = new Captcha;
      
      // inicjacja parametrów
      $this->baseurl = "index.php";
      $this->context = (isset($_SESSION["context"]))?$_SESSION["context"]:NULL;
      $this->u = (isset($_SESSION["user"]))?$_SESSION["user"]:false;

      // administrator
      $admin = $this->user->get("admin");
      if( !isset($admin['userid']) )
          $this->user->insert(array( "userid"=>"admin", "username"=>"admin","userlevel"=>10,"pass"=>md5("admin") ));
    }                               

    public function login($userid,$pass){
         if( !($this->u=$this->user->get($userid)) ) {
            $this->error="Bad user name or password!";
            return false;
         } 
         if( $this->u["pass"]!=md5($pass) ){
            $this->error="Bad user name or password!"; 
            return false;
         }
         $_SESSION = array();
         session_regenerate_id();
         $_SESSION["token"] = md5(session_id().__FILE__);
         $_SESSION["user"] = $this->u;
         $_SESSION["context"] = "topics";
         $this->reload();
    }
    public function logout(){
       $_SESSION = array();
       if (ini_get("session.use_cookies")) {
          $params = session_get_cookie_params();
          setcookie(session_name(), '', time() - 42000,
              $params["path"], $params["domain"],
              $params["secure"], $params["httponly"]
          );
       }
       session_destroy();
       $this->reload();
    }
    public function register($userid,$username,$pass){
       if($u=$this->user->get($userid)){
          $this->error .= "Bad username";return false; }
       $u = array("userid"=>$userid,"username"=>$username,"userlevel"=>0,"pass"=>md5($pass));   
       $this->user->insert($u);
       $_SESSION["user"] = $u;
       $_SESSION["context"] = "topics";
       $this->reload();
    }
    public function insert_topic($topic,$topic_body){
       $this->topic->insert(array("topic"=>$topic,"topic_body"=>$topic_body,"date"=>date("Y-m-d H:i:s"),"userid"=>$this->u['userid'] ));
       $this->reload();
    }
    public function delete_topic($topicid){
       $this->topic->delete($topicid);
       $this->reload();
    }
    public function update_topic($topicid,$topic,$topic_body){
       $this->topic->update(array("topicid"=>$topicid,"topic"=>$topic,"topic_body"=>$topic_body,"date"=>date("Y-m-d H:i:s"),"userid"=>$this->u['userid'] ));
       $this->reload();
    }
    public function insert_post($post){
          $p = array(
            "post"=>$post, 
            "userid"=>$this->u["userid"],
            "topicid"=>$_SESSION["topicid"],
            "date"=>date("Y-m-d H:i:s")
          );  
          if($this->post->insert($p)) $this->reload();
          else return false;
    }
    public function delete_post($postid){
          if( $this->post->delete($postid) ) $this->reload();
          else return false;
    }
    public function update_post($post,$postid){
       if($p = $this->post->get($postid)){
          $p['post']=$post;
          if( $this->post->update($p) ) $this->reload();
          else return false;
       }else return false;
    }
    public function delete_user($userid){
          if( $this->user->delete($userid) ) $this->reload();
          else return false;
    }
    public function update_user($userid){
       if($u = $this->user->get($userid)){
          $u['userlevel']=($u['userlevel']==10)?0:10;
          if( $this->user->update($u) ) $this->reload();
          else return false;
       }else return false;
    }
    public function count_posts($topicid){
        if( $p=$this->post->getAll($topicid,"topicid") ) return count($p);
        else return 0;   
    }
    
//-----------------------------------------
//---------- metoda prosess() -------------
//-----------------------------------------    
public function process(){

if( isset($_SESSION["token"]) and $_SESSION["token"] != md5(session_id().__FILE__)) $this->logout();

// --- send the captcha image binary ---
if(isset($_GET['capthaimg'])){
  $this->captcha->show();
}
// --- send en image binary ---
if(isset($_GET["image"]) and $_GET["image"]!=""){
   echo $this->image->send($_GET["image"], 300);
   exit;
}

$data = array( "last_post"=>($lastpost = $this->post->getLastItem())?$lastpost["date"]:"- brak wpisów -",
               "topic"=>false,
               "images"=>false 
             );
             
//---------- Akcje publiczne -------------
// login 
if(isset($_POST['userid']) and $_POST['userid']!="" and isset($_POST['pass'])){
   if( !$this->login($_POST['userid'],$_POST['pass']) ){
      $data["error1"]=$this->error;
   }
}
// register
if(isset($_POST['userid']) and isset($_POST['pass1']) and $_POST['pass1']!=""  and ($_POST['pass1']==$_POST['pass2'])){
   if( !$this->captcha->check(strtoupper($_POST['captcha']))) {
      $data["error"]="Wpisano niewłaściwy kod kontrolny";
   }else{
      if( !$this->register($_POST['userid'],$_POST['username'],$_POST['pass1']) )
         $data["error"]=$this->error;
   }
}
// logout
if(isset($_GET['cmd']) and $_GET['cmd']=='logout'){
   $this->logout();
}

if($this->context){  // --- akcje wymagajace zalogowania ---
   
   if(isset($_GET['cmd']) and $_GET['cmd']=='posts'){
      $_SESSION['context']=$this->context="posts";
      $_SESSION['topicid']=$_GET['id'];
      $this->reload();
   }
   if(isset($_GET['cmd']) and $_GET['cmd']=='topics'){
     $_SESSION['context']=$this->context='topics';
     if(isset($_SESSION['topicid'])) unset( $_SESSION['topicid'] );
     $this->reload();
   }
   if(isset($_GET['cmd']) and $_GET['cmd']=='images'){
     $_SESSION['context']=$this->context="images";
     if(isset($_SESSION['topicid'])) unset( $_SESSION['topicid'] );
     $this->reload();
   }
   if(isset($_GET['cmd']) and $_GET['cmd']=='userlist'){
     $_SESSION['userlist']=($_SESSION['userlist'])?false:true;
     $this->reload();
   }
   if(isset($_GET['cmd']) and $_GET['cmd']=='imgdelete'){
     $this->image->delete($_GET['imgid']);  
     $this->reload();  
   }
  
// --- AJAX response ---
   if(isset($_GET['cmd']) and $_GET['cmd']=='gettopic'){
     if( $t = $this->topic->get($_GET['topicid']) ){
       echo json_encode( $t, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT );
     }else{
     // throw new Exception("ERROR: Topic ID: ".$_GET['topicid']." not  found!");
       echo "<p>ERROR: Topic ID: ".$_GET['topicid']." not  found!</p>\n";
     }  
     exit;
   } 

   if(isset($_GET['cmd']) and $_GET['cmd']=='getpost'){
      if( $t = $this->post->get($_GET['postid']) ){
        echo json_encode( $t, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT );
      }else{
        echo "<p>ERROR: Post ID: ".$_GET['postid']." not  found!</p>\n";
      }  
      exit;
    } 
   if(isset($_GET['cmd']) and $_GET['cmd']=='getimg'){
      if( $t = $this->image->get($_GET['imgid']) ){
        echo json_encode( $t, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT );
      }else{
        echo "<p>ERROR: Img ID: ".$_GET['imgid']." not  found!</p>\n";
      }  
      exit;
    } 
// ----------------------------------------------------------------------------   
// ----------- do uzupełnienia kod odpowiedzi AJAX dla postu i obrazka
// ----------------------------------------------------------------------------   

   // --- users operation ---
   if(isset($_GET['cmd']) and $_GET['cmd']=='changeuser' and $this->u['userlevel']==10){
      if($_GET['userid']!="admin") $this->update_user($_GET['userid']);
   }
   if(isset($_GET['cmd']) and $_GET['cmd']=='deluser' and $this->u['userlevel']==10){
      if($_GET['userid']!="admin") {
         if($p=$this->post->getAll($_GET['userid'],'userid')) 
             foreach( $p as $k=>$v) $this->post->delete($k);
         if($p=$this->topic->getAll($_GET['userid'],'userid')) 
             foreach( $p as $k=>$v) $this->topic->delete($k);
         if($img=$this->image->getAll( $_GET['userid'], "userid" ))
             foreach($img as $k=>$v) $this->image->delete($k);    
         $this->delete_user($_GET['userid']);
      }   
   }
}

// --- post context ---
if($this->context=='posts'){
    $data["topic"]=$this->topic->get($_SESSION['topicid']);
    $data["posts"]=$this->post->getAll($_SESSION['topicid'],"topicid","date desc");
    $data['post']=false;
    if(isset($_POST['post']) and $_POST['post']!='')
       if($_POST['postid']!='')
           $this->update_post($_POST['post'],$_POST['postid']);
       else
           $this->insert_post($_POST['post']);
    if(isset($_GET['cmd']) and $_GET['cmd']=='delete'){
       if( $this->image->delete_from_post($_GET['id']) )
           $this->delete_post($_GET['id']);
       $this->reload();  
    }
    if( isset($_FILES['image']) and $_FILES['image']['name']!="" ){
       if( !$this->image->insert($_FILES['image']) ){
         $data['uploaderror'] = $this->image->error; 
       }else{
         $this->reload();
       }
    }
    if( isset($_POST['imagetitle']) ){
       if( !$this->image->update($_POST["imgid"], $_POST['imagetitle']) ){
         $data['uploaderror'] = $this->image->error; 
       }else{
         $this->reload();  
       }
    }

  $data['images']=$this->image->getAll($_SESSION['topicid'],"topicid");  
  $data['users']=$this->user->getAll(); 
} // end of context posts

// --- topics context ---
if($this->context=='topics'){
 if( isset($_POST['topic']) and $_POST['topic'] and $_POST['topic_body'] ){
   if($_POST['topicid']=="")
   $this->topic->insert(array("topic"=>$_POST['topic'],"topic_body"=>$_POST['topic_body'],
                              "date"=>date("Y-m-d H:i:s"),"userid"=>$this->u['userid']));
   else
   $this->topic->update(array("topic"=>$_POST['topic'],"topic_body"=>$_POST['topic_body'],
                              "date"=>date("Y-m-d H:i:s"),"userid"=>$this->u['userid'],
                              "topicid"=>$_POST['topicid']));
   $this->reload();
   }
 if(isset($_GET['cmd']) and $_GET['cmd']=='topicdelete' and $this->u['userlevel']==10){
    if($p=$this->post->getAll($_GET['id'],'topicid')) 
        foreach( $p as $k=>$v) $this->post->delete($k);
    $this->topic->delete($_GET['id']);
    $this->reload();
 }
 if(isset($_GET['cmd']) and $_GET['cmd']=='topicedit' and $this->u['userlevel']==10){
    $data["topic"]=$this->topic->get($_GET['id']);
 }
 $data['users']=$this->user->getAll();
 $data['topics']=$this->topic->getAll(false,false, "date desc");
} // end of context topics

if($this->context=='images'){
  $data['users']=$this->user->getAll();
  $data['images']=$this->image->getAll(false,false, "date desc");
}  // end of context images

return $data;
} //---- end of function process()


//-----------------------------------------
//---------- metoda makepage() -------------
//-----------------------------------------    
  public function makepage($data){
    $this->view("header",$data);
    switch($this->context){
      case "topics":
        $this->view("userinfo",$data);
        $this->view("topics",$data);
      break;
      case "posts":
        $this->view("userinfo",$data);
        $this->view("posts",$data);
      break;
      case "images":
        $this->view("userinfo",$data);
        $this->view("images",$data);
      break;
      case "login":
      default:
       $this->view("menu_login",$data);
       $this->view("login",$data);
       $this->view("register",$data);
    }
    $this->view("footer",$data);
  } 
    
//-----------------------------------------
//---------- metoda view() -------------
//-----------------------------------------    
  public function view($view,$data=NULL,$tostring=false){
      $buf="";
      if($data) extract($data);
      if($tostring) ob_start();
      include("view/$view.php");
      if($tostring) { 
         $buf = ob_get_contents();
         ob_end_clean();
         return $buf;
      }
  }
  
  protected function reload(){
     header("Location: $this->baseurl");
     exit;
  }
  
} //------ end of class Form ---------------------------------------------------   