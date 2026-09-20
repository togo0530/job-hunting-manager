<?php

session_start();

include("db.php");

$message = "";


// =========================
// ユーザー登録
// =========================

if (isset($_POST["register"])) {

    $name = trim($_POST["name"]);
    $user_password = $_POST["password"];


    // 入力確認
    if (
        !empty($name) &&
        !empty($user_password)
    ) {


        // =========================
        // 同じユーザー名を確認
        // =========================

        $sql = "SELECT * FROM users
                WHERE name = :name";


        $stmt = $pdo->prepare($sql);


        $stmt->bindParam(
            ':name',
            $name,
            PDO::PARAM_STR
        );


        $stmt->execute();


        $existing_user =
            $stmt->fetch();


        // すでに存在
        if ($existing_user) {

            $message =
                "このユーザー名はすでに使用されています。";
        }


        // 新規登録
        else {


            // =========================
            // パスワードをハッシュ化
            // =========================

            $hashed_password =
                password_hash(
                    $user_password,
                    PASSWORD_DEFAULT
                );


            // =========================
            // DBへ登録
            // =========================

            $sql = "INSERT INTO users
                    (
                        name,
                        password
                    )
                    VALUES
                    (
                        :name,
                        :password
                    )";


            $stmt =
                $pdo->prepare($sql);


            $stmt->bindParam(
                ':name',
                $name,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':password',
                $hashed_password,
                PDO::PARAM_STR
            );


            $stmt->execute();


            // =========================
            // ログイン画面へ
            // =========================

            header("Location: login.php");
            exit;
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

    <title>ユーザー登録</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<div class="container">


    <h2>
        ユーザー登録
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
                name="register"
                value="ユーザー登録"
            >


        </form>


        <p>

            すでにアカウントを持っている方は

            <a href="login.php">
                ログイン
            </a>

        </p>


    </div>


</div>


</body>

</html>