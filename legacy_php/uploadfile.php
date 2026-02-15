<?php
class Uploadfile extends Datatable {
    public $image = "image";
    public $error = "";
    public $folder = "files/";
    public $prefix = "";
    
public function insert($file){
    switch($file['type']){
       case "image/jpg":
       case "image/jpeg":
         $sufix = ".jpg";
       break;
       case "image/gif":
         $sufix = ".gif";
       break;
       case "image/png":
         $sufix = ".png";
       break;
       default:
         $this->error = "Bad file type!";
         return false;
    }
    parent::insert( array( 
       "userid"=>$_SESSION['user']['userid'],
       "postid"=>$_POST["postid"],
       "topicid"=>$_SESSION["topicid"],
       "name"=>basename($file['name'],$sufix),
       "sufix"=>$sufix,
       "title"=>($_POST["imagetitle"]!='')?$_POST["imagetitle"]:basename($file['name'],$sufix),
       "date"=>date("Y-m-d H:i:s")
    ));
    $img = parent::getLastItem();
    if( move_uploaded_file($file['tmp_name'], $this->folder.$this->prefix.$img["id"].$sufix ) ){
       return $img["id"];
    }else{
       parent::delete($img["id"]);
       $this->error = "Upload error!";
       return false;
    }  
}

public function update($id, $title=""){
    $img = $this->get($id);
    $img["title"]=($title!="")?$title:$img["name"];
    return parent::update( $img );
}

public function delete($id,$key=false){
    if( !($img = $this->get($id)) ) return false; 
    if( unlink($this->folder.$this->prefix.$img["id"].$img["sufix"]) )
       return parent::delete($id);
}

public function delete_from_post( $postid ){
   $result = true;
   if( $img = $this->getAll( $postid, "postid" ) )
     foreach( $img as $k=>$v ) 
        if(!$this->delete($k)) 
            $result=false;
   return $result;      
}

public function send($id, $width=0){
    $font = 'fonts/stormfaze.ttf';
    $fontsize = 18;
    $textangle = random_int(-25, 25);
    $text = date("Y-m-d H:i:s"); 
    
    if( !($img = $this->get($id)) ) {
       $this->error = "Send id error: bad ID";
       return false;
    }
    switch($img["sufix"]){
      case ".jpg":
         $imdata = imagecreatefromjpeg($this->folder.$this->prefix.$img["id"].$img["sufix"]);
      break;
      case ".gif":
         $imdata = imagecreatefromgif($this->folder.$this->prefix.$img["id"].$img["sufix"]);
      break;
      case ".png":
         $imdata = imagecreatefrompng($this->folder.$this->prefix.$img["id"].$img["sufix"]);
      break;  
      default:
        $this->error = "Send error: bad image extension";
        return false;
    }  
    if( !$imdata ){
       $this->error = "Send error: bad image file";
       return false;
    }
    if( $width ) $imdata = imagescale($imdata, $width );

    $color  = imagecolorallocate($imdata, 0, 255, 0);
    $textbox = imagettfbbox( $fontsize, $textangle , $font , $text );
    imagettftext($imdata, $fontsize, $textangle, 
                (imagesx($imdata)-($textbox[4]-$textbox[0]))/2,
                (imagesy($imdata)-($textbox[3]-$textbox[6])+$fontsize)/2, 
                $color, $font, $text);

    ob_start();
    header('Content-Type: image/png');
    imagepng($imdata);
    $buf = ob_get_contents();
    ob_end_clean();
    
    imagedestroy($imdata);
    return $buf; 
}

} // -------- end of Uploadfile -----------