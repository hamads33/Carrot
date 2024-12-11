<?php
session_start();
require 'C:/xampp/htdocs/Carrot/db_config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_pages/login.php");
    exit();
}

// Check if the form is submitted for creating a community
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $user_id = $_SESSION['user_id']; // The user creating the community

    $profile_pic_path = null;

    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_name = $_FILES['profile_pic']['name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $new_file_name = 'community_' . time() . '.' . $file_extension;
            $upload_path = 'uploads/community_pics/' . $new_file_name;
            $full_upload_path = __DIR__ . '/' . $upload_path;

            if (move_uploaded_file($file_tmp, $full_upload_path)) {
                $profile_pic_path = $upload_path;
            }
        }
    }

    // Validate form data
    if (empty($name)) {
        $error_message = "Community name is required.";
    } else {
        // Insert the new community into the database
        $query = "INSERT INTO communities (name, description, user_id, profile_pic) 
                  VALUES ('$name', '$description', '$user_id', '$profile_pic_path')";
        $result = mysqli_query($conn, $query);

        if ($result) {
            $success_message = "Community created successfully!";
            $community_id = mysqli_insert_id($conn);

            // Automatically join the creator to the community
            $join_query = "INSERT INTO community_members (user_id, community_id) VALUES ('$user_id', '$community_id')";
            mysqli_query($conn, $join_query);

            // Redirect to the community page
            echo "<script>
                    alert('$success_message');
                    window.location.href = 'community.php?id=$community_id';
                  </script>";
            exit();
        } else {
            $error_message = "Error creating community. Please try again.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Community</title>
    <style>
       body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}

/* Header */
.header {
    background-color: #ff4500;
    padding: 15px 20px;
    color: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar {
    display: flex;
    gap: 15px;
}

.navbar button {
    background-color: white;
    color: #ff4500;
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}

.navbar button:hover {
    background-color: #e03e00;
    color: white;
}

/* Create Community Area */
.create-community-container {
    max-width: 600px;
    margin: 100px auto 20px; /* Adjust for fixed navbar */
    padding: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.create-community-container h1 {
    text-align: center;
    color: #333;
    font-size: 28px;
    margin-bottom: 20px;
}

.create-community-container .form-group {
    margin-bottom: 15px;
}

.create-community-container .form-group label {
    display: block;
    font-weight: bold;
    color: #555;
    margin-bottom: 5px;
    font-size: 14px;
}

.create-community-container .form-group input, 
.create-community-container .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    color: #333;
    background-color: #f9f9f9;
    transition: border-color 0.3s;
}

.create-community-container .form-group input:focus, 
.create-community-container .form-group textarea:focus {
    border-color: #ff4500;
    outline: none;
    background-color: #fff;
}

.create-community-container .form-group textarea {
    resize: none;
}

.create-community-container .form-group button {
    width: 100%;
    padding: 12px;
    background-color: #ff4500;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.create-community-container .form-group button:hover {
    background-color: #e03e00;
}

.create-community-container .error, 
.create-community-container .success {
    text-align: center;
    font-size: 16px;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
}

.create-community-container .error {
    background-color: #f8d7da;
    color: #721c24;
}

.create-community-container .success {
    background-color: #d4edda;
    color: #155724;
}

    </style>
</head>
<body>
<?php include 'C:/xampp/htdocs/Carrot/Reuseable_Assets/navbar.php'; ?>

<div class="create-community-container">
    <h1>Create a New Community</h1>

    <?php if (isset($error_message)): ?>
        <div class="error"><?php echo $error_message; ?></div>
    <?php elseif (isset($success_message)): ?>
        <div class="success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="createc.php" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Community Name</label>
            <input type="text" id="name" name="name" placeholder="Enter community name" required>
        </div>
        <div class="form-group">
            <label for="description">Community Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Enter community description"></textarea>
        </div>
        <div class="form-group">
            <label for="profile_pic">Community Profile Picture</label>
            <input type="file" id="profile_pic" name="profile_pic" accept=".jpg,.jpeg,.png,.gif">
        </div>
        <div class="form-group">
            <button type="submit">Create Community</button>
        </div>
    </form>
</div>

</body>
</html>