<?php
session_start();
include("db.php");

// ログインしていない場合はログイン画面へ
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// ==============================
// 表示する年月を決める
// ==============================

// URLに year と month があれば使用
$year = isset($_GET["year"]) ? (int)$_GET["year"] : (int)date("Y");
$month = isset($_GET["month"]) ? (int)$_GET["month"] : (int)date("n");

// 不正な値への簡単な対策
if ($year < 2000 || $year > 2100) {
    $year = (int)date("Y");
}

if ($month < 1 || $month > 12) {
    $month = (int)date("n");
}

// ==============================
// 前月・次月を計算
// ==============================

$current_month = new DateTime(sprintf("%04d-%02d-01", $year, $month));

$prev_month = clone $current_month;
$prev_month->modify("-1 month");

$next_month = clone $current_month;
$next_month->modify("+1 month");

$prev_year = $prev_month->format("Y");
$prev_month_number = $prev_month->format("n");

$next_year = $next_month->format("Y");
$next_month_number = $next_month->format("n");

// ==============================
// カレンダー作成に必要な情報
// ==============================

// その月の1日の曜日
// 0 = 日曜日 ～ 6 = 土曜日
$first_weekday = (int)$current_month->format("w");

// その月の日数
$days_in_month = (int)$current_month->format("t");

// ==============================
// ログイン中ユーザーの予定を取得
// ==============================

$start_date = sprintf("%04d-%02d-01", $year, $month);
$end_date = sprintf("%04d-%02d-%02d", $year, $month, $days_in_month);

$sql = "SELECT id, company_name, next_date, next_action
        FROM companies
        WHERE user_id = :user_id
        AND next_date BETWEEN :start_date AND :end_date
        ORDER BY next_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
$stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
$stmt->execute();

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==============================
// 日付ごとに予定をまとめる
// ==============================

$schedules = array();

foreach ($companies as $company) {
    $date = $company["next_date"];

    if (!isset($schedules[$date])) {
        $schedules[$date] = array();
    }

    $schedules[$date][] = $company;
}

// 今日の日付
$today = date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>就活管理 - カレンダー</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            margin-bottom: 20px;
        }

        .calendar-header h3 {
            margin: 0;
        }

        .month-button {
            display: inline-block;
            padding: 8px 15px;
            background-color: #333;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .month-button:hover {
            opacity: 0.8;
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            table-layout: fixed;
        }

        .calendar-table th {
            padding: 12px 5px;
            border: 1px solid #ddd;
            background-color: #f1f1f1;
        }

        .calendar-table td {
            height: 120px;
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .calendar-table th:first-child {
            color: #d9534f;
        }

        .calendar-table th:last-child {
            color: #337ab7;
        }

        .day-number {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .today {
            background-color: #fff8dc;
        }

        .schedule {
            margin-top: 5px;
            padding: 6px;
            background-color: #eeeeee;
            border-radius: 5px;
            font-size: 13px;
            line-height: 1.4;
        }

        .schedule-company {
            font-weight: bold;
        }

        .calendar-menu {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .calendar-menu a {
            color: #333;
            font-weight: bold;
        }

        @media (max-width: 600px) {

            .calendar-table td {
                height: 90px;
                padding: 4px;
            }

            .calendar-table th {
                padding: 8px 2px;
                font-size: 13px;
            }

            .day-number {
                font-size: 13px;
            }

            .schedule {
                padding: 4px;
                font-size: 10px;
            }

            .calendar-header h3 {
                font-size: 18px;
            }

            .month-button {
                padding: 7px 10px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>


<!-- ==============================
     共通ヘッダー
============================== -->

<header class="app-header">

    <div class="header-inner">

        <!-- 左：アプリ名 -->
        <div class="app-title">
            就活管理
        </div>


        <!-- 右：ユーザー情報 -->
        <div class="header-right">

            <div class="user-menu">

                <span class="header-user-name">
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["user_name"],
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                    さん
                </span>

                <a href="logout.php" class="logout-link">
                    ログアウト
                </a>

            </div>

        </div>

    </div>

</header>


<div class="container">


    <!-- ==============================
         ページタイトル
    ============================== -->

    <div class="welcome-area">

        <h1>カレンダー</h1>

        <p>
            登録した企業の次回予定を確認できます。
        </p>

    </div>


    <!-- 企業一覧へ戻る -->

    <div class="calendar-menu">

        <a href="main.php">
            企業一覧へ戻る
        </a>

    </div>


    <!-- 月の切り替え -->

    <div class="calendar-header">

        <a
            class="month-button"
            href="calendar.php?year=<?php echo $prev_year; ?>&month=<?php echo $prev_month_number; ?>">
            ← 前月
        </a>

        <h3>
            <?php echo $year; ?>年<?php echo $month; ?>月
        </h3>

        <a
            class="month-button"
            href="calendar.php?year=<?php echo $next_year; ?>&month=<?php echo $next_month_number; ?>">
            次月 →
        </a>

    </div>


    <!-- カレンダー -->

    <table class="calendar-table">

        <thead>

            <tr>
                <th>日</th>
                <th>月</th>
                <th>火</th>
                <th>水</th>
                <th>木</th>
                <th>金</th>
                <th>土</th>
            </tr>

        </thead>

        <tbody>

        <?php

        $day = 1;
        $cell = 0;

        // 最大6週間分表示
        for ($week = 0; $week < 6; $week++) {

            echo "<tr>";

            for ($weekday = 0; $weekday < 7; $weekday++) {

                // 月初より前の空白
                if ($cell < $first_weekday) {

                    echo "<td></td>";

                }

                // 月の日付をすべて表示した後
                elseif ($day > $days_in_month) {

                    echo "<td></td>";

                }

                else {

                    $date = sprintf(
                        "%04d-%02d-%02d",
                        $year,
                        $month,
                        $day
                    );

                    // 今日なら背景を変える
                    $today_class = ($date === $today) ? "today" : "";

                    echo '<td class="' . $today_class . '">';

                    echo '<div class="day-number">';
                    echo $day;
                    echo '</div>';


                    // ==============================
                    // この日の予定を表示
                    // ==============================

                    if (isset($schedules[$date])) {

                        foreach ($schedules[$date] as $schedule) {

                            echo '<div class="schedule">';

                            echo '<div class="schedule-company">';

                            echo htmlspecialchars(
                                $schedule["company_name"],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            echo '</div>';

                            if (!empty($schedule["next_action"])) {

                                echo '<div>';

                                echo htmlspecialchars(
                                    $schedule["next_action"],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                echo '</div>';
                            }

                            echo '</div>';
                        }
                    }

                    echo "</td>";

                    $day++;
                }

                $cell++;
            }

            echo "</tr>";

            // 月の日付を全部表示したら終了
            if ($day > $days_in_month) {
                break;
            }
        }

        ?>

        </tbody>

    </table>

</div>

</body>

</html>