    <?php
    require_once("global.php");
    class Post extends GlobalMethods {

        private $pdo;

        public function __construct(\PDO $pdo){
            $this->pdo = $pdo;
        }

        public function signup($data) {
            if (empty($data['email']) || empty($data['password']) || empty($data['username'])) {
                return [
                    'status' => 'error',
                    'message' => 'Email, username, and password are required.'
                ];
            }
    
            $email = $data['email'];
            $username = $data['username'];
            $password = $data['password'];
    
            $query = "SELECT * FROM users WHERE email = :email OR username = :username LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($user) {
                return [
                    'status' => 'error',
                    'message' => 'Email or username already exists.'
                ];
            }
    
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
            $query = "INSERT INTO users (email, username, password) VALUES (:email, :username, :password)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashedPassword);
    
            if ($stmt->execute()) {
                return [
                    'status' => 'success',
                    'message' => 'Signup successful.'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Signup failed. Please try again.'
                ];
            }
        }
    

        public function login($data) {
            if (empty($data['email']) || empty($data['password'])) {
                $this->sendPayload(null, "failed", "Email and password are required.", 404);
            }
    
            $email = trim($data['email']);
            $password = trim($data['password']);
    
            // Debugging: Log received email and password
            error_log("Login attempt: email = $email, password = $password");
    
            $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($user) {
                // Debugging: Log fetched user data
                error_log("User found: " . print_r($user, true));
    
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    return [
                        'status' => 'success',
                        'message' => 'Login successful.'
                    ];
                } else {
                    error_log("Password mismatch for user: $email");
                }
            } else {
                error_log("User not found: $email");
            }

            return [
                'status' => 'error',
                'message' => 'Invalid email or password.'
            ];
        }

    
        
        // public function login($data) {
        //     try {
        //         // Prepare and execute the query to fetch user information based on email
        //         $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        //         $stmt->bindParam(':email', $data->email);
        //         $stmt->execute();
        
        //         // Fetch the result
        //         $stmt_flag = $stmt->rowCount();
        
        //         if ($stmt_flag > 0) {
        //             $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        //             // Check if the provided password matches the stored hash
        //             if (password_verify($data->password, $userData['password'])) {
        //                 // Passwords match, login successful
                        
        
        //                 return $this->sendPayload(null, "Successfully logged in", "Success", 200);
        //             } else {
        //                 // Passwords do not match
        //                 return $this->sendPayload(null, "Incorrect email or password", "Error", 400);
        //             }
        //         } else {
        //             // User not found
        //             return $this->sendPayload(null, "Incorrect email or password", "Error", 400);
        //         }
        //     } catch (\Exception $e) {
        //         return $this->sendPayload(null, "Failed to login", $e->getMessage(), 500);
        //     }
        // }
        




        function insertReport($data) {
            // Check for required fields
            if (!isset($data->title) || !isset($data->description)) {
                return $this->sendResponse("Missing fields", null, 400);
            }
        
            try {
                // Prepare and execute the SQL statement
                $stmt = $this->pdo->prepare("INSERT INTO reports (title, description, date_created, user_id) 
                                            VALUES (:title, :description, NOW(), :user_id)");
                $stmt->execute([
                    'title' => $data->title,
                    'description' => $data->description,
                    'user_id' => 1
                ]);
        
                // Get the ID of the newly inserted report
                $lastInsertId = $this->pdo->lastInsertId(); 
        
                return $this->sendPayload(null, "Success", "Successfully Added", 200);
            } catch (\PDOException $e) {

                return $this->sendResponse("Failed to generate report. Please try again later.", null, 500);
            }
        }


        function flipbook($data, $user_id) {
            // Check for required fields
            // if (!isset($data->reportid) || !isset($data->collageid)) {
            //     return $this->sendResponse("Missing fields", null, 400);
            // }
            
        
            try {
                // Prepare and execute the SQL statement
                $stmt = $this->pdo->prepare("INSERT INTO flipbook (user_id, report_id, collage_id) 
                                            VALUES (:user_id, :report_id, :collage_id)");
                $stmt->execute([
                    'user_id' => $user_id,
                    'report_id' => $data->report_id,
                    'collage_id' => $data->collage_id
                    
                ]);
        
                
                $lastInsertId = $this->pdo->lastInsertId(); 
        
                return $this->sendResponse("Report generated successfully", null, 200);
            } catch (\PDOException $e) {

                return $this->sendResponse("Failed to generate report. Please try again later.", null, 500);
            }
        }

     
        function uploadImage($data) {
            try {
                $stmt = $this->pdo->prepare("SELECT report_id FROM reports WHERE user_id = 1 ORDER BY report_id DESC LIMIT 1");
                $stmt->execute();
                $result = $stmt->fetchColumn(); 
        
                if ($result) {
                    $reportId = $result;
                } else {
                    return $this->sendResponse("No existing reports found for this user.", null, 404); 
                }
            } catch (\PDOException $e) {
                return $this->sendResponse("Error retrieving report ID.", null, 500); 
            }
        
            if (!isset($_FILES['image'])) {
                return $this->sendResponse("Missing image file", null, 400);
            }
        
            $imagePath = $_FILES['image']['tmp_name']; 
            $fileDestination = 'C:\xampp\htdocs\arco2\images';

            $filename = basename($_FILES['image']['name']); 
            $targetFilePath = $fileDestination . '/' . $filename; 
        
            if (!file_exists($fileDestination)) {
                mkdir($fileDestination, 0755, true); 
            }
        
            if (!move_uploaded_file($imagePath, $targetFilePath)) {
                return $this->sendResponse("Failed to move uploaded image.", null, 500); 
            }
        
            
            try {
                $stmt = $this->pdo->prepare("INSERT INTO collage (report_id, user_id, image_path) VALUES (:report_id, :user_id, :image_path)");
                $stmt->execute([
                    'report_id' => $reportId,
                    'user_id' => 1,
                    'image_path' => $targetFilePath 
                ]);
        
                return $this->sendPayload(null, "Success", "Image uploaded successfully", 200);
            } catch (\PDOException $e) {
                return $this->sendResponse("Failed to upload image.", null, 500); 
            }
        }
        


        //Final Done
        public function annualreports($data, $id) {
            
            $sql = "INSERT INTO annualreports (
                        title,
                        year,
                        executive_summary,
                        company_achievements,
                        financial_statements,
                        management_discussion,
                        future_outlook,
                        user_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
            try {
                
                $statement = $this->pdo->prepare($sql);
        
                
                $statement->execute([
                    $data->title,
                    $data->year,
                    $data->executive_summary,
                    $data->company_achievements,
                    $data->financial_statements,
                    $data->management_discussion,
                    $data->future_outlook,
                    $id 
                ]);
        
                
                return $this->sendPayload(null, "success", "Successfully created a new record.", 200);
            } catch (\PDOException $e) {
                
                $errmsg = $e->getMessage();
                return $this->sendPayload(null, "failed", $errmsg, 400);
            }
        }

        //final done
        public function eventreport($data, $id) {
           
            $sql = "INSERT INTO eventreports (
                            event_name,
                            event_date,
                            event_title,
                            address,
                            expected_participants,
                            total_participants,
                            summary,
                            user_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
            try {
               
                $statement = $this->pdo->prepare($sql);
        
                
                $statement->execute([
                    $data->event_name,
                    $data->event_date,
                    $data->event_title,
                    $data->address,
                    $data->expected_participants,
                    $data->total_participants,
                    $data->summary,
                    $id
                ]);
        
                
                return $this->sendPayload(null, "success", "Successfully created a new record.", 200);
            } catch (\PDOException $e) {
                
                $errmsg = $e->getMessage();
                return $this->sendPayload(null, "failed", $errmsg, 400);
            }
        }
        
        //final done
        public function expenses($data, $user_id) {
            
            $sql = "SELECT event_id FROM eventreports WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        
            try {
                $statement = $this->pdo->prepare($sql);
                $statement->execute([$user_id]);
                $latestEventId = $statement->fetchColumn(0);
        
                if ($latestEventId === false) {
                    return $this->sendPayload(null, "failed", "No recent event found for this user.", 404);
                }
        
                // SQL query to insert expense data with multiple columns
                $insertSql = "INSERT INTO eventexpenses (
                    event_id,
                    expense_item,
                    expense_amount,
                    expense_item1, expense_amount1,
                    expense_item2, expense_amount2,
                    expense_item3, expense_amount3,
                    expense_item4, expense_amount4,
                    expense_item5, expense_amount5,
                    expense_item6, expense_amount6,
                    expense_item7, expense_amount7,
                    expense_item8, expense_amount8,
                    expense_item9, expense_amount9,
                    expense_item10, expense_amount10
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
        
                // Validate data and set default values to avoid mismatches
                $values = [
                    $latestEventId,
                    $data->expense_item ?? null, $data->expense_amount ?? null,
                    $data->expense_item1 ?? null, $data->expense_amount1 ?? null,
                    $data->expense_item2 ?? null, $data->expense_amount2 ?? null,
                    $data->expense_item3 ?? null, $data->expense_amount3 ?? null,
                    $data->expense_item4 ?? null, $data->expense_amount4 ?? null,
                    $data->expense_item5 ?? null, $data->expense_amount5 ?? null,
                    $data->expense_item6 ?? null, $data->expense_amount6 ?? null,
                    $data->expense_item7 ?? null, $data->expense_amount7 ?? null,
                    $data->expense_item8 ?? null, $data->expense_amount8 ?? null,
                    $data->expense_item9 ?? null, $data->expense_amount9 ?? null,
                    $data->expense_item10 ?? null, $data->expense_amount10 ?? null
                ];
        
        
                // Prepare and execute the SQL statement with the correct number of values
                $statement = $this->pdo->prepare($insertSql);
                $statement->execute($values);
        
                return $this->sendPayload(null, "success", "Expense record successfully created.", 200);
        
            } catch (\PDOException $e) {
                return $this->sendPayload(null, "failed", "Database error: " . $e->getMessage(), 400);
            } catch (Exception $e) {
                return $this->sendPayload(null, "failed", "Error: " . $e->getMessage(), 400);
            }
        }


        public function insertFinancialReport($data, $id) {
            $sql = "INSERT INTO FinancialReports (
                        report_title,
                        prepared_by,
                        start_date,
                        end_date,
                        income1,
                        income_salary1,
                        income2,
                        income_salary2,
                        income3,
                        income_salary3,
                        income4,
                        income_salary4,
                        income5,
                        income_salary5,
                        income6,
                        income_salary6,
                        income7,
                        income_salary7,
                        income8,
                        income_salary8,
                        income9,
                        income_salary9,
                        income10,
                        income_salary10,
                        expense_item1,
                        expense_amount1,
                        expense_item2,
                        expense_amount2,
                        expense_item3,
                        expense_amount3,
                        expense_item4,
                        expense_amount4,
                        expense_item5,
                        expense_amount5,
                        expense_item6,
                        expense_amount6,
                        expense_item7,
                        expense_amount7,
                        expense_item8,
                        expense_amount8,
                        expense_item9,
                        expense_amount9,
                        expense_item10,
                        expense_amount10,
                        executive_summary,
                        user_id
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )";
        
            try {
                $statement = $this->pdo->prepare($sql);
        
                $statement->execute([
                    $data->report_title,
                    $data->prepared_by,
                    $data->start_date,
                    $data->end_date,
                    $data->income1,
                    $data->income_salary1,
                    $data->income2,
                    $data->income_salary2,
                    $data->income3,
                    $data->income_salary3,
                    $data->income4,
                    $data->income_salary4,
                    $data->income5,
                    $data->income_salary5,
                    $data->income6,
                    $data->income_salary6,
                    $data->income7,
                    $data->income_salary7,
                    $data->income8,
                    $data->income_salary8,
                    $data->income9,
                    $data->income_salary9,
                    $data->income10,
                    $data->income_salary10,
                    $data->expense_item1,
                    $data->expense_amount1,
                    $data->expense_item2,
                    $data->expense_amount2,
                    $data->expense_item3,
                    $data->expense_amount3,
                    $data->expense_item4,
                    $data->expense_amount4,
                    $data->expense_item5,
                    $data->expense_amount5,
                    $data->expense_item6,
                    $data->expense_amount6,
                    $data->expense_item7,
                    $data->expense_amount7,
                    $data->expense_item8,
                    $data->expense_amount8,
                    $data->expense_item9,
                    $data->expense_amount9,
                    $data->expense_item10,
                    $data->expense_amount10,
                    $data->executive_summary,
                    $id
                ]);
        
                return $this->sendPayload(null, "success", "Financial report created successfully.", 201);
            } catch (\PDOException $e) {
                $errmsg = $e->getMessage();
        
                if (strpos($errmsg, 'foreign key constraint') !== false) {
                    return $this->sendPayload(null, "failed", "Foreign key constraint violation: User ID does not exist.", 400);
                }
        
                return $this->sendPayload(null, "failed", $errmsg, 400);
            }
        }


        //final
        public function edit_eventreport($data, $id){
            $sql = "UPDATE eventreports
            SET 
                event_name = ?,
                event_date = ?,
                event_title = ?,
                address = ?,
                expected_participants = ?,
                total_participants = ?,
                summary = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE
                event_id = ?;";
            try{
                $statement = $this->pdo->prepare($sql);
                $statement->execute(
                    [
                      $data->event_name,
                      $data->event_date,
                      $data->event_title,
                      $data->address,
                      $data->expected_participants,
                      $data->total_participants,
                      $data->summary,
                      $id
                    ]
                );
                return $this->sendPayload(null, "success", "Successfully updated record.", 200);
        
            }
            catch(\PDOException $e){
                $errmsg = $e->getMessage();
                $code = 400;
            }
           
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }

        //final
        public function edit_financialreport($data, $id){
            $sql = "UPDATE financialreports
            SET 
                report_title = ?,
                prepared_by = ?,
                start_date = ?,
                end_date = ?,
                income1 = ?,
                income_salary1 = ?,
                income2 = ?,
                income_salary2 = ?,
                income3 = ?,
                income_salary3 = ?,
                income4 = ?,
                income_salary4 = ?,
                income5 = ?,
                income_salary5 = ?,
                income6 = ?,
                income_salary6 = ?,
                income7 = ?,
                income_salary7 = ?,
                income8 = ?,
                income_salary8 = ?,
                income9 = ?,
                income_salary9 = ?,
                income10 = ?,
                income_salary10 = ?,
                expense_item1 = ?,
                expense_amount1 = ?,
                expense_item2 = ?,
                expense_amount2 = ?,
                expense_item3 = ?,
                expense_amount3 = ?,
                expense_item4 = ?,
                expense_amount4 = ?,
                expense_item5 = ?,
                expense_amount5 = ?,
                expense_item6 = ?,
                expense_amount6 = ?,
                expense_item7 = ?,
                expense_amount7 = ?,
                expense_item8 = ?,
                expense_amount8 = ?,
                expense_item9 = ?,
                expense_amount9 = ?,
                expense_item10 = ?,
                expense_amount10 = ?,
                executive_summary = ?
            WHERE
                financialreport_id = ?;
            ";
            try{
                $statement = $this->pdo->prepare($sql);
                $statement->execute(
                    [
                      $data->report_title,
                      $data->prepared_by,
                      $data->start_date,
                      $data->end_date,
                      $data->income1,
                      $data->income_salary1,
                      $data->income2,
                      $data->income_salary2,
                      $data->income3,
                      $data->income_salary3,
                      $data->income4,
                      $data->income_salary4,
                      $data->income5,
                      $data->income_salary5,
                      $data->income6,
                      $data->income_salary6,
                      $data->income7,
                      $data->income_salary7,
                      $data->income8,
                      $data->income_salary8,
                      $data->income9,
                      $data->income_salary9,
                      $data->income10,
                      $data->income_salary10,
                      $data->expense_item1,
                      $data->expense_amount1,
                      $data->expense_item2,
                      $data->expense_amount2,
                      $data->expense_item3,
                      $data->expense_amount3,
                      $data->expense_item4,
                      $data->expense_amount4,
                      $data->expense_item5,
                      $data->expense_amount5,
                      $data->expense_item6,
                      $data->expense_amount6,
                      $data->expense_item7,
                      $data->expense_amount7,
                      $data->expense_item8,
                      $data->expense_amount8,
                      $data->expense_item9,
                      $data->expense_amount9,
                      $data->expense_item10,
                      $data->expense_amount10,
                      $data->executive_summary,
                      $id
                    ]
                );
                return $this->sendPayload(null, "success", "Successfully updated record.", 200);
        
            }
            catch(\PDOException $e){
                $errmsg = $e->getMessage();
                $code = 400;
            }
           
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }


        public function edit_annualreport($data, $id){
            $sql = "UPDATE annualreports
            SET 
                title = ?,
                year = ?,
                executive_summary = ?,
                company_achievements = ?,
                financial_statements = ?,
                management_discussion = ?,
                future_outlook = ?
            WHERE
                report_id = ?;";
            try{
                $statement = $this->pdo->prepare($sql);
                $statement->execute(
                    [
                      $data->title,
                      $data->year,
                      $data->executive_summary,
                      $data->company_achievements,
                      $data->financial_statements,
                      $data->management_discussion,
                      $data->future_outlook,
                      $id
                    ]
                );
                return $this->sendPayload(null, "success", "Successfully updated record.", 200);
        
            }
            catch(\PDOException $e){
                $errmsg = $e->getMessage();
                $code = 400;
            }
           
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }
    
        public function delete_annualreport($id){
            $sql = "DELETE FROM annualreports WHERE report_id = ?";
            try{
                $statement = $this->pdo->prepare($sql);
                $statement->execute(
                    [
                      $id
                    ]
                );
                return $this->sendPayload(null, "success", "Successfully deleted record.", 200);
        
            }
            catch(\PDOException $e){
                $errmsg = $e->getMessage();
                $code = 400;
            }
           
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }

        public function delete_financialreport($id){
            $sql = "DELETE FROM financialreports WHERE financialreport_id = ?";
            try{
                $statement = $this->pdo->prepare($sql);
                $statement->execute(
                    [
                      $id
                    ]
                );
                return $this->sendPayload(null, "success", "Successfully deleted record.", 200);
        
            }
            catch(\PDOException $e){
                $errmsg = $e->getMessage();
                $code = 400;
            }
           
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }

        public function delete_eventreport($id){
            $sql = "DELETE FROM eventreports WHERE event_id = ?";
            try{
                $statement = $this->pdo->prepare($sql);
                $statement->execute(
                    [
                      $id
                    ]
                );
                return $this->sendPayload(null, "success", "Successfully deleted record.", 200);
        
            }
            catch(\PDOException $e){
                $errmsg = $e->getMessage();
                $code = 400;
            }
           
            return $this->sendPayload(null, "failed", $errmsg, $code);
        }

    
        
        
        
        
        

     

        
        
    
        

    
    }
    ?>
