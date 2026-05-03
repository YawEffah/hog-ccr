<!-- Global Confirm Modal -->
<div class="modal-overlay" id="globalConfirmModal" style="z-index: 9999;">
  <div class="modal" style="max-width: 400px; text-align: center; padding: 32px 24px;">
    <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF2F2; color: #DC2626; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;">
      <i class="ph ph-warning" id="globalConfirmIcon"></i>
    </div>
    <h3 id="globalConfirmTitle" style="margin-bottom: 12px; font-size: 18px; font-weight: 600; color: var(--deep2);">Confirm Action</h3>
    <p id="globalConfirmMessage" style="color: var(--muted); font-size: 14px; margin-bottom: 28px; line-height: 1.5;">Are you sure you want to proceed?</p>
    <div style="display: flex; gap: 12px; justify-content: center;">
      <button class="btn btn-outline" style="flex: 1;" onclick="closeModal('globalConfirmModal')">Cancel</button>
      <button class="btn btn-primary" style="flex: 1; background: #DC2626; border-color: #DC2626; color: white;" id="globalConfirmBtn">Confirm</button>
    </div>
  </div>
</div>
