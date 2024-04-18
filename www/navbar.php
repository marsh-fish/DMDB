<!-- Custom styles for this template -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>


<nav>
    <ul>
        <li class="nav-item ms-5"><a href="./welcome.php#">Home</a></li>
        <li class="nav-item"><a href="./upload.php">Upload</a></li>
        <li class="nav-item"><a href="#">Team</a></li>
        <li class="nav-item me-5"><a href="#">Contact</a></li>
        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
        <li class="nav-item ms-5">
            <!-- 使用Font Awesome的user圖標 -->
            <a class="nav-link" href="./user.php?user_id=<?php echo htmlspecialchars($_SESSION["username"]); ?>">
            <i class="fa fa-user" aria-hidden="true"><?= $_SESSION["username"]; ?></i>
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item ms-0">
        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
            <a class="nav-link" href="logout.php">Logout</a>
        <?php else: ?>
            <a class="nav-link" href="login.php">Login</a>
        <?php endif; ?>
        </li>
    </ul>
</nav>
