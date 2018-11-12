<?php
session_start(); 
$username = 'nmasalykina';
$password = 'neto1828';
$pdo = new PDO('mysql:host=localhost;dbname=nmasalykina;charset=utf8', $username, $password);
if(!empty($_POST)) {
    $login = ($_POST['login']);
    $password = ($_POST['password']);
    $query = $pdo->prepare('SELECT login, password FROM user WHERE login = ?');
    $query->execute([$login]);
    $userData = $query->fetch();
}
// Вход на сайт
if (isset($_POST['sign_in'])) {
   if ($userData && password_verify($_POST['password'], $userData['password'])) {
       $_SESSION['login'] = $login;
       header('Location: list.php');
   } else {
       echo 'Ошибка! Неверные данные.';
   }
}
// Регистрация
if (isset($_POST['sign_up'])) {
    if ($userData == false) {
    $reg = $pdo->prepare('INSERT INTO  user (login, password) VALUES (?,?)');
    $reg->execute([$login, $password]);
    $_SESSION['login'] = $login;
    header('Location: list.php');
    } else { 
        echo 'Такой пользователь уже есть!';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Домашнее задание к лекции 4.3 «SELECT из нескольких таблиц»
</title>
<link href='css/style.css' rel='stylesheet' type='text/css' >
</head>
<body>
  <div class="login">
  	<?php if (!isset($_SESSION['login'])) { ?>
	<h1>Войдите или зарегистрируйтесь:</h1>
    <form method="post" class="index">
    	<input name="login" placeholder="Логин"><br>
    	<input type="password" name="password" placeholder="Пароль"><br><br>
        <button type="submit" name="sign_in" class="btn btn-primary btn-block btn-large">Вход</button><br>
        <button type="submit" name="sign_up" class="btn btn-primary btn-block btn-large">Регистрация</button>
    </form>
    <?php } else { ?>
    <h1>Вы уже вошли на сайт <?php echo ", ".$_SESSION['login'] ?> !</h1>
    <p><a href="list.php">Вернуться обратно</a></p>
    <p><a href="core/logout.php">Выйти</a></p>
<?php } ?>
</div>
</body>
</html>
