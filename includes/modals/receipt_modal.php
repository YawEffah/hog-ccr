<!-- ── FINANCE RECEIPT MODAL ────────────────────────────────────────────── -->
<div class="modal-overlay" id="viewReceiptModal">
  <div class="modal" style="max-width:440px; padding: 0; background: #F8FAFC; overflow: hidden;">
    <div class="modal-header" style="background: white; border-bottom: 1px solid #E2E8F0; padding: 16px 24px;">
      <h3 style="margin:0; font-size: 18px; color: var(--deep);">Digital Receipt</h3>
      <button class="close-btn" onclick="closeModal('viewReceiptModal')"><i class="ph ph-x"></i></button>
    </div>
    
    <div class="modal-body" style="padding: 24px;">
      <!-- Receipt Paper Effect -->
      <div style="background: white; border: 1px solid #E2E8F0; border-radius: 8px; padding: 32px 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); position: relative;">
        
        <!-- Church Branding -->
        <div style="text-align: center; margin-bottom: 24px;">
          <div style="font-size: 20px; font-weight: 800; color: var(--deep); letter-spacing: -0.5px;">HOUSE OF GRACE CCR</div>
          <div style="font-size: 11px; color: var(--gold); font-weight: 700; text-transform: uppercase; margin-top: 4px; letter-spacing: 1px;">Official Payment Receipt</div>
        </div>

        <!-- Success Indicator -->
        <div style="display: flex; justify-content: center; margin-bottom: 24px;">
          <div style="background: #F0FDFA; color: #0D9488; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px; border: 1px solid #CCFBF1;">
            <i class="ph-fill ph-check-circle"></i> PAYMENT CONFIRMED
          </div>
        </div>

        <!-- Transaction Details -->
        <div style="border-top: 1px dashed #CBD5E1; border-bottom: 1px dashed #CBD5E1; padding: 20px 0; margin-bottom: 24px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
            <span style="font-size: 13px; color: #64748B;">Receipt No:</span>
            <span style="font-size: 13px; font-weight: 700; color: var(--deep);" id="receiptId">#0000</span>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
            <span style="font-size: 13px; color: #64748B;">Date:</span>
            <span style="font-size: 13px; font-weight: 600;" id="receiptDate">Jan 01, 2026</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="font-size: 13px; color: #64748B;">Payer:</span>
            <span style="font-size: 13px; font-weight: 600; text-align: right;" id="receiptMember">Guest Member</span>
          </div>
        </div>

        <!-- Financial Breakdown -->
        <div style="margin-bottom: 32px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span style="font-size: 14px; color: #1E293B; font-weight: 600;" id="receiptType">Tithe</span>
            <span style="font-size: 18px; font-weight: 800; color: #0D9488;">GH₵ <span id="receiptAmount">0.00</span></span>
          </div>
          <div style="font-size: 12px; color: #64748B;" id="receiptMethodBox">
            Method: <span id="receiptMethod">Cash</span> <span id="receiptRef" style="margin-left: 8px; color: #94A3B8;">(Ref: N/A)</span>
          </div>
        </div>

        <!-- Footer Note -->
        <div style="text-align: center; color: #94A3B8; font-size: 12px; line-height: 1.5;">
          Thank you for your generous contribution. <br>
          "God loves a cheerful giver."
        </div>

      </div>
    </div>

    <div class="modal-footer" style="background: white; border-top: 1px solid #E2E8F0; padding: 16px 24px; display: flex; gap: 12px;">
      <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeModal('viewReceiptModal')">Close</button>
      <form method="POST" action="handlers/finance_handler.php" style="flex:1; margin: 0;" id="resendReceiptForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="resend_receipt">
        <input type="hidden" name="txn_id" id="resendTxnId">
        <!-- Return to current page -->
        <input type="hidden" name="return_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">
          <i class="ph ph-paper-plane-tilt"></i> Resend
        </button>
      </form>
    </div>
  </div>
</div>
