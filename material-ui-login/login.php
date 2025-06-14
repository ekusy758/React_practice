<?php
    header("Access-Control-Allow-Origin: *");
    
    $post_data = json_decode(file_get_contents("php://input"), true);
    $res_data = array(); 

    session_start();

    if (!empty($post_data['name']) && !empty($post_data['pass'])) {
        $name = $post_data['name'];
        $pass = hash('SHA256', $post_data['pass']);
    } else {
        $res_data['result'] = 'NG';
        $res_data['msg'] = 'Error: フォーム入力に不備があるようです。';
        echo(json_encode($res_data));
        exit();
    }

    if (isset($_SESSION['id']) && isset($_SESSION['name'])) {
        $res_data['result'] = 'OK';
        $res_data['msg'] = 'Info: 既にログイン済みです。';
        echo(json_encode($res_data));
        exit();
        
    } else {
        // DB接続情報はサンプルです。
        $dsn = 'mysql:host=localhost;dbname=SAMPLE;charset=utf8';
        $db_user = 'sample';
        $db_pass = 'sample';

        try {
            $pdo = new PDO($dsn, $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            $statement = $pdo->prepare('SELECT * FROM users WHERE name=? AND password=?');
            $statement->bindParam(1, $name, PDO::PARAM_STR);
            $statement->bindParam(2, $pass, PDO::PARAM_STR);

            $statement->execute();

            if ($row = $statement->fetch()) { // 失敗した場合、または行が残っていない場合はfalse
                $_SESSION['id'] = $row['id'];
                $_SESSION['name'] = $row['name'];

                $res_data['result'] = 'OK';
                $res_data['msg'] = 'Info: ログインが成功しました。';
                echo(json_encode($res_data));
            } else {
                $res_data['result'] = 'NG';
                $res_data['msg'] = 'Error: ログインに失敗しました。';
                echo(json_encode($res_data));

                // セッションを完全に破棄
                session_destroy();
                setcookie(session_name(), '', 0, '/');
            }

        } catch (PDOException $e) { // Error: SQLSTATE[HY000] [2002] 対象のコンピューターによって拒否されたため、接続できませんでした。
            $res_data['result'] = 'NG';
            $res_data['msg'] = 'Error: ' . $e->getMessage();
            echo(json_encode($res_data));

            // セッションを完全に破棄
            session_destroy();
            setcookie(session_name(), '', 0, '/');
        }
    }
?>
