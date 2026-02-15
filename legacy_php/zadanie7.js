//-----------------------------------------------------------------------------
// zadanie7.js
//-----------------------------------------------------------------------------
//
var modal_width = 450;
// funkcje pomocnicze
function modal_center(item){
   $(item).css({"position":"fixed",
                "width":modal_width.toString()+"px",
                "left":((window.innerWidth-modal_width)/2).toString()+"px",
                "top":((window.innerHeight-$(item).height())/2).toString()+"px"
                });
}
function modal_slideToggle(item){
   modal_center(item);
   $(item).slideToggle();
};
// kod wykonywany po załadowaniu całej strony
$(document).ready(function(){
   $(".modal").hide();
   $(".modal").find("form").prepend("<a href='' class='close_modal'>X</a>");
   $("a.close_modal").click( function(event){
        $(this).parents(".modal").hide();
        event.preventDefault();
   });
   $("nav a[href='#login']").click( function(event){
        modal_center("#login");
        $("nav a[href='#register']").show();
        $("nav a[href='#login']").hide();
        $("#register").hide();
        $("#login").slideDown();
        event.preventDefault();
   });
   $("nav a[href='#register']").click( function(event){
        modal_center("#register");
        $("nav a[href='#register']").hide();
        $("nav a[href='#login']").show();
        $("#login").hide();
        $("#register").slideDown();
        event.preventDefault();
   }).hide();
   $("#addtopic").click( function(event){
        $("#modal_topic h2").html("Dodaj nowy temat do dyskusji");
        modal_slideToggle("#modal_topic");
        event.preventDefault();
   });
   $("#addpost").click( function(event){
        modal_slideToggle("#modal_post");
        event.preventDefault();
   });
   $("#uploadfile").click( function(event){
     modal_slideToggle("#modal_file");
     event.preventDefault();
   });
   $("#imgedit").click( function(event){
     modal_slideToggle("#modal_fileedit");
     event.preventDefault();
   });
   // zastosowanie pobierania danych z pomocą AJAX
   $("nav a.topicedit").click( function(event){
        // wstawia napis oraz numer tematu do nagłówka form.
        $("#modal_topic h2").html("Edycja tematu ID: <span topicid=\""+$(this).attr("topicid")+"\">"+$(this).attr("topicid")+"</sapn>");
        // pobiera dane z serwera metodą GET
        $.get("?cmd=gettopic&topicid="+$(this).attr("topicid"),
              // pobrane dane są przekazywane w data fo funkcji,
              // funkcja odpowiada za wykorzystanie pobranych danych
              // oczekiwane są dane w formacie JSON 
              function( data, status){
               // tworzy obiekt topic z napisu o formacie JSON
               try{
                    var topic=JSON.parse(data);
               }catch(e){
                    alert(data);
                    return;
               };
               // dane są umieszczane w polach form.
               $("#modal_topic [name='topic']").val(topic.topic).focus(); 
               $("#modal_topic [name='topic_body']").val(topic.topic_body);
               $("#modal_topic [name='topicid']").val(topic.topicid);
        });
        modal_slideToggle("#modal_topic");
        event.preventDefault();
   });
// ------------------- do uzupełnienia ----------------------------------------
// kod obsługi dla: dodawania postów, edycji postów, dodawania obrazków,
// edycji podpisu pod obrazkiem, oraz obsługa odpowiednich 'przycisków'
//

   // dzialamy z postami
   $("nav a.postedit").click( function(event){
     $("#modal_post h2").html("Edycja postu ID: <span postid=\""+$(this).attr("postid")+"\">"+$(this).attr("postid")+"</sapn>");
     $.get("?cmd=getpost&postid="+$(this).attr("postid"), 
           function( data, status){
               try{
                    var post=JSON.parse(data);
               }catch(e){
                    alert(data);
                    return;
               };
            $("#modal_post [name='post']").val(post.post).focus();
            $("#modal_post [name='postid']").val(post.postid);
     });
     modal_slideToggle("#modal_post");
     event.preventDefault();
     });

     // dzialamy z obrazkami
     $("nav a.uploadfile").click( function(event){
          $("#modal_file h2").html("Dodaj ilustrację do wpisu ID: <span postid=\""+$(this).attr("postid")+"\">"+$(this).attr("postid")+"</sapn>");
          $.get("?cmd=getpost&postid="+$(this).attr("postid"),
                function( data, status){
                    try{
                         var file=JSON.parse(data);
                    }catch(e){
                         alert(data);
                         return;
                    };
                 $("#modal_file [id='pid']").val(file.pid);
                 $("#modal_file [name='imagetitle']").val(file.imagetitle);
                 $("#modal_file [name='postid']").val(file.postid);
          });
          modal_slideToggle("#modal_file");
          event.preventDefault();
     });

     // dzialamy z obrazkami edycja opisu obrazka
     $(".imgedit").click( function(event){
          $("#modal_fileedit h2").html("Edytuj podpis obrazka o ID: <span imgid=\""+$(this).attr("imgid")+"\">"+$(this).attr("imgid")+"</sapn>");
          $.get("?cmd=getimage&imgid="+$(this).attr("imgid"),
                function( data, status){
                    try{
                         var file=JSON.parse(data);
                    }catch(e){
                         alert(data);
                         return;
                    };
                 $("#modal_fileedit [name='imagetitle']").val(file.imagetitle);
                 $("#modal_fileedit [name='imgid']").val(file.id);
          });
          modal_slideToggle("#modal_fileedit");
          event.preventDefault();
     });

//
// ------------------- do uzupełnienia ----------------------------------------
   
   $("article.topic").mouseenter(function(){
     $(this).find("footer").css("background-color", "#ccc");
   });
   $("article.topic").mouseleave(function(){
     $(this).find("footer").css("background-color", "#ddd");
   });
}); 