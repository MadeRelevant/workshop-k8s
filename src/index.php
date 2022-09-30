<?php

// The most simplistic PHP script to post some messages to my guestbook to demonstrate a basic k8s setup ;)
error_reporting(-1);
ini_set('display_errors', 1);

$pdo = new PDO('mysql:host=db;dbname=workshop;user=root;password=workshop');
$logger = fopen('php://stderr', 'w');

doBenchmarkIfRequested();
handleNewMessage();
deleteAllMessagesIfRequested();
showForm();
showMessages();

function doLog(string $message): void
{
    global $logger;
    fwrite($logger, $message . PHP_EOL);
}

function doBenchmarkIfRequested()
{
    if (!isset($_GET['benchmark'])) {
        return;
    }

    doLog('Performing benchmark');

    $x = 0.0001;
    for ($i = 0; $i <= 1000000; $i++) {
        $x += sqrt($x);
    }
    echo "OK!";
    exit(0);
}

function showForm()
{
    doLog('Showing form');

    $characters = [
        'unicorn', 'pikachu', 'rutte', 'dog', 'dolphin', 'ant', 'snake', 'einstein',
    ];
    $character = $characters[array_rand($characters)];
    ?>
    <h1>Hi! Welcome to my guestbook, leave a message and be nice! ;)</h1>
    <form method="post">
        <textarea name="message" style="width: 350px; height: 75px;">My favorite to test messages with is: <?=$character?></textarea>
        <input type="submit" value="Submit"/>
    </form>

    <?php
}

function handleNewMessage()
{
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $message = $_POST['message'] ?? null;
    if (empty($message)) {
        doLog('Message input validation failed');
        return;
    }

    doLog('Inserting new message');

    $stmt = $pdo->prepare('insert into guestbook (created, message) values (now(), ?)');
    $stmt->execute([$message]);

    // redirect back so you dont get the annoying resubmit thingy
    header('Location: /');
    exit(0);
}

function deleteAllMessagesIfRequested() {
    global $pdo;

    if (!isset($_GET['deleteAll'])) {
        return;
    }

    doLog('Deleting all messages over http');

    $stmt = $pdo->prepare('delete from guestbook');
    $stmt->execute();

    header('Location: /');
    exit(0);
}

function showMessages()
{
    global $pdo;

    // get all messages, newest on top
    $stmt = $pdo->query('select * from guestbook order by id desc');
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($messages) === 0) {
        doLog('Tried to show messages but none were found');
        echo 'No messages entered, be the first!' . PHP_EOL;
        return;
    }

    doLog('Showing all messages');

    ?>
    <a href="?deleteAll">Delete all messages</a>
    <hr />
    <?php

    foreach ($messages as $message) {
        echo "<strong>Created:</strong> <i>{$message['created']}</i><br />" . PHP_EOL;
        echo htmlspecialchars($message['message']) . PHP_EOL;
        echo '<hr />' . PHP_EOL;
    }
}
