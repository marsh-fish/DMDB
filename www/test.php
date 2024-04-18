<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION["loggedin"])){
    header("location: ./login.php");
    exit;
}

$userIp = "";
if (!empty($_SERVER["HTTP_CLIENT_IP"])){
    $userIp = $_SERVER["HTTP_CLIENT_IP"];
}elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
    $userIp = $_SERVER["HTTP_X_FORWARDED_FOR"];
}else{
    $userIp = $_SERVER["REMOTE_ADDR"];
}
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="test.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>

<table class="table table-striped zotero-table" id="zotero-table">
    <thead class="thead-dark">
        <tr>
        <th>Title</th>
        <th>Author(s)</th>
        <th>Publication</th>
        <th>Date</th>
        </tr>
    </thead>
    <tbody id="content" class="zotero-content">
    </tbody>
    </table>
<section id="screen1">

<p>Scroll down</p>

<nav>
    <ul>
<li><a href="#">Home</a></li>
        <li><a href="#">About</a></li>
        <li><a href="#">Services</a></li>
        <li><a href="#">Team</a></li>
        <li><a href="#">Contact</a></li>
    </ul>
</nav>

</section>

<section id="screen2"></section>
<section id="screen3"></section>


<script>
    $(document).ready(function(){
    $(window).bind('scroll', function() {
    var navHeight = $( window ).height() - 70;
            if ($(window).scrollTop() > navHeight) {
                $('nav').addClass('fixed');
            }
            else {
                $('nav').removeClass('fixed');
            }
        });
    });
</script>

</body>
</html>
