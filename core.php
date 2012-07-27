<?php
session_start();
/* Данные для подключения к БД */
$server = "localhost";
$username = "ngbizua";
$password = "ngbizua123";
$db = "korotchenkocom";

/* Фильтрация всех получаемых переменных */
$start = htmlspecialchars(stripslashes($_POST['start']));
$name = htmlspecialchars(stripslashes($_POST['name']));
$msg = htmlspecialchars(stripslashes($_POST['msg']));
$update = htmlspecialchars(stripslashes($_POST['update']));
$id = htmlspecialchars(stripslashes($_POST['id']));
/* Соединение с БД и если не удачное, то отправка ошибок клиенту */
$connect = mysql_connect($server, $username, $password);
if (!$connect) {
    $data['err'] = 1;
    echo json_encode($data);
    exit;
}
$db_sel = mysql_select_db($db,$connect);
if (!$db_sel) {
    $data['err'] = 2;
    echo json_encode($data);
    exit;
}
# Установка языка записи в БД
mysql_query("SET NAMES utf8");
/* Если игрок загрузил страницу чата, то наш script.js передает серверу данные $_POST['start'] вот и обрабатываем эти данные */
if (!empty($start)) {
    /* Если не существует сессия name, то возвращаем 0, в другом случае указываем логин игрока, который хранится в сессии, передаем id последнего сообщения */
    if (empty($_SESSION['name'])) {
        $data['ans'] = 0;
    } else {
        $data['ans'] = 1;
        $data['login'] = $_SESSION['name'];
        $msg_row = mysql_fetch_array(mysql_query("SELECT 'post_id' FROM post ORDER BY 'post_id' ASC"));
        $data['id'] = $msg_row['post_id'];
    }
    echo json_encode($data); // Отправляем данные в формате json
    exit;
}
/* Если переменная $name не пуста, то выполняем код */
if (!empty($name)) {
    /* Ищем игрока в БД */
    $sql = mysql_query("SELECT * FROM users WHERE `user_name`='".$name."'");
    if (mysql_num_rows($sql) == 0) {
        /* Если не находим, то создаем новую запись, запоминаем логин и передаем клиенту логин и id последнего сообщения */
        mysql_query("INSERT INTO users (`user_name`,`user_online`,`last_update`) VALUES ('".$name."','1','".time()."')");
        $data['ans'] = 0;
        $_SESSION['name'] = $name;
        $data['login'] = $_SESSION['name'];
        $msg_row = mysql_fetch_array(mysql_query("SELECT 'post_id' FROM post ORDER BY 'post_id' ASC"));
        $data['id'] = $msg_row['post_id'];
    } else {
        /* Если игрок в БД уже есть, то проверяем онлайн он или нет. Если онлайн, то выдаем ошибку, если нет, то делаем его онлайн и передаем нужные данные клиенту */
        $row = mysql_fetch_array($sql);
        if ($row['user_online'] == 0) {
            mysql_query("UPDATE users SET `user_online`='1' WHERE `user_name`='".$name."'");
            $data['ans'] = 0;
            $data['login'] = $_SESSION['name'];
            $msg_row = mysql_fetch_array(mysql_query("SELECT 'post_id' FROM post ORDER BY 'post_id' ASC"));
            $data['id'] = $msg_row['post_id'];
        } else {
            $data['ans'] = 1;
        }
    }
    echo json_encode($data);
    exit;
}
/* Получение нового сообщения */
if (!empty($msg)) {
    if (empty($msg)) { /* Если переменная пуста, то возвращаем 0 */
        $data['ans'] = 0;
    } else { /* В другом случае записываем в БД сообщение, обновляем запись игрока в БД (указываем что игрок онлайн и обновляем время последнего действия, нужно для отслеживания игроков онлайн) и отправляем нужные данные клиенту */
        $t = time();
        mysql_query("INSERT INTO post (`post_login`,`post_time`,`post_txt`) VALUES ('".$_SESSION['name']."','".$t."','".$msg."')");
        mysql_query("UPDATE users SET `last_update`='".$t."', `user_online`='1' WHERE `user_name`='".$_SESSION['name']."'");
        $data['ans'] = 1;
        $data['login'] = $_SESSION['name'];
        $data['time'] = date('H:i:s', $t);
        $data['msg'] = $msg;
    }
    echo json_encode($data);
    exit;
}
/* Если переменная $update не пуста (функция обновления на стороне клиента) */
if (!empty($update)) {
    if (empty($update)) {
        $data['ans'] = 0;
    } else {
        /* Выбираем всех игроков с базы */
        $user = mysql_query("SELECT * FROM users");
        $data['user'] = "";
        while ($user_row = mysql_fetch_array($user)) {
            /* Если игрок онлайн, то проверяем время его последнего действия и добавляем к нему 1 час (3600 сек) и запоминаем для передачи его имени клиенту. Если игрок в течении часа ничего не делал, то переводим его в режим оффлайн и убираем из списка игроков. Перевод игрока в режим оффлайн будет состоятся только в том случае если хоть 1 пользователь будет находится в чате. */
            if ($user_row['user_online'] == 1) {
                $t = $user_row['last_update']+3600;
                if ($t >= time()) {
                    $data['user'] .= "<div>".$user_row['user_name']."</div>";
                } else {
                    mysql_query("UPDATE users SET `user_online`='0' WHERE `user_name`='".$user_row['user_name']."'");
                }
            }
        }
        /* Выбираем все сообщения */
        $msg_sql = mysql_query("SELECT * FROM post ORDER BY 'post_id' ASC");
        $data['msg'] = "";
        while ($msg_row = mysql_fetch_array($msg_sql)) {
            /* Производим проверку id. Если id игрока меньше чем id последнего сообщения, значит в БД появилось новое сообщение, выбираем его и запоминаем для передачи клиенту. Данное действие нужно для того что бы в чат клиенту добавлять по несколько сообщений, а не обновлять весь блок (div). Т.к. бывает нужно выделить какое то сообщение, а не можешь, т.к. блок обновляется и выделение сбрасывается. */
            if ($id < $msg_row['post_id']) {
                $data['msg'] .= "<div>[".date('H:i:s', $msg_row['post_time'])."] <strong>".$msg_row['post_login'].": </strong>".$msg_row['post_txt']."</div>";
                $data['id'] = $msg_row['post_id'];
            }
        }
    }
    echo json_encode($data);
    exit;
}

?>