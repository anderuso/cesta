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
        $stmt = $db->prepare('INSERT INTO destinations (`name`, `order`, `lat`, `lng`) VALUES (?,?,?,?)');
        $stmt->execute([
            $_POST['name'] ?? '',
            (int)($_POST['order'] ?? 0),
            $_POST['latitude'] ?? '',
            $_POST['longitude'] ?? ''
        ]);
    } elseif ($action === 'update') {
        $stmt = $db->prepare('UPDATE destinations SET `name`=?, `order`=?, `lat`=?, `lng`=? WHERE id=?');
        $stmt->execute([
            $_POST['name'] ?? '',
            (int)($_POST['order'] ?? 0),
            $_POST['latitude'] ?? '',
            $_POST['longitude'] ?? '',
            $_POST['id'] ?? 0
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>
<h1>Destinations Administration</h1>
<p><a href="/logout.php">Logout</a></p>

<?php if ($editDest): ?>
    <h2>Edit Destination</h2>
    <form method="post" action="/admin/destinations.php">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?=htmlspecialchars($editDest['id'])?>"><label>Name: <input type="text" name="name" value="<?=htmlspecialchars($editDest['name'])?>" required></label><br>
        <label>Latitude: <input type="text" name="latitude" value="<?=htmlspecialchars($editDest['lat'] ?? '')?>"></label><br>
        <label>Longitude: <input type="text" name="longitude" value="<?=htmlspecialchars($editDest['lng'] ?? '')?>"></label><br>
        <label>Order: <input type="number" name="order" value="<?=htmlspecialchars($editDest['order'])?>" required min="0"></label><br>
        <div id="mapEdit" style="height:300px;margin-top:10px;"></div>
        <input type="submit" value="Save">
    </form>
    <p><a href="/admin/destinations.php">Back to list</a></p>
<?php else: ?>
    <h2>Add New Destination</h2>
    <form method="post" action="/admin/destinations.php">
        <input type="hidden" name="action" value="add">
        <label>Name: <input type="text" name="name" required></label><br>
        <label>Order: <input type="number" name="order" required min="0"></label><br>
        <label>Latitude: <input type="text" name="latitude"></label><br>
        <label>Longitude: <input type="text" name="longitude"></label><br>
        <div id="mapAdd" style="height:300px;margin-top:10px;"></div>
        <input type="submit" value="Add">
    </form>
<?php endif; ?>

<h2>Existing Destinations</h2>
<table border="1" cellpadding="3" cellspacing="0">
<tr><th>ID</th><th>Name</th><th>Latitude</th><th>Longitude</th><th>Order</th><th>Actions</th></tr>
<?php foreach ($destinations as $d): ?>
<tr>
<td><?=htmlspecialchars($d['id'])?></td>
<td><?=htmlspecialchars($d['name'])?></td>
<td><?=htmlspecialchars($d['lat'] ?? '')?></td>
<td><?=htmlspecialchars($d['lng'] ?? '')?></td>
<td><?=htmlspecialchars($d['order'])?></td>
<td>
<a href="/admin/destinations.php?edit=<?=htmlspecialchars($d['id'])?>">Edit</a>
<form method="post" action="/admin/destinations.php" style="display:inline;margin-left:5px;" onsubmit="return confirm('Delete?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=htmlspecialchars($d['id'])?>"><button type="submit">Delete</button></form>
</td>
</tr>
<?php endforeach; ?>
</table>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function initMap(id, latEl, lngEl, initCoords){
    var map = L.map(id).setView([0,0],2);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"&copy; OpenStreetMap contributors"}).addTo(map);
    var marker;
    if(initCoords){
        marker = L.marker(initCoords).addTo(map);
    }
    map.on('click', function(e){
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;
        latEl.value = lat.toFixed(6);
        lngEl.value = lng.toFixed(6);
        if(marker){
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }
    });
}

document.addEventListener('DOMContentLoaded', function(){
    var forms = document.querySelectorAll('form');
    forms.forEach(function(form){
        var mapEl = form.querySelector('#mapEdit')||form.querySelector('#mapAdd');
        if(mapEl){
            var latEl = form.querySelector('input[name="latitude"]');
            var lngEl = form.querySelector('input[name="longitude"]');
            var initCoord = null;
            if(latEl.value && lngEl.value){
                initCoord = [parseFloat(latEl.value), parseFloat(lngEl.value)];
            }
            initMap(mapEl.id, latEl, lngEl, initCoord);
        }
    });
});
</script>

</html>
