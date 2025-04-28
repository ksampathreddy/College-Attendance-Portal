<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $posted_by = $_SESSION['username'];
    
    $sql = "INSERT INTO notices (title, content, posted_by) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $title, $content, $posted_by);
    
    if ($stmt->execute()) {
        echo "<p class='success'>Notice posted successfully!</p>";
    } else {
        echo "<p class='error'>Error posting notice: " . $conn->error . "</p>";
    }
}
?>
<style>
    .f{
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
  .btn {
            background-color: #007bff;
            margin: 10px 0;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            /* width: 100%; */
        }
        
</style>

<h2 class="text-center">Post Notice</h2>
<form method="POST" action="">
    <div class="f">
    <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required>
    </div>
    <div class="form-group">
        <label for="content">Content</label>
        <textarea id="content" name="content" rows="5" required></textarea>
    </div>
    <button type="submit" class="btn">Post Notice</button>
</div>
</form>
<a href="admin_dashboard.php" class="back-link" style="font-size:20px">← Back to Dashboard</a>
<style>
.back-link {
            display: block;
            margin-top: 50px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
            font-size:20px;
        }
</style>