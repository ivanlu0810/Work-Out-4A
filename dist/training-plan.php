<?php
session_start();

// 確保有 session，取得 user_id
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 9;

// 讀取 HTML 內容
$html_content = file_get_contents(__DIR__ . '/training-plan.html');

// 在 </head> 之前注入 currentUserId
$injection = "
    <script>
        window.currentUserId = " . $user_id . ";
        console.log('PHP 注入的 currentUserId:', window.currentUserId);
    </script>
";

$html_content = str_replace('</head>', $injection . '</head>', $html_content);

// 直接輸出 HTML
echo $html_content;
?>