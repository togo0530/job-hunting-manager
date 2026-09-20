<?php
session_start();
include("db.php");

// ログインしていない場合
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// 削除する企業IDが送られていない場合
if (!isset($_POST["company_id"])) {
    header("Location: main.php");
    exit;
}

$company_id = (int)$_POST["company_id"];


// ========================================
// 「削除する」が押された場合
// ========================================
if (isset($_POST["delete_confirm"])) {

    $sql = "DELETE FROM companies
            WHERE id = :id
            AND user_id = :user_id";

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    header("Location: main.php");
    exit;
}


// ========================================
// 削除対象の企業情報を取得
// ========================================
$sql = "SELECT *
        FROM companies
        WHERE id = :id
        AND user_id = :user_id";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':id', $company_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

$stmt->execute();

$company = $stmt->fetch(PDO::FETCH_ASSOC);


// 対象企業が存在しない場合
if (!$company) {
    header("Location: main.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>企業情報の削除確認</title>

    <link rel="stylesheet" href="style.css">

</head>


<body>

<div class="container">

    <h2>削除確認</h2>


    <div class="delete-confirm-box">

        <h3>
            <?php
            echo htmlspecialchars(
                $company["company_name"],
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </h3>


        <p class="delete-warning">
            この企業情報を本当に削除しますか？
        </p>

        <p>
            削除すると、この企業に登録されている情報は
            すべて削除されます。
        </p>


        <div class="delete-company-info">

            <p>
                <strong>選考状況：</strong>

                <?php
                echo htmlspecialchars(
                    $company["selection_status"],
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </p>


            <p>
                <strong>次回予定日：</strong>

                <?php
                if (!empty($company["next_date"])) {

                    echo htmlspecialchars(
                        $company["next_date"],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                } else {

                    echo "未設定";
                }
                ?>
            </p>


            <p>
                <strong>次回予定：</strong>

                <?php
                if (!empty($company["next_action"])) {

                    echo htmlspecialchars(
                        $company["next_action"],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                } else {

                    echo "未設定";
                }
                ?>
            </p>


            <p>
                <strong>メモ：</strong><br>

                <?php
                if (!empty($company["memo"])) {

                    echo nl2br(
                        htmlspecialchars(
                            $company["memo"],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    );

                } else {

                    echo "なし";
                }
                ?>
            </p>

        </div>


        <div class="delete-actions">

            <!-- キャンセル -->
            <a href="main.php"
               class="cancel-button">

                キャンセル

            </a>


            <!-- 本当に削除 -->
            <form method="POST"
                  action="delete_confirm.php">

                <input
                    type="hidden"
                    name="company_id"
                    value="<?php echo $company["id"]; ?>"
                >

                <input
                    type="submit"
                    name="delete_confirm"
                    value="削除する"
                    class="delete-final-button"
                >

            </form>

        </div>

    </div>

</div>

</body>

</html>