<?php
require_once __DIR__.'/../../vendor/autoload.php';
use Dotenv\Dotenv;
use App\Auth;
use App\Database;

$dotenv = Dotenv::createImmutable(__DIR__.'/../../');
$dotenv->load();

$auth = new Auth();
if (!$auth->isAdmin()) {
    header('Location: /login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $db->prepare('INSERT INTO books (book_title, book_author, class_name, student_name, num_pages) VALUES (?,?,?,?,?)');
        $stmt->execute([
            $_POST['book_title'] ?? '',
            $_POST['book_author'] ?? '',
            $_POST['class_name'] ?? '',
            $_POST['student_name'] ?? '',
            (int)($_POST['num_pages'] ?? 0),
        ]);
    } elseif ($action === 'update') {
        $stmt = $db->prepare('UPDATE books SET book_title=?, book_author=?, class_name=?, student_name=?, num_pages=? WHERE id=?');
        $stmt->execute([
            $_POST['book_title'] ?? '',
            $_POST['book_author'] ?? '',
            $_POST['class_name'] ?? '',
            $_POST['student_name'] ?? '',
            (int)($_POST['num_pages'] ?? 0),
            $_POST['id'] ?? 0,
        ]);
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM books WHERE id=?');
        $stmt->execute([$_POST['id'] ?? 0]);
    }
    header('Location: /admin/books.php');
    exit;
}

// If editing, fetch the record
$editBook = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editBook = $stmt->fetch();
}

$books = $db->query('SELECT * FROM books ORDER BY id DESC')->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Books – Cesta (Admin)</title>
</head>
<body>
<h1>Books Administration</h1>
<p><a href="/logout.php">Logout</a></p>

<?php if ($editBook): ?>
    <h2>Edit Book</h2>
    <form method="post" action="/admin/books.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?=htmlspecialchars($editBook['id'])?>">
        <label>Title: <input type="text" name="book_title" value="<?=htmlspecialchars($editBook['book_title'])?>" required></label><br>
        <label>Author: <input type="text" name="book_author" value="<?=htmlspecialchars($editBook['book_author'])?>" required></label><br>
        <label>Class: <input type="text" name="class_name" value="<?=htmlspecialchars($editBook['class_name'])?>" required></label><br>
        <label>Student: <input type="text" name="student_name" value="<?=htmlspecialchars($editBook['student_name'])?>" required></label><br>
        <label>Pages: <input type="number" name="num_pages" value="<?=htmlspecialchars($editBook['num_pages'])?>" required min="1"></label><br>
        <button type="submit">Update</button>
    </form>
    <p><a href="/admin/books.php">Back to list</a></p>
<?php else: ?>
    <h2>Add New Book</h2>
    <form method="post" action="/admin/books.php">
        <input type="hidden" name="action" value="add">
        <label>Title: <input type="text" name="book_title" required></label><br>
        <label>Author: <input type="text" name="book_author" required></label><br>
        <label>Class: <input type="text" name="class_name" required></label><br>
        <label>Student: <input type="text" name="student_name" required></label><br>
        <label>Pages: <input type="number" name="num_pages" required min="1"></label><br>
        <button type="submit">Add</button>
    </form>
<?php endif; ?>

<h2>Existing Books</h2>
<table border="1" cellpadding="3" cellspacing="0">
<tr><th>ID</th><th>Title</th><th>Author</th><th>Class</th><th>Student</th><th>Pages</th><th>Actions</th></tr>
<?php foreach ($books as $b): ?>
<tr>
<td><?=htmlspecialchars($b['id'])?></td>
<td><?=htmlspecialchars($b['book_title'])?></td>
<td><?=htmlspecialchars($b['book_author'])?></td>
<td><?=htmlspecialchars($b['class_name'])?></td>
<td><?=htmlspecialchars($b['student_name'])?></td>
<td><?=htmlspecialchars($b['num_pages'])?></td>
<td>
<a href="/admin/books.php?edit=<?=htmlspecialchars($b['id'])?>">Edit</a>
<form method="post" action="/admin/books.php" style="display:inline;margin-left:5px;" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=htmlspecialchars($b['id'])?>"><button type="submit">Delete</button></form>
</td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
