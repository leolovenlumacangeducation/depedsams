$(document).ready(function() {
    const viewModal = new bootstrap.Modal(document.getElementById('viewIncomingIcsModal'));

    const dataTable = $('#incomingIcsListTable').DataTable({
        "processing": true,
        "ajax": {
            "url": "api/get_incoming_ics_list.php",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "ics_number" },
            { "data": "source_office" },
            { "data": "date_received" },
            { "data": "issued_by_name" },
            { "data": "received_by_name" },
            { 
                "data": "incoming_ics_id",
                "orderable": false,
                "render": function(data) {
                    return `<button type="button" class="btn btn-sm btn-info view-ics-btn" data-id="${data}" title="View Details"><i class="bi bi-eye"></i></button>`;
                }
            }
        ],
        "order": [[2, 'desc']] // Default sort by date received
    });

    // Event listener for the view button
    $('#incomingIcsListTable tbody').on('click', '.view-ics-btn', function() {
        const icsId = $(this).data('id');
        showViewModal(icsId);
    });

    function showViewModal(icsId) {
        const modalBody = $('#view-ics-modal-body');
        modalBody.html('<div class="text-center p-5"><div class="spinner-border" role="status"></div></div>');
        viewModal.show();

        $.ajax({
            url: `api/incoming_ics_view.php?id=${icsId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const { header, items } = response.data;
                    let itemsHtml = '';
                    items.forEach(item => {
                        itemsHtml += `
                            <tr>
                                <td>${item.description}</td>
                                <td>${item.category_name}</td>
                                <td class="text-end">${item.quantity}</td>
                                <td>${item.unit_name}</td>
                                <td class="text-end">${parseFloat(item.unit_cost).toLocaleString('en-US', { style: 'currency', currency: 'PHP' })}</td>
                            </tr>
                        `;
                    });

                    const content = `
                        <p><strong>ICS Number:</strong> ${header.ics_number}</p>
                        <p><strong>Source Office:</strong> ${header.source_office}</p>
                        <p><strong>Date Received:</strong> ${header.date_received}</p>
                        <p><strong>Issued By:</strong> ${header.issued_by_name} (${header.issued_by_position || 'N/A'})</p>
                        <hr>
                        <h6>Items Received:</h6>
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Description</th><th>Category</th><th class="text-end">Qty</th><th>Unit</th><th class="text-end">Unit Cost</th></tr></thead>
                            <tbody>${itemsHtml}</tbody>
                        </table>
                    `;
                    modalBody.html(content);
                } else {
                    modalBody.html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function() {
                modalBody.html('<div class="alert alert-danger">An error occurred while fetching details.</div>');
            }
        });
    }
});