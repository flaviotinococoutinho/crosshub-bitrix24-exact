<?php

http_response_code(isset($_GET['fail']) ? 422 : 200);
header('Content-Type: application/json');
echo json_encode(array(
    'method' => $_SERVER['REQUEST_METHOD'],
    'body' => file_get_contents('php://input'),
    'token' => isset($_SERVER['HTTP_TOKEN_EXACT']) ? $_SERVER['HTTP_TOKEN_EXACT'] : null,
));
