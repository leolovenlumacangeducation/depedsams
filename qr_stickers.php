<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
// Simple permission check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Admin-only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    $_SESSION['flash_message'] = 'You do not have permission to access QR Stickers.';
    header('Location: index.php');
    exit;
}

// Use site header/footer for consistent UI
$is_public_page = false; // ensure header shows navigation
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$items = [];
// Fetch some sample items (limited) from SEP and PPE to pick from
try {
    $stmt = $pdo->query("SELECT sep_id as id, 'sep' as type, property_number, (SELECT description FROM tbl_po_item pi WHERE pi.po_item_id = s.po_item_id) as description FROM tbl_sep s ORDER BY s.sep_id DESC LIMIT 200");
    $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));
    $stmt = $pdo->query("SELECT ppe_id as id, 'ppe' as type, property_number, (SELECT description FROM tbl_po_item pi WHERE pi.po_item_id = p.po_item_id) as description FROM tbl_ppe p ORDER BY p.ppe_id DESC LIMIT 200");
    $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    $items = [];
}

?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" style="padding-top:20px;">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4">Generate QR Stickers</h1>
    </div>
    <p>Select items to generate QR stickers. The PDF will open in a new tab.</p>
    <form id="qr-form" method="post" action="api/generate_qr_pdf.php" target="_blank">
                <div class="row mb-3 g-2 align-items-end">
            <div class="col-auto">
                <label for="label_option" class="form-label">Label</label>
                <select id="label_option" name="label" class="form-select form-select-sm">
                    <option value="description" selected>Description</option>
                            <option value="details">Details (Description, Model, Serial)</option>
                    <option value="filename">Filename</option>
                    <option value="both">Both</option>
                </select>
            </div>
            <div class="col-auto">
                <label for="truncate_len" class="form-label">Truncate (chars)</label>
                <input type="number" id="truncate_len" name="truncate_len" class="form-control form-control-sm" value="60" min="0">
            </div>
            <div class="col-auto">
                <button type="button" id="previewBtn" class="btn btn-sm btn-outline-secondary">Preview</button>
            </div>
        </div>

        <div class="mb-3">
            <button type="button" id="selectAll" class="btn btn-sm btn-outline-primary">Select All</button>
            <button type="button" id="deselectAll" class="btn btn-sm btn-outline-secondary">Deselect All</button>
        </div>

        <div class="row">
            <?php foreach ($items as $it): ?>
                <div class="col-12 col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="items[]" value="<?php echo htmlspecialchars($it['type'] . '|' . $it['id']); ?>" id="item-<?php echo htmlspecialchars($it['type'] . '-' . $it['id']); ?>">
                        <label class="form-check-label" for="item-<?php echo htmlspecialchars($it['type'] . '-' . $it['id']); ?>"><?php echo htmlspecialchars($it['property_number'] . ' — ' . $it['description'] . (isset($it['model_number']) ? ' — ' . $it['model_number'] : '') . (isset($it['serial_number']) ? ' — ' . $it['serial_number'] : '')); ?></label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary" type="submit">Generate PDF</button>
        </div>
    </form>
        </main>

        <!-- Preview Modal -->
        <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="previewModalLabel">Sticker Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                            <div id="sticker-mock" style="width:220px; height:140px; border:1px solid #ddd; margin:0 auto; padding:0; display:flex; align-items:center; justify-content:center;">
                                <div id="preview-svg-container" style="width:100%; height:100%; display:block;"></div>
                            </div>
                            </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.getElementById('selectAll').addEventListener('click', function(){
    document.querySelectorAll('input[name="items[]"]').forEach(function(cb){ cb.checked = true; });
});
document.getElementById('deselectAll').addEventListener('click', function(){
    document.querySelectorAll('input[name="items[]"]').forEach(function(cb){ cb.checked = false; });
});

document.getElementById('previewBtn').addEventListener('click', function(){
    const firstChecked = document.querySelector('input[name="items[]"]:checked');
    if (!firstChecked) {
        alert('Please select at least one item to preview.');
        return;
    }
    const val = firstChecked.value; // e.g., 'ppe|123'
    const [type, id] = val.split('|');

    // Get options from the form
    const labelOption = document.getElementById('label_option').value;
    const truncateLen = parseInt(document.getElementById('truncate_len').value) || 0;

    // Request server-side SVG preview which will fetch its own data, just like the PDF generator.
    const params = new URLSearchParams();
    params.set('type', type);
    params.set('id', id);
    params.set('label', labelOption);
    params.set('truncate_len', String(truncateLen));
    const previewUrl = 'api/preview_qr.php?' + params.toString();

    // Fetch SVG and inject into container.
    fetch(previewUrl, { cache: 'no-store' }).then(function(resp){
        if (!resp.ok) {
            console.error(resp);
            throw new Error('Network response was not ok. Check console for details.');
        }
        return resp.text();
    }).then(function(svgText){
        const container = document.getElementById('preview-svg-container');
        container.innerHTML = svgText;
        var previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
        previewModal.show();
    }).catch(function(err){
        alert("Could not generate preview. Please ensure the item is valid and try again.");
        console.error(err);
    });
});
</script>
