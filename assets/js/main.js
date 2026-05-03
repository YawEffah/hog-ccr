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
