<?php

$pageTitle = 'Emergency Guidance & Nearest Facilities';

$db = Database::getConnection();

// Get emergency tips
$stmt = $db->query("SELECT * FROM emergency_tips ORDER BY category");
$tips = $stmt->fetchAll();

// Group tips by category
$groupedTips = [];
foreach ($tips as $tip) {
    $groupedTips[$tip['category']][] = $tip;
}

// Get emergency facilities
$stmt = $db->query("
    SELECT id, name, address, phone, latitude, longitude,
           (6371 * acos(cos(radians(-15.3875)) * cos(radians(latitude))
           * cos(radians(longitude) - radians(28.3228)) + sin(radians(-15.3875))
           * sin(radians(latitude)))) AS distance
    FROM facilities
    WHERE is_emergency = 1 AND is_active = 1
    HAVING distance < 100
    ORDER BY distance
    LIMIT 10
");
$facilities = $stmt->fetchAll();

// If no facilities with geolocation, fall back
if (empty($facilities)) {
    $stmt = $db->query("
        SELECT id, name, address, phone, latitude, longitude
        FROM facilities
        WHERE is_emergency = 1 AND is_active = 1
        ORDER BY name
        LIMIT 10
    ");
    $facilities = $stmt->fetchAll();
}
?>
<div class="topbar">
    <div>
        <h1 class="topbar-title">Emergency guidance</h1>
        <p class="topbar-subtitle">Immediate steps to take for medical emergencies</p>
    </div>
</div>

<div class="emergency-banner">
    <?= icon('alert-triangle') ?>
    <span>This information does not replace professional emergency services. If you are in immediate danger, call <strong>991</strong>.</span>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);">
    <div>
        <div class="emergency-categories">
            <?php foreach (array_keys($groupedTips) as $index => $cat): ?>
                <div class="emergency-category-btn <?= $index === 0 ? 'active' : '' ?>" data-category="<?= e($cat) ?>">
                    <?= icon('alert-triangle') ?>
                    <div><?= e(ucfirst($cat)) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($groupedTips)): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state-title">No emergency tips available</div>
                    <p class="empty-state-text">Emergency tips are being loaded. Please check back later.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($groupedTips as $category => $categoryTips): ?>
                <div class="category-tips" id="tips-<?= e($category) ?>" style="<?= array_keys($groupedTips)[0] === $category ? '' : 'display:none;' ?>">
                    <?php foreach ($categoryTips as $tip): ?>
                        <div class="card" style="margin-bottom:var(--space-6);">
                            <div class="card-header">
                                <h3><?= e($tip['title']) ?></h3>
                            </div>
                            <div class="card-body">
                                <?php if ($tip['warning']): ?>
                                    <div class="alert alert-danger" style="margin-bottom:var(--space-4);">
                                        <?= icon('alert-triangle') ?>
                                        <div class="alert-text">
                                            <div class="alert-title">Warning</div>
                                            <?= e($tip['warning']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="tip-steps">
                                    <?php
                                    $steps = json_decode($tip['steps'], true);
                                    if (!is_array($steps)) $steps = explode("\n", $tip['steps']);
                                    foreach ($steps as $idx => $step): if (empty(trim($step))) continue; ?>
                                        <div class="tip-step">
                                            <div class="tip-step-number"><?= $idx + 1 ?></div>
                                            <div class="tip-step-text"><?= e($step) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div>
        <h2 style="font-size:var(--text-xl);margin-bottom:var(--space-4);">Nearest emergency facilities</h2>
        <button id="findNearestBtn" class="btn btn-primary btn-lg" style="margin-bottom:var(--space-4);">
            <?= icon('map-pin') ?> Find nearest facility
        </button>

        <div id="facilitiesLoading" style="display:none;text-align:center;padding:var(--space-6);">
            <p>Getting your location and finding nearby facilities...</p>
        </div>

        <div id="facilitiesList">
            <?php if (empty($facilities)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><?= icon('map-pin') ?></div>
                    <div class="empty-state-title">No emergency facilities found</div>
                    <p class="empty-state-text">Call <a href="tel:991">991</a> for assistance.</p>
                </div>
            <?php else: ?>
                <?php foreach ($facilities as $facility): ?>
                    <div class="card" style="margin-bottom:var(--space-4);border-left:3px solid var(--danger);">
                        <div class="card-body">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-2);">
                                <h3 style="font-size:var(--text-lg);"><?= icon('alert-triangle') ?> <?= e($facility['name']) ?></h3>
                                <?php if (isset($facility['distance'])): ?>
                                    <span class="badge badge-danger"><?= number_format($facility['distance'], 1) ?> km</span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:var(--text-sm);color:var(--text-secondary);margin-bottom:var(--space-2);"><?= e($facility['address']) ?></p>
                            <?php if ($facility['phone']): ?>
                                <p style="margin-bottom:var(--space-3);"><a href="tel:<?= e($facility['phone']) ?>"><?= e($facility['phone']) ?></a></p>
                            <?php endif; ?>
                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $facility['latitude'] ?>,<?= $facility['longitude'] ?>"
                               target="_blank" class="btn btn-primary btn-sm"><?= icon('map-pin') ?> Get directions</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.emergency-category-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.emergency-category-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        document.querySelectorAll('.category-tips').forEach(t => t.style.display = 'none');
        document.getElementById('tips-' + this.dataset.category).style.display = 'block';
    });
});

document.getElementById('findNearestBtn').addEventListener('click', function() {
    const loading = document.getElementById('facilitiesLoading');
    const list = document.getElementById('facilitiesList');
    loading.style.display = 'block';

    if (!navigator.geolocation) {
        loading.innerHTML = '<p>Geolocation is not supported by your browser.</p>';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            window.location.href = '/api/search-facilities?lat=' + lat + '&lng=' + lng + '&emergency=1';
        },
        function(error) {
            loading.style.display = 'none';
            alert('Could not get your location. Please enable location services or use the facilities list below.');
        }
    );
});
</script>
