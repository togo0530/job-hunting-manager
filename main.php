<?php

session_start();


// ========================================
// ログイン確認
// ========================================

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


// ========================================
// DB接続
// ========================================

include("db.php");

$user_id = $_SESSION["user_id"];


// ========================================
// 初期値
// ========================================

$edit_id = "";
$edit_company_name = "";
$edit_selection_status = "検討中";
$edit_next_date = "";
$edit_next_action = "";
$edit_memo = "";

$message = "";


// Gemini関係
$ai_answer = "";
$ai_company_id = "";
$ai_error = "";


// ========================================
// 並び替え
// ========================================

$sort = isset($_GET["sort"])
    ? $_GET["sort"]
    : "register";


switch ($sort) {

    // 予定が早い順
    case "date_asc":

        $order_by =
            "CASE
                WHEN next_date IS NULL
                OR next_date = ''
                THEN 1
                ELSE 0
             END,
             next_date ASC,
             id ASC";

        break;


    // 予定が遅い順
    case "date_desc":

        $order_by =
            "CASE
                WHEN next_date IS NULL
                OR next_date = ''
                THEN 1
                ELSE 0
             END,
             next_date DESC,
             id ASC";

        break;


    // 登録順
    default:

        $sort = "register";

        $order_by = "id ASC";

        break;
}


// ========================================
// 編集する企業を取得
// ========================================

if (isset($_POST["edit"])) {

    $edit_id = $_POST["edit_id"];


    $sql = "SELECT *
            FROM companies
            WHERE id = :id
            AND user_id = :user_id";


    $stmt = $pdo->prepare($sql);


    $stmt->bindParam(
        ':id',
        $edit_id,
        PDO::PARAM_INT
    );


    $stmt->bindParam(
        ':user_id',
        $user_id,
        PDO::PARAM_INT
    );


    $stmt->execute();


    $company = $stmt->fetch();


    if ($company) {

        $edit_id =
            $company["id"];

        $edit_company_name =
            $company["company_name"];

        $edit_selection_status =
            $company["selection_status"];

        $edit_next_date =
            $company["next_date"];

        $edit_next_action =
            $company["next_action"];

        $edit_memo =
            $company["memo"];
    }
}


// ========================================
// 企業登録・更新
// ========================================

if (isset($_POST["register_company"])) {

    $company_name =
        trim($_POST["company_name"]);

    $selection_status =
        $_POST["selection_status"];

    $next_date =
        $_POST["next_date"];

    $next_action =
        $_POST["next_action"];

    $memo =
        $_POST["memo"];

    $edit_number =
        $_POST["edit_number"];


    if (!empty($company_name)) {


        // ========================================
        // 更新
        // ========================================

        if (!empty($edit_number)) {

            $sql = "UPDATE companies
                    SET
                        company_name = :company_name,
                        selection_status = :selection_status,
                        next_date = :next_date,
                        next_action = :next_action,
                        memo = :memo
                    WHERE id = :id
                    AND user_id = :user_id";


            $stmt =
                $pdo->prepare($sql);


            $stmt->bindParam(
                ':company_name',
                $company_name,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':selection_status',
                $selection_status,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':next_date',
                $next_date,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':next_action',
                $next_action,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':memo',
                $memo,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':id',
                $edit_number,
                PDO::PARAM_INT
            );


            $stmt->bindParam(
                ':user_id',
                $user_id,
                PDO::PARAM_INT
            );


            $stmt->execute();


            $message =
                "企業情報を更新しました。";
        }


        // ========================================
        // 新規登録
        // ========================================

        else {

            $sql = "INSERT INTO companies
                    (
                        user_id,
                        company_name,
                        selection_status,
                        next_date,
                        next_action,
                        memo
                    )
                    VALUES
                    (
                        :user_id,
                        :company_name,
                        :selection_status,
                        :next_date,
                        :next_action,
                        :memo
                    )";


            $stmt =
                $pdo->prepare($sql);


            $stmt->bindParam(
                ':user_id',
                $user_id,
                PDO::PARAM_INT
            );


            $stmt->bindParam(
                ':company_name',
                $company_name,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':selection_status',
                $selection_status,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':next_date',
                $next_date,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':next_action',
                $next_action,
                PDO::PARAM_STR
            );


            $stmt->bindParam(
                ':memo',
                $memo,
                PDO::PARAM_STR
            );


            $stmt->execute();


            $message =
                "企業情報を登録しました。";
        }


    } else {

        $message =
            "企業名を入力してください。";
    }
}


// ========================================
// Gemini AI企業研究
// ========================================

if (isset($_POST["ai_company"])) {

    $ai_company_id =
        $_POST["ai_company_id"];


    // ========================================
    // ログインユーザーの企業か確認
    // ========================================

    $sql = "SELECT *
            FROM companies
            WHERE id = :id
            AND user_id = :user_id";


    $stmt =
        $pdo->prepare($sql);


    $stmt->bindParam(
        ':id',
        $ai_company_id,
        PDO::PARAM_INT
    );


    $stmt->bindParam(
        ':user_id',
        $user_id,
        PDO::PARAM_INT
    );


    $stmt->execute();


    $ai_company =
        $stmt->fetch();


    if ($ai_company) {


        // ========================================
        // Gemini APIキー
        // ========================================

        include("gemini_config.php");


        $company_name =
            $ai_company["company_name"];


        // ========================================
        // Geminiへのプロンプト
        // ========================================

        $prompt = <<<PROMPT

あなたは、就職活動を行う大学生の
企業研究を支援するAIです。

対象企業について、
就職活動で必要となる情報を
正確かつ簡潔に整理してください。


【対象企業】

{$company_name}



【重要な方針】

すぐに最終回答を作成せず、
回答の精度を高めるために
内部で調査・整理・評価・再考を行ってください。

最終的に表示するのは、

・企業概要
・主な事業
・選考に関する参考情報

の3項目だけです。

途中の思考過程、
タスク分解、
評価内容、
修正過程は表示しないでください。


━━━━━━━━━━━━━━━━━━━━
【STEP 0：企業の存在確認】
━━━━━━━━━━━━━━━━━━━━

最初に、入力された企業名について、
実在する企業として認識できるか確認してください。

企業の存在を十分に確認できない場合、
企業概要・主な事業・選考情報を推測で作成してはいけません。

その場合は最終回答として、

「入力された企業について、信頼できる情報を確認できませんでした。
企業名が正しいか確認してください。」

とのみ回答してください。

同名企業が複数存在し、
対象企業を特定できない場合も推測で決定せず、

「同名または類似した企業が複数存在するため、
企業を特定できませんでした。」

と回答してください。


━━━━━━━━━━━━━━━━━━━━
【STEP 1：タスク分解】
━━━━━━━━━━━━━━━━━━━━

3つの項目それぞれについて、
精度を高めるために必要な情報を
複数のタスクへ分解してください。


■ 企業概要

以下の観点を内部で調査・整理してください。

・正式な企業名
・企業グループや親会社との関係
・設立や所在地などの基本情報
・従業員数や売上高などの企業規模
・属する業界や業種
・業界内での役割や立ち位置
・企業の特徴や強み
・最近の重要な事業方針や動向

これらすべてを最終回答に
載せる必要はありません。

企業を理解するために
重要な情報だけを選択してください。


■ 主な事業

以下の観点を内部で調査・整理してください。

・主要な事業領域
・具体的な製品やサービス
・主な顧客
・法人向けか個人向けか
・各事業が企業全体で持つ役割
・特に注力している事業
・最近開始または拡大している事業
・就活生が仕事内容を理解するために重要な事業

単なる事業名の列挙ではなく、

「誰に」
「何を」
「どのように提供している企業なのか」

が理解できるように整理してください。


■ 選考に関する参考情報

以下の観点を内部で調査・整理してください。

・新卒採用情報
・募集職種
・応募条件
・エントリー方法
・ESの有無
・Webテストや適性検査
・グループディスカッション等
・面接のおおよその回数
・一般的に知られている選考フロー
・インターンやイベントと本選考の関係
・採用スケジュール
・企業が公開している求める人物像

選考情報は年度によって
変更される可能性が高いため、
古い情報を現在の情報として
断定しないでください。


━━━━━━━━━━━━━━━━━━━━
【STEP 2：情報整理】
━━━━━━━━━━━━━━━━━━━━

保有している情報の中では、
可能な限り新しい情報を優先してください。

ただし、

現在の最新情報であることを
確認できない場合は、
最新情報であると断定しないでください。

情報が不明な場合は
推測で補完しないでください。

似た名前の別企業と
混同しないよう注意してください。


━━━━━━━━━━━━━━━━━━━━
【STEP 3：回答案の作成】
━━━━━━━━━━━━━━━━━━━━

調査・整理した情報から、

就職活動中の大学生が
短時間で企業を理解するために
重要な情報だけを選択してください。

情報量を増やすことより、

「短時間で企業の特徴を理解できること」

を優先してください。


━━━━━━━━━━━━━━━━━━━━
【STEP 4：自己評価】
━━━━━━━━━━━━━━━━━━━━

作成した回答案について、
内部で以下を確認してください。

・対象企業を取り違えていないか

・古い情報を現在の情報として
  扱っていないか

・根拠のない情報を
  作っていないか

・企業の特徴が理解できるか

・主な事業内容が具体的に
  理解できるか

・選考情報を断定しすぎていないか

・就職活動に必要な情報が
  抜けていないか

・同じ内容を繰り返していないか

・文章が長くなりすぎていないか

・初めてその企業を見る大学生でも
  理解できるか


━━━━━━━━━━━━━━━━━━━━
【STEP 5：再考・修正】
━━━━━━━━━━━━━━━━━━━━

自己評価で問題が見つかった場合は、
回答案を修正してください。

不足している情報がある場合は、
保有している情報を再確認してください。

情報の信頼性に自信がない場合は、

断定的な表現を避け、

「〜とされます」
「年度によって異なる可能性があります」
「最新情報の確認が必要です」

など、
不確実性が分かる表現にしてください。


━━━━━━━━━━━━━━━━━━━━
【STEP 6：最終確認】
━━━━━━━━━━━━━━━━━━━━

修正後の回答について、
もう一度以下を確認してください。

・正確性
・新しさ
・簡潔さ
・分かりやすさ
・就活生にとっての有用性

問題がある場合は、
再度修正してください。


━━━━━━━━━━━━━━━━━━━━
【最終出力形式】
━━━━━━━━━━━━━━━━━━━━

STEP 0で企業を十分に確認できなかった場合は、
STEP 0で指定したエラーメッセージのみを出力してください。

企業を確認できた場合は、
必ず以下の3項目だけをこの順番で出力してください。


【企業概要】

企業の特徴、
業界での役割、
企業規模などから、

就活生が企業を理解するために
重要な内容を簡潔に説明してください。


【主な事業】

主要な事業について、

「誰に何を提供しているのか」

が分かるように
簡潔に説明してください。


【選考に関する参考情報】

新卒採用を中心に、

確認できる範囲の
選考フローや特徴を
簡潔に説明してください。

年度によって変わる可能性のある情報は、
断定しないでください。


【回答ルール】

・可能な限り新しい情報を優先する

・最新情報であることを
  確認できない場合は断定しない

・事実と推測を混同しない

・確認できない情報を作らない

・就活生に必要な情報を優先する

・専門用語は必要に応じて
  簡単に説明する

・各項目は簡潔にする

・同じ説明を繰り返さない

・対象企業を勝手に
  別企業として解釈しない

・内部の思考過程は表示しない


PROMPT;


        // ========================================
        // Gemini Interactions API
        // ========================================

        $url =
            "https://generativelanguage.googleapis.com/v1beta/interactions";


        $data = [

            "model" =>
                "gemini-3.6-flash",

            "input" =>
                $prompt

        ];


        $jsonData =
            json_encode(
                $data,
                JSON_UNESCAPED_UNICODE
            );


        // ========================================
        // cURL
        // ========================================

        $ch =
            curl_init($url);


        curl_setopt(
            $ch,
            CURLOPT_POST,
            true
        );


        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $jsonData
        );


        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                "Content-Type: application/json",
                "x-goog-api-key: "
                    . $geminiApiKey
            ]
        );


        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            true
        );


        curl_setopt(
            $ch,
            CURLOPT_TIMEOUT,
            60
        );


        // ========================================
        // Geminiへ送信
        // ========================================

        $response =
            curl_exec($ch);


        // ========================================
        // 通信エラー
        // ========================================

        if (curl_errno($ch)) {

            $ai_error =
                "AIとの通信中にエラーが発生しました。";

        } else {

            $result =
                json_decode(
                    $response,
                    true
                );


            // ========================================
            // Gemini回答取得
            // ========================================

            $answer = "";


            if (isset($result["steps"])) {

                foreach (
                    $result["steps"]
                    as $step
                ) {

                    if (
                        isset($step["type"]) &&
                        $step["type"] === "model_output" &&
                        isset($step["content"])
                    ) {

                        foreach (
                            $step["content"]
                            as $content
                        ) {

                            if (
                                isset($content["type"]) &&
                                $content["type"] === "text" &&
                                isset($content["text"])
                            ) {

                                $answer .=
                                    $content["text"];
                            }
                        }
                    }
                }
            }


            // ========================================
            // AI回答成功
            // ========================================

            if (!empty($answer)) {

                $ai_answer =
                    $answer;

            } else {


                if (
                    isset($result["error"]["code"]) &&
                    $result["error"]["code"]
                    === "service_unavailable"
                ) {

                    $ai_error =
                        "現在AIが混雑しています。少し時間を空けてもう一度お試しください。";

                } else {

                    $ai_error =
                        "AIから回答を取得できませんでした。";
                }
            }
        }


        curl_close($ch);


    } else {

        $ai_error =
            "企業情報を確認できませんでした。";
    }
}


// ========================================
// 企業一覧取得
// ========================================

$sql = "SELECT *
        FROM companies
        WHERE user_id = :user_id
        ORDER BY " . $order_by;


$stmt =
    $pdo->prepare($sql);


$stmt->bindParam(
    ':user_id',
    $user_id,
    PDO::PARAM_INT
);


$stmt->execute();


$companies =
    $stmt->fetchAll();


// ========================================
// ダッシュボード用の数字を計算
// ========================================

// 登録企業数
$total_companies =
    count($companies);


// 選考中企業数
$active_count = 0;


// 今後の予定数
$schedule_count = 0;


foreach ($companies as $summary_company) {


    // 検討中・内定・その他以外を
    // 「選考中」とする
    if (
        $summary_company["selection_status"] !== "検討中" &&
        $summary_company["selection_status"] !== "内定" &&
        $summary_company["selection_status"] !== "その他"
    ) {

        $active_count++;
    }


    // 次回予定日が設定されている企業
    if (!empty($summary_company["next_date"])) {

        $schedule_count++;
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

    <title>
        就活管理
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- ========================================
     ヘッダー
======================================== -->

<header class="app-header">


    <div class="header-inner">


        <!-- アプリ名 -->

        <div class="app-title">

            就活管理

        </div>


        <!-- 右側 -->

        <div class="header-right">


            <!-- カレンダー -->

            <a
                href="calendar.php"
                class="calendar-nav-button"
            >

                📅 カレンダー

            </a>


            <!-- ユーザー -->

            <div class="user-menu">


                <span class="header-user-name">

                    <?php

                    echo htmlspecialchars(
                        $_SESSION["user_name"],
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    ?>

                    さん

                </span>


                <a
                    href="logout.php"
                    class="logout-link"
                >

                    ログアウト

                </a>


            </div>


        </div>


    </div>


</header>


<!-- ========================================
     メイン
======================================== -->

<main class="container">


    <!-- ========================================
         あいさつ
    ========================================= -->

    <section class="welcome-area">


        <h1>

            ホーム

        </h1>


        <p>

            おかえりなさい、

            <?php

            echo htmlspecialchars(
                $_SESSION["user_name"],
                ENT_QUOTES,
                "UTF-8"
            );

            ?>

            さん。

            就職活動の状況を整理していきましょう。

        </p>


    </section>


    <!-- ========================================
         ダッシュボード
    ========================================= -->

    <section class="summary-grid">


        <!-- 登録企業 -->

        <div class="summary-card">


            <p class="summary-label">

                登録企業

            </p>


            <p class="summary-number">

                <?php
                echo $total_companies;
                ?>

                <span class="summary-unit">

                    社

                </span>

            </p>


        </div>


        <!-- 選考中 -->

        <div class="summary-card">


            <p class="summary-label">

                選考中

            </p>


            <p class="summary-number">

                <?php
                echo $active_count;
                ?>

                <span class="summary-unit">

                    社

                </span>

            </p>


        </div>


        <!-- 今後の予定 -->

        <div class="summary-card">


            <p class="summary-label">

                今後の予定

            </p>


            <p class="summary-number">

                <?php
                echo $schedule_count;
                ?>

                <span class="summary-unit">

                    件

                </span>

            </p>


        </div>


    </section>


    <!-- ========================================
         メッセージ
    ========================================= -->

    <?php if (!empty($message)) { ?>


        <div class="message">


            <?php

            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                "UTF-8"
            );

            ?>


        </div>


    <?php } ?>


    <!-- ========================================
         企業登録・編集
    ========================================= -->

    <section class="form-box">


        <h3>


            <?php

            if (!empty($edit_id)) {

                echo "企業情報を編集";

            } else {

                echo "＋ 企業を登録";
            }

            ?>


        </h3>


        <form
            action=""
            method="post"
        >


            <!-- 企業名 -->

            <label for="company_name">

                企業名

            </label>


            <input
                type="text"
                id="company_name"
                name="company_name"
                placeholder="例：株式会社〇〇"
                value="<?php

                echo htmlspecialchars(
                    $edit_company_name,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>"
                required
            >


            <!-- 選考状況 -->

            <label for="selection_status">

                選考状況

            </label>


            <select
                id="selection_status"
                name="selection_status"
            >


                <?php

                $statuses = [

                    "検討中",

                    "説明会・インターン",

                    "ES提出",

                    "Webテスト",

                    "面接",

                    "最終面接",

                    "内定",

                    "その他"

                ];


                foreach (
                    $statuses
                    as $status
                ) {


                    echo '<option value="'
                        . htmlspecialchars(
                            $status,
                            ENT_QUOTES,
                            "UTF-8"
                        )
                        . '"';


                    if (
                        $edit_selection_status
                        ===
                        $status
                    ) {

                        echo " selected";
                    }


                    echo ">"
                        . htmlspecialchars(
                            $status,
                            ENT_QUOTES,
                            "UTF-8"
                        )
                        . "</option>";
                }

                ?>


            </select>


            <!-- 次回予定日 -->

            <label for="next_date">

                次回予定日

            </label>


            <input
                type="date"
                id="next_date"
                name="next_date"
                value="<?php

                echo htmlspecialchars(
                    $edit_next_date,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>"
            >


            <!-- 次回予定 -->

            <label for="next_action">

                次回予定

            </label>


            <input
                type="text"
                id="next_action"
                name="next_action"
                placeholder="例：一次面接"
                value="<?php

                echo htmlspecialchars(
                    $edit_next_action,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>"
            >


            <!-- メモ -->

            <label for="memo">

                メモ

            </label>


            <textarea
                id="memo"
                name="memo"
                rows="5"
                placeholder="企業についてのメモや準備内容など"
            ><?php

            echo htmlspecialchars(
                $edit_memo,
                ENT_QUOTES,
                "UTF-8"
            );

            ?></textarea>


            <!-- 編集番号 -->

            <input
                type="hidden"
                name="edit_number"
                value="<?php

                echo htmlspecialchars(
                    $edit_id,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>"
            >


            <!-- 登録・更新 -->

            <input
                type="submit"
                name="register_company"
                value="<?php

                echo !empty($edit_id)
                    ? "変更を保存"
                    : "企業を登録";

                ?>"
            >


        </form>


    </section>


    <!-- ========================================
         登録企業一覧ヘッダー
    ========================================= -->

    <section class="list-header">


        <div class="list-title">


            <h2>

                登録企業

            </h2>


            <p>

                選考状況や次回予定を確認できます。

            </p>


        </div>


        <!-- ========================================
             並び替え
        ========================================= -->

        <div class="sort-box">


            <form
                method="get"
                action="main.php"
                class="sort-form"
            >


                <label for="sort">

                    並び替え

                </label>


                <select
                    name="sort"
                    id="sort"
                    onchange="this.form.submit()"
                >


                    <option
                        value="register"
                        <?php

                        if ($sort === "register") {
                            echo "selected";
                        }

                        ?>
                    >

                        登録順

                    </option>


                    <option
                        value="date_asc"
                        <?php

                        if ($sort === "date_asc") {
                            echo "selected";
                        }

                        ?>
                    >

                        予定が早い順

                    </option>


                    <option
                        value="date_desc"
                        <?php

                        if ($sort === "date_desc") {
                            echo "selected";
                        }

                        ?>
                    >

                        予定が遅い順

                    </option>


                </select>


            </form>


        </div>


    </section>


    <!-- ========================================
         企業がない場合
    ========================================= -->

    <?php if (empty($companies)) { ?>


        <div class="company-card">


            <p>

                まだ企業が登録されていません。

            </p>


        </div>


    <?php } ?>


    <!-- ========================================
         企業一覧
    ========================================= -->

    <?php foreach ($companies as $company) { ?>


        <?php


        // ========================================
        // ステータスによって
        // バッジの色を変更
        // ========================================

        switch ($company["selection_status"]) {


            case "説明会・インターン":

                $status_class =
                    "status-event";

                break;


            case "ES提出":

                $status_class =
                    "status-es";

                break;


            case "Webテスト":

                $status_class =
                    "status-test";

                break;


            case "面接":

                $status_class =
                    "status-interview";

                break;


            case "最終面接":

                $status_class =
                    "status-final";

                break;


            case "内定":

                $status_class =
                    "status-offer";

                break;


            case "その他":

                $status_class =
                    "status-other";

                break;


            default:

                $status_class =
                    "status-consider";

                break;
        }


        ?>


        <article class="company-card">


            <!-- ========================================
                 企業名・ステータス
            ========================================= -->

            <div class="company-top">


                <h3 class="company-name">


                    <?php

                    echo htmlspecialchars(
                        $company["company_name"],
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    ?>


                </h3>


                <span
                    class="status-badge
                    <?php echo $status_class; ?>"
                >


                    <?php

                    echo htmlspecialchars(
                        $company["selection_status"],
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    ?>


                </span>


            </div>


            <!-- ========================================
                 次回予定
            ========================================= -->

            <div class="company-info-grid">


                <!-- 日付 -->

                <div class="info-box">


                    <div class="info-label">

                        NEXT DATE

                    </div>


                    <div class="info-value">


                        <?php


                        if (
                            !empty(
                                $company["next_date"]
                            )
                        ) {

                            echo htmlspecialchars(
                                $company["next_date"],
                                ENT_QUOTES,
                                "UTF-8"
                            );

                        } else {

                            echo "未設定";
                        }


                        ?>


                    </div>


                </div>


                <!-- 内容 -->

                <div class="info-box">


                    <div class="info-label">

                        NEXT ACTION

                    </div>


                    <div class="info-value">


                        <?php


                        if (
                            !empty(
                                $company["next_action"]
                            )
                        ) {

                            echo htmlspecialchars(
                                $company["next_action"],
                                ENT_QUOTES,
                                "UTF-8"
                            );

                        } else {

                            echo "未設定";
                        }


                        ?>


                    </div>


                </div>


            </div>


            <!-- ========================================
                 メモ
            ========================================= -->

            <div class="memo-box">


                <div class="memo-label">

                    MEMO

                </div>


                <div class="memo-text">


                    <?php


                    if (
                        !empty(
                            $company["memo"]
                        )
                    ) {

                        echo nl2br(
                            htmlspecialchars(
                                $company["memo"],
                                ENT_QUOTES,
                                "UTF-8"
                            )
                        );

                    } else {

                        echo "メモはありません。";
                    }


                    ?>


                </div>


            </div>


            <!-- ========================================
                 編集・削除
            ========================================= -->

            <div class="actions">


                <!-- 編集 -->

                <form
                    action=""
                    method="post"
                >


                    <input
                        type="hidden"
                        name="edit_id"
                        value="<?php
                        echo $company["id"];
                        ?>"
                    >


                    <input
                        type="submit"
                        name="edit"
                        value="編集"
                        class="edit-button"
                    >


                </form>


                <!-- 削除 -->

                <form
                    action="delete_confirm.php"
                    method="post"
                >


                    <input
                        type="hidden"
                        name="company_id"
                        value="<?php
                        echo $company["id"];
                        ?>"
                    >


                    <input
                        type="submit"
                        value="削除"
                        class="delete-button"
                    >


                </form>


            </div>


            <!-- ========================================
                 AI企業研究
            ========================================= -->

            <div class="ai-section">


                <form
                    action=""
                    method="post"
                >


                    <input
                        type="hidden"
                        name="ai_company_id"
                        value="<?php
                        echo $company["id"];
                        ?>"
                    >


                    <input
                        type="submit"
                        name="ai_company"
                        value="✨ AIで企業情報を見る"
                        class="ai-button"
                    >


                </form>


                <!-- ========================================
                     AI回答
                ========================================= -->

                <?php


                if (
                    !empty($ai_company_id) &&
                    $ai_company_id
                    == $company["id"]
                ) {


                ?>


                    <?php if (!empty($ai_answer)) { ?>


                        <div class="ai-result">


                            <h4>

                                ✨ AI企業研究

                            </h4>


                            <div>


                                <?php

                                echo nl2br(
                                    htmlspecialchars(
                                        $ai_answer,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                );

                                ?>


                            </div>


                            <p class="ai-note">

                                ※AIが生成した企業研究の
                                参考情報です。
                                選考内容などは変更される
                                場合があります。
                                最新情報は企業の公式採用サイトを
                                ご確認ください。

                            </p>


                        </div>


                    <?php } ?>


                    <!-- AIエラー -->

                    <?php if (!empty($ai_error)) { ?>


                        <div class="message">


                            <?php

                            echo htmlspecialchars(
                                $ai_error,
                                ENT_QUOTES,
                                "UTF-8"
                            );

                            ?>


                        </div>


                    <?php } ?>


                <?php } ?>


            </div>


        </article>


    <?php } ?>


</main>


</body>

</html>