<?php
require_once __DIR__.'/../vendor/autoload.php';
use Dotenv\Dotenv;
use App\Database;
use App\Auth;

$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

$auth = new Auth();

// Output DOCTYPE and start of HTML
echo '<!DOCTYPE html><html><head><title>Cesta</title>';
// Leaflet CSS
echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
echo '</head><body>';

// Page title and map container
echo '<h1>Welcome to Cesta</h1>';
echo '<div id="map" style="height:400px;width:100%;margin-top:20px;"></div>';

try {
    $database = new Database();
    $db = $database->getConnection();

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
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Database error: ' . htmlspecialchars($e->getMessage());
}

// Leaflet JS and map initialization
// Leaflet JS
// Using CDN without integrity for simplicity
// Map init script
echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
// Initialize map
echo '<script>var sumPages = ' . $sumPages . ';</script>';
echo '<script>
var map = L.map("map").setView([0,0],2);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{
attribution:"\u0026copy; OpenStreetMap contributors"
}).addTo(map);
var marker1 = L.marker([49.9456349,14.3270674]).addTo(map).bindPopup("Černošice");
var marker2 = L.marker([59.9133301,10.7389701]).addTo(map).bindPopup("Oslo");

map.fitBounds(
    L.latLngBounds([
    marker1.getLatLng(),
    marker2.getLatLng()
    ]),
    { padding: [10, 10] }
);

var line = L.polyline([marker1.getLatLng(), marker2.getLatLng()],{color:"red",weight:4}).addTo(map);

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
).addTo(map);</script>';

// End of body and html
echo '</body></html>';
