<?php
require_once(__DIR__ . '/dbconfig.php');
include 'toast.php';


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION["loggedin"])){
    header("location: ./welcome.php");
    exit;
}



if($_SERVER["REQUEST_METHOD"] == "POST"){
    // 取得 POST 過來的資料
    $username=mb_convert_case($_POST["username"], MB_CASE_UPPER, "UTF-8");
    $password=$_POST["password"];
    $password_hash=password_hash($password,PASSWORD_DEFAULT);

    // 以帳號進資料庫查詢
    $sql = "SELECT `user_id`, `username`, `password`, `name`, `email` FROM `member` WHERE `username`=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $result_user_id, $result_username, $result_password, $result_name, $result_email);
    $fetched = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // 驗證密碼，先檢查是否有查詢到資料
    if($fetched && password_verify($password, $result_password)){
        // 密碼通過驗證
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // 把資料存入 Session
        $_SESSION["loggedin"] = true;
        $_SESSION["user_id"] = $result_user_id;
        $_SESSION["username"] = $result_username;
        $_SESSION["name"] = $result_name;
        $_SESSION["email"] = $result_email;
        // 轉跳到會員頁面
        header("location: ./welcome.php");
        exit;
    }else{
        // 密碼驗證失敗
        showToastOnLoad('帳號或密碼錯誤. Incorrect ID or Password.', "danger");
    }
}

// Close connection
mysqli_close($conn);
include 'toast.html';
?>
<!doctype html>
<html lang="zh-hant">

<head>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="login.css" rel="stylesheet">
    <title>登入範例</title>
</head>

<body class="text-center">
    <main class="form-signin w-100 m-auto">
        <form method="POST" action="">
            <h2 class="h2 mb-2 fw-bold">2024 資料庫系統</h2>
            <h1 class="h1 mb-4 fw-bold">登入範例</h1>

            <div class="form-floating">
                <input type="text" class="form-control" id="inputUsername" placeholder="帳號" name="username">
                <label for="inputUsername">帳號</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="inputPassword" placeholder="密碼" name="password">
                <label for="inputPassword">密碼</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary fw-bold" type="submit">登入</button>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
</body>

</html>
