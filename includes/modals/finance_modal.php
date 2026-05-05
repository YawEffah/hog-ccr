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
          <div class="form-group" style="position: relative;">
            <label class="form-label">Member (Search by Name or ID)</label>
            <input class="form-control" id="financeMemberSearch" name="member_display"
              placeholder="Enter name or member ID…" oninput="filterFinanceMember(this.value)" autocomplete="off"
              required>
            <input type="hidden" name="member_id" id="financeMemberId">
            <div id="financeSuggestions"
              style="position:absolute; top:100%; left:0; right:0; z-index:100; background:#fff; border:1px solid #EDE8DF; border-radius:8px; max-height:200px; overflow-y:auto; display:none; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-top:4px;">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Transaction Type</label>
            <select class="form-control" name="transaction_type" required>
              <option>Tithe</option>
              <option>Offering</option>
              <option>Donation</option>
              <option>Pledge</option>
              <option>Project Contribution</option>
              <option>Welfare</option>
            </select>
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
        <div class="grid-2" style="gap:16px;" id="financeContactFields">
          <div class="form-group"><label class="form-label">Phone Number</label><input class="form-control" name="phone"
              placeholder="0244-000-000"></div>
          <div class="form-group"><label class="form-label">Email Address</label><input class="form-control"
              type="email" name="email" placeholder="receipt-to@email.com"></div>
        </div>
        <div class="form-group"><label class="form-label">Notes</label><textarea class="form-control" name="notes"
            rows="2" placeholder="Optional notes…" style="resize:none;"></textarea></div>
        <div style="background:#F1F5F9;border-radius:10px;padding:14px;display:flex;align-items:center;gap:12px;">
          <input type="checkbox" id="genReceipt" name="generate_receipt" checked
            style="width:16px;height:16px;cursor:pointer;">
          <div>
            <label for="genReceipt"
              style="font-size:13px;font-weight:600;cursor:pointer;color:var(--deep2);display:block;">Send
              receipt automatically via SMS & email</label>
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
</script>