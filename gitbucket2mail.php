<?php

function str_lreplace($search, $replace, $subject) {
    $pos = strrpos($subject, $search);
    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}

$to = isset($_GET['to']) ? $_GET['to'] : '{}';
$request = isset($_POST['payload']) ? $_POST['payload'] : '{}';
$json = json_decode($request);

$repoName = $json->repository->name;
$branch = $json->ref;
$message = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
$message .= '<div style="margin-top: 10px;font-size: 30px; font-family: Arial, monospace;">Tirasa - GitBucket</div>';
$message .= '<hr/>';

$from = '';
$mailMessage = '';
if ($json->issue) {
    $mailMessage = 'Issue #' . $json->issue->number . ' ' . $json->issue->title . ': ' . ($json->comment ? 'commented' : $json->action);
    $from = $json->sender->login . ' <' . $json->sender->email . '>';

    $issueRef = $json->repository->html_url . '/issues/' . $json->issue->number;
    if ($json->comment) {
        $issueRef .= '#comment-' . $json->comment->id;

        $message .= '<b>' . $json->comment->user->login . '</b> commented:<br/>';
        $message .= '<pre>' . $json->comment->body . '</pre>';
    }

    $message .= '<a href="' . $issueRef . '">' . $issueRef . '</a>';
} else {
    $firstCommitMessage = $json->commits[0]->message;
    foreach ($json->commits as $commit) {

        // prepare commit variables
        $commiterName = $json->pusher->name;
        $commiterEmail = $json->pusher->email;
        $commitId = $commit->id;
        $commitMessage = $commit->message;
        $commitTimestamp = $commit->timestamp;
	// temporary(?) fix
        $commitUrl = str_replace("commits", "commit", str_replace("api/v3/", "", $commit->url));
        $commitAdded = $commit->added;
        $commitModiefied = $commit->modified;
        $commitRemoved = $commit->removed;

        // general Info

        $message .= '<div><strong>Branch: </strong>' . $branch . '</div>';
        $message .= '<div><strong>Commit: </strong><a href="' . $commitUrl . '" class="commit">' . $commitId . '</a></div>';
        $message .= '<div><strong>Date: </strong>' . $commitTimestamp . '</div>';
        $message .= '<div><strong>Message</strong><br/>' . nl2br(str_lreplace('\n', '', $commitMessage)) . '</div>';
        $message .= '</div>';

        $message .= '<div style="margin-top: 10px;font-size: 15px;font-weight:bold; font-family: Arial, monospace;">Changed paths:</div>';
        $message .= '<div style="margin-top: 10px; border: 1px solid #d2e6ed;">';

        foreach ($commit->added as $commitAdded) {
            $message .= '<div style="background: #EAF2F5; color: #000; font-size: 12px; font-family: Arial, monospace; padding: 1px 4px; border-bottom: 1px solid #d2e6ed;">A   ' . $commitAdded . '</div>';
        }

        foreach ($commit->modified as $commitModified) {
            $message .= '<div style="background: #EAF2F5; color: #000; font-size: 12px; font-family: Arial, monospace; padding: 1px 4px; border-bottom: 1px solid #d2e6ed;">M   ' . $commitModified . '</div>';
        }

        foreach ($commit->removed as $commitRemoved) {
            $message .= '<div style="background: #EAF2F5; color: #000; font-size: 12px; font-family: Arial, monospace; padding: 1px 4px; border-bottom: 1px solid #d2e6ed;">D   ' . $commitRemoved . '</div>';
        }
        $message .= '</div>';
    }

    $mailMessage = str_replace('\n', ' ', str_lreplace('\n', ' ', $firstCommitMessage));
    $from = $commiterName . ' <' . $commiterEmail . '>';
}
$message .= '<div style="margin-top: 10px;font-size: 12px; font-family: Arial, monospace;">--<br/>You received this message because you are subscribed to Tirasa ' . $repoName . ' project.</div>';
$message .= '</body></html>';

$subject = '[' . $repoName . '] ' . $mailMessage;

$headers = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
$headers .= 'From: ' . $from . "\r\n";
$headers .= 'Reply-To: ' . $to . "\r\n";
mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $headers);
?>
