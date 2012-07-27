$(document).ready(function(){/* Как только загрузится документ, начинает работать код, который расположен внутри */
    var name = null, // Будущее имя пользователя
        id = 0; // id последнего сообщения
    /* Начало работы, создаем запрос, который отсылает в скрипт core.php данные start=true методом POST ($_POST['start']=true) и принимаем от сервера данные в формате json */
    $.ajax({
        type: "POST",
        url: "core.php",
        data: "start=true",
        dataType: 'json',
        success: function(data) {
            /* Если пришла ошибка от сервера, то показываем её пользователю и перезагружаем страницу */
            if (data.err) {
                if (data.err == 2) {
                    apprise('Невозможно найти БД', {}, function(r){
                        location.href = "index1.html";
                    });
                } else if (data.err == 1) {
                    apprise('База данных ушла в себя', {}, function(r){
                        location.href = "index1.html";
                    });
                }
                return false;
            }
            /* Если ошибок нет и получен ответ = 0 то показываем пользователю всплывающее окно */
            if (data.ans == 0) {
                apprise('Введите логин', {'input': true}, function(login){ /* Если пользователь не ввел логин либо нажал "Отменить" перезагружаем страницу */
                    if (login == false) {
                        location.href = "index1.html";
                    } else {/* Если ввел логин, то отправляем в файл core.php введеный логин и принимаем ответ опять же в формате json */
                        $.ajax({
                            type: "POST",
                            url: "core.php",
                            data: "name="+login,
                            dataType: 'json',
                            success: function(data) {
                                /* Если ответ = 0, то запоминаем имя пользователя и id последнего сообщения */
                                if (data.ans == 0) {
                                    name = data.login;
                                    id = data.id;
                                } else { /* Иначе выдаем ошибку и перезагружаем страницу */
                                    apprise('Такой логин уже используется', function(r){
                                        location.href = "index1.html";
                                    });
                                }
                            }
                        });
                    }
                });
            } else { /* Если получаем ответ != 0, то запоминаем имя пользователя и id последнего сообщения */
                name = data.login;
                id = data.id;
            }
        }
    });
    /* Функция которая возвращает сообщения и пользователей онлайн, обновляется каждые 2 сек (2000 милисек) */
    update();
    setInterval(update, "2000");

    function update() {
        /* Отправляем на сервер (файл core.php) данные update=1 и id последнего сообщения */
        $.ajax({
            type: "POST",
            url: "core.php",
            data: "update=1&id="+id,
            dataType: 'json',
            success: function(data) {
                /* Проверяем наличие ошибок */
                if (data.err) {
                    if (data.err == 2) {
                        apprise('Невозможно найти БД', {}, function(r){
                            location.href = "index1.html";
                        });
                    } else if (data.err == 1) {
                        apprise('База данных ушла в себя', {}, function(r){
                            location.href = "index1.html";
                        });
                    }
                    return false;
                }
                /* Обновляем список пользователей */
                if (data.user != $("#user .d").html()) {
                    $("#user .d").html(data.user);
                }
                /* Добавляем новое сообщение на экран, прокручиваем скролл вниз и удаляем старые сообщения, оставивши последние 10 */
                if (data.msg != "") {
                    id = data.id;
                    $('#message .d').append(data.msg);
                    $('#message').scrollTop($('#message')[0].scrollHeight);
                    var size = $("#message .d div").size();
                    if (size > 10) {
                        for (var i = 0; i < size-10; i++) {
                            $("#message .d div").eq(i).remove();
                        }
                    }
                }
            }
        });
        /* удаляем старые сообщения, оставивши последние 10. Данное действие дублируется, т.к. при загрузке страницы с чатом удаляются сообщения и при каждом новом полученом сообщении */
        var size = $("#message .d div").size();
        if (size > 10) {
            for (var i = 0; i < size-10; i++) {
                $("#message .d div").eq(i).remove();
            }
        }
    }
    /* Отслеживаем нажатие клавиш */
    $('.msg').keydown(function(event){
        if (event.which == 13) { /* Если нажато Enter, запоминаем сообщение и  */
            var msg = $(this).val(),
                then = $(this); /* Обращение на самого себя, т.е. на элемент, который имеет класс msg (в нашем случае это input) */
            if (msg == "") { /* Если сообщение пустое, то прекращаем выполнять следующий код*/
                return false;
            }
            /* Отправляем на сервер сообщение и если пришел положительный ответ (!= 0) то прокручиваем блок сообщений вниз и очищаем строку в которую вводим сообщение */
            $.ajax({
                type: "POST",
                url: "core.php",
                data: "msg="+msg,
                dataType: 'json',
                success: function(data) {
                    if (data.ans != 0) {
                        $('#message').scrollTop($('#message')[0].scrollHeight);
                        then.val("");
                    }
                }
            });
        };
    });
});