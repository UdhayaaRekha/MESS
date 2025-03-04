<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "sql201.infinityfree.com";  
$username = "if0_38434055";  
$password = "F6ebjHoht6LasEG";  
$dbname = "if0_38434055_user_management";  

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reg_no = $_POST['reg_no'];
    $name = $_POST['name'];
    $ph_no = $_POST['ph_no'];
    $mail = $_POST['mail'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $acc_type = $_POST['acc_type'];

    // Insert query (use prepared statements for security)
    $query = "INSERT INTO users (reg_no, name, ph_no, mail, password, dob, gender, acc_type) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssss", $reg_no, $name, $ph_no, $mail, $password, $dob, $gender, $acc_type);
    
    if ($stmt->execute()) {
        echo "Registration Successful!";
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();
?>
