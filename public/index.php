<?php
require_once __DIR__.'/bootstrap.php';

use App\Auth;
use App\Database;

$auth = new Auth();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Knihonauti</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>
    <div class="container">
        <h1><img src="ship.png" class="ship"/>&nbsp;Knihonauti&nbsp;<img src="ship.png" class="ship"/></h1>
        
        <!-- Map section -->
        <div id="map" style="height:500px;"></div>
        
        <?php
        $database = new Database();
        $db = $database->getConnection();
        
        // Load destinations for map
        $destStmt = $db->query('SELECT * FROM destinations ORDER BY `order` ASC');
        $destinations = $destStmt->fetchAll();
        
        // Add Cernosice as first destination
        array_unshift($destinations, [
            'id' => 0,
            'name' => 'Černošice',
            'lat' => 49.9456349,
            'lng' => 14.3270674,
            'order' => -1
        ]);
        
        // Fetch class statistics
        $statsQuery = $db->query('SELECT class_name, SUM(num_pages) AS total FROM books GROUP BY class_name');
        $stats = $statsQuery->fetchAll();
        $sumPages = 0;
        
        if ($stats) {
            foreach ($stats as $s) {
                $sumPages += (int)$s['total'] * 0.1;
            }

            // Calculate total distance between all destinations (Cernosice + DB destinations)
            $totalDistance = 0;
            if (count($destinations) >= 2) {
                $prevDestination = $destinations[0];
                for ($i = 1; $i < count($destinations); $i++) {
                    $currentDestination = $destinations[$i];
                    $distance = sqrt(
                        pow($prevDestination['lat'] - $currentDestination['lat'], 2) +
                        pow($prevDestination['lng'] - $currentDestination['lng'], 2)
                    ) * 111; // Approximate conversion
                    $totalDistance += $distance;
                    $prevDestination = $currentDestination;
                }
            } else if (count($destinations) == 1) {
                $totalDistance = 0; // No distance to calculate
            }
        ?>
        <!-- Progress Bar section -->
        <div class="progress-container">
            <div class="progress-bar-container">
                <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
            </div>
            <div class="progress-bar-info">
                    <span>Přečteno <strong><?php echo number_format($sumPages * 10, 0, ',', ' '); ?></strong> stránek, <strong><?php echo number_format($sumPages, 1, ',', ' '); ?> km</strong> z <strong><?php echo number_format($totalDistance, 0, ',', ' '); ?> km</strong></span>
                </div>
        </div>
        <div class="stats-section" style="background-image: url('ship.png'); background-size: 50%; background-position: center; background-repeat: no-repeat; background-blend-mode: overlay;">
                <h2 style="text-align:center;color:#333;margin-top:0;">📊 Statistika tříd</h2>
                <div class="stats-container">
                    <?php
                    $maxTotal = 0;
                    foreach ($stats as $st) {
                        if ($st['total'] > $maxTotal) {
                            $maxTotal = $st['total'];
                        }
                    }
                    
                    foreach ($stats as $s) {
                        $height = $maxTotal > 0 ? round(($s['total'] / $maxTotal) * 200) : 0;
                        $className = htmlspecialchars($s['class_name']);
                        $totalPages = (int)$s['total'];
                        echo "<div class='stat-bar'>
                            <div class='bar' style='height:{$height}px;' data-pages='{$totalPages}'></div>
                            <span>{$className}</span>
                        </div>";
                    }
                    ?>
                </div>
            </div>
        <?php } ?>
        
        <!-- Top Right Navigation -->
        <div class="top-right-nav">
            <?php if (!$auth->isGuest()): ?>
                <a href="admin/books.php">📚 Knihy</a>
                <a href="admin/destinations.php">🌍 Cíle</a>
                <a href="logout.php">🚪 Odhlásit se</a>
            <?php else: ?>
                <a href="login.php">🚀 Přihlásit se</a>
            <?php endif; ?>
        </div>
    </div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Map initialization after Leaflet is loaded
    var sumPages = <?php echo isset($sumPages) ? $sumPages : 0;?>;

    // Create map first
    var map = L.map("map").setView([0, 0], 7);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{
        attribution:"© OpenStreetMap contributors"
    }).addTo(map);
    
    markersObj = [];
    markersLatLng = [];

    <?php foreach($destinations as $d){ ?>
    var m<?php echo $d['id'];?> = L.marker([<?php echo $d['lat'];?>,<?php echo $d['lng'];?>]).addTo(map).bindPopup("<?php echo htmlspecialchars($d['name']);?>");
    markersObj.push(m<?php echo $d['id'];?>);
    markersLatLng.push(m<?php echo $d['id'];?>.getLatLng());
    <?php } ?>
    
    var marker1 = markersObj[0];
    var marker2 = markersObj.length>1 ? markersObj[1] : marker1;
    
    // Create bounds with all markers to ensure complete coverage
    var bounds = L.latLngBounds(markersLatLng);
    
    // Fit bounds with some padding and set zoom
    map.fitBounds(bounds, { 
        padding: [50, 50], 
        maxZoom: 13 
    });
    
    var line = L.polyline(markersLatLng,{color:"red",weight:4,dashArray:"5,5"}).addTo(map);

    var start = marker1.getLatLng();
    var end   = marker2.getLatLng();

    var totalDistance = start.distanceTo(end);
    var blueDistance = sumPages * 1000;

    var remaining = blueDistance;
    var bluePoints = [markersLatLng[0]];
    for (var i = 0; i < markersLatLng.length - 1; i++){
        var segStart = markersLatLng[i];
        var segEnd = markersLatLng[i+1];
        var segDist = segStart.distanceTo(segEnd);
        if (remaining <= segDist){
            var ratio = remaining / segDist;
            var lat = segStart.lat + (segEnd.lat - segStart.lat)*ratio;
            var lng = segStart.lng + (segEnd.lng - segStart.lng)*ratio;
            bluePoints.push(L.latLng(lat,lng));
            break;
        } else {
            bluePoints.push(segEnd);
            remaining -= segDist;
        }
    }

    var blueLine = L.polyline(
        bluePoints,
        {
            color: "blue",
            weight: 6
        }
    ).addTo(map);
    </script>
    <script>
        // Animate progress bar
        var totalDistanceKm = <?php echo number_format($totalDistance, 0, ',', ''); ?>;
        var progress = sumPages / totalDistanceKm;
        if (totalDistance > 0) {
            progress = Math.min(progress, 1);
        }
        var progressBar = document.getElementById('progress-bar');
        
        // Animate to value
        setTimeout(function() {
            if (progressBar) {
                progressBar.style.width = (progress * 100) + '%';
            }
        }, 600);
    </script>
</body>
</html>
