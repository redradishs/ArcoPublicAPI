    <?php

    use Firebase\JWT\JWT;
    require_once("global.php");
    require 'vendor/autoload.php'; 

    class Post extends GlobalMethods {

        private $pdo;

        public function __construct(\PDO $pdo){
            $this->pdo = $pdo;
        }

        public function login($data) {
            $secret_key = "63448c0f19663276ceabdc626d7aab8855872cc7ef5b152d099c41dcbbccd4ce"; // Use your secret key here
            $issuer_claim = "http://localhost";
            $audience_claim = "http://localhost";
            $issuedat_claim = time();
            $notbefore_claim = $issuedat_claim + 10;
            $expire_claim = $issuedat_claim + 3600;
        
            if (isset($data->email) && isset($data->password)) {
                $email = $data->email;
                $password = $data->password;
        
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
        
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
                if ($user && password_verify($password, $user['password'])) {
                    $token = array(
                        "iss" => $issuer_claim,
                        "aud" => $audience_claim,
                        "iat" => $issuedat_claim,
                        "nbf" => $notbefore_claim,
                        "exp" => $expire_claim,
                        "data" => array(
                            "id" => $user['user_id'],
                            "email" => $user['email']
                        )
                    );
        
                    $jwt = JWT::encode($token, $secret_key, 'HS256');
        
                    return json_encode(array(
                        "message" => "Login successful",
                        "jwt" => $jwt,
                        "email" => $email,
                        "expireAt" => $expire_claim
                    ));
                } else {
                    http_response_code(401);
                    return json_encode(array("message" => "Invalid email or password"));
                }
            } else {
                http_response_code(400);
                return json_encode(array("message" => "Email and password are required"));
            }
        }
        
        public function signup($data) {
            $secret_key = "63448c0f19663276ceabdc626d7aab8855872cc7ef5b152d099c41dcbbccd4ce"; // Use your secret key here
            $issuer_claim = "http://localhost";
            $audience_claim = "http://localhost";
            $issuedat_claim = time();
            $notbefore_claim = $issuedat_claim + 10;
            $expire_claim = $issuedat_claim + 3600;
        
            if (isset($data->name) && isset($data->email) && isset($data->password)) {
                $name = $data->name;
                $email = $data->email;
                $password = password_hash($data->password, PASSWORD_BCRYPT);
        
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
        
                if ($stmt->rowCount() > 0) {
                    http_response_code(409);
                    return json_encode(["message" => "Email already exists"]);
                } else {
                    $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $password);
        
                    if ($stmt->execute()) {
                        $user_id = $this->pdo->lastInsertId();
                        $token = array(
                            "iss" => $issuer_claim,
                            "aud" => $audience_claim,
                            "iat" => $issuedat_claim,
                            "nbf" => $notbefore_claim,
                            "exp" => $expire_claim,
                            "data" => array(
                                "id" => $user_id,
                                "name" => $name,
                                "email" => $email
                            )
                        );
        
                        $jwt = JWT::encode($token, $secret_key, 'HS256');
                        return json_encode(
                            array(
                                "message" => "Signup successful",
                                "jwt" => $jwt,
                                "name" => $name,
                                "email" => $email,
                                "expireAt" => $expire_claim
                            )
                        );
                    } else {
                        http_response_code(500);
                        return json_encode(["message" => "Internal server error"]);
                    }
                }
            } else {
                http_response_code(400);
                return json_encode(["message" => "Username, email, and password are required"]);
            }
        }

        //add task working
        public function addTask($data, $id) {
            if (is_null($id)) {
                return $this->sendPayload(null, "failed", "User ID cannot be null.", 400);
            }
        
            if (!isset($data->title)) {
                return $this->sendPayload(null, "failed", "The fields cannot be null.", 400);
            }
        
            $sql = "INSERT INTO tasks (title, description, status, due_date, user_id) VALUES (?, ?, ?, ?, ?)";
        
            try {
                $statement = $this->pdo->prepare($sql);
        
                error_log("Inserting project report with user_id: " . $id);
                error_log("Data: " . print_r($data, true));
        
                $statement->execute([
                    $data->title,
                    $data->description,
                    $data->status,
                    $data->due_date,
                    $id 
                ]);
        
                return $this->sendPayload(null, "success", "Successfully created a new project report.", 200);
            } catch (\PDOException $e) {
                error_log("PDOException in projectreport: " . $e->getMessage());
        
                $errmsg = $e->getMessage();
                return $this->sendPayload(null, "failed", $errmsg, 400);
            }
        }
    
        //edit done
        public function edit_task($data, $id){
            $sql = "UPDATE tasks
            SET 
                title = ?,
                description = ?,
                status = ?,
                due_date = ?
            WHERE
                id = ?;";
            try{
                $statement = $this->pdo->prepare($sql);
                $statement->execute(
                    [
                      $data->title,
                      $data->description,
                      $data->status,
                      $data->due_date,
                      $id
                    ]
                );
                return $this->sendPayload(null, "success", "Successfully updated task.", 200);
        
            }
            catch(\PDOException $e){
                $errmsg = $e->getMessage();
                $code = 400;
            }
           
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }

        public function changestatus($data, $id){
            $sql = "UPDATE tasks
            SET 
                status = ?
            WHERE
                id = ?;";
            try{
                $statement = $this->pdo->prepare($sql);
                $statement->execute(
                    [
                      $data->status,
                      $id
                    ]
                );
                return $this->sendPayload(null, "success", "Successfully updated task.", 200);
        
            }
            catch(\PDOException $e){
                $errmsg = $e->getMessage();
                $code = 400;
            }
           
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }

        public function delete_tasker($id){
            $sql = "DELETE FROM tasks WHERE id = ?";
            try {
                $statement = $this->pdo->prepare($sql);
                $statement->execute([$id]);
        
                return $this->sendPayload(null, "success", "Successfully deleted task.", 200);
            } catch (\PDOException $e) {
                $errmsg = $e->getMessage();
                $code = 400;
        
                return $this->sendPayload(null, "failed", $errmsg, $code);
            }
        }


        // public function updateTaskOrder($data, $id) {
        //     $sql = "UPDATE tasks SET `order` = :order WHERE id = :id";
        
        //     try {
        //         $statement = $this->pdo->prepare($sql);
        //         $statement->execute(
        //             [
        //               $data->order,
        //               $id
        //             ]
        //         );
        //         return $this->sendPayload(null, "success", "Successfully updated task.", 200);
        
        //     }
        //     catch(\PDOException $e){
        //         $errmsg = $e->getMessage();
        //         $code = 400;
        //     }
        // }

        public function updateTaskOrder($tasks) {
            $sql = "UPDATE tasks SET `order` = :order WHERE id = :id";
        
            try {
                $this->pdo->beginTransaction();
        
                foreach ($tasks as $task) {
                    $statement = $this->pdo->prepare($sql);
                    $statement->bindParam(':order', $task->order);
                    $statement->bindParam(':id', $task->id);
                    $statement->execute();
                }
        
                $this->pdo->commit();
        
                return $this->sendPayload(null, "success", "Successfully updated task order.", 200);
            } catch (\PDOException $e) {
                $this->pdo->rollBack();
                $errmsg = $e->getMessage();
                return $this->sendPayload(null, "failed", $errmsg, 400);
            }
        }
        
        





        public function update_document($data, $id) {
            $sql = "UPDATE richtext
                    SET 
                        content = ?,
                        edited_by = ?,
                        edited_at = CURRENT_TIMESTAMP
                    WHERE
                        id = ?;";
                        
            try {
                $statement = $this->pdo->prepare($sql);
                $statement->execute(
                    [
                        $data->content,
                        $data->edited_by,
                        $id
                    ]
                );
                return $this->sendPayload(null, "success", "Successfully updated the record.", 200);
            } catch (\PDOException $e) {
                $errmsg = $e->getMessage();
                return $this->sendPayload(null, "failed", $errmsg, 400);
            }
        }


    }


        ///kfjsdljflslkdfskldjflksjlkjfldjsfj


?>
