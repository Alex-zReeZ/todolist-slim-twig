<?php
/* require autoload */
require __DIR__ . '/../vendor/autoload.php';

/* start session */
session_start();

/* Require connection to database */
require __DIR__ . '/../src/connectToDatabase.php';

/* Require function for todolist */
require __DIR__ . "/../src/todoFunction.php";
