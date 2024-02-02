<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$host = __DIR__ . '/../database.db';
$dsn = "sqlite:$host";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, null, null, $options);

$app = AppFactory::create();
$twig = Twig::create(__DIR__ . '/../template', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/toto', function (Request $request, Response $response) {
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

    $todoName = $request->getParsedBody()['todo'] ?? null;

    if ($todoName) {
        $statement = $pdo->prepare("INSERT INTO todo (name) VALUES (:name)");
        $statement->bindParam(':name', $todoName);
        $statement->execute();
    }

    $todos = $pdo->query("SELECT * FROM todo")->fetchAll();

    $view = Twig::fromRequest($request);
    return $view->render($response, 'user.twig', [
        'todos' => $todos
    ]);
});



$app->run();