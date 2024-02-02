<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;

$app = AppFactory::create();
$twig = Twig::create(__DIR__ . '/../template', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

require "connectToDatabase.php";

$app->get('/', function (Request $request, Response $response) {
    global $pdo;
    $view = Twig::fromRequest($request);

    $statement = $pdo->prepare("SELECT * FROM todo");
    $statement->execute();

    $todos = $statement->fetchAll();

    return $view->render($response, 'user.twig', [
        'todos' => $todos
    ]);
});

$app->post('/', function ($request, $response) {
    global $pdo;

    $todoName = $request->getParsedBody()['todo'];
    $resetTodos = $request->getParsedBody()['resetDatabase'];
    $removeTodo = $request->getParsedBody()['removeTodo'];


    if ($todoName) {
        $statement = $pdo->prepare("INSERT INTO todo (name) VALUES (:name)");
        $statement->bindParam(':name', $todoName);
        $statement->execute();
    } elseif ($resetTodos) {
        $statement = $pdo->prepare("DELETE FROM todo");
        $statement->execute();
    } elseif ($removeTodo) {
        $id = $removeTodo;
        $statement = $pdo->prepare('DELETE FROM todo WHERE id = :id;');
        $statement->execute(['id' => $id]);
    }


    $todos = $pdo->query("SELECT * FROM todo")->fetchAll();


    $view = Twig::fromRequest($request);
    return $view->render($response, 'user.twig', [
        'todos' => $todos
    ]);
});



$app->run();