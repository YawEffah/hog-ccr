function openModal(id) {
  document.getElementById(id).classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

// Close modal or dropdown when clicking on overlay or outside
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }

  // Close notification panel when clicking outside
  const notifPanel = document.getElementById('notifPanel');
  const notifBtn = document.getElementById('notifBtn');
  if (notifPanel && notifPanel.classList.contains('active') && !notifPanel.contains(e.target) && !notifBtn.contains(e.target)) {
    notifPanel.classList.remove('active');
  }
});

// Tab toggle functionality
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.tabs').forEach(tabs => {
    tabs.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
      });
    });
  });
});
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('active');
  // Create overlay if not exists
  let overlay = document.getElementById('sidebar-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'sidebar-overlay';
    overlay.className = 'sidebar-overlay';
    overlay.onclick = toggleSidebar;
    document.body.appendChild(overlay);
  }
  overlay.classList.toggle('active');
}
function toggleNotifications() {
  const panel = document.getElementById('notifPanel');
  if (panel) {
    panel.classList.toggle('active');
    // Hide dot if panel is opened
    const dot = document.querySelector('.notif-dot');
    if (dot) dot.style.display = 'none';
  }
}

/**
 * Global Confirm Modal Utility
 * @param {string} title Modal title
 * @param {string} message Modal body text
 * @param {string} confirmText Text for confirm button
 * @param {function} onConfirm Callback when confirm is clicked
 * @param {string} type 'danger', 'warning', 'info'
 */
function showConfirmModal(title, message, confirmText, onConfirm, type = 'danger') {
  document.getElementById('globalConfirmTitle').textContent = title;
  document.getElementById('globalConfirmMessage').textContent = message;

  const btn = document.getElementById('globalConfirmBtn');
  btn.textContent = confirmText;

  const icon = document.getElementById('globalConfirmIcon');
  const iconWrap = icon.parentElement;

  if (type === 'danger') {
    iconWrap.style.background = '#FEF2F2';
    iconWrap.style.color = '#DC2626';
    icon.className = 'ph ph-warning';
    btn.style.background = '#DC2626';
    btn.style.borderColor = '#DC2626';
    btn.style.color = 'white';
  } else {
    iconWrap.style.background = '#EEF2FF';
    iconWrap.style.color = 'var(--deep)';
    icon.className = 'ph ph-info';
    btn.style.background = 'var(--deep)';
    btn.style.borderColor = 'var(--deep)';
    btn.style.color = 'white';
  }

  // Remove old listeners to avoid multiple triggers
  const newBtn = btn.cloneNode(true);
  btn.parentNode.replaceChild(newBtn, btn);

  newBtn.onclick = function () {
    closeModal('globalConfirmModal');
    if (typeof onConfirm === 'function') {
      onConfirm();
    }
  };

  openModal('globalConfirmModal');
}

/**
 * Global Loader Utility
 */
function showGlobalLoader(text = 'Processing...') {
  let overlay = document.getElementById('globalLoaderOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'globalLoaderOverlay';
    overlay.className = 'global-loader-overlay';
    overlay.innerHTML = `
            <div class="spinner"></div>
            <div class="loader-text" id="globalLoaderText"></div>
        `;
    document.body.appendChild(overlay);
  }
  document.getElementById('globalLoaderText').textContent = text;
  overlay.classList.add('active');
}

function hideGlobalLoader() {
  const overlay = document.getElementById('globalLoaderOverlay');
  if (overlay) {
    overlay.classList.remove('active');
  }
}

// Background Task and Form Interception
document.addEventListener('DOMContentLoaded', () => {
  // Trigger background queue processing invisibly
  fetch('handlers/process_queue.php').catch(e => console.error('Queue error:', e));

  // Intercept forms that trigger long-running tasks to show loading state
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function (e) {
      // All checkbox names that may trigger a queued notification
      const notifyCheckboxes = [
        'notify_members',       // Events & Announcements
        'send_welcome',         // Members & Welfare enrol
        'send_notification',    // Welfare payment
        'send_receipt',         // (legacy alias)
        'generate_receipt',     // Finance receipt
      ];

      // All action values that always send notifications
      const notifyActions = [
        'send_welfare_messages',
        'broadcast_event',
        'broadcast_announcement',
        'send_ministry_bulk_message',
        'resend_receipt',
      ];

      const actionEl = this.querySelector('[name="action"]');
      const actionVal = actionEl ? actionEl.value : '';

      const needsLoader =
        notifyCheckboxes.some(name => this.querySelector(`[name="${name}"]:checked`)) ||
        notifyActions.includes(actionVal) ||
        this.hasAttribute('data-loader');

      if (needsLoader) {
        let msg = 'Processing, please wait...';
        if (actionVal.includes('send') || actionVal.includes('broadcast') || actionVal.includes('message')) {
          msg = 'Sending Notifications...';
        } else if (actionVal.includes('receipt')) {
          msg = 'Sending Receipt...';
        }
        showGlobalLoader(msg);
      }
    });
  });
});

/**
 * Global Toast Utility
 * @param {string} message The message to display
 * @param {string} type 'success', 'error', or 'info'
 * @param {number} duration Duration in milliseconds
 */
function showToast(message, type = 'success', duration = 5000) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;

  let iconClass = 'ph-info';
  let title = 'Notification';

  if (type === 'success') {
    iconClass = 'ph-check-circle';
    title = 'Success';
  } else if (type === 'error') {
    iconClass = 'ph-warning-circle';
    title = 'Error';
  }

  toast.innerHTML = `
        <div class="toast-icon">
            <i class="ph ${iconClass}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="this.closest('.toast').remove()">
            <i class="ph ph-x"></i>
        </button>
        <div class="toast-progress">
            <div class="toast-progress-bar"></div>
        </div>
    `;

  container.appendChild(toast);

  // Trigger animation
  setTimeout(() => toast.classList.add('show'), 10);

  // Animate progress bar
  const progressBar = toast.querySelector('.toast-progress-bar');
  progressBar.style.transitionDuration = `${duration}ms`;
  setTimeout(() => progressBar.style.width = '0%', 20);

  // Auto dismiss
  setTimeout(() => {
    toast.classList.remove('show');
    toast.classList.add('hide');
    setTimeout(() => toast.remove(), 400); // wait for hide animation
  }, duration);
}

// ==========================================
// NOTIFICATIONS API & POLLING
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
  const notifDot = document.querySelector('.notif-dot');
  const markAllBtn = document.getElementById('markAllReadBtn');

  // Polling function
  function fetchNotifications() {
    fetch('ajax/notifications_api.php?action=fetch')
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          // Update dot if there are unread notifications
          if (data.count > 0) {
            if (notifDot) {
              notifDot.style.display = 'block';
              notifDot.textContent = data.count > 9 ? '9+' : data.count;
            }
          } else {
            if (notifDot) notifDot.style.display = 'none';
          }
        }
      })
      .catch(err => console.error('Error fetching notifications:', err));
  }

  // Poll every 30 seconds
  setInterval(fetchNotifications, 30000);

  // Initial fetch
  fetchNotifications();

  // Mark single notification as read
  document.body.addEventListener('click', (e) => {
    const notifItem = e.target.closest('.notif-item');
    if (notifItem && notifItem.classList.contains('unread')) {
      const notifId = notifItem.dataset.id;
      if (notifId) {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('id', notifId);

        fetch('ajax/notifications_api.php', {
          method: 'POST',
          body: formData
        })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'success') {
              notifItem.classList.remove('unread');
              fetchNotifications(); // Refresh count
            }
          });
      }
    }
  });

  // Mark all as read
  if (markAllBtn) {
    markAllBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const formData = new FormData();
      formData.append('action', 'mark_all_read');

      fetch('ajax/notifications_api.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            document.querySelectorAll('.notif-item.unread').forEach(item => item.classList.remove('unread'));
            if (notifDot) notifDot.style.display = 'none';
          }
        });
    });
  }
});

/* =====================================================
   IDLE SESSION TIMEOUT
   - 28 min idle → show warning modal with 2-min countdown
   - 30 min idle → redirect to login
   ===================================================== */
(function () {
  const WARN_AT = 28 * 60 * 1000;  // 28 minutes — show warning
  const LOGOUT_AT = 30 * 60 * 1000;  // 30 minutes — force logout
  const THROTTLE = 30 * 1000;       // throttle activity events to once per 30s

  let idleStart = Date.now();
  let warnTimer = null;
  let countdownInterval = null;
  let lastActivity = Date.now();
  let warningShown = false;

  function startTimers() {
    clearTimeout(warnTimer);
    clearInterval(countdownInterval);
    warningShown = false;
    idleStart = Date.now();

    // Close the modal if it's open
    const modal = document.getElementById('idleTimeoutModal');
    if (modal) modal.classList.remove('open');

    warnTimer = setTimeout(showIdleWarning, WARN_AT);
  }

  function showIdleWarning() {
    warningShown = true;
    const modal = document.getElementById('idleTimeoutModal');
    if (modal) modal.classList.add('open');

    // Start the 2-minute countdown
    let remaining = Math.round((LOGOUT_AT - WARN_AT) / 1000); // 120 seconds
    updateCountdownDisplay(remaining);

    countdownInterval = setInterval(function () {
      remaining--;
      if (remaining <= 0) {
        clearInterval(countdownInterval);
        window.location.href = 'login.php?reason=idle';
      } else {
        updateCountdownDisplay(remaining);
      }
    }, 1000);
  }

  function updateCountdownDisplay(seconds) {
    const el = document.getElementById('idleCountdownDisplay');
    if (el) {
      const m = Math.floor(seconds / 60);
      const s = seconds % 60;
      el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
    }
  }

  // Global function called by the modal's "Stay Logged In" button
  window.resetIdleTimer = function () {
    startTimers();
  };

  // Throttled activity handler
  function onActivity() {
    const now = Date.now();
    if (now - lastActivity < THROTTLE && !warningShown) return;
    lastActivity = now;

    // If warning is showing, only the "Stay Logged In" button should reset
    if (warningShown) return;

    startTimers();
  }

  // Listen for user activity
  ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(function (evt) {
    document.addEventListener(evt, onActivity, { passive: true });
  });

  // Initialize
  startTimers();
})();
