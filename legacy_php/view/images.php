<section class="images-table">
<table>
<caption>Lista plików graficznych</caption>
<tr><th>Id</th><th>Obrazek</th><th>Uczestnik</th><th>Post</th><th>Nazwa</th><th>Data</th><th></th></tr>
<?php if($images) foreach( $images as $k=>$img ){ ?>
<tr>
<td><?=$k?></td>
<td ><img src="?image=<?=$img["id"]?>" /><br /><?=($img["title"]!=""?$img["title"]:"- brak -")?></td>
<td><?=$img["userid"]?><br />[<?=$users[$img["userid"]]["username"]?>]</td>
<td><?=$img["postid"]?></td>
<td><?=$img["name"].$img["sufix"]?></td>
<td><?=$img["date"]?></td>
<td><nav><a class="danger" href="?cmd=imgdelete&imgid=<?=$img["id"]?>" >KASUJ</a></nav></td>
</tr>
<?php } ?>
</table>
</section>
