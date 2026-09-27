<?php
require_once __DIR__.'/../bootstrap.php';

use App\Auth;
use App\Database;

$auth = new Auth();
if (!$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get top 10 students ordered by total pages (most pages at the top)
$students = $db->query('
    SELECT 
        student_name,
        class_name,
        SUM(num_pages) as total_pages
    FROM books
    GROUP BY student_name, class_name
    ORDER BY total_pages DESC
    LIMIT 10
')->fetchAll();

// Get top 10 books ordered by number of students (most students first)
$books = $db->query('
    SELECT 
        book_title,
        book_author,
        COUNT(student_name) as student_count
    FROM books
    GROUP BY book_title, book_author
    ORDER BY student_count DESC
    LIMIT 10
')->fetchAll();

// Get top 10 authors ordered by most frequent occurrences (most authors first)
$authors = $db->query('
    SELECT 
        book_author,
        COUNT(*) as author_count
    FROM books
    GROUP BY book_author
    ORDER BY author_count DESC
    LIMIT 10
')->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Statistics – Cesta (Admin)</title>
</head>
<body>
<h1>Student Statistics</h1>
<p><a href="../index.php">Homepage</a> | <a href="books.php">Books</a></p>

<h2>Top 10 žáků s největším počtem stran</h2>
<table border="1" cellpadding="3" cellspacing="0">
<tr>
    <th>Pořadí</th>
    <th>Žák</th>
    <th>Třída</th>
    <th>Celkem stránek</th>
</tr>
<?php $rank = 1; ?>
<?php foreach ($students as $student): ?>
<tr>
    <td><?=htmlspecialchars($rank++)?></td>
    <td><?=htmlspecialchars($student['student_name'])?></td>
    <td><?=htmlspecialchars($student['class_name'])?></td>
    <td><?=htmlspecialchars($student['total_pages'])?></td>
</tr>
<?php endforeach; ?>
</table>

<h2>Top 10 nejčtenějších knih</h2>
<table border="1" cellpadding="3" cellspacing="0">
<tr>
    <th>Pořadí</th>
    <th>Kniha</th>
    <th>Autor</th>
    <th>Žáků přečetlo</th>
</tr>
<?php $rank = 1; ?>
<?php foreach ($books as $book): ?>
<tr>
    <td><?=htmlspecialchars($rank++)?></td>
    <td><?=htmlspecialchars($book['book_title'])?></td>
    <td><?=htmlspecialchars($book['book_author'])?></td>
    <td><?=htmlspecialchars($book['student_count'])?></td>
</tr>
<?php endforeach; ?>
</table>

<h2>Top 10 nejčtenějších autorů</h2>
<table border="1" cellpadding="3" cellspacing="0">
<tr>
    <th>Pořadí</th>
    <th>Autor</th>
    <th>Počet</th>
</tr>
<?php $rank = 1; ?>
<?php foreach ($authors as $author): ?>
<tr>
    <td><?=htmlspecialchars($rank++)?></td>
    <td><?=htmlspecialchars($author['book_author'])?></td>
    <td><?=htmlspecialchars($author['author_count'])?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
