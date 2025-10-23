document.addEventListener('DOMContentLoaded', function () {
    let iirupTable;
    let iirupViewModal;

    // Initialize DataTables
    if ($.fn.DataTable.isDataTable('#iirupListTable')) {
        $('#iirupListTable').DataTable().destroy();
    }
    iirupTable = $('#iirupListTable').DataTable({
        processing: true,
        serverSide: false, // Using client-side processing
        ajax: {
            url: 'api/iirup_api.php',
            type: 'GET',
            dataSrc: 'data'
        },
        columns: [
            { data: 'iirup_number' },
            { 
                data: 'as_of_date',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : 'N/A';
                }
            },
            { data: 'disposal_method' },
            { 
                data: 'status',
                render: function(data) {
                    let badgeClass = 'bg-secondary';
                    if (data === 'Disposed') badgeClass = 'bg-danger';
                    if (data === 'Approved') badgeClass = 'bg-success';
                    if (data === 'For Approval') badgeClass = 'bg-warning text-dark';
                    if (data === 'Draft') badgeClass = 'bg-info text-dark';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            { data: 'created_by' },
            { 
                data: 'date_created',
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : 'N/A';
                }
            },
            {
                data: 'iirup_id',
                render: function (data, type, row) {
                    return `<button class="btn btn-sm btn-info view-iirup-btn" data-id="${data}" title="View IIRUP"><i class="bi bi-eye"></i></button>`;
                },
                orderable: false
            }
        ],
        order: [[5, 'desc']] // Order by date created descending
    });

    // Event listener for view buttons
    $('#iirupListTable tbody').on('click', '.view-iirup-btn', function () {
        const iirupId = $(this).data('id');
        showIirupModal(iirupId); // Using the shared modal function
    });
});