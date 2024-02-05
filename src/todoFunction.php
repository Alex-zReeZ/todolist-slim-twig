<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;

// Create Slim application
$app = AppFactory::create();

// Configure Twig for views
$twig = Twig::create(__DIR__ . '/../template', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// Routing middleware and error handling
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Connect to the database
require "connectToDatabase.php";

// Route to display the list of tasks
$app->get('/todo', function (Request $request, Response $response) {
    global $pdo;
    $view = Twig::fromRequest($request);

    $statement = $pdo->prepare("SELECT * FROM todo");
    $statement->execute();

    $todos = $statement->fetchAll();

    return $view->render($response, 'user.twig', [
        'todos' => $todos
    ]);
});

// Route to add a new task
$app->post('/todo/add', function ($request, $response) {
    global $pdo;

    $todoName = $request->getParsedBody()['todo'];

    if ($todoName) {
        $statement = $pdo->prepare("INSERT INTO todo (name) VALUES (:name)");
        $statement->bindParam('name', $todoName);
        $statement->execute();
    }

    return $response->withHeader('Location', '/todo')->withStatus(302);
});

// Route to reset all tasks
$app->post('/todo/reset', function ($request, $response) {
    global $pdo;

    $statement = $pdo->prepare("DELETE FROM todo");
    $statement->execute();

    return $response->withHeader('Location', '/todo')->withStatus(302);
});

// Route to remove a specific task
$app->post('/todo/remove', function ($request, $response) {
    global $pdo;

    $id = $request->getParsedBody()['removeTodo'];

    $statement = $pdo->prepare('DELETE FROM todo WHERE id = :id;');
    $statement->execute(['id' => $id]);

    return $response->withHeader('Location', '/todo')->withStatus(302);
});

// Route to modify a task
$app->post('/todo/modify', function ($request, $response) {
    global $pdo;

    $newValue = $request->getParsedBody()['modifyTodo'];
    $todoId = $request->getParsedBody()['todoId'];

    $statement = $pdo->prepare('UPDATE todo SET name = :newValue WHERE id = :todoId');
    $statement->execute(['newValue' => $newValue, 'todoId' => $todoId]);

    return $response->withHeader('Location', '/todo')->withStatus(302);
});


$app->run();
