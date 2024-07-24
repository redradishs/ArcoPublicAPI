<?php

require_once "global.php";


class Get extends GlobalMethods{

    private $pdo;

    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }


    public function gettasks($data) {
        $sqlString = "SELECT * FROM tasks WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sqlString);
        $stmt->bindParam(1, $data, PDO::PARAM_INT); 
        $stmt->execute();
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    
        if ($result) { 
            return $this->getResponse($result, "Success", null, 200); 
        } else {
            return $this->getResponse(null, "Failed", "Failed to retrieve", 404); 
        }
    }

    public function getuser($id) {
        $sqlString = "SELECT name FROM users WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sqlString);
        $stmt->bindParam(1, $id, PDO::PARAM_INT); 
        $stmt->execute();
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    
        if ($result) { 
            return $this->getResponse($result, "Success", null, 200); 
        } else {
            return $this->getResponse(null, "Failed", "Failed to retrieve", 404); 
        }
    }

    public function gettaski() {
        try {
            $sqlString = "SELECT * FROM tasks";
            $stmt = $this->pdo->prepare($sqlString);
    
            if (!$stmt) {
                return $this->getResponse(null, "Failed", "Failed to prepare statement", 500);
            }
    
            if (!$stmt->execute()) {
                return $this->getResponse(null, "Failed", "Failed to execute statement", 500);
            }
    
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if ($result) {
                return $this->getResponse($result, "Success", null, 200);
            } else {
                return $this->getResponse(null, "Failed", "No records found", 404);
            }
        } catch (Exception $e) {
            return $this->getResponse(null, "Failed", "Exception: " . $e->getMessage(), 500);
        }
    }
    

    public function getonetask($data) {
        try {
            $sqlString = "SELECT * FROM tasks WHERE id = ?";
            $stmt = $this->pdo->prepare($sqlString);
    
            if (!$stmt) {
                return $this->getResponse(null, "Failed", "Failed to prepare statement", 500);
            }
    
            $stmt->bindParam(1, $data, PDO::PARAM_INT);
    
            if (!$stmt->execute()) {
                return $this->getResponse(null, "Failed", "Failed to execute statement", 500);
            }
    
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($result) {
                return $this->getResponse($result, "Success", null, 200);
            } else {
                return $this->getResponse(null, "Failed", "No record found", 404);
            }
        } catch (Exception $e) {
            return $this->getResponse(null, "Failed", "Exception: " . $e->getMessage(), 500);
        }
    }

    public function getstatus($status) {
        try {
            $sqlString = "SELECT * FROM tasks WHERE status = ?";
            $stmt = $this->pdo->prepare($sqlString);
    
            if (!$stmt) {
                return $this->getResponse(null, "Failed", "Failed to prepare statement", 500);
            }
    
            $stmt->bindParam(1, $status, PDO::PARAM_STR);
    
            if (!$stmt->execute()) {
                return $this->getResponse(null, "Failed", "Failed to execute statement", 500);
            }
    
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if ($result) {
                return $this->getResponse($result, "Success", null, 200);
            } else {
                return $this->getResponse(null, "Failed", "No records found", 404);
            }
        } catch (Exception $e) {
            return $this->getResponse(null, "Failed", "Exception: " . $e->getMessage(), 500);
        }
    }
    
}


?>