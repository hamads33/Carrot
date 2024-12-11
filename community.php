<?php
session_start();
require 'C:/xampp/htdocs/Carrot/db_config.php';

// Checkin if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if a community ID is provided
if (!isset($_GET['id'])) {
    echo "Community not found.";
    exit();
}

$community_id = intval($_GET['id']);

// Fetch community details
$query = "SELECT * FROM communities WHERE id = $community_id";
$result = mysqli_query($conn, $query);
$community = mysqli_fetch_assoc($result);

if (!$community) {
    echo "Community not found.";
    exit();
}

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    if (!empty($content)) {
        $post_query = "INSERT INTO community_posts (user_id, community_id, content) VALUES ('$user_id', '$community_id', '$content')";
        mysqli_query($conn, $post_query);
    }
}
// Fetch community members
$members_query = "SELECT u.id, u.name, u.profile_pic 
                  FROM community_members cm
                  JOIN users u ON cm.user_id = u.id
                  WHERE cm.community_id = $community_id
                  ORDER BY cm.joined_at ASC";
$members_result = mysqli_query($conn, $members_query);

// Fetch posts in the community
$posts_query = "SELECT p.*, u.name AS user_name 
                FROM community_posts p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.community_id = $community_id 
                ORDER BY p.created_at DESC";
$posts_result = mysqli_query($conn, $posts_query);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($community['name']); ?> - Community</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .header {
            background-color: #ff4500;
            padding: 15px 20px;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .community-details {
            text-align: center;
        }
        .community-details img {
            max-width: 100px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .community-details h1 {
            font-size: 24px;
            color: #333;
        }
        .community-details p {
            color: #666;
        }
        .post-form {
            margin-top: 20px;
        }
        .post-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            resize: none;
        }
        .post-form button {
            margin-top: 10px;
            width: 100%;
            padding: 10px;
            background-color: #ff4500;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .post-form button:hover {
            background-color: #e03e00;
        }
        .posts {
            margin-top: 30px;
        }
        .post {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .post h4 {
            margin: 0;
            color: #333;
        }
        .post p {
            margin: 5px 0 0;
            color: #555;
        }
        .post .time {
            font-size: 12px;
            color: #888;
        }
		.members {
    margin-top: 30px;
    padding: 20px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.members h2 {
    margin: 0 0 15px;
    color: #333;
}
.members ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.members ul li {
    font-size: 16px;
    color: #555;
}

    </style>
</head>
<body>
<?php include 'C:/xampp/htdocs/Carrot/Reuseable_Assets/navbar.php'; ?>

<div class="container">
    <div class="community-details">
        <?php if ($community['profile_pic']): ?>
            <img src="<?php echo htmlspecialchars($community['profile_pic']); ?>" alt="Community Profile Picture">
        <?php else: ?>
		<img src="/Carrot/uploads/community_pics/default_community.png.png" alt="Default Profile Picture">


        <?php endif; ?>
        <h1><?php echo htmlspecialchars($community['name']); ?></h1>
        <p><?php echo htmlspecialchars($community['description']); ?></p>
    </div>
<div class="members">
    <h2>Members</h2>
    <?php if (mysqli_num_rows($members_result) > 0): ?>
        <ul>
            <?php while ($member = mysqli_fetch_assoc($members_result)): ?>
                <li style="display: flex; align-items: center; margin-bottom: 10px;">
                    <img src="<?php echo htmlspecialchars($member['profile_pic'] ?? 'default_user.png'); ?>" 
                         alt="Member Profile Picture" 
                         style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                    <span><?php echo htmlspecialchars($member['name']); ?></span>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No members yet. Be the first to join!</p>
    <?php endif; ?>
</div>

    <div class="post-form">
        <form method="POST" action="">
            <textarea name="content" rows="4" placeholder="Write something..." required></textarea>
            <button type="submit">Post</button>
        </form>
    </div>

    <div class="posts">
        <h2>Posts</h2>
        <?php if (mysqli_num_rows($posts_result) > 0): ?>
            <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                <div class="post">
                    <h4><?php echo htmlspecialchars($post['user_name']); ?></h4>
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    <span class="time"><?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No posts yet. Be the first to post!</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
