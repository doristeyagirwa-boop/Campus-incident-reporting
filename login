<?php 
// 1. Start recording the HTML
ob_start(); 
?>

<div class="page active">
  <div class="topbar">
    <div class="brand"><span class="crest">CG</span> CampusGuard</div>
    <nav><a href="/campusguard/index.php">Back to Home</a></nav>
  </div>
  <div class="auth-wrap">
    <div class="auth-card">
      <h2>Welcome back</h2>
      <p class="sub">Log in to report or track incidents.</p>
      <form action="/campusguard/student/dashboard.php" method="POST">
        <div class="field">
          <label>Email</label>
          <input type="email" placeholder="jane.wanjiru@university.ac.ke" required>
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" placeholder="••••••••" required>
        </div>
        <button class="btn btn-primary btn-block mt-24" type="submit">Log In</button>
      </form>
      <div class="auth-foot">
        No account? <a href="/campusguard/register.php" style="color:var(--navy);font-weight:600;">Register</a>
        &nbsp;·&nbsp;
        <a href="/campusguard/admin/dashboard.php" style="color:var(--navy);font-weight:600;">Admin demo →</a>
      </div>
    </div>
  </div>
</div>

<?php 
// 2. Stop recording and save the HTML into the $page_content variable
$page_content = ob_get_clean(); 

// 3. Load the main layout file
include 'includes/main.php'; 
?>