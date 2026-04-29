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
