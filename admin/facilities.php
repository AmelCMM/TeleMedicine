<?php

require_once dirname(__DIR__) . '/includes/auth_guard.php';
requireRole(ROLE_ADMIN);

$db = Database::getConnection();

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $lat = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $lng = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $isEmergency = isset($_POST['is_emergency']) ? 1 : 0;
    $editId = (int)($_POST['edit_id'] ?? 0);

    if (empty($name) || empty($type) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Name, type, and address are required.']);
        exit;
    }

    try {
        if ($editId) {
            $stmt = $db->prepare("
                UPDATE facilities SET name = ?, type = ?, address = ?, phone = ?,
                    latitude = ?, longitude = ?, is_emergency = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $type, $address, $phone, $lat, $lng, $isEmergency, $editId]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO facilities (name, type, address, phone, latitude, longitude, is_emergency)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $type, $address, $phone, $lat, $lng, $isEmergency]);
        }

        echo json_encode(['success' => true, 'message' => $editId ? 'Facility updated.' : 'Facility added.']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save facility.']);
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM facilities WHERE id = ?");
    $stmt->execute([$id]);
    setFlashMessage('success', 'Facility deleted.');
    redirect('/admin/facilities');
}

$search = trim($_GET['search'] ?? '');
$typeFilter = $_GET['type'] ?? '';

$query = "SELECT * FROM facilities WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= ' AND name LIKE ?';
    $params[] = '%' . $search . '%';
}

if (!empty($typeFilter)) {
    $query .= ' AND type = ?';
    $params[] = $typeFilter;
}

$query .= ' ORDER BY name';
$stmt = $db->prepare($query);
$stmt->execute($params);
$facilities = $stmt->fetchAll();

$facilityTypes = ['clinic', 'hospital', 'pharmacy', 'health_post'];

$pageTitle = 'Manage Facilities';
?>
<div class="page-header">
    <div>
        <h1 class="topbar-title">Manage facilities</h1>
    </div>
    <div class="topbar-actions">
        <button id="addFacilityBtn" class="btn btn-primary"><?= icon('plus') ?> Add facility</button>
    </div>
</div>

<div class="card" style="padding:var(--space-4);margin-bottom:var(--space-6);">
    <form method="GET" class="search-form">
        <input type="text" name="search" class="field-input" placeholder="Search facilities..." value="<?= e($search) ?>">
        <select name="type" class="field-select" style="max-width: 200px;">
            <option value="">All types</option>
            <?php foreach ($facilityTypes as $ft): ?>
                <option value="<?= $ft ?>" <?= $typeFilter === $ft ? 'selected' : '' ?>>
                    <?= ucfirst(str_replace('_', ' ', $ft)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="/admin/facilities" class="btn btn-ghost">Clear</a>
    </form>
</div>

<?php if (empty($facilities)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><?= icon('map-pin') ?></div>
        <div class="empty-state-title">No facilities found</div>
        <p class="empty-state-text">Add a facility to get started.</p>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:var(--space-4);">
        <?php foreach ($facilities as $facility): ?>
            <div class="card">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--space-3);">
                        <h3 style="font-size:var(--text-lg);"><?= e($facility['name']) ?></h3>
                        <span class="tag"><?= e($facility['type']) ?></span>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:var(--space-4);font-size:var(--text-sm);color:var(--text-secondary);margin-bottom:var(--space-3);">
                        <span><?= icon('map-pin') ?> <?= e($facility['address']) ?></span>
                        <?php if ($facility['phone']): ?>
                            <span><?= icon('phone') ?> <?= e($facility['phone']) ?></span>
                        <?php endif; ?>
                        <?php if ($facility['is_emergency']): ?>
                            <span class="badge badge-danger">Emergency</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:var(--space-3);">
                        <button class="btn btn-secondary btn-sm edit-facility"
                                data-id="<?= $facility['id'] ?>"
                                data-name="<?= e($facility['name']) ?>"
                                data-type="<?= $facility['type'] ?>"
                                data-address="<?= e($facility['address']) ?>"
                                data-phone="<?= e($facility['phone']) ?>"
                                data-lat="<?= $facility['latitude'] ?>"
                                data-lng="<?= $facility['longitude'] ?>"
                                data-emergency="<?= $facility['is_emergency'] ?>">
                            <?= icon('edit') ?> Edit
                        </button>
                        <a href="?delete=<?= $facility['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this facility?')"><?= icon('trash') ?> Delete</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add/Edit Modal -->
<div class="modal-backdrop" id="facilityModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Add facility</h3>
            <button class="modal-close"><?= icon('x') ?></button>
        </div>
        <div class="modal-body">
            <form id="facilityForm" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="edit_id" id="editId" value="0">

                <div class="form-group">
                    <label class="field-label" for="facility_name">Facility name</label>
                    <input class="field-input" type="text" id="facility_name" name="name" required placeholder="e.g. Lusaka General Hospital">
                </div>
                <div class="form-group">
                    <label class="field-label" for="facility_type">Facility type</label>
                    <select class="field-select" id="facility_type" name="type" required>
                        <?php foreach ($facilityTypes as $ft): ?>
                            <option value="<?= $ft ?>"><?= ucfirst(str_replace('_', ' ', $ft)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="field-label" for="facility_address">Address</label>
                    <textarea class="field-textarea" id="facility_address" name="address" required placeholder="Enter street address and province..."></textarea>
                </div>
                <div class="form-group">
                    <label class="field-label" for="facility_phone">Phone number</label>
                    <input class="field-input" type="tel" id="facility_phone" name="phone" placeholder="+260 ...">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);">
                    <div class="form-group">
                        <label class="field-label" for="facility_lat">Latitude</label>
                        <input class="field-input" type="number" id="facility_lat" name="latitude" step="any">
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="facility_lng">Longitude</label>
                        <input class="field-input" type="number" id="facility_lng" name="longitude" step="any">
                    </div>
                </div>
                <div class="form-group">
                    <label class="field-check">
                        <input type="checkbox" id="facility_emergency" name="is_emergency" value="1">
                        <span>Emergency facility (24/7 care)</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost modal-close">Cancel</button>
            <button type="submit" form="facilityForm" class="btn btn-primary" id="saveBtn">Save facility</button>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('facilityModal');
const openModal = () => modal.classList.add('open');
const closeModal = () => modal.classList.remove('open');

document.getElementById('addFacilityBtn').addEventListener('click', function() {
    document.getElementById('modalTitle').textContent = 'Add facility';
    document.getElementById('editId').value = '0';
    document.getElementById('facilityForm').reset();
    openModal();
});

document.querySelectorAll('.edit-facility').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('modalTitle').textContent = 'Edit facility';
        document.getElementById('editId').value = this.dataset.id;
        document.getElementById('facility_name').value = this.dataset.name;
        document.getElementById('facility_type').value = this.dataset.type;
        document.getElementById('facility_address').value = this.dataset.address;
        document.getElementById('facility_phone').value = this.dataset.phone;
        document.getElementById('facility_lat').value = this.dataset.lat;
        document.getElementById('facility_lng').value = this.dataset.lng;
        document.getElementById('facility_emergency').checked = this.dataset.emergency === '1';
        openModal();
    });
});

document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', closeModal);
});

modal.addEventListener('click', function(e) {
    if (e.target === modal) closeModal();
});

document.getElementById('facilityForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    try {
        const formData = new FormData(this);
        const resp = await fetch('/admin/facilities', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success) {
            showToast('success', 'Success', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('error', 'Error', data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        showToast('error', 'Error', 'An error occurred.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
</script>
