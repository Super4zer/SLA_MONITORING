import { SLA_API } from './api.js';

// DOM Elements
const clockEl = document.getElementById('live-clock');
const listWaiting = document.getElementById('list-waiting');
const listOverdue = document.getElementById('list-overdue');
const listCompleted = document.getElementById('list-completed');

const countWaiting = document.getElementById('count-waiting');
const countOverdue = document.getElementById('count-overdue');
const countCompleted = document.getElementById('count-completed');

// Update Clock
function updateClock() {
    const now = new Date();
    clockEl.textContent = now.toLocaleTimeString('id-ID');
}
setInterval(updateClock, 1000);
updateClock();

// Formatter for elapsed time
function formatElapsedTime(dateString) {
    const received = new Date(dateString);
    const now = new Date();
    const diffSeconds = Math.floor((now - received) / 1000);
    
    const minutes = Math.floor(diffSeconds / 60);
    const seconds = diffSeconds % 60;
    
    return `${minutes}m ${seconds}s`;
}

// XSS Safe HTML Element Creator
function createTicketElement(ticket, type) {
    const div = document.createElement('div');
    div.className = 'ticket';
    
    const header = document.createElement('div');
    header.className = 'ticket-header';
    
    const groupId = document.createElement('span');
    groupId.className = 'group-id';
    groupId.textContent = `Group: ${ticket.group_id}`;
    
    const time = document.createElement('span');
    time.className = 'time';
    time.textContent = new Date(ticket.time_received).toLocaleTimeString('id-ID');
    
    header.appendChild(groupId);
    header.appendChild(time);
    
    const clientPhone = document.createElement('div');
    clientPhone.className = 'client-phone';
    clientPhone.textContent = `Client: +${ticket.client_phone}`;
    
    const message = document.createElement('div');
    message.className = 'message';
    message.textContent = ticket.message_content;
    
    const footer = document.createElement('div');
    footer.className = 'ticket-footer';
    
    const timer = document.createElement('div');
    timer.className = `timer ${type === 'waiting' ? 'warning' : type === 'overdue' ? 'danger' : 'success'}`;
    
    if (type === 'completed') {
        timer.textContent = `SLA: ${ticket.sla_seconds}s (by ${ticket.responded_by || 'Explanation'})`;
    } else {
        timer.textContent = `Elapsed: ${formatElapsedTime(ticket.time_received)}`;
    }
    
    footer.appendChild(timer);
    
    // Add action buttons if not completed
    if (type !== 'completed') {
        const btnGroup = document.createElement('div');
        btnGroup.className = 'btn-group';
        
        const btnResolve = document.createElement('button');
        btnResolve.className = 'btn-resolve';
        btnResolve.textContent = 'Resolve';
        btnResolve.onclick = () => handleResolve(ticket.id_monitoring);
        
        const btnEscalate = document.createElement('button');
        btnEscalate.className = 'btn-escalate';
        btnEscalate.textContent = 'Escalate';
        btnEscalate.onclick = () => handleEscalate(ticket.id_monitoring, ticket.client_phone, ticket.message_content);
        
        btnGroup.appendChild(btnResolve);
        btnGroup.appendChild(btnEscalate);
        footer.appendChild(btnGroup);
    }
    
    div.appendChild(header);
    div.appendChild(clientPhone);
    div.appendChild(message);
    div.appendChild(footer);
    
    return div;
}

async function handleResolve(id) {
    const res = await SLA_API.resolve(id);
    if (res && res.status === 'success') {
        refreshDashboard();
    } else {
        alert('Gagal resolve komplain');
    }
}

async function handleEscalate(id, phone, msg) {
    const res = await SLA_API.escalate(id, `Client ${phone}`, msg);
    if (res && res.status === 'success') {
        alert(`Berhasil dieskalasi dengan Log ID: ${res.log_klikdsi_id}`);
        refreshDashboard();
    } else {
        alert('Gagal eskalasi komplain');
    }
}

function renderList(container, data, type) {
    container.replaceChildren(); // Safe way to clear content
    
    if (!data || data.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'empty-state';
        empty.textContent = 'Kosong';
        container.appendChild(empty);
        return;
    }
    
    data.forEach(ticket => {
        container.appendChild(createTicketElement(ticket, type));
    });
}

// Fetch and Render
async function refreshDashboard() {
    const [waitingRes, overdueRes, completedRes] = await Promise.all([
        SLA_API.getWaiting(),
        SLA_API.getOverdue(),
        SLA_API.getCompleted()
    ]);
    
    if (waitingRes?.data) {
        countWaiting.textContent = waitingRes.data.length;
        renderList(listWaiting, waitingRes.data, 'waiting');
    }
    
    if (overdueRes?.data) {
        countOverdue.textContent = overdueRes.data.length;
        renderList(listOverdue, overdueRes.data, 'overdue');
    }
    
    if (completedRes?.data) {
        countCompleted.textContent = completedRes.data.length;
        renderList(listCompleted, completedRes.data, 'completed');
    }
}

// Initialization & Polling
refreshDashboard();
setInterval(refreshDashboard, 5000);
