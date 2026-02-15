<section>
  <nav>
  <table><tr>
  <td style="width: 33.3%;"></td>
  <td  style="width: 33.3%;">
    <a href="<?=$this->baseurl?>?cmd=topics">Lista tematów</a>
  </td>
  <td  style="width: 33.3%;"></td>
  </tr></table>
  </nav>

  <article  class="topic">
    <header>Temat dyskusji: <b><?=htmlentities($topic['topic'])?></b></header>
    <div><?=nl2br(htmlentities($topic['topic_body']))?></div>
    <footer>
    ID: <?=$topic['topicid']?>, Autor: <?=htmlentities($users[$topic['userid']]['username'])?>, Data: <?=$topic['date']?>
    </footer>
  </article>
  <nav><a href="#" id="addpost" >+ Dodaj wpis</a></nav>  
<?php if( !$posts ){ ?>
  <p>To forum nie zawiera jeszcze żadnych głosów w dyskusji!</p>
<?php 
   }else{
      foreach($posts as $k=>$v){ 
?>
  <article class="post">
  <div><?=nl2br(htmlentities($v['post']))?><br />
<?php 
   if($images){ 
      foreach($images as $imgid=>$img){ 
         if($img["postid"]!=$v["postid"]) continue; 
?>
  <div class="image">
  <img src="?image=<?=$img["id"]?>" /><br />
  <?=$img["title"]?><br />
<?php 
         if($this->u['userlevel']==10 or $this->u['userid']==$v['userid']) { 
?>
  <a href="#" imgid="<?=$img["id"]?>" class="imgedit" >EDYTUJ</a> 
  <a class="danger" href="?cmd=imgdelete&imgid=<?=$img["id"]?>" >KASUJ</a>
<?php } ?>
  </div>
  <?php } } ?>
  </div>
  <footer>
  <nav>
  <?php if( $this->u['userlevel']==10 or $this->u['userid']==$v['userid']){ ?>
  <a href="#" postid="<?=$v['postid']?>" class="postedit">EDYTUJ</a>  
  <a href="#" postid="<?=$v['postid']?>" class="uploadfile" >+ OBRAZEK</a>
  <a class="danger" href="?&id=<?=$v['postid']?>&cmd=delete">KASUJ</a> 
<?php } ?>  
  </nav>
  ID: <?=$v['postid']?>, Autor: <?=htmlentities($users[$v['userid']]['username'])?>, Utworzono dnia: <?=$v['date']?>
  <div style="clear:both;"></div>    
  </footer>
  </article>
<?php  } } ?>

  <div class="modal" id="modal_post">
  <form action="<?=$this->baseurl?>" method="post" enctype="multipart/form-data">
     <header><h2>Dodaj nową wypowiedź do dyskusji</h2></header>  
     <textarea name="post" autofocus cols="80" rows="10" placeholder="Wpisz tu swoją wypowiedź." ></textarea><br />
     <input type="hidden" name="postid" value="" />
     <button type="submit" >Zapisz</button>
  </form>
  </div>
  
  <div class="modal" id="modal_file">
  <form action="<?=$this->baseurl?>" method="post" enctype="multipart/form-data">
  <header><h2>Dodaj ilustrację do wpisu ID: <span id="pid"></span></h2></header>
  <input type="file" name="image" > 
  <input type="text" name="imagetitle" value="" placeholder="Opis pliku" /> 
  <button type="submit" >Prześlij</button>
  <input type="hidden" name="postid" value="" />
  </form>
  </div>

  <div class="modal" id="modal_fileedit">
  <form action="<?=$this->baseurl?>" method="post" enctype="multipart/form-data">
  <header><h2>Edytuj podpis</h2></header>
  <input type="text" name="imagetitle" value="" placeholder="Opis pliku" /> 
  <button type="submit" >Zapisz</button>
  <input type="hidden" name="imgid" value="" />
  </form>
  </div>
  
</section>
