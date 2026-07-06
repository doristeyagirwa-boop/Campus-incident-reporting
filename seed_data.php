<?php
require_once __DIR__ . '/db.php';

$pdo = get_db();

// Sample categories
$categories = ['Network', 'Hardware', 'Software', 'Security', 'Database'];
$stmt = $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (:name)");
foreach ($categories as $name) {
    $stmt->execute(['name' => $name]);
}

// Sample users
$first_names = [
    'Brian', 'Faith', 'Kevin', 'Grace', 'Dennis', 'Mercy', 'Victor', 'Lydia',
    'Samuel', 'Esther', 'Daniel', 'Winnie', 'Joseph', 'Caroline', 'Michael',
    'Doris', 'James', 'Beatrice', 'Peter', 'Stella',
];

$last_names = [
    'Kamau', 'Otieno', 'Mwangi', 'Achieng', 'Njoroge', 'Odhiambo', 'Kariuki',
    'Adhiambo', 'Mutua', 'Wanjiru', 'Gitonga', 'Awino', 'Kimani', 'Auma',
    'Waweru', 'Ogola', 'Ndungu', 'Anyango', 'Macharia', 'Moraa',
];

// Default admin account: admin@campus.ac.ke / admin123
$stmt = $pdo->prepare("INSERT IGNORE INTO users (full_name, email, password, role) VALUES ('Admin User', 'admin@campus.ac.ke', :password, 'admin')");
$stmt->execute(['password' => password_hash('admin123', PASSWORD_DEFAULT)]);

$user_stmt = $pdo->prepare("INSERT IGNORE INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, 'user')");
$user_password = password_hash('password123', PASSWORD_DEFAULT);
for ($i = 0; $i < 20; $i++) {
    $first = $first_names[array_rand($first_names)];
    $last = $last_names[array_rand($last_names)];
    $full_name = "$first $last";
    $email = strtolower("$first.$last$i@strathmore.com");
    $user_stmt->execute(['full_name' => $full_name, 'email' => $email, 'password' => $user_password]);
}

// Sample incidents
$titles = [
    'Internet is down in block A',
    'Projector not working in room 101',
    'System keeps crashing',
    'Unauthorized access detected',
    'Database connection error',
    'WiFi slow in library',
    'Printer not working',
    'Power outage in lab',
    'Software license expired',
    'Network cable broken',
];

$statuses = ['open', 'in_progress', 'resolved'];
$priorities = ['low', 'medium', 'high'];

$incident_stmt = $pdo->prepare("
    INSERT IGNORE INTO incidents (title, description, status, priority, category_id, reported_by)
    VALUES (:title, :description, :status, :priority, :category_id, :reported_by)
");
foreach ($titles as $title) {
    $incident_stmt->execute([
        'title' => $title,
        'description' => "Description for: $title",
        'status' => $statuses[array_rand($statuses)],
        'priority' => $priorities[array_rand($priorities)],
        'category_id' => random_int(1, 5),
        'reported_by' => random_int(1, 20),
    ]);
}

// Sample comments
$comments = [
    'This issue is still ongoing please fix it',
    'I have the same problem in block B',
    'This was resolved yesterday thanks',
    'Please prioritize this issue',
    'IT team is working on it',
];

$comment_stmt = $pdo->prepare("INSERT INTO comments (incident_id, user_id, comment_text) VALUES (:incident_id, :user_id, :comment_text)");
for ($i = 0; $i < 10; $i++) {
    $comment_stmt->execute([
        'incident_id' => random_int(1, 10),
        'user_id' => random_int(1, 20),
        'comment_text' => $comments[array_rand($comments)],
    ]);
}

// Sample notifications
$notification_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
for ($i = 0; $i < 10; $i++) {
    $incident_num = random_int(1, 10);
    $notification_stmt->execute([
        'user_id' => random_int(1, 20),
        'message' => "New update on incident #$incident_num",
    ]);
}

echo "Sample data inserted successfully! Default admin login: admin@campus.ac.ke / admin123\n";
