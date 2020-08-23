<?php

include_once 'core/session.php';
include_once 'core/login_model.php';
//ini_set("display_errors", 1);

$msg = '';

if (isset($_POST['login']) && !empty($_POST['username']) && !empty($_POST['password'])) {
    
    $model = new LoginModel();
    $loginData['username'] = $_POST['username'];
    $loginData['password'] = $_POST['password'];
    
    $user = $model->checkLogin($loginData);
    
    if ($user) {
        $_SESSION['valid'] = "1";
        $_SESSION['timeout'] = time();
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
    
        //print_r ($user);
        $url = parse_url();

        header('Location: cpanel.php'); 
    } else {
        $msg = 'Wrong username or password';
    }
}

<html lang = "en">

    <head>
        <link href = "bower_components/bootstrap/dist/css/bootstrap.min.css" rel = "stylesheet">
        <link href = "bower_components/bootstrap/dist/css/bootstrap-theme.min.css" rel = "stylesheet">
        <link href = "assets/css/style.css" rel = "stylesheet">

        <script src = "bower_components/jquery/dist/jquery.min.js"></script>  
        <script src = "bower_components/bootstrap/dist/js/bootstrap.min.js"></script>  
    </head>

    <body class="login_page">
        <div class = "container">
            <form class="form-signin" role = "form" 
                  action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                  method = "post" 
                  style="max-width: 300px; margin: 0 auto; margin-top:100px;">
                <h2 class="form-signin-heading">Login</h2>
                <h4 class = "form-signin-heading"><?php echo $msg; ?></h4>

                <input type="text" class="form-control" 
                       name="username" placeholder="Username" 
                       required autofocus>
                <input type="password" class="form-control" 
                       name="password" placeholder="Password" required style="margin-top: 5px;">

                <button class="btn btn-lg btn-primary btn-block" 
                        type="submit" name = "login" style="margin-top: 20px;">Sign in </button>

                <br/>
            </form>    
        </div> 
    </body>
</html>
