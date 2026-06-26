// pms_v2/app.js - UI and interactive calculations for Project Management System

// Global state for tracker
let currentProjectId = null;

// Modal management
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // If it's an add modal, reset the forms to clear previous edit states
    if (modalId === 'addClientModal') {
        const titleEl = document.getElementById('clientModalTitle');
        if (titleEl) titleEl.innerText = "Add New Client Contact";
        const actionEl = document.getElementById('clientFormAction');
        if (actionEl) actionEl.value = "add_client";
        const idEl = document.getElementById('clientFormId');
        if (idEl) idEl.value = "";
        
        const form = modal.querySelector('form');
        if (form) form.reset();
    } else if (modalId === 'addProjectModal') {
        const titleEl = document.getElementById('projectModalTitle');
        if (titleEl) titleEl.innerText = "Create Project Account";
        const actionEl = document.getElementById('projectFormAction');
        if (actionEl) actionEl.value = "add_project";
        const idEl = document.getElementById('projectFormId');
        if (idEl) idEl.value = "";
        
        const form = modal.querySelector('form');
        if (form) form.reset();
    }
    
    modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Toast helper
function showToast(message) {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.innerText = message;
        toast.classList.add('active');
        setTimeout(() => {
            toast.classList.remove('active');
        }, 3000);
    }
}

// Populate Edit Client form
function openEditClientModal(client) {
    const titleEl = document.getElementById('clientModalTitle');
    if (titleEl) titleEl.innerText = "Update Client Contact Details";
    
    const actionEl = document.getElementById('clientFormAction');
    if (actionEl) actionEl.value = "edit_client";
    
    const idEl = document.getElementById('clientFormId');
    if (idEl) idEl.value = client.id;
    
    document.getElementById('c_name').value = client.name || '';
    document.getElementById('c_email').value = client.email || '';
    document.getElementById('c_phone').value = client.phone || '';
    document.getElementById('c_company').value = client.company || '';
    document.getElementById('c_address').value = client.address || '';
    
    const modal = document.getElementById('addClientModal');
    if (modal) modal.classList.add('active');
}

// Populate Edit Project form
function openEditProjectModal(project) {
    const titleEl = document.getElementById('projectModalTitle');
    if (titleEl) titleEl.innerText = "Update Project Workspace Details";
    
    const actionEl = document.getElementById('projectFormAction');
    if (actionEl) actionEl.value = "edit_project";
    
    const idEl = document.getElementById('projectFormId');
    if (idEl) idEl.value = project.id;
    
    document.getElementById('p_client').value = project.client_id || '';
    document.getElementById('p_title').value = project.title || '';
    document.getElementById('p_description').value = project.description || '';
    document.getElementById('p_status').value = project.status || 'Planning';
    document.getElementById('p_budget').value = project.total_budget || '0.00';
    
    const modal = document.getElementById('addProjectModal');
    if (modal) modal.classList.add('active');
}

// AI Proposal Generator Action
function triggerGenerateProposal(projectId) {
    openModal('proposalModal');
    
    const loadingEl = document.getElementById('proposalLoading');
    const containerEl = document.getElementById('proposalContainer');
    
    loadingEl.style.display = 'block';
    containerEl.style.display = 'none';
    containerEl.innerHTML = '';
    
    // Fetch request with action ajax_generate_proposal
    const params = new URLSearchParams();
    params.append('action', 'ajax_generate_proposal');
    params.append('project_id', projectId);
    
    fetch('./dashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(response => response.json())
    .then(data => {
        loadingEl.style.display = 'none';
        containerEl.style.display = 'block';
        
        if (data.success) {
            containerEl.innerHTML = data.proposal_content;
            showToast(data.message || "Proposal generated successfully!");
        } else {
            containerEl.innerHTML = `<div class="alert-box error"><i data-lucide="alert-triangle"></i><span>Failed: ${data.message || 'Unknown API Error'}</span></div>`;
        }
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    })
    .catch(err => {
        loadingEl.style.display = 'none';
        containerEl.style.display = 'block';
        containerEl.innerHTML = `<div class="alert-box error"><span>Connection Error: ${err.message}</span></div>`;
    });
}

// Estimate Builder Functions
function openEstimateBuilder(projectId, projectTitle) {
    document.getElementById('estimateProjectTitle').innerText = "Estimate Worksheet: " + projectTitle;
    document.getElementById('estimateProjectFormId').value = projectId;
    
    const itemsBody = document.getElementById('estimateItemsBody');
    itemsBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Loading existing estimates...</td></tr>';
    
    openModal('estimateModal');
    
    // Fetch current estimate details
    fetch(`./dashboard.php?api_action=get_estimate&project_id=${projectId}`)
    .then(response => response.json())
    .then(data => {
        itemsBody.innerHTML = '';
        if (data.success && data.exists && data.items && data.items.length > 0) {
            data.items.forEach(item => {
                addEstimateRow(item.description, item.qty, item.rate);
            });
        } else {
            // Seed with one empty row
            addEstimateRow('', 1, 0.00);
        }
        recalculateEstimateTotal();
    })
    .catch(err => {
        itemsBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: hsl(var(--destructive));">Error loading estimate.</td></tr>';
    });
}

function addEstimateRow(desc = '', qty = 1, rate = 0.00) {
    const tbody = document.getElementById('estimateItemsBody');
    const tr = document.createElement('tr');
    
    tr.innerHTML = `
        <td>
            <input type="text" name="item_desc[]" class="form-control" value="${escapeHtml(desc)}" placeholder="Item description / Service description" required>
        </td>
        <td>
            <input type="number" name="item_qty[]" class="form-control" style="text-align: center;" min="0.01" step="any" value="${qty}" oninput="recalculateEstimateTotal()">
        </td>
        <td>
            <input type="number" name="item_rate[]" class="form-control" style="text-align: right;" min="0" step="any" value="${rate}" oninput="recalculateEstimateTotal()">
        </td>
        <td style="text-align: right; vertical-align: middle; font-weight: bold;" class="item-row-total">
            $0.00
        </td>
        <td style="text-align: center; vertical-align: middle;">
            <button type="button" class="btn btn-destructive" style="padding: 0.25rem 0.5rem;" onclick="removeEstimateRow(this)">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(tr);
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function removeEstimateRow(button) {
    const row = button.closest('tr');
    row.parentNode.removeChild(row);
    recalculateEstimateTotal();
}

function recalculateEstimateTotal() {
    const rows = document.querySelectorAll('#estimateItemsBody tr');
    let total = 0.00;
    
    rows.forEach(row => {
        const qtyEl = row.querySelector('input[name="item_qty[]"]');
        const rateEl = row.querySelector('input[name="item_rate[]"]');
        const totalEl = row.querySelector('.item-row-total');
        
        if (qtyEl && rateEl && totalEl) {
            const qty = parseFloat(qtyEl.value) || 0;
            const rate = parseFloat(rateEl.value) || 0;
            const rowTotal = qty * rate;
            totalEl.innerText = '$' + rowTotal.toFixed(2);
            total += rowTotal;
        }
    });
    
    document.getElementById('estimateTotalLabel').innerText = '$' + total.toFixed(2);
}

// Milestone Checklist Workspace Functions
function openMilestonesWorkspace(projectId, projectTitle) {
    document.getElementById('milestoneProjectTitle').innerText = "Milestones: " + projectTitle;
    document.getElementById('milestoneProjectFormId').value = projectId;
    
    const tableBody = document.getElementById('milestonesTableBody');
    tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Loading milestone phases...</td></tr>';
    
    openModal('milestonesModal');
    
    // Fetch milestones
    fetch(`./dashboard.php?api_action=get_milestones&project_id=${projectId}`)
    .then(response => response.json())
    .then(data => {
        tableBody.innerHTML = '';
        if (data.success && data.milestones && data.milestones.length > 0) {
            data.milestones.forEach(m => {
                const tr = document.createElement('tr');
                
                // Status & billing logic column html
                let statusHtml = '';
                if (m.status === 'Pending') {
                    statusHtml = `
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span class="badge badge-planning">Pending</span>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="complete_milestone">
                                <input type="hidden" name="milestone_id" value="${m.id}">
                                <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                    <i data-lucide="check" style="width:12px; height:12px;"></i> Complete
                                </button>
                            </form>
                        </div>
                    `;
                } else {
                    // Completed status
                    if (!m.invoice_id) {
                        // Completed but somehow no invoice linked (safety fallback)
                        statusHtml = `
                            <span class="badge badge-completed">Completed</span>
                        `;
                    } else {
                        const invStatus = m.invoice_status;
                        const invClass = invStatus === 'Paid' ? 'badge-completed' : 'badge-unpaid';
                        const isVerified = parseInt(m.invoice_verified) === 1;
                        const verifiedBadge = isVerified 
                            ? `<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981;">Verified</span>`
                            : `<span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b;">Awaiting Verification</span>`;
                        
                        statusHtml = `
                            <div style="display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.8rem;">
                                <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                    <span class="badge badge-completed">Completed</span>
                                    <span class="badge ${invClass}">${invStatus}</span>
                                    ${verifiedBadge}
                                </div>
                                <div style="color: var(--muted-foreground); margin-top:0.25rem;">
                                    Invoice: <strong>${m.invoice_number}</strong>
                                </div>
                                <div style="display: flex; gap: 0.25rem; margin-top: 0.25rem;">
                                    ${isVerified ? `
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="send_invoice_email">
                                            <input type="hidden" name="invoice_id" value="${m.invoice_id}">
                                            <button type="submit" class="btn btn-outline" style="padding: 0.15rem 0.4rem; font-size: 0.75rem;" title="Email Invoice Attachment">
                                                <i data-lucide="mail" style="width:12px; height:12px;"></i> Email Invoice
                                            </button>
                                        </form>
                                    ` : `
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="verify_invoice">
                                            <input type="hidden" name="invoice_id" value="${m.invoice_id}">
                                            <button type="submit" class="btn btn-secondary" style="padding: 0.15rem 0.4rem; font-size: 0.75rem;" title="Verify Invoice">
                                                <i data-lucide="shield-check" style="width:12px; height:12px;"></i> Verify Invoice
                                            </button>
                                        </form>
                                    `}
                                    ${(invStatus === 'Unpaid' && isVerified) ? `
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_invoice_paid">
                                            <input type="hidden" name="invoice_id" value="${m.invoice_id}">
                                            <button type="submit" class="btn btn-secondary" style="padding: 0.15rem 0.4rem; font-size: 0.75rem;" title="Mark invoice as paid">
                                                <i data-lucide="dollar-sign" style="width:12px; height:12px;"></i> Mark Paid
                                            </button>
                                        </form>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    }
                }
                
                tr.innerHTML = `
                    <td>
                        <strong>${escapeHtml(m.title)}</strong>
                        ${m.description ? `<div style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.15rem;">${escapeHtml(m.description)}</div>` : ''}
                    </td>
                    <td style="font-weight: bold; color: hsl(var(--primary));">$${parseFloat(m.amount).toFixed(2)}</td>
                    <td>${m.due_date}</td>
                    <td>${statusHtml}</td>
                    <td style="text-align: center;">
                        <form method="POST" action="" onsubmit="return confirm('Delete this milestone?');" style="display: inline;">
                            <input type="hidden" name="action" value="delete_milestone">
                            <input type="hidden" name="milestone_id" value="${m.id}">
                            <button type="submit" class="btn btn-destructive" style="padding: 0.25rem 0.5rem;"><i data-lucide="trash-2" style="width: 14px; height: 14px;"></i></button>
                        </form>
                    </td>
                `;
                
                tableBody.appendChild(tr);
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--muted-foreground);">No milestones configured for this project yet.</td></tr>';
        }
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    })
    .catch(err => {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: hsl(var(--destructive));">Error loading milestone phase details.</td></tr>';
    });
}

// Sent Email Viewer Modal
function viewEmailBody(log) {
    document.getElementById('emailViewSubject').innerText = log.subject || 'Sent Email Details';
    document.getElementById('emailViewRecipient').innerText = log.recipient || '';
    document.getElementById('emailViewDate').innerText = log.sent_at || '';
    
    // Inject email body safely into viewer container
    document.getElementById('emailViewBody').innerHTML = log.body || '';
    
    openModal('emailViewModal');
}

// Escape helper for templates
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Open and populate Edit Invoice modal
function openEditInvoiceModal(invoice) {
    document.getElementById('editInvoiceId').value = invoice.id;
    document.getElementById('editInvoiceNum').value = invoice.invoice_number || '';
    document.getElementById('editInvoiceAmount').value = invoice.amount || '0.00';
    document.getElementById('editInvoiceStatus').value = invoice.status || 'Unpaid';
    document.getElementById('editInvoiceVerified').value = invoice.is_verified || '0';
    
    openModal('editInvoiceModal');
}
