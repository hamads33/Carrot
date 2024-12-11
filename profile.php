<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_pages/login.php");
    exit();
}

require 'db_config.php';

// Get user data from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT username, email, bio, profile_pic FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Error fetching user data: " . mysqli_error($conn);
    exit();
}

$user = mysqli_fetch_assoc($result);

// Query for posts
$query_posts = "SELECT id, title, content, created_at FROM posts WHERE user_id = $user_id";
$result_posts = mysqli_query($conn, $query_posts);

if (!$result_posts) {
    echo "Error fetching posts: " . mysqli_error($conn);
    exit();
}

$posts = mysqli_fetch_all($result_posts, MYSQLI_ASSOC);

// Query for comments and their respective posts
$query_comments = "
    SELECT c.content, c.created_at, p.title, c.post_id 
    FROM comments c 
    JOIN posts p ON c.post_id = p.id 
    WHERE c.user_id = $user_id
";
$result_comments = mysqli_query($conn, $query_comments);

if (!$result_comments) {
    echo "Error fetching comments: " . mysqli_error($conn);
    exit();
}

$comments = mysqli_fetch_all($result_comments, MYSQLI_ASSOC);
if (isset($_POST['upload_pic'])) {
    // Check if the file was uploaded
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_name = $_FILES['profile_pic']['name'];
        $file_size = $_FILES['profile_pic']['size'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        echo "File received: $file_name<br>";
        echo "Temporary path: $file_tmp<br>";
        echo "File size: $file_size bytes<br>";
        echo "File extension: $file_extension<br>";

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate file type
        if (in_array($file_extension, $allowed_extensions)) {
            echo "File type is valid.<br>";

            // Validate file size
            if ($file_size <= 5000000) {
                echo "File size is within the limit.<br>";

                // Generate new file name and set the upload path
                $new_file_name = 'profile_' . $user_id . '.' . $file_extension;
                $upload_path = 'uploads/profile_pics/' . $new_file_name;
                $full_upload_path = __DIR__ . '/' . $upload_path; // Get absolute path

                echo "Upload path: $full_upload_path<br>";

                // Move the uploaded file
                if (move_uploaded_file($file_tmp, $full_upload_path)) {
                    echo "File successfully moved to: $full_upload_path<br>";

                    // Update the database
                    $query_update_pic = "UPDATE users SET profile_pic = '$upload_path' WHERE id = $user_id";
                    if (mysqli_query($conn, $query_update_pic)) {
                        echo "Database updated successfully.<br>";
                        header("Location: profile.php");
                        exit();
                    } else {
                        echo "Error updating database: " . mysqli_error($conn) . "<br>";
                    }
                } else {
                    echo "Error moving file. Check folder permissions.<br>";
                }
            } else {
                echo "File size exceeds the limit. Maximum allowed: 5MB.<br>";
            }
        } else {
            echo "Invalid file type. Allowed types: jpg, jpeg, png, gif.<br>";
        }
    } else {
        // Provide details about the upload error
        echo "File upload error. Code: " . $_FILES['profile_pic']['error'] . "<br>";
        switch ($_FILES['profile_pic']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                echo "The uploaded file exceeds the upload_max_filesize directive in php.ini.<br>";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                echo "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.<br>";
                break;
            case UPLOAD_ERR_PARTIAL:
                echo "The uploaded file was only partially uploaded.<br>";
                break;
            case UPLOAD_ERR_NO_FILE:
                echo "No file was uploaded.<br>";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                echo "Missing a temporary folder.<br>";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                echo "Failed to write file to disk.<br>";
                break;
            case UPLOAD_ERR_EXTENSION:
                echo "A PHP extension stopped the file upload.<br>";
                break;
            default:
                echo "Unknown upload error.<br>";
                break;
        }
    }
}



// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = isset($_POST['username']) ? $_POST['username'] : $user['username'];
    $new_email = isset($_POST['email']) ? $_POST['email'] : $user['email'];
    $new_bio = isset($_POST['bio']) ? $_POST['bio'] : $user['bio'];

    $query_update = "UPDATE users SET username = '$new_username', email = '$new_email', bio = '$new_bio' WHERE id = $user_id";
    $update_result = mysqli_query($conn, $query_update);

    if ($update_result) {
        header("Location: profile.php");
        exit();
    } else {
        echo "Error updating profile.";
        exit();
    }
}
// Query for communities created by the user
$query_communities = "SELECT id, name, description FROM communities WHERE user_id = $user_id";
$result_communities = mysqli_query($conn, $query_communities);

if (!$result_communities) {
    echo "Error fetching communities: " . mysqli_error($conn);
    exit();
}

$communities = mysqli_fetch_all($result_communities, MYSQLI_ASSOC);


// Fetch posts authored by the user in communities
$query_community_posts = "
    SELECT p.id, p.content, p.created_at, c.name AS community_name
    FROM community_posts p
    JOIN communities c ON p.community_id = c.id
    WHERE p.user_id = $user_id
    ORDER BY p.created_at DESC
";

$result_community_posts = mysqli_query($conn, $query_community_posts);

if (!$result_community_posts) {
    echo "Error fetching community posts: " . mysqli_error($conn);
    exit();
}

$community_posts = mysqli_fetch_all($result_community_posts, MYSQLI_ASSOC);

// Fetch comments made by the user on community posts
$query_community_comments = "
    SELECT c.content AS comment_content, c.created_at, cp.content AS post_content, cm.name AS community_name
    FROM comments c
    JOIN community_posts cp ON c.post_id = cp.id
    JOIN communities cm ON cp.community_id = cm.id
    WHERE c.user_id = $user_id
    ORDER BY c.created_at DESC
";

$result_community_comments = mysqli_query($conn, $query_community_comments);

if (!$result_community_comments) {
    echo "Error fetching community comments: " . mysqli_error($conn);
    exit();
}

$community_comments = mysqli_fetch_all($result_community_comments, MYSQLI_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #fafafa; color: #333; }
        .header { background-color: #ff4500; padding: 15px; color: white; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .header .container { display: flex; justify-content: space-between; align-items: center; }
        .navbar { display: flex; gap: 15px; }
        .navbar button { background-color: white; color: #ff4500; padding: 8px 16px; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: background-color 0.3s ease; }
        .navbar button:hover { background-color: #e03e00; color: white; }

        /* Profile Container */
        .profile-container { display: flex; justify-content: center; margin: 30px auto; padding: 30px; background-color: white; border-radius: 10px; max-width: 1200px; box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); }

        .profile-left { width: 30%; padding-right: 30px; border-right: 1px solid #ddd; text-align: center; }
        .profile-right { width: 70%; padding-left: 30px; }

        .profile-header h1 { font-size: 28px; color: #333; }
        .profile-pic { width: 120px; height: 120px; border-radius: 50%; border: 4px solid #ff4500; overflow: hidden; margin-bottom: 15px; margin-left: auto; margin-right: auto; }
        .profile-pic img { width: 100%; height: 100%; object-fit: cover; }

        .upload-btn { background-color: #ff4500; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-size: 14px; cursor: pointer; transition: all 0.3s ease; }
        .upload-btn:hover { background-color: #e03e00; }

        .profile-form { margin-top: 20px; }
        .profile-form label { font-weight: bold; display: block; margin-bottom: 5px; color: #555; }
        .profile-form input { width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #ddd; font-size: 14px; color: #333; }
        .profile-form button { background-color: #ff4500; color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; transition: all 0.3s ease; }
        .profile-form button:hover { background-color: #e03e00; }

        /* Posts Section */
        .user-posts { margin-top: 40px; }
        .user-posts h2 { font-size: 22px; font-weight: bold; color: #333; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .post { background-color: #fff; margin-bottom: 20px; padding: 15px; border-radius: 6px; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1); }
        .post h3 { margin-bottom: 8px; font-size: 18px; color: #ff4500; }
        .post p { font-size: 14px; color: #333; }
        .post p small { font-size: 12px; color: #777; }

        /* Communities Section */
        .user-communities { margin-top: 40px; }
        .user-communities h3 { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .community-list { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; }
        .community-item { background-color: #fff; padding: 12px 20px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); width: 45%; }

        /* Activity Section */
        .activity-section { margin-top: 40px; }
        .activity-section h3 { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .activity-item { background-color: #fff; padding: 15px; border-radius: 6px; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1); margin-bottom: 15px; }
        .activity-item p { font-size: 14px; color: #333; }

        a { color: #ff4500; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<?php include 'C:/xampp/htdocs/Carrot/Reuseable_Assets/navbar.php'; ?>

<div class="profile-container">
    <!-- Profile Left Section -->
    <div class="profile-left">
        <!-- Profile Picture Section -->
<div class="profile-pic">
    <img src="<?php echo $user['profile_pic']; ?>" alt="Profile Picture">
</div>
<!-- Profile Picture Upload Form -->
<form method="POST" action="profile.php" enctype="multipart/form-data">
    <label for="profile_pic">Upload New Profile Picture:</label>
    <input type="file" id="profile_pic" name="profile_pic" accept="image/*" required>
    <button type="submit" name="upload_pic" class="upload-btn">Upload</button>
</form>

        <h1><?php echo htmlspecialchars($user['username']); ?></h1>
        <button class="upload-btn">Upload New Picture</button>

        <!-- Profile Edit Form -->
        <div class="profile-form">
            <form method="POST" action="profile.php">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

                <button type="submit">Update Profile</button>
            </form>
        </div>
    </div>

    <!-- Profile Right Section (Posts and Activity) -->
    <div class="profile-right">
     <div class="user-posts">
    <h2>Your Posts in Communities</h2>
    <?php if (count($community_posts) > 0): ?>
        <?php foreach ($community_posts as $post): ?>
            <div class="post">
                <h3>In Community: <?php echo htmlspecialchars($post['community_name']); ?></h3>
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <p><small>Posted on: <?php echo $post['created_at']; ?></small></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>You have not created any posts in communities yet.</p>
    <?php endif; ?>
</div>



<div class="user-communities">
    <h3>Your Communities</h3>
    <?php if (count($communities) > 0): ?>
        <div class="community-list">
            <?php foreach ($communities as $community): ?>
                <div class="community-item">
                    <a href="community.php?id=<?php echo $community['id']; ?>">
                        <?php echo htmlspecialchars($community['name']); ?>
                    </a>
                    <p><?php echo htmlspecialchars($community['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>You have not created any communities yet.</p>
    <?php endif; ?>
</div>


   <div class="activity-section">
    <h3>Your Comments in Communities</h3>
    <?php if (count($community_comments) > 0): ?>
        <?php foreach ($community_comments as $comment): ?>
            <div class="activity-item">
                <p>
                    Commented in Community: <strong><?php echo htmlspecialchars($comment['community_name']); ?></strong>
                </p>
                <p>
                    On Post: <em><?php echo htmlspecialchars($comment['post_content']); ?></em>
                </p>
                <p><?php echo htmlspecialchars($comment['comment_content']); ?></p>
                <p><small>Commented on: <?php echo $comment['created_at']; ?></small></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>You have not commented on any community posts yet.</p>
    <?php endif; ?>
</div>


    </div>
</div>
</body>
</html>
