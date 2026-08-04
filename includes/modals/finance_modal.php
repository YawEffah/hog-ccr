<!-- Add Finance Modal -->
<div class="modal-overlay" id="addFinanceModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Record Transaction</h3>
      <button class="close-btn" onclick="closeModal('addFinanceModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/finance_handler.php" method="POST" id="addFinanceForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_transaction">
      <div class="modal-body">
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Week</label>
            <select class="form-control" name="week_number" required>
              <option value="Week 1">Week 1</option>
              <option value="Week 2">Week 2</option>
              <option value="Week 3">Week 3</option>
              <option value="Week 4">Week 4</option>
              <option value="Week 5">Week 5</option>
            </select>
          </div>
          <div class="form-group" style="position: relative;">
            <label class="form-label">Transaction Type</label>
            <input type="hidden" name="transaction_type" id="transactionTypeSelect" required>
            <div class="custom-select-trigger" onclick="toggleTxnDropdown()" id="txnSelectTrigger">
              <span id="txnSelectedText" style="color:var(--muted)">Select or add new...</span>
              <i class="ph ph-caret-down"></i>
            </div>
            <div class="custom-select-dropdown" id="txnSelectDropdown" style="display:none;">
              <div class="custom-select-search">
                <input type="text" id="txnSearchInput" placeholder="Type new & press Enter..." onkeydown="handleTxnInput(event)" autocomplete="off">
                <button type="button" class="btn btn-sm btn-primary" onclick="addNewTxnType()" style="padding: 4px 8px; font-size:12px; height:auto; min-height:0;"><i class="ph ph-plus"></i></button>
              </div>
              <div class="custom-select-options" id="txnSelectOptions">
                <?php
                  if (isset($db)) {
                      $typeStmt = $db->query("SELECT DISTINCT type FROM finance_transactions WHERE type IS NOT NULL AND type != ''");
                      $dbTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);
                  } else {
                      $dbTypes = [];
                  }
                  $defaultTypes = ['Tithe', 'Offering', 'Donation', 'Pledge', 'Project Contribution', 'Welfare', 'Half Year Thanks Giving', 'End of Year Thanks Giving'];
                  $allTypes = array_unique(array_merge($defaultTypes, $dbTypes));
                  foreach($allTypes as $t) {
                      echo '<div class="custom-select-option" onclick="selectTxnType(\'' . htmlspecialchars(addslashes($t)) . '\')">' . htmlspecialchars($t) . '</div>';
                  }
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Amount (GH₵)</label><input type="number" step="0.01"
              class="form-control" name="amount" placeholder="0.00" required></div>
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select class="form-control" name="payment_method" id="paymentMethodSelect">
              <option value="Cash">Cash</option>
              <option value="MoMo">MoMo</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-control"
              name="date" value="<?= date('Y-m-d') ?>"></div>
          <div class="form-group">
            <label class="form-label" id="refLabel">Reference / Trans ID</label>
            <input class="form-control" name="reference_no" placeholder="e.g. TXN123 or CHQ456">
          </div>
        </div>

        <div class="form-group"><label class="form-label">Notes</label><textarea class="form-control" name="notes"
            rows="2" placeholder="Optional notes…" style="resize:none;"></textarea></div>
        <div style="background:#F1F5F9;border-radius:10px;padding:14px;display:flex;align-items:center;gap:12px;">
          <input type="checkbox" id="genReceipt" name="generate_receipt" checked
            style="width:16px;height:16px;cursor:pointer;">
          <div>
            <label for="genReceipt"
              style="font-size:13px;font-weight:600;cursor:pointer;color:var(--deep2);display:block;">Send summary notification to Finance Officer & Head Pastor</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addFinanceModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Transaction</button>
      </div>
    </form>
  </div>
</div>

<!-- Set Monthly Target Modal -->
<div class="modal-overlay" id="setTargetModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <h3>Set Monthly Target</h3>
      <button class="close-btn" onclick="closeModal('setTargetModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/finance_handler.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="set_target">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Target Amount (GH₵)</label>
          <input type="number" step="0.01" class="form-control" name="monthly_target" placeholder="e.g. 30000"
            value="30000" required>
        </div>
        <div class="form-group">
          <label class="form-label">Month</label>
          <input type="month" class="form-control" name="target_month" value="<?= date('Y-m') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Notes (Optional)</label>
          <textarea class="form-control" name="notes" rows="2" placeholder="e.g. Special project funding target"
            style="resize:none;"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('setTargetModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Target</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.getElementById('paymentMethodSelect')?.addEventListener('change', function () {
    const label = document.getElementById('refLabel');
    const method = this.value;

    if (method === 'MoMo') {
      label.textContent = 'Transaction ID';
    } else if (method === 'Bank Transfer') {
      label.textContent = 'Bank Reference';
    } else if (method === 'Cheque') {
      label.textContent = 'Cheque Number';
    } else {
      label.textContent = 'Reference / Trans ID';
    }
  });

  function toggleTxnDropdown() {
    const dropdown = document.getElementById('txnSelectDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'flex' : 'none';
    if(dropdown.style.display === 'flex') {
        document.getElementById('txnSearchInput').focus();
    }
  }

  function selectTxnType(value) {
    document.getElementById('transactionTypeSelect').value = value;
    document.getElementById('txnSelectedText').textContent = value;
    document.getElementById('txnSelectedText').style.color = '#1E293B';
    document.getElementById('txnSelectDropdown').style.display = 'none';
    document.getElementById('txnSearchInput').value = '';
  }

  function addNewTxnType() {
    const input = document.getElementById('txnSearchInput');
    const val = input.value.trim();
    if(val) {
        selectTxnType(val);
    }
  }

  function handleTxnInput(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addNewTxnType();
    }
  }

  document.addEventListener('click', function(e) {
    const trigger = document.getElementById('txnSelectTrigger');
    const dropdown = document.getElementById('txnSelectDropdown');
    if (trigger && dropdown && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
  });
</script>
<style>
  .custom-select-trigger {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    font-size: 14px;
    color: #1E293B;
  }
  .custom-select-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    z-index: 100;
    flex-direction: column;
    max-height: 250px;
    overflow: hidden;
  }
  .custom-select-search {
    padding: 8px;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .custom-select-search input {
    border: 1px solid #E2E8F0;
    border-radius: 4px;
    padding: 6px 10px;
    outline: none;
    width: 100%;
    font-size: 13px;
  }
  .custom-select-options {
    overflow-y: auto;
    max-height: 200px;
  }
  .custom-select-option {
    padding: 10px 14px;
    font-size: 14px;
    cursor: pointer;
    color: #334155;
    transition: background 0.2s;
  }
  .custom-select-option:hover {
    background: #F1F5F9;
  }
</style>