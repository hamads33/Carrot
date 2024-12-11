<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<style>
/* General Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* Body */
body {
  font-family: Arial, sans-serif;
  background-color: #f7f7f7;
  color: #333;
}

/* Header */
.header {
  background-color: #ff4500;
  padding: 15px 20px;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header .logo {
  font-size: 20px;
  font-weight: bold;
}

.header nav {
  display: flex;
  gap: 10px;
}

.header nav a {
  text-decoration: none;
  color: #ff4500;
}

.header .btn {
  padding: 8px 12px;
  background-color: white;
  color: #ff4500;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.3s, color 0.3s;
}

.header .btn:hover {
  background-color: #e03e00;
  color: white;
}

.header .btn a {
  text-decoration: none;
  color: inherit;
  display: inline-block;
}
</style>

<header class="header">
  <div class="logo">Carrot</div>
  <nav>
    <button class="btn"><a href="/Carrot/main.php">Home</a></button>
    <button class="btn"><a href="/Carrot/createc.php">+ Create</a></button>
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
      <button class="btn"><a href="profile.php">Profile</a></button>
      <button class="btn"><a href="auth_pages/logout.php">Logout</a></button>
    <?php else: ?>
      <button class="btn"><a href="login.php">Login</a></button>
      <button class="btn"><a href="/Carrot/auth_pages/signup.php">Signup</a></button>
    <?php endif; ?>
  </nav>
</header>
