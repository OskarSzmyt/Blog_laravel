<?php
session_start();
include("forum.php");
$forum = new Forum( );
$forum->makepage( $forum->process() );
