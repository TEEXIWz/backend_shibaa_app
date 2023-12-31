<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

//searchAll
$app->get('/post', function (Request $request, Response $response) {
    $conn = $GLOBALS['conn'];
    $sql = 'SELECT      id,user.uid,name,title,post.description,liked,created_at,post.img,user.img as uimg
            FROM        post
            INNER JOIN  user
            ON          post.uid = user.uid
            ORDER BY    id desc';
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = array();
    foreach ($result as $row) {
        array_push($data, $row);
    }

    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

//search PostTags
$app->get('/posttag/{tid}', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['conn'];
    $sql = 'SELECT id,user.uid,name,title,post.description,liked,created_at,post.img,user.img as uimg
            FROM ((post
            INNER JOIN  post_tags
            ON          post.id = post_tags.pid)
            INNER JOIN  user
            ON          post.uid = user.uid)
            WHERE tid=?';
    $stmt = $conn->prepare($sql);

    $stmt->bind_param('s', $args['tid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = array();
    foreach ($result as $row) {
        array_push($data, $row);
    }

    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

//searchByUserID
$app->get('/postbyuser/{id}', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['conn'];
    $id = $args['id'];
    $sql = 'SELECT      id,user.uid,name,title,post.description,liked,created_at,post.img,user.img as uimg
            FROM        post
            INNER JOIN  user
            ON          post.uid = user.uid
            WHERE       user.uid = ?
            ORDER BY    id desc';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = array();
    foreach ($result as $row) {
        array_push($data, $row);
    }

    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

//searchByPostID
$app->get('/post/{id}', function (Request $request, Response $response, $args) {
    $conn = $GLOBALS['conn'];
    $id = $args['id'];
    $sql = 'SELECT      id,user.uid,name,title,post.description,liked,created_at,post.img,user.img as uimg
            FROM        post
            INNER JOIN  user
            ON          post.uid = user.uid
            WHERE       id = ?
            ORDER BY    id desc';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $response->getBody()->write(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

$app->post('/post', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);

    $conn = $GLOBALS['conn'];
    $sql = 'INSERT INTO post (uid,title, description, liked, created_at, img)
            VALUES (?, ?, ?, 0, NOW(), ?)';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $jsonData['uid'], $jsonData['title'], $jsonData['description'], $jsonData['img']);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    if ($affected > 0) {
        $data = ["affected_rows" => $affected, "last_fid" => $conn->insert_id];
        $response->getBody()->write(json_encode($conn->insert_id));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }
});

//edit
$app->post('/post/edit/{id}', function (Request $request, Response $response, $args) {
    $json = $request->getBody();
    $jsonData = json_decode($json, true);
    $id = $args['id'];
    $conn = $GLOBALS['conn'];
    $sql = 'UPDATE post set title=?, description=?, img=? where id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $jsonData['title'], $jsonData['description'], $jsonData['img'], $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    if ($affected > 0) {
        $data = ["affected_rows" => $affected];
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }
});

//delete 
$app->delete('/post/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $conn = $GLOBALS['conn'];
    $sql = 'delete from post_tags where pid = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $sql = 'delete from post where id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
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
