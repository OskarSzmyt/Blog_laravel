<?php if(!isset($error)) $error=""; ?>
<section  class="modal" id="register">
  <form action="<?=$this->baseurl;?>" method="post">
     <a name="newuser_form"></a>
     <header><h2>Jesli nie jesteś zarejestrowany, to możesz zapisać się do forum.</h2></header>  
     <input type="text" name="userid" placeholder="Nazwa logowania (dozwolone są tylko: litery, cyfry i znak '-')" pattern="[A-Za-z0-9\-]*" autofocus \><br />
     <input type="text" name="username" placeholder="Imię autora" \><br />
     <input type="password" name="pass1" placeholder="Hasło" \><br />
     <input type="password" name="pass2" placeholder="Powtórz hasło" \><br />
     <div style="text-align:center;margin:10px 0;">
     <img src="?capthaimg=1" alt="captcha" title="Wpisz kod kontrolny z obrazka" onclick="this.src='?capthaimg='+Math.random();" /><br />
     'kliknij' na obrazek by zmieńić kod kontrolny<br />
     <input style="width:300px;" type="text" name="captcha" placeholder="Wpisz kod kontrolny z obrazka" \></div>
     <br />
     <?="<div class=\"error\">$error</div>";?>
     <button type="submit" >Zapisz się do forum</button>
  </form>
</section>    
