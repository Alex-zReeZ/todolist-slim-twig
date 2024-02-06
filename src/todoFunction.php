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

// Require dataBase
require "connectToDatabase.php";

// All function
// display all the todos
$app->get('/todo', function (Request $request, Response $response) {
    global $pdo;
    $view = Twig::fromRequest($request);

    $stmt = $pdo->prepare("SELECT * FROM todo");
    $stmt->execute();

    $todos = $stmt->fetchAll();

    return $view->render($response, 'todo.twig', [
        'todos' => $todos
    ]);
});

// Add a new todo
$app->post('/todo/add', function ($request, $response) {
    global $pdo;

    $todoName = $request->getParsedBody()['todo'];

    if ($todoName) {
        $stmt = $pdo->prepare("INSERT INTO todo (name) VALUES (:name)");
        $stmt->bindParam('name', $todoName);
        $stmt->execute();
    }

    return $response->withHeader('Location', '/todo')->withStatus(302);
});

// Reset all todos
$app->post('/todo/reset', function ($request, $response) {
    global $pdo;

    $stmt = $pdo->prepare("DELETE FROM todo");
    $stmt->execute();

    return $response->withHeader('Location', '/todo')->withStatus(302);
});

// Remove a specific todos
$app->post('/todo/remove', function ($request, $response) {
    global $pdo;

    $id = $request->getParsedBody()['removeTodo'];

    $stmt = $pdo->prepare('DELETE FROM todo WHERE id = :id;');
    $stmt->execute(['id' => $id]);

    return $response->withHeader('Location', '/todo')->withStatus(302);
});

// Modify a todos
$app->post('/todo/modify', function ($request, $response) {
    global $pdo;

    $newValue = $request->getParsedBody()['modifyTodo'];
    $todoId = $request->getParsedBody()['todoId'];

    $stmt = $pdo->prepare('UPDATE todo SET name = :newValue WHERE id = :todoId');
    $stmt->execute(['newValue' => $newValue, 'todoId' => $todoId]);

    return $response->withHeader('Location', '/todo')->withStatus(302);
});

// Sort by alphabetical order
$app->get('/todo/sortAZ', function ($request, $response) {
    global $pdo;
    $view = Twig::fromRequest($request);

    $stmt = $pdo->prepare('SELECT * FROM todo ORDER BY name');
    $stmt->execute();

    $todos = $stmt->fetchAll();
    return $view->render($response, 'todo.twig', [
        'todos' => $todos,
    ]);
});

// Sort by inverse alphabetical order
$app->get('/todo/sortZA', function ($request, $response) {
    global $pdo;
    $view = Twig::fromRequest($request);

    $stmt = $pdo->prepare('SELECT * FROM todo ORDER BY name desc');
    $stmt->execute();

    $todos = $stmt->fetchAll();

    $response->withHeader('Location', '/todo/{name}');
    return $view->render($response, 'todo.twig', [
        'todos' => $todos,
    ]);
});

// Show todo that were marked done
$app->post('/todo/done', function ($request, $response) {
    global $pdo;

    $view = Twig::fromRequest($request);

    $postData = $request->getParsedBody()['done'];

    $addData = $pdo->prepare("INSERT INTO done (name, id) SELECT name, id FROM todo WHERE id = :id");
    $addData->execute(['id' => $postData]);

    $removeData = $pdo->prepare("DELETE FROM todo WHERE id = :id");
    $removeData->execute(['id' => $postData]);

    $dones = $pdo->query("SELECT * FROM done")->fetchAll();

    return $view->render($response, 'doneTodo.twig', [
        'dones' => $dones
    ]);
});

// Show the todo written in the url
$app->get('/todo/z{name}', function ($request, $response, $args) {
    global $pdo;

    $view = Twig::fromRequest($request);

    $name = $args['name'];

    $stmt = $pdo->prepare("SELECT * FROM todo WHERE name LIKE :name");
    $stmt->bindParam(':name', $name);
    $stmt->execute();

    $todo = $stmt->fetch();

    return $view->render($response, 'TargetTodo.twig', [
        'todos' => [$todo]
    ]);
});

// Show the todo written in the search bar
$app->get('/search', function ($request, $response) {
    global $pdo;

    $view = Twig::fromRequest($request);

    $searchTerm = $request->getQueryParams()['searchTodo'];

    $stmt = $pdo->prepare("SELECT * FROM todo WHERE name LIKE :searchTerm");
    $str = "%$searchTerm%";
    $stmt->bindParam(':searchTerm', $str);
    $stmt->execute();

    $todos = $stmt->fetchAll();

    return $view->render($response, 'targetTodo.twig', [
        'todos' => $todos
    ]);
});



$app->run();