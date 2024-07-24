<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
    }
    exit(0);
}

require_once("config/database.php");
require_once("modules/post.php");
require_once("modules/get.php");




$con = new Connection();
$pdo = $con->connect();


$get = new Get($pdo);
$post = new Post($pdo);


if (isset($_REQUEST['request'])) {

    $request = explode('/', $_REQUEST['request']);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Not Found"]);
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($request[0]) {
            case 'tasker':   //get all
                if (count($request) > 1) {
                    echo json_encode($get->gettasks($request[1]));
                } else {
                    echo json_encode($get->gettasks($data));
                }
                break;

            case 'taskerr': //get all under one user_id
                if (count($request) > 1) {
                    echo json_encode($get->gettaski($request[1]));
                } else {
                    echo json_encode($get->gettaski());
                }
                break;


            case 'username':   //get_username
                if (count($request) > 1) {
                    echo json_encode($get->getuser($request[1]));
                } else {
                    echo json_encode($get->getuser($data));
                }
                break;

            case 'onetask':  //get_one_task
                if (count($request) > 1) {
                    echo json_encode($get->getonetask($request[1]));
                } else {
                    echo json_encode($get->getonetask($data));
                }
                break;

            case 'getstatus': //get status string
                if (count($request) > 1) {
                    echo json_encode($get->getstatus($request[1]));
                } else {
                    echo json_encode($get->getstatus($data));
                }
                break;

            default:
                http_response_code(403);
                echo json_encode(["error" => "Forbidden"]);
                break;
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        switch ($request[0]) {

            case 'signup':  //signup
                echo $post->signup($data);
                break;

            case 'login':  //login
                echo $post->login($data);
                break;
            case 'edit_task': //done working
                echo json_encode($post->edit_task($data, $request[1]));
                break;
            case 'delete_task': //done working
                echo json_encode($post->delete_tasker($request[1]));
                break;
            case 'addtask': //relevant done working
                echo json_encode($post->addTask($data, $request[1]));
                break;
            case 'update_status': //done working
                echo json_encode($post->changestatus($data, $request[1]));
                break;
            case 'update_task_order':
                echo json_encode($post->updateTaskOrder($data));
                break;



            default:
                http_response_code(403);
                echo json_encode(["error" => "Forbidden"]);
                break;
        }
        break;
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        break;
}
