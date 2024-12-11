<?php
session_start();
require 'db_config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth_pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all communities
$communities_query = "SELECT * FROM communities";
$communities_result = mysqli_query($conn, $communities_query);

// Fetch posts with associated community and user details
$posts_query = "SELECT community_posts.id, community_posts.content, users.name, communities.name AS community_name, 
                communities.id AS community_id, community_posts.created_at, community_posts.votes
                FROM community_posts
                JOIN users ON community_posts.user_id = users.id
                JOIN communities ON community_posts.community_id = communities.id
                ORDER BY community_posts.created_at DESC";

$posts_result = mysqli_query($conn, $posts_query);

// Function to check if the user is a member of a community
function is_user_member($user_id, $community_id, $conn) {
    $query = "SELECT * FROM community_members WHERE user_id = $user_id AND community_id = $community_id";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Handle join/leave community actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['community_id']) && !isset($_POST['comment_content'])) {
    $community_id = $_POST['community_id'];
    $is_member = is_user_member($user_id, $community_id, $conn);

    if ($is_member) {
        // Leave the community
        $leave_query = "DELETE FROM community_members WHERE user_id = $user_id AND community_id = $community_id";
        mysqli_query($conn, $leave_query);
    } else {
        // Join the community
        $join_query = "INSERT INTO community_members (user_id, community_id) VALUES ($user_id, $community_id)";
        mysqli_query($conn, $join_query);
    }

    // Refresh the page to update the status
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'], $_POST['post_id'])) {
    $comment_content = mysqli_real_escape_string($conn, $_POST['comment_content']);
    $post_id = $_POST['post_id'];

    $add_comment_query = "INSERT INTO comments (content, user_id, post_id) VALUES ('$comment_content', '$user_id', '$post_id')";
    if (mysqli_query($conn, $add_comment_query)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<script>alert('Error adding comment.');</script>";
    }
}

// Function to fetch comments for a post
function get_comments($post_id, $conn) {
    $comments_query = "SELECT comments.content, users.name AS commenter_name, comments.created_at
                       FROM comments
                       JOIN users ON comments.user_id = users.id
                       WHERE comments.post_id = '$post_id'
                       ORDER BY comments.created_at ASC";
    return mysqli_query($conn, $comments_query);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_type'], $_POST['post_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = intval($_POST['post_id']);
    $vote_type = $_POST['vote_type']; // Should be 'upvote' or 'downvote'

    // Check if the user has already voted on this post
    $check_vote_query = "SELECT vote_type FROM post_votes WHERE user_id = $user_id AND post_id = $post_id";
    $check_vote_result = mysqli_query($conn, $check_vote_query);

    if (mysqli_num_rows($check_vote_result) > 0) {
        // User has already voted, check the existing vote type
        $existing_vote = mysqli_fetch_assoc($check_vote_result)['vote_type'];

        if ($existing_vote === $vote_type) {
            // Same vote type, undo the vote (delete record)
            $undo_vote_query = "DELETE FROM post_votes WHERE user_id = $user_id AND post_id = $post_id";
            mysqli_query($conn, $undo_vote_query);
        } else {
            // Different vote type, update the vote
            $update_vote_query = "UPDATE post_votes SET vote_type = '$vote_type', created_at = NOW() 
                                  WHERE user_id = $user_id AND post_id = $post_id";
            mysqli_query($conn, $update_vote_query);
        }
    } else {
        // No previous vote, insert a new one
        $insert_vote_query = "INSERT INTO post_votes (user_id, post_id, vote_type) 
                              VALUES ($user_id, $post_id, '$vote_type')";
        mysqli_query($conn, $insert_vote_query);
    }

    // Redirect to refresh the page or avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f6f7f8;
            color: #333;
        }

        .container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
        }

        .sidebar {
            width: 20%;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin-bottom: 10px;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #333;
            padding: 8px 0;
            display: block;
            transition: color 0.3s;
        }

        .sidebar ul li a:hover {
            color: #ff4500;
        }

        main {
            width: 75%;
            margin-left: 20px;
        }

        .post {
            display: flex;
            background-color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .votes {
            width: 10%;
            text-align: center;
            margin-right: 20px;
        }

        .votes button {
            background-color: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }

        .content {
            width: 85%;
        }

        .content h3 {
            margin-top: 0;
        }

        .content h3 a {
            color: #ff4500;
            text-decoration: none;
        }

        .content h3 a:hover {
            text-decoration: underline;
        }

        .comment-section {
            margin-top: 15px;
        }

        .comment-section textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .comment-btn {
            padding: 10px 20px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .comment-btn:hover {
            background-color: #45a049;
        }

        .comment-item {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .join-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .join-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<header>
    <?php include 'C:/xampp/htdocs/Carrot/Reuseable_Assets/navbar.php'; ?>
</header>
<div class="container">
    <aside class="sidebar">
        <h2>Communities</h2>
        <ul>
            <?php while ($community = mysqli_fetch_assoc($communities_result)): ?>
                <li>
                    <a href="community.php?id=<?php echo $community['id']; ?>">
                        <?php echo htmlspecialchars($community['name']); ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    </aside>
    <main>
        <h2>Posts</h2>
        <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
    <?php
    // Fetching upvotes and downvotes for this post
    $post_id = $post['id'];
    $vote_query = "SELECT 
                      SUM(vote_type = 'upvote') AS upvotes,
                      SUM(vote_type = 'downvote') AS downvotes
                   FROM post_votes
                   WHERE post_id = $post_id";
    $vote_result = mysqli_query($conn, $vote_query);
    $votes = mysqli_fetch_assoc($vote_result);

    $upvotes = $votes['upvotes'] ?? 0;
    $downvotes = $votes['downvotes'] ?? 0;
    ?>

            <div class="post">
   <div class="votes">
    <form method="POST">
        <input type="hidden" name="vote_type" value="upvote">
        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
        <button type="submit">▲ </button>
    </form>

    <p><?php echo $upvotes - $downvotes; ?></p>

    <form method="POST">
        <input type="hidden" name="vote_type" value="downvote">
        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
        <button type="submit">▼ </button>
    </form>
</div>



                <div class="content">
                    <h3>
                        <a href="community.php?id=<?php echo $post['community_id']; ?>">
                            <?php echo htmlspecialchars($post['community_name']); ?>
                        </a>
                    </h3>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="community_id" value="<?php echo $post['community_id']; ?>">
                        <button class="join-btn" type="submit">
                            <?php echo is_user_member($user_id, $post['community_id'], $conn) ? 'Joined' : 'Join'; ?>
                        </button>
                    </form>
                    <p><?php echo (htmlspecialchars($post['content'])); ?></p>
                    <p><small>Posted by <?php echo htmlspecialchars($post['name']); ?> on <?php echo $post['created_at']; ?></small></p>

                    <div class="comment-section">
                        <h4>Comments</h4>
                        <form method="POST">
                            <textarea name="comment_content" placeholder="Write a comment..." required></textarea>
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button class="comment-btn" type="submit">Submit Comment</button>
                        </form>
                        <?php
                        $comments_result = get_comments($post['id'], $conn);
                        while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                            <div class="comment-item">
                                <p><strong><?php echo htmlspecialchars($comment['commenter_name']); ?>:</strong> <?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                <p><small><?php echo $comment['created_at']; ?></small></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </main>
</div>
</body>
</html>