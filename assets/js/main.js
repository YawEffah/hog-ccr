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
    
    newBtn.onclick = function() {
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
        form.addEventListener('submit', function(e) {
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

            const actionEl  = this.querySelector('[name="action"]');
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
