<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

//register
$app->post('/user/register', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['conn'];

    $body = $request->getBody();
    $jsonData = json_decode($body, true);

    $hashpwd = password_hash($jsonData['password'], PASSWORD_DEFAULT);

    $sql = "SELECT * FROM user WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $jsonData['username']);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $sql = "INSERT INTO user (username,password,name,img) VALUES (?, ?, ?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $jsonData['username'], $hashpwd, $jsonData['name'], $jsonData['img']);
        $stmt->execute();

        $response->getBody()->write(json_encode("สมัครสมาชิกสำเร็จ", JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
        return $response->withStatus(201);
    } else {
        $response->getBody()->write(json_encode("มีชื่อผู้นี้อยู่แล้ว", JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
        return $response->withStatus(202);
    }
});

//login
$app->post('/user/login', function (Request $request, Response $response) {
    $conn = $GLOBALS['conn'];

    $body = $request->getBody();
    $jsonData = json_decode($body, true);
    $username = $jsonData['username'];
    $password = $jsonData['password'];

    $sql = "  SELECT  * 
            FROM    user 
            WHERE   username = ? ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $status = 201;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $pwdIndb = $row['password'];

        if (password_verify($password, $pwdIndb)) {
            $response->getBody()->write(json_encode($row, JSON_UNESCAPED_UNICODE));
            $status = 200;
        } else {
            $response->getBody()->write(json_encode("รหัสผ่านผิด", JSON_UNESCAPED_UNICODE));
        }
    } else {
        $response->getBody()->write(json_encode("ชื่อผู้ใช้ผิด", JSON_UNESCAPED_UNICODE));
    }
    return $response->withStatus($status);
});

//editUser
$app->put('/user/edit/{id}', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['conn'];
    $json = $request->getBody();
    $jsonData = json_decode($json, true);
    $id = $args['id'];
    $hashpwd = password_hash($jsonData['password'], PASSWORD_DEFAULT);
    $sql = 'update user set name=?, username=? , password=? , img=? where uid = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $jsonData['name'], $jsonData['username'], $hashpwd, $jsonData['img'], $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    if ($affected > 0) {
        $data = ["affected_rows" => $affected];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
});

//allUser
$app->get('/user', function (Request $request, Response $response) {
    $conn = $GLOBALS['conn'];
    $sql = 'SELECT *
            FROM user';
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = array();
    foreach ($result as $row) {
        array_push($data, $row);
    }

    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});
