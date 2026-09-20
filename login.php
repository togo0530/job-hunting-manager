<?php

session_start();

include("db.php");

$message = "";


// =========================
// ログイン
// =========================

if (isset($_POST["login"])) {

    $name = trim($_POST["name"]);
    $user_password = $_POST["password"];


    if (
        !empty($name) &&
        !empty($user_password)
    ) {


        // =========================
        // ユーザーを検索
        // =========================

        $sql = "SELECT * FROM users
                WHERE name = :name";


        $stmt =
            $pdo->prepare($sql);


        $stmt->bindParam(
            ':name',
            $name,
            PDO::PARAM_STR
        );


        $stmt->execute();


        $user_data =
            $stmt->fetch();


        // =========================
        // パスワード確認
        // =========================

        if (
            $user_data &&
            password_verify(
                $user_password,
                $user_data["password"]
            )
        ) {


            // SESSIONに保存
            $_SESSION["user_id"] =
                $user_data["id"];

            $_SESSION["user_name"] =
                $user_data["name"];


            // メイン画面へ
            header("Location: main.php");
            exit;

        } else {

            $message =
                "ユーザー名またはパスワードが違います。";
        }


    } else {

        $message =
            "ユーザー名とパスワードを入力してください。";
    }
}

?>


<!DOCTYPE html>

<html lang="ja">


<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ログイン</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<div class="container">


    <h2>
        ログイン
    </h2>


    <?php if (!empty($message)) { ?>

        <div class="message">

            <?php

            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            );

            ?>

        </div>

    <?php } ?>


    <div class="form-box">


        <form
            action=""
            method="post"
        >


            <label>
                ユーザー名
            </label>


            <input
                type="text"
                name="name"
                required
            >


            <label>
                パスワード
            </label>


            <input
                type="password"
                name="password"
                required
            >


            <input
                type="submit"
                name="login"
                value="ログイン"
            >


        </form>


        <p>

            初めて利用する方は

            <a href="register.php">
                新規登録
            </a>

        </p>


    </div>


</div>


</body>

</html>