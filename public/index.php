<?php
require_once __DIR__.'/../vendor/autoload.php';
use Dotenv\Dotenv;
use App\Auth;
use App\Database;

$dotenv = Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

$auth = new Auth();
if (!$auth->isAdmin()) {
    header('Location: /login.php');
    exit;
}

// Output DOCTYPE and start of HTML
echo '<!DOCTYPE html><html><head><title>Cesta</title>';
// Leaflet CSS
echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
echo '</head><body>';

// Page title and map container
echo '<h1>Welcome to Cesta</h1>';
echo '<div id="map" style="height:400px;width:100%;margin-top:20px;"></div>';


$database = new Database();
$db = $database->getConnection();

// Load destinations for map
$destStmt = $db->query('SELECT * FROM destinations ORDER BY `order` ASC');
$destinations = $destStmt->fetchAll();

// Fetch class statistics
$statsQuery = $db->query('SELECT class_name, SUM(num_pages) AS total FROM books GROUP BY class_name');
$stats = $statsQuery->fetchAll();
if ($stats) {
    // Determine max total for scaling
    // Calculate sum of pages for distance
    $sumPages = 0;
    foreach ($stats as $s) {
        $sumPages += (int)$s['total'];
    }
    // Display sum as km
    echo "<p>Sum of all pages: {$sumPages} km</p>";

    // Determine max total for scaling
    $maxTotal = 0;
    foreach ($stats as $st) {
        if ($st['total'] > $maxTotal) {
            $maxTotal = $st['total'];
        }
    }

    echo '<h2>Class Pages Chart</h2>';
    echo '<div style="display:flex;align-items:flex-end;justify-content:center;">';
    foreach ($stats as $s) {
        $height = $maxTotal > 0 ? round(($s['total'] / $maxTotal) * 200) : 0;
        $className = htmlspecialchars($s['class_name']);
        $totalPages = (int)$s['total'];
        echo "<div style=\"margin:0 10px;text-align:center;\"><div style=\"background:steelblue;height:{$height}px;width:40px;\" title=\"{$totalPages} pages\"></div><div>{$className}</div></div>";
    }
    echo '</div>';
}

// Auth related navigation
if ($auth->isGuest()) {
    echo '<p><a href="/login.php">Login</a></p>';
} else {
    echo '<p><a href="/admin/books.php">Books management</a></p>';
    echo '<p><a href="/admin/destinations.php">Destinations management</a></p>';
    echo '<p><a href="/logout.php">Logout</a></p>';
}
?>

<!-- Leaflet JS and map initialization -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>var sumPages = <?php echo $sumPages;?>;</script>
<script>
var map = L.map("map").setView([0,0],2);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"&copy; OpenStreetMap contributors"}).addTo(map);
var markerC = L.marker([49.9456349,14.3270674]).addTo(map).bindPopup("Černošice");
var markersLatLng = [markerC.getLatLng()];
var markersObj = [markerC];
<?php foreach($destinations as $d){ ?>
var m<?php echo $d['id'];?> = L.marker([<?php echo $d['lat'];?>,<?php echo $d['lng'];?>]).addTo(map).bindPopup("<?php echo $d['name'];?>");
markersObj.push(m<?php echo $d['id'];?>);
markersLatLng.push(m<?php echo $d['id'];?>.getLatLng());
<?php } ?>
var marker1 = markersObj[0];
var marker2 = markersObj.length>1 ? markersObj[1] : marker1;

map.fitBounds(
    L.latLngBounds([
    marker1.getLatLng(),
    marker2.getLatLng()
    ]),
    { padding: [10, 10] }
);

var line = L.polyline(markersLatLng,{color:"red",weight:4,dashArray:"5,5"}).addTo(map);

// --------------------------------------------------
// Blue portion: sumPages kilometres from marker1
// --------------------------------------------------

var start = marker1.getLatLng();
var end   = marker2.getLatLng();

// Total distance between the markers in metres
var totalDistance = start.distanceTo(end);

// sumPages is in kilometres
var blueDistance = sumPages * 1000;

// Dont allow the blue line to extend beyond marker2
blueDistance = Math.min(blueDistance, totalDistance);

// Fraction of the line covered by sumPages
var fraction = blueDistance / totalDistance;

// Calculate the point at that fraction
var blueEnd = L.latLng(start.lat + (end.lat - start.lat) * fraction, start.lng + (end.lng - start.lng) * fraction);

// Draw blue portion
var blueLine = L.polyline(
    [start, blueEnd],
    {
        color: "blue",
        weight: 6
    }
).addTo(map);
</script>

<!-- End of body and html -->
<?php echo '</body></html>';?>
