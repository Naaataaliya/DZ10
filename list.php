<?php 
session_start();
if (isset($_SESSION['login'])) {
    $login = $_SESSION['login'];
} else { 
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found'); 
    die('404 Not Found');
}
$button = 'Добавить';
$errors = "";
$username = 'root';
$password = '';
$pdo = new PDO('mysql:host=localhost;dbname=test;charset=utf8', $username, $password);

if (isset($_POST['save'])) {
        $description = $_POST['description'];
        $id = $_POST['id'];
    if ($id) {
        $editTask = $pdo->prepare("UPDATE task SET description = ? WHERE id = ? LIMIT 1");
        $editTask->execute([$description, $id]);
        header('Location: list.php');
    } else {
        $currentUser = $pdo->prepare("SELECT id, login FROM user WHERE login = ?");
        $currentUser->execute([$login]);
        $user = $currentUser->fetch();
        $addTask = $pdo->prepare("INSERT INTO task (description, is_done, date_add, user_id, assigned_user_id) VALUES (?, ?, now, ?, ?)");
        $addTask->execute([$description, false, $user['id'], $user['id']]);
        header('Location: list.php');
    }
}
if (isset($_GET['action'])) {
	// задача выполнена
    if ($_GET['action'] === 'done') {
        $sql_query = 'UPDATE task SET is_done = 1 WHERE id = ?';
        $rows = $pdo->prepare($sql_query);
        $rows->execute([$_GET['id']]);
        header('Location: list.php');
    }
    // редактировать задачу
    if ($_GET['action'] === 'edit') {
        $sql_query = 'SELECT * FROM task WHERE id = ?';
        $rows = $pdo->prepare($sql_query);
        $rows->execute([$_GET['id']]);
        $description = $rows->fetch()['description'];
        $button = 'Сохранить';
    }
    //удалить задачу
    if ($_GET['action'] === 'delete') {
        $sql_query = "DELETE FROM task WHERE id = ?";
        $rows = $pdo->prepare($sql_query);
        $rows->execute([$_GET['id']]);
        header('Location: list.php');
    }
}
$users = $pdo->query('SELECT login FROM user');
$users = $users->fetchAll(PDO::FETCH_ASSOC);
//переложить ответственность
if (isset($_POST['assign'])) {
        $assign_to = $pdo->quote($_POST['assign_to']);
        $taskId = $_POST['id'];
        $assign_user_id = $pdo->query("SELECT id FROM user WHERE login = $assign_to")->fetch()['id'];
        $assignPrep = $pdo->prepare("UPDATE task SET assigned_user_id = ? WHERE id = ? LIMIT 1");
        $assignPrep->execute([$assign_user_id, $taskId]);
    }
 ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Занятие 4.2. Запросы SELECT, INSERT, UPDATE и DELETE</title>
    <link href='css/style.css' rel='stylesheet' type='text/css' >
</head>
<body>
    <h1>Добро пожаловать, <?php echo "$login"; ?></h1>
    <h2>Список задач:</h2>
    <form method="POST">
        <?php if (isset($errors)) { ?>
        <p><?= $errors; ?></p>
        <?php } ?>
        <input type="hidden" name="id" value="<?php if (isset($_GET['id'])){echo($_GET['id']);}?>">
        <input type="text" name="description" required placeholder="Описание задачи" value="<?php if (isset($description)) {echo($description);} ?> " />
        <button type="submit" name="save" value="<?php echo($button); ?> class="btn btn-primary btn-block btn-large">Добавить</button>
    </form>
<br>
<table>
    <tr>
        <th>Описание задачи</th>
        <th>Дата добавления</th>
        <th>Статус</th>
        <th>Действие</th>
        <th>Ответственный</th>
        <th>Автор</th>
        <th>Закрепить задачу за другим пользователем</th>
    </tr>
    <?php
    $select = "SELECT t.id as task_id, t.description as description, u.id as author_id, u.login as author_name, au.id as assigned_user_id, au.login as assigned_user_name, t.is_done as is_done, t.date_added as date_added FROM task t INNER JOIN user u ON u.id=t.user_id INNER JOIN user au ON t.assigned_user_id=au.id WHERE u.login = ?";
    $result = $pdo->prepare($select);
    $result->execute([$login]);
    while ($row = $result->fetch()) {
    ?>
    <tr>
        <td><?= $row['description']; ?></td>
        <td><?= $row['date_added']; ?></td>
        <td>
            <?php  
            if ($row['is_done'] == 1) {
                echo '<span style="color: green">Выполнено</span>';
            } elseif ($row['is_done'] == 0) {
                echo '<span style="color: red">Выполнить</span>';
            }
            ?>   
        </td>
        <td>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=edit'>Изменить</a>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=done'>Выполнить</a>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=delete'>Удалить</a>
        </td>
        <td><strong><?= $row['assigned_user_name']; ?></strong></td>
        <td><strong><?= $row['author_name']; ?></strong></td>
        <td><form method="post">
                <input type="hidden" name="id" value="<?php echo($row['task_id']); ?>">
                <select name="assign_to" class="btn btn-primary btn-block btn-large" style="background: lightblue">
                    <?php 
                        foreach ($users as $user) {
                            if ($user['login'] !== $login)
                            {
                                echo '<option>' . $user['login'] . '</option>';
                            }
                        }
                     ?>
                </select><br><br>
                <input type="submit" value="Переложить ответственность" name="assign" class="btn btn-primary btn-block btn-large">
            </form>
        </td>
    </tr>
    <?php  } ?>
</table><br>
<h2>Также, посмотрите, что от Вас требуют другие люди:</h2>
    <table>
    <tr>
        <th>Описание задачи</th>
        <th>Дата добавления</th>
        <th>Статус</th>
        <th>Действия</th>
        <th>Ответственный</th>
        <th>Автор</th>
    </tr>

<?php
$select = "SELECT t.id as task_id, t.description as description, u.id as author_id, u.login as author_name, au.id as assigned_user_id, au.login as assigned_user_name, t.is_done as is_done, t.date_added as date_added FROM task t INNER JOIN user u ON u.id=t.user_id INNER JOIN user au ON t.assigned_user_id=au.id WHERE au.login = ? AND u.login <> ?";
$result = $pdo->prepare($select);
$result->execute([$login, $login]);
while ($row = $result->fetch()) {
?>
<tr>
    <td><?php echo $row['description'];?></td>
    <td><?php echo $row['date_added']; ?></td>
    <td>
            <?php  
            if ($row['is_done'] == 1) {
                echo '<span style="color: green">Выполнено</span>';
            } elseif ($row['is_done'] == 0) {
                echo '<span style="color: red">Выполнить</span>';
            }
            ?>   
        </td>
        <td>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=edit'>Изменить</a>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=done'>Выполнить</a>
            <a href='list.php?id=<?php echo($row['task_id'])?>&action=delete'>Удалить</a>
        </td>
        <td><strong><?= $row['assigned_user_name']; ?></strong></td>
        <td><strong><?= $row['author_name']; ?></strong></td>
</tr>
<?php } ?>     
</table><br>
<p class="logout"><a href="core/logout.php">Выйти</a></p>
</body>
</html>