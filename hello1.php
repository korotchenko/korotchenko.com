<!DOCTYPE html>
<html>
<head>

    <link rel="stylesheet" type="text/css" href="/css/hello.css"/>

    <script type="text/javascript" src="/js/jquery.min.js"></script>
    <script type="text/javascript" src="/js/hello.js"></script>
</head>

<body>

<?php

$server = "localhost";
$username = "ngbizua";
$password = "ngbizua123"; // TODO: insert correct password from Gmail
$db = "korotchenkocom";

$link = mysql_connect($server, $username, $password);

if (!$link)
    die("Can not connect to db.");

mysql_select_db($db, $link);

if (isset($_REQUEST)) {
    if (isset($_REQUEST['message'])) {
        $message = $_REQUEST['message'];
        $query = "INSERT INTO messages VALUES(null, '{$message}')";
        $result = mysql_query($query);

        if ($result) {
            /*echo "Entity added.";*/
        } else {
            echo "Entity not added.";
        }

    }
}

?>

<form name="input" action="" method="get" id="form">
    <label for="message">
        message:
        <input type="text" name="message" id="message" value="<?php echo $_REQUEST['message'] ?>"/>
    </label>
    <input type="submit" value="Submit" id="submit"/>
</form>

<?php

/* Выполняем SQL-запрос */
$query = "SELECT * FROM messages ORDER BY id DESC LIMIT 5";
$result = mysql_query($query) or die("Query failed : " . mysql_error());

/* Выводим результаты в html */
print "<table>\n";
while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    print "\t<tr>\n";
    foreach ($line as $col_value) {
        print "\t\t<td>$col_value</td>\n";
    }
    print "\t</tr>\n";
}
print "</table>\n";

/* Освобождаем память от результата */
mysql_free_result($result);

mysql_close($link);

?>

<!--<button onclick="myFunction()">submit</button>-->

</body>
</html>
