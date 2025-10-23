$(document).ready(function() {
    // Initialize DataTable
    const table = $('#receivedListTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'api/receive_list_api.php',
            type: 'POST',
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                showToast('Error loading data. Please try again.', 'Error', 'error');
            }
        },
        columns: [
            { data: 'dr_number' },
            { data: 'po_number' },
            { data: 'supplier_name' },
            { data: 'dr_number' },
            { 
                data: 'date_received',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : '';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <button type="button" class="btn btn-sm btn-info view-delivery" data-id="${row.delivery_id}">
                            <i class="bi bi-eye"></i> View
                        </button>`;
                }
            }
        ],
        order: [[4, 'desc']], // Sort by date received by default
        responsive: true,
        pageLength: 10
    });

    // View Delivery click handler
    $('#receivedListTable').on('click', '.view-delivery', function() {
        const deliveryId = $(this).data('id');
        
        // Show loading state
        $('#deliveryModal').modal('show');
        $('#delivery-items').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');
        
        // Load delivery details via API
        $.get(`api/delivery_view.php?id=${deliveryId}`)
            .done(function(response) {
                if (response.success) {
                    populateDeliveryModal(response.data);
                } else {
                    showToast(response.message || 'Error loading IAR details', 'Error', 'error');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                showToast('Error loading delivery details. Please try again.', 'Error', 'error');
            });
    });

    function populateDeliveryModal(data) {
        // Update modal header details
        $('#delivery-number').text(data.header.delivery_receipt_no || data.header.dr_number);
        $('#po-number').text(data.header.po_number);
        $('#supplier-name').text(data.header.supplier_name);
        $('#date-received').text(new Date(data.header.date_received).toLocaleDateString());

        // Clear and populate items table
        const tbody = $('#delivery-items').empty();
        data.items.forEach(item => {
            tbody.append(`
                <tr>
                    <td>${item.property_number || item.stock_number || ''}</td>
                    <td>
                        <small class="text-muted">[${item.inventory_type}]</small><br>
                        ${item.description}
                    </td>
                    <td class="text-center">${item.unit}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">${Number(item.unit_cost).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'PHP'
                    })}</td>
                    <td class="text-end">${Number(item.total).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'PHP'
                    })}</td>
                </tr>
            `);
        });

        // Update total amount
        const total = data.items.reduce((sum, item) => sum + Number(item.total), 0);
        $('#total-amount').text(total.toLocaleString('en-US', {
            style: 'currency',
            currency: 'PHP'
        }));
    }
});