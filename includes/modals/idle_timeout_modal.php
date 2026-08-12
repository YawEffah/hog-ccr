<!-- Idle Timeout Warning Modal -->
<div class="modal-overlay" id="idleTimeoutModal" style="z-index: 10000;">
  <div class="modal" style="max-width: 400px; text-align: center; padding: 32px 24px;">
    <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF3C7; color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px;">
      <i class="ph ph-clock"></i>
    </div>
    <h3 style="margin-bottom: 12px; font-size: 18px; font-weight: 600; color: var(--deep2);">Session Expiring Soon</h3>
    <p style="color: var(--muted); font-size: 14px; margin-bottom: 28px; line-height: 1.5;">
      You've been inactive. You'll be logged out in <strong id="idleCountdownDisplay" style="color: #D97706;">2:00</strong>.
    </p>
    <div style="display: flex; gap: 12px; justify-content: center;">
      <button class="btn btn-outline" style="flex: 1;" onclick="window.location.href='logout.php'">Logout Now</button>
      <button class="btn btn-primary" style="flex: 1;" id="idleStayBtn" onclick="resetIdleTimer()">Stay Logged In</button>
    </div>
  </div>
</div>
