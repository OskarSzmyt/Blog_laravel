<?php
class Captcha {
   public $code="";
   
   private $sx = 200;
   private $sy = 50;
   private $rotation = 50;
   
   public function __construct(){
       if( isset($_SESSION["captcha"]) ) $this->code=$_SESSION["captcha"]; 
   }
   
   public function show(){
     $font = array( 'fonts/stormfaze.ttf', 
                    'fonts/hemihead.ttf',
                    'fonts/leadcoat.ttf',
                    'fonts/stocky.ttf',
                    'fonts/arial.ttf' );
     $this->code =  chr(random_int(ord('A'),ord('Z')))
                   .chr(random_int(ord('A'),ord('Z')))
                   .chr(random_int(ord('A'),ord('Z')))
                   .chr(random_int(ord('A'),ord('Z')));
     $_SESSION["captcha"]=$this->code;     
     header ('Content-Type: image/png');
     $im = @imagecreatetruecolor($this->sx, $this->sy);
     $background_color = imagecolorallocate($im, 0, 0, 0);
     imagecolortransparent ( $im, $background_color  );
     for( $n=0;$n<strlen($this->code);$n=$n+1){
       $color[$n] = imagecolorallocate($im, 150, 150, 150);
       
       imagettftext($im, random_int(20,28), random_int(-$this->rotation,$this->rotation),10+($n*($this->sx/4)), 30, 
                $color[$n], $font[random_int(0,4)], $this->code[$n] );
     }           
     imagepng($im);
     imagedestroy($im); 
     exit;          
   }
   
   public function check($code){
     return ($code==$this->code)?true:false;
   }

} // -------- end of captcha -----------