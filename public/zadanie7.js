var modal_width = 450;

function modal_center(item) {
   $(item).css({
      "position": "fixed",
      "width": modal_width.toString() + "px",
      "left": ((window.innerWidth - modal_width) / 2).toString() + "px",
      "top": ((window.innerHeight - $(item).height()) / 2).toString() + "px"
   });
}

function modal_slideToggle(item) {
   modal_center(item);
   $(item).slideToggle();
}

$(document).ready(function () {
   $(".modal").hide();
   $(".modal").find("form").prepend("<a href='' class='close_modal'>X</a>");

   $("a.close_modal").click(function (event) {
      $(this).parents(".modal").hide();
      event.preventDefault();
   });

   $("nav a[href='#login']").click(function (event) {
      modal_center("#login");
      $("nav a[href='#register']").show();
      $("nav a[href='#login']").hide();
      $("#register").hide();
      $("#login").slideDown();
      event.preventDefault();
   });

   $("nav a[href='#register']").click(function (event) {
      modal_center("#register");
      $("nav a[href='#register']").hide();
      $("nav a[href='#login']").show();
      $("#login").hide();
      $("#register").slideDown();
      event.preventDefault();
   }).hide();

   if ($("#login").length) {
      $("nav a[href='#login']").trigger("click");
   }

   $("#addtopic").click(function (event) {
      $("#modal_topic [name='topic']").val("");
      $("#modal_topic [name='topic_body']").val("");
      $("#modal_topic [name='topicid']").val("");
      $("#modal_topic h2").text("Dodaj nowy temat do dyskusji");
      modal_slideToggle("#modal_topic");
      event.preventDefault();
   });

   $("nav a.topicedit").click(function (event) {
      var topicId = $(this).attr("topicid");
      $("#modal_topic h2").html("Edycja tematu ID: <span topicid='" + topicId + "'>" + topicId + "</span>");
      $.get("?cmd=gettopic&topicid=" + topicId, function (data) {
         $("#modal_topic [name='topic']").val(data.topic).focus();
         $("#modal_topic [name='topic_body']").val(data.topic_body);
         $("#modal_topic [name='topicid']").val(data.topicid);
      });
      modal_slideToggle("#modal_topic");
      event.preventDefault();
   });

   $("#addpost").click(function (event) {
      $("#modal_post h2").text("Dodaj nową wypowiedź do dyskusji");
      $("#modal_post [name='post']").val("");
      $("#modal_post [name='postid']").val("");
      modal_slideToggle("#modal_post");
      event.preventDefault();
   });

   $("a.postedit").click(function (event) {
      var postId = $(this).attr("postid");
      $("#modal_post h2").html("Edycja wpisu ID: <span postid='" + postId + "'>" + postId + "</span>");
      $.get("?cmd=getpost&postid=" + postId, function (data) {
         $("#modal_post [name='post']").val(data.post).focus();
         $("#modal_post [name='postid']").val(data.postid);
      });
      modal_slideToggle("#modal_post");
      event.preventDefault();
   });

   $("a.uploadfile").click(function (event) {
      var postId = $(this).attr("postid");
      $("#modal_file #pid").text(postId);
      $("#modal_file [name='postid']").val(postId);
      $("#modal_file [name='image']").val("");
      $("#modal_file [name='imagetitle']").val("");
      modal_slideToggle("#modal_file");
      event.preventDefault();
   });

   $("a.imgedit").click(function (event) {
      var imgId = $(this).attr("imgid");
      $.get("?cmd=getimage&imgid=" + imgId, function (data) {
         $("#modal_fileedit [name='imagetitle']").val(data.title).focus();
         $("#modal_fileedit [name='imgid']").val(data.id);
      });
      modal_slideToggle("#modal_fileedit");
      event.preventDefault();
   });

   $("article.topic").mouseenter(function () {
      $(this).find("footer").css("background-color", "#ccc");
   });

   $("article.topic").mouseleave(function () {
      $(this).find("footer").css("background-color", "#ddd");
   });
});
