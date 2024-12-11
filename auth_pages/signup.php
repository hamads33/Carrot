<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$database = "carrot";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $pass = $_POST['password'];
	 $email = $_POST['email'];
    $sql = "INSERT INTO users(name,username,password,email) VALUES ('$name','$username','$pass','$email')";
    if ($conn->query($sql)) {
        echo "Registered Successfully!";
    }
	else{
echo "error";
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .form-container {
            width: 300px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container input[type="text"],
        .form-container input[type="password"],
        .form-container button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .form-container button {
            background-color: #e03e00;
            color: white;
            border: none;
        }
        .form-container button:hover {
            background-color: #e03e00;
  color: white;
        }
    </style>
</head>
<body>
	<?php include __DIR__ . '/../Reuseable_Assets/navbar.php'; ?>
    <div class="form-container">
        <h2>Signup</h2>
        <form action="" method="post">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="submit">Submit</button>
        </form>
    </div>
</body>
</html>
