<!-- ================================================================
  WELFARE MODALS
  1. enrolWelfareModal      – Enrol an existing member into welfare
  2. recordWelfarePaymentModal – Record a contribution payment
  3. viewWelfareMemberModal  – View member + contribution history
  4. sendWelfareMessageModal – Send messages to payers on a chosen date
================================================================ -->

<!-- 1. Enrol Member Modal -->
<div class="modal-overlay" id="enrolWelfareModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <h3>Enrol Member into Welfare</h3>
      <button class="close-btn" onclick="closeModal('enrolWelfareModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/welfare_handler.php" method="POST" id="enrolWelfareForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="enrol_welfare">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Select Member</label>
          <input class="form-control" id="enrolMemberSearch" name="member_display" placeholder="Search by name or ID…" oninput="filterWelfareEnrolList(this.value)" autocomplete="off" required>
          <input type="hidden" name="member_id" id="enrolMemberId" required>
        </div>
        <div id="enrolSuggestions" style="background:#F8FAFC;border:1px solid #EDE8DF;border-radius:8px;max-height:140px;overflow-y:auto;display:none;margin-top:-10px;margin-bottom:14px;"></div>

        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Date of Enrolment</label>
            <input type="date" class="form-control" name="enrol_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Monthly Contribution (GH₵)</label>
            <input type="number" step="0.01" class="form-control" name="monthly_amount" placeholder="e.g. 20.00" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes (Optional)</label>
          <textarea class="form-control" name="notes" rows="2" placeholder="Any remarks…" style="resize:none;"></textarea>
        </div>
        <div style="background:#F0FDF4;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;border:1px solid #BBF7D0;">
          <i class="ph ph-info" style="color:#15803D;font-size:18px;flex-shrink:0;"></i>
          <span style="font-size:12px;color:#15803D;">Only existing church members can be enrolled into Welfare. If the person is not yet in the Members list, add them first.</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('enrolWelfareModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="ph ph-hand-heart"></i> Enrol Member
        </button>
      </div>
    </form>
  </div>
</div>

<!-- 2. Record Welfare Payment Modal -->
<div class="modal-overlay" id="recordWelfarePaymentModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Record Welfare Contribution</h3>
      <button class="close-btn" onclick="closeModal('recordWelfarePaymentModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/welfare_handler.php" method="POST" id="recordWelfarePaymentForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="record_welfare_payment">
      <input type="hidden" name="welfare_member_id" id="paymentWelfareMemberId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Welfare Member</label>
          <input class="form-control" id="paymentMemberDisplay" name="member_display"
            placeholder="Search or pre-filled from row action…" oninput="searchWelfarePayers(this.value)" required>
        </div>
        <div id="payerSuggestions" style="background:#F8FAFC;border:1px solid #EDE8DF;border-radius:8px;max-height:140px;overflow-y:auto;display:none;margin-top:-10px;margin-bottom:14px;"></div>

        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Amount (GH₵)</label>
            <input type="number" step="0.01" class="form-control" name="amount" id="welfarePayAmount" placeholder="0.00" required>
          </div>
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select class="form-control" name="payment_method" id="welfarePayMethod">
              <option value="Cash">Cash</option>
              <option value="MoMo">MoMo</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Payment Date</label>
            <input type="date" class="form-control" name="payment_date" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" id="welfareRefLabel">Reference / Trans ID</label>
            <input class="form-control" name="reference" placeholder="e.g. TXN123">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea class="form-control" name="notes" rows="2" placeholder="Optional…" style="resize:none;"></textarea>
        </div>
        <!-- Send notification toggle -->
        <div style="background:#F0FDFA;border-radius:10px;padding:14px;display:flex;align-items:flex-start;gap:12px;border:1px solid #99F6E4;">
          <input type="checkbox" id="sendWelfareNotif" name="send_notification" checked
            style="width:16px;height:16px;cursor:pointer;margin-top:2px;accent-color:#0D9488;">
          <div>
            <label for="sendWelfareNotif" style="font-size:13px;font-weight:600;cursor:pointer;color:#0D9488;display:block;">
              Send payment confirmation to member
            </label>
            <span style="font-size:11px;color:#0F766E;">Message will be sent via Email to the member's registered address.</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('recordWelfarePaymentModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Contribution</button>
      </div>
    </form>
  </div>
</div>

<!-- 3. View Welfare Member Modal -->
<div class="modal-overlay" id="viewWelfareMemberModal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <div style="display:flex;align-items:center;gap:14px;">
        <div class="avatar" style="width:48px;height:48px;font-size:18px;background:#CCFBF1;color:#0D9488;" id="welfareViewAvatar">JD</div>
        <div>
          <h3 id="welfareViewName">Jane Doe</h3>
          <div style="font-size:12px;color:var(--muted);" id="welfareViewId">CCR-001 · Welfare Member</div>
        </div>
      </div>
      <button class="close-btn" onclick="closeModal('viewWelfareMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <div class="modal-body">
      <!-- Profile row -->
      <div class="grid-2" style="gap:20px;margin-bottom:20px;">
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Phone</div>
          <div style="font-weight:500;" id="welfareViewPhone">0244-000-000</div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Email</div>
          <div style="font-weight:500;" id="welfareViewEmail">member@email.com</div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Enrolled</div>
          <div style="font-weight:500;" id="welfareViewEnrolled">Jan 2025</div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Status</div>
          <div id="welfareViewStatus"><span class="badge badge-welfare">Active</span></div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Last Payment</div>
          <div style="font-weight:500;" id="welfareViewLastPay">Apr 15, 2026</div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Total Contributed</div>
          <div style="font-weight:700;color:#0D9488;" id="welfareViewTotal">GH₵ 480.00</div>
        </div>
      </div>
      <!-- Contribution history -->
      <div style="font-size:12px;font-weight:700;color:var(--muted);letter-spacing:0.8px;text-transform:uppercase;margin-bottom:10px;">Recent Contributions</div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Reference</th>
              <th>Notif.</th>
            </tr>
          </thead>
          <tbody id="welfareViewHistory">
            <tr><td colspan="5" style="text-align:center;color:var(--muted);font-size:13px;padding:20px;">No contributions recorded yet.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('viewWelfareMemberModal')">Close</button>
      <button class="btn btn-primary" id="welfareViewRecordBtn">
        <i class="ph ph-plus"></i> Record Payment
      </button>
    </div>
  </div>
</div>

<!-- 4. Send Message Modal -->
<div class="modal-overlay" id="sendWelfareMessageModal">
  <div class="modal" style="max-width:540px;">
    <div class="modal-header">
      <h3>Send Payment Message</h3>
      <button class="close-btn" onclick="closeModal('sendWelfareMessageModal')"><i class="ph ph-x"></i></button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:20px;">
        Select a date to load all welfare members who made contributions on that day,
        then send them a personalised confirmation message.
      </p>
      <div class="grid-2" style="gap:16px;margin-bottom:16px;">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Payment Date</label>
          <input type="date" class="form-control" id="msgPaymentDate" value="<?= date('Y-m-d') ?>" oninput="loadPayersForDate(this.value)">
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Channel</label>
          <select class="form-control" id="msgChannel">
            <option value="sms">SMS</option>
            <option value="email">Email</option>
            <option value="both">Both (SMS + Email)</option>
          </select>
        </div>
      </div>

      <!-- Custom message toggle -->
      <div class="form-group">
        <label class="form-label">Message Preview</label>
        <textarea class="form-control" id="msgBody" rows="3" style="resize:none;font-size:13px;"
          placeholder="Auto-generated on date selection…"></textarea>
        <div style="font-size:11px;color:var(--muted);margin-top:4px;">You may edit this message before sending.</div>
      </div>

      <!-- Payer list -->
      <div style="font-size:12px;font-weight:700;color:var(--muted);letter-spacing:0.8px;text-transform:uppercase;margin-bottom:8px;">
        Recipients <span id="payerCountBadge" class="badge badge-welfare" style="font-size:11px;margin-left:6px;">0</span>
      </div>
      <div id="payerListBox" style="background:#F8FAFC;border:1px solid #EDE8DF;border-radius:10px;max-height:180px;overflow-y:auto;padding:0;">
        <div style="padding:20px;text-align:center;color:var(--muted);font-size:13px;">Select a date to load recipients.</div>
      </div>
    </div>
    <div class="modal-footer">
      <div id="msgResultBadge" style="flex:1;font-size:13px;display:none;"></div>
      <button class="btn btn-outline" onclick="closeModal('sendWelfareMessageModal')">Cancel</button>
      <button class="btn btn-primary" id="sendMsgBtn" onclick="dispatchWelfareMessages()">
        <i class="ph ph-paper-plane-tilt"></i> Send Messages
      </button>
    </div>
  </div>
</div>

<script>
/* =====================================================
   WELFARE MODAL JAVASCRIPT
   ===================================================== */

// --- Mock welfare members for JS (TODO: replace with real API/JSON) ---
const welfareMembersData = [
  { id: 'W-001', member_id: 'CCR-001', name: 'Abena Kusi',      phone: '0244-123-456', email: 'abena@email.com',   enrolled: 'Jan 2025', last_pay: 'Apr 15, 2026', total: '480.00', status: 'Active',  history: [
    { date: 'Apr 15, 2026', amount: '20.00', method: 'MoMo',  ref: 'TXN8821', notif: true },
    { date: 'Mar 12, 2026', amount: '20.00', method: 'Cash',  ref: '',        notif: false },
  ]},
  { id: 'W-002', member_id: 'CCR-002', name: 'Kwame Ofori',     phone: '0200-987-654', email: 'kwame@email.com',   enrolled: 'Feb 2025', last_pay: 'Apr 15, 2026', total: '360.00', status: 'Active',  history: [
    { date: 'Apr 15, 2026', amount: '20.00', method: 'MoMo',  ref: 'TXN8830', notif: true },
  ]},
  { id: 'W-003', member_id: 'CCR-004', name: 'Michael Boateng', phone: '0277-456-123', email: 'michael@email.com', enrolled: 'Mar 2025', last_pay: 'Mar 1, 2026',  total: '140.00', status: 'Arrears', history: [
    { date: 'Mar 1, 2026',  amount: '20.00', method: 'Cash',  ref: '',        notif: false },
  ]},
  { id: 'W-004', member_id: 'CCR-006', name: 'Pastor Adu',      phone: '0201-000-001', email: 'pastor@ccrhog.org', enrolled: 'Jan 2025', last_pay: 'Apr 28, 2026', total: '600.00', status: 'Active',  history: [
    { date: 'Apr 28, 2026', amount: '50.00', method: 'Bank Transfer', ref: 'BNK0042', notif: true },
  ]},
];

// Contributions on today's date for the send-message default
const todayStr = new Date().toISOString().split('T')[0];

/* ---- Enrol modal member search ---- */
const enrolMembersData = <?php echo json_encode(array_map(function($m) {
    return [
        'id' => $m['id'],
        'member_code' => $m['member_code'],
        'name' => htmlspecialchars($m['first_name'] . ' ' . $m['last_name'])
    ];
}, $nonWelfareMembers)); ?>;

function filterWelfareEnrolList(q) {
  const box = document.getElementById('enrolSuggestions');
  const allMembers = enrolMembersData;
  if (!q) { box.style.display = 'none'; return; }
  const filtered = allMembers.filter(m =>
    m.name.toLowerCase().includes(q.toLowerCase()) || m.member_code.toLowerCase().includes(q.toLowerCase())
  );
  if (!filtered.length) { box.style.display = 'none'; return; }
  box.innerHTML = filtered.map(m =>
    `<div onclick="selectEnrolMember('${m.id}','${m.name}')"
      style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid #F4F0EA;"
      onmouseover="this.style.background='#F0FDFA'" onmouseout="this.style.background=''">${m.name} <span style="color:var(--muted);font-size:11px;">${m.member_code}</span></div>`
  ).join('');
  box.style.display = 'block';
}

function selectEnrolMember(id, name) {
  document.getElementById('enrolMemberSearch').value = name;
  document.getElementById('enrolMemberId').value = id;
  document.getElementById('enrolSuggestions').style.display = 'none';
}

/* ---- Record payment member search ---- */
function searchWelfarePayers(q) {
  const box = document.getElementById('payerSuggestions');
  if (!q) { box.style.display = 'none'; return; }
  const filtered = welfareMembersData.filter(m =>
    m.name.toLowerCase().includes(q.toLowerCase()) || m.member_id.toLowerCase().includes(q.toLowerCase())
  );
  if (!filtered.length) { box.style.display = 'none'; return; }
  box.innerHTML = filtered.map(m =>
    `<div onclick="selectWelfarePayer('${m.id}','${m.name}')"
      style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid #F4F0EA;"
      onmouseover="this.style.background='#F0FDFA'" onmouseout="this.style.background=''">${m.name} <span style="color:var(--muted);font-size:11px;">${m.member_id}</span></div>`
  ).join('');
  box.style.display = 'block';
}

function selectWelfarePayer(wid, name) {
  document.getElementById('paymentMemberDisplay').value = name;
  document.getElementById('paymentWelfareMemberId').value = wid;
  document.getElementById('payerSuggestions').style.display = 'none';
}

// Dynamic reference label based on payment method
document.getElementById('welfarePayMethod')?.addEventListener('change', function () {
  const labels = { 'MoMo': 'Transaction ID', 'Bank Transfer': 'Bank Reference', 'Cheque': 'Cheque Number' };
  const lbl = document.getElementById('welfareRefLabel');
  if (lbl) lbl.textContent = labels[this.value] || 'Reference / Trans ID';
});

/* ---- View welfare member ---- */
function viewWelfareMember(wid) {
  const m = welfareMembersData.find(x => x.id === wid);
  if (!m) return;

  const initials = m.name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
  document.getElementById('welfareViewAvatar').textContent = initials;
  document.getElementById('welfareViewName').textContent = m.name;
  document.getElementById('welfareViewId').textContent = m.member_id + ' · Welfare Member';
  document.getElementById('welfareViewPhone').textContent = m.phone;
  document.getElementById('welfareViewEmail').textContent = m.email;
  document.getElementById('welfareViewEnrolled').textContent = m.enrolled;
  document.getElementById('welfareViewLastPay').textContent = m.last_pay;
  document.getElementById('welfareViewTotal').textContent = 'GH₵ ' + m.total;

  const statusClass = m.status === 'Active' ? 'badge-welfare' : 'badge-red';
  document.getElementById('welfareViewStatus').innerHTML = `<span class="badge ${statusClass}">${m.status}</span>`;

  // History
  const tbody = document.getElementById('welfareViewHistory');
  if (!m.history || !m.history.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:20px;">No contributions yet.</td></tr>';
  } else {
    tbody.innerHTML = m.history.map(h => `
      <tr>
        <td style="font-size:12px;">${h.date}</td>
        <td style="font-weight:600;color:#0D9488;">GH₵ ${h.amount}</td>
        <td><span class="badge badge-gray">${h.method}</span></td>
        <td style="font-size:12px;color:var(--muted);">${h.ref || '—'}</td>
        <td>${h.notif ? '<i class="ph ph-check-circle" style="color:#0D9488;font-size:16px;" title="Sent"></i>' : '<i class="ph ph-x-circle" style="color:var(--muted);font-size:16px;" title="Not sent"></i>'}</td>
      </tr>`).join('');
  }

  document.getElementById('welfareViewRecordBtn').onclick = () => {
    closeModal('viewWelfareMemberModal');
    openRecordPaymentFor(wid, m.name);
  };

  openModal('viewWelfareMemberModal');
}

function openRecordPaymentFor(wid, name) {
  document.getElementById('paymentMemberDisplay').value = name;
  document.getElementById('paymentWelfareMemberId').value = wid;
  openModal('recordWelfarePaymentModal');
}

/* ---- Send message modal ---- */
function loadPayersForDate(dateVal) {
  const box = document.getElementById('payerListBox');
  const badge = document.getElementById('payerCountBadge');
  const msgBox = document.getElementById('msgBody');

  // Simulate: members who paid on that date
  const fmtDate = dateVal ? new Date(dateVal + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

  // For demo, show payers if date is today or a known date
  const payers = welfareMembersData.filter(m =>
    m.history.some(h => h.date.includes('Apr 15') || h.date.includes('Apr 28'))
  ).slice(0, 3); // mock subset

  badge.textContent = payers.length;

  if (!payers.length) {
    box.innerHTML = '<div style="padding:20px;text-align:center;color:var(--muted);font-size:13px;">No contributions recorded for this date.</div>';
    msgBox.value = '';
    return;
  }

  box.innerHTML = payers.map(m => `
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid #EDE8DF;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div class="avatar" style="width:30px;height:30px;font-size:12px;background:#CCFBF1;color:#0D9488;">${m.name.split(' ').map(w=>w[0]).join('').substring(0,2)}</div>
        <div>
          <div style="font-size:13px;font-weight:500;">${m.name}</div>
          <div style="font-size:11px;color:var(--muted);">${m.phone}</div>
        </div>
      </div>
      <span class="badge badge-welfare" style="font-size:11px;">GH₵ ${m.history[0].amount}</span>
    </div>`).join('');

  msgBox.value = `Dear [Name], your welfare contribution of GH₵ [amount] on ${fmtDate} has been received. God bless you. — House of Grace CCR`;
}

function dispatchWelfareMessages() {
  const btn = document.getElementById('sendMsgBtn');
  const result = document.getElementById('msgResultBadge');
  const count = parseInt(document.getElementById('payerCountBadge').textContent) || 0;

  if (!count) { alert('No recipients for the selected date.'); return; }

  btn.disabled = true;
  btn.innerHTML = '<i class="ph ph-spinner" style="animation:spin 1s linear infinite;"></i> Sending…';

  setTimeout(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="ph ph-paper-plane-tilt"></i> Send Messages';
    result.style.display = 'block';
    result.innerHTML = `<span class="badge badge-welfare"><i class="ph ph-check"></i> ${count} message(s) sent successfully</span>`;
  }, 1800);
}

// Auto-load for today when modal opens
document.getElementById('sendWelfareMessageModal')?.addEventListener('click', function(e) {
  if (e.target === this) return;
});

function openSendWelfareMessage() {
  loadPayersForDate(document.getElementById('msgPaymentDate')?.value || todayStr);
  openModal('sendWelfareMessageModal');
}
</script>
