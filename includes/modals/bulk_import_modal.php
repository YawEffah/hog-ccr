<?php
/**
 * Bulk Import Modal — included by members.php
 * Handles the file upload form and result display.
 */
$bulkResult = $_SESSION['bulk_import_result'] ?? null;
if ($bulkResult !== null) {
    unset($_SESSION['bulk_import_result']);
}
?>

<!-- ════════════════════════════════════════════════════════
     BULK IMPORT MODAL
════════════════════════════════════════════════════════ -->
<div id="bulkImportModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="bulkImportTitle">
  <div class="modal" style="max-width:620px; width:100%;">

    <!-- Header -->
    <div class="modal-header" style="background: linear-gradient(135deg, #1E3A8A 0%, #1D4ED8 100%); border-radius: 12px 12px 0 0; padding: 20px 24px; display:flex; align-items:center; justify-content:space-between;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:38px; height:38px; border-radius:10px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center;">
          <i class="ph ph-file-arrow-up" style="font-size:20px; color:#fff;"></i>
        </div>
        <div>
          <h3 id="bulkImportTitle" style="margin:0; font-size:16px; font-weight:600; color:#fff;">Bulk Member Import</h3>
          <p style="margin:0; font-size:12px; color:rgba(255,255,255,0.7);">Upload an Excel or CSV file to register multiple members at once</p>
        </div>
      </div>
      <button onclick="closeModal('bulkImportModal')" style="background:rgba(255,255,255,0.15); border:none; border-radius:8px; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#fff;" aria-label="Close">
        <i class="ph ph-x" style="font-size:18px;"></i>
      </button>
    </div>

    <!-- Body -->
    <div style="padding:24px;">

      <?php if ($bulkResult): ?>
      <!-- ── Result Panel ────────────────────────────────── -->
      <div id="bulkResultPanel">
        <!-- Summary cards -->
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:20px;">
          <div style="background:#ECFDF5; border:1px solid #A7F3D0; border-radius:10px; padding:14px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:#065F46;"><?= $bulkResult['imported'] ?></div>
            <div style="font-size:12px; color:#065F46; font-weight:500;">Imported</div>
          </div>
          <div style="background:#FEF3C7; border:1px solid #FDE68A; border-radius:10px; padding:14px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:#92400E;"><?= count($bulkResult['errors']) ?></div>
            <div style="font-size:12px; color:#92400E; font-weight:500;">Errors</div>
          </div>
          <div style="background:#EEF2FF; border:1px solid #C7D2FE; border-radius:10px; padding:14px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:#3730A3;"><?= $bulkResult['total'] ?></div>
            <div style="font-size:12px; color:#3730A3; font-weight:500;">Total Rows</div>
          </div>
        </div>

        <?php if (!empty($bulkResult['errors'])): ?>
        <!-- Error table -->
        <div style="margin-bottom:16px;">
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
            <div style="font-size:13px; font-weight:600; color:#374151; display:flex; align-items:center; gap:6px;">
              <i class="ph ph-warning-circle" style="color:#F59E0B;"></i>
              Rows with errors (<?= count($bulkResult['errors']) ?>)
            </div>
            <button onclick="downloadErrorReport()" class="btn btn-outline btn-sm" style="font-size:11px; padding:4px 10px;">
              <i class="ph ph-download-simple"></i> Download Error Report
            </button>
          </div>
          <div style="max-height:220px; overflow-y:auto; border:1px solid #E5E7EB; border-radius:8px;">
            <table style="width:100%; font-size:12px; border-collapse:collapse;">
              <thead>
                <tr style="background:#F9FAFB; position:sticky; top:0;">
                  <th style="padding:8px 10px; text-align:left; color:#6B7280; font-weight:600; border-bottom:1px solid #E5E7EB;">Row</th>
                  <th style="padding:8px 10px; text-align:left; color:#6B7280; font-weight:600; border-bottom:1px solid #E5E7EB;">Name</th>
                  <th style="padding:8px 10px; text-align:left; color:#6B7280; font-weight:600; border-bottom:1px solid #E5E7EB;">Reason</th>
                </tr>
              </thead>
              <tbody id="bulkErrorRows">
                <?php foreach ($bulkResult['errors'] as $errRow): ?>
                <tr style="border-bottom:1px solid #F3F4F6;">
                  <td style="padding:7px 10px; color:#374151; font-weight:500;"><?= $errRow['row'] ?></td>
                  <td style="padding:7px 10px; color:#374151;"><?= htmlspecialchars($errRow['name']) ?></td>
                  <td style="padding:7px 10px; color:#DC2626;"><?= htmlspecialchars($errRow['errors']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div style="display:flex; gap:10px; justify-content:flex-end;">
          <button onclick="resetBulkImport()" class="btn btn-outline btn-sm">
            <i class="ph ph-arrow-counter-clockwise"></i> Import Another File
          </button>
          <button onclick="closeModal('bulkImportModal')" class="btn btn-primary btn-sm">
            <i class="ph ph-check"></i> Done
          </button>
        </div>
      </div>

      <!-- Hidden error data for JS download -->
      <script>
        const bulkErrorData = <?= json_encode($bulkResult['errors'] ?? []) ?>;
      </script>

      <?php else: ?>
      <!-- ── Upload Form ─────────────────────────────────── -->
      <div id="bulkUploadForm">
        <!-- How-to tip -->
        <div style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:8px; padding:12px 14px; margin-bottom:20px; display:flex; gap:10px; align-items:flex-start;">
          <i class="ph ph-info" style="color:#2563EB; font-size:18px; flex-shrink:0; margin-top:1px;"></i>
          <div style="font-size:12.5px; color:#1E40AF; line-height:1.6;">
            <strong>How it works:</strong> Download the Excel template, fill in your member details (one per row), then upload the completed file. Valid rows are imported instantly; errors are shown with clear explanations.
          </div>
        </div>

        <!-- Step 1 — Download template -->
        <div style="display:flex; align-items:center; gap:16px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
          <div style="width:36px; height:36px; border-radius:9px; background:#1E3A8A; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <span style="font-size:13px; font-weight:700; color:#fff;">1</span>
          </div>
          <div style="flex:1;">
            <div style="font-size:13px; font-weight:600; color:#111827;">Download the Template</div>
            <div style="font-size:12px; color:#6B7280; margin-top:2px;">Get the pre-formatted Excel file with all columns and an instructions sheet.</div>
          </div>
          <a href="download_member_template.php" class="btn btn-outline btn-sm" style="white-space:nowrap; flex-shrink:0;">
            <i class="ph ph-file-xls"></i> Download .xlsx
          </a>
        </div>

        <!-- Step 2 — Fill in data -->
        <div style="display:flex; align-items:center; gap:16px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
          <div style="width:36px; height:36px; border-radius:9px; background:#1E3A8A; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <span style="font-size:13px; font-weight:700; color:#fff;">2</span>
          </div>
          <div>
            <div style="font-size:13px; font-weight:600; color:#111827;">Fill In Member Details</div>
            <div style="font-size:12px; color:#6B7280; margin-top:2px;">Enter member data row by row. Delete the example row before saving. Required fields: <strong>First Name, Last Name, Gender, Phone</strong>.</div>
          </div>
        </div>

        <!-- Step 3 — Upload -->
        <div style="display:flex; align-items:flex-start; gap:16px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:10px; padding:14px 16px; margin-bottom:20px;">
          <div style="width:36px; height:36px; border-radius:9px; background:#1E3A8A; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px;">
            <span style="font-size:13px; font-weight:700; color:#fff;">3</span>
          </div>
          <div style="flex:1;">
            <div style="font-size:13px; font-weight:600; color:#111827; margin-bottom:8px;">Upload the Completed File</div>

            <form id="bulkImportForm" method="POST" action="handlers/bulk_import_handler.php" enctype="multipart/form-data">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="bulk_import_members">

              <!-- Drop zone -->
              <div id="dropZone"
                   onclick="document.getElementById('importFileInput').click()"
                   ondrop="handleFileDrop(event)"
                   ondragover="event.preventDefault(); this.classList.add('drag-over')"
                   ondragleave="this.classList.remove('drag-over')"
                   style="border:2px dashed #CBD5E1; border-radius:10px; padding:28px 20px; text-align:center; cursor:pointer; transition:all 0.2s; background:#fff; margin-bottom:12px;">
                <i class="ph ph-upload-simple" style="font-size:32px; color:#94A3B8; display:block; margin-bottom:8px;"></i>
                <div style="font-size:13px; font-weight:500; color:#374151;" id="dropZoneLabel">Click to select file or drag & drop here</div>
                <div style="font-size:11px; color:#9CA3AF; margin-top:4px;">Accepts .xlsx or .csv — Max 10 MB</div>
              </div>

              <input type="file" id="importFileInput" name="import_file"
                     accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
                     style="display:none;" onchange="handleFileSelect(this)">

              <!-- Selected file display -->
              <div id="selectedFileBar" style="display:none; background:#F0FDF4; border:1px solid #BBF7D0; border-radius:8px; padding:10px 14px; margin-bottom:12px; display:none; align-items:center; gap:10px;">
                <i class="ph ph-file-check" style="color:#16A34A; font-size:18px;"></i>
                <span id="selectedFileName" style="font-size:13px; color:#166534; font-weight:500; flex:1;"></span>
                <button type="button" onclick="clearFile()" style="background:none; border:none; cursor:pointer; color:#DC2626; font-size:18px; display:flex; align-items:center;" aria-label="Remove file">
                  <i class="ph ph-x-circle"></i>
                </button>
              </div>

              <!-- Submit -->
              <button type="submit" id="bulkImportSubmitBtn" class="btn btn-primary" style="width:100%; justify-content:center;" disabled>
                <i class="ph ph-cloud-arrow-up"></i> Import Members
              </button>
            </form>
          </div>
        </div>


      </div>
      <?php endif; ?>

    </div><!-- /body -->
  </div>
</div>

<style>
#dropZone.drag-over {
  border-color: #2563EB !important;
  background: #EFF6FF !important;
}
#dropZone:hover {
  border-color: #94A3B8;
  background: #F8FAFC;
}
</style>

<script>
function handleFileSelect(input) {
  if (input.files && input.files[0]) {
    showSelectedFile(input.files[0]);
  }
}

function handleFileDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (!file) return;

  const ext = file.name.split('.').pop().toLowerCase();
  if (!['xlsx', 'csv'].includes(ext)) {
    alert('Only .xlsx or .csv files are accepted.');
    return;
  }
  if (file.size > 10 * 1024 * 1024) {
    alert('File is too large. Maximum size is 10 MB.');
    return;
  }

  // Assign to real file input
  const dt = new DataTransfer();
  dt.items.add(file);
  document.getElementById('importFileInput').files = dt.files;
  showSelectedFile(file);
}

function showSelectedFile(file) {
  const bar  = document.getElementById('selectedFileBar');
  const name = document.getElementById('selectedFileName');
  const btn  = document.getElementById('bulkImportSubmitBtn');
  const zone = document.getElementById('dropZone');

  name.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
  bar.style.display = 'flex';
  zone.style.display = 'none';
  btn.disabled = false;
}

function clearFile() {
  document.getElementById('importFileInput').value = '';
  document.getElementById('selectedFileBar').style.display = 'none';
  document.getElementById('dropZone').style.display = 'block';
  document.getElementById('bulkImportSubmitBtn').disabled = true;
}

function resetBulkImport() {
  // Reload the page without the success param to show the upload form
  window.location.href = 'members.php';
}


function downloadErrorReport() {
  if (!window.bulkErrorData || !bulkErrorData.length) return;

  const rows = [['Row', 'Name', 'Phone', 'Error Reason']];
  bulkErrorData.forEach(r => rows.push([r.row, r.name, r.phone, r.errors]));

  let csv = rows.map(r => r.map(v => '"' + String(v).replace(/"/g,'""') + '"').join(',')).join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'import_errors_' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
  URL.revokeObjectURL(url);
}

// Show loading state on form submit
document.getElementById('bulkImportForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('bulkImportSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="ph ph-circle-notch" style="animation:spin 1s linear infinite"></i> Importing…';
});
</script>
