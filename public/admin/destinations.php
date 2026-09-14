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
        $stmt = $db->prepare('INSERT INTO destinations (`name`, `order`) VALUES (?,?)');
        $stmt->execute([
            $_POST['name'] ?? '',
            (int)($_POST['order'] ?? 0),
        ]);
    } elseif ($action === 'update') {
        $stmt = $db->prepare('UPDATE destinations SET `name`=?, `order`=? WHERE id=?');
        $stmt->execute([
            $_POST['name'] ?? '',
            (int)($_POST['order'] ?? 0),
            $_POST['id'] ?? 0,
        ]);
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM destinations WHERE id=?');
        $stmt->execute([$_POST['id'] ?? 0]);
    }
    header('Location: /admin/destinations.php');
    exit;
}

// If editing, fetch the record
$editDest = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM destinations WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editDest = $stmt->fetch();
}

$destinations = $db->query('SELECT * FROM destinations ORDER BY `order` ASC')->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Destinations – Cesta (Admin)</title>
</head>
<body>
<h1>Destinations Administration</h1>
<p><a href="/logout.php">Logout</a></p>

<?php if ($editDest): ?>
    <h2>Edit Destination</h2>
    <form method="post" action="/admin/destinations.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?=htmlspecialchars($editDest['id'])?>">
        <label>Name: <input type="text" name="name" value="<?=htmlspecialchars($editDest['name'])?>" required></label><br>
        <label>Order: <input type="number" name="order" value="<?=htmlspecialchars($editDest['order'])?>" required min="0"></label><br>
        <button type="submit">Update</button>
    </form>
    <p><a href="/admin/destinations.php">Back to list</a></p>
<?php else: ?>
    <h2>Add New Destination</h2>
    <form method="post" action="/admin/destinations.php">
        <input type="hidden" name="action" value="add">
        <label>Name: <input type="text" name="name" required></label><br>
        <label>Order: <input type="number" name="order" required min="0"></label><br>
        <button type="submit">Add</button>
    </form>
<?php endif; ?>

<h2>Existing Destinations</h2>
<table border="1" cellpadding="3" cellspacing="0">
<tr><th>ID</th><th>Name</th><th>Order</th><th>Actions</th></tr>
<?php foreach ($destinations as $d): ?>
<tr>
<td><?=htmlspecialchars($d['id'])?></td>
<td><?=htmlspecialchars($d['name'])?></td>
<td><?=htmlspecialchars($d['order'])?></td>
<td>
<a href="/admin/destinations.php?edit=<?=htmlspecialchars($d['id'])?>">Edit</a>
<form method="post" action="/admin/destinations.php" style="display:inline;margin-left:5px;" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=htmlspecialchars($d['id'])?>"><button type="submit">Delete</button></form>
</td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
