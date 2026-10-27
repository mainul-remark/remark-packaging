<script>
    $(document).ready(function () {

        const canViewUsers = @json(auth()->user()->can('users.view'));
        const canUpdateUsers = @json(auth()->user()->can('users.update'));
        const canDeleteUsers = @json(auth()->user()->can('users.delete'));

        let table = $("#userDatatable").DataTable({
            dom: 'QBflrtip',
            processing: true,
            deferRender: true,
            serverSide: false,

            // Controller already returns latest users first.
            order: [],

            buttons: [
                {
                    extend: 'excel',
                    title: 'User Report List',
                    exportOptions: {
                        columns: ':not(:first-child):not(:last-child)'
                    }
                },
                {
                    extend: 'csv',
                    title: 'User Report List',
                    exportOptions: {
                        columns: ':not(:first-child):not(:last-child)'
                    }
                }
            ],

            ajax: {
                url: "{{ route('users.index') }}",
                type: 'GET',
                cache: false,
                dataSrc: function (json) {
                    return json.data || [];
                }
            },

            columns: [
                {
                    data: 'profile_photo_url',
                    title: 'Image',
                    orderable: false,
                    render: function (data, type) {
                        if (type !== 'display') return data || '';

                        return $('<img>', {
                            src: data,
                            alt: 'User Image',
                            class: 'rounded-circle',
                            css: {
                                width: '50px',
                                height: '50px',
                                objectFit: 'cover'
                            }
                        }).prop('outerHTML');
                    }
                },
                { data: 'employee_id', title: 'EmpId', defaultContent: '-' },
                { data: 'name', title: 'Name', defaultContent: '' },
                { data: 'email', title: 'Email', defaultContent: '' },
                {
                    data: 'role_names',
                    title: 'Role',
                    defaultContent: '',
                    render: function (data, type) {
                        if (type !== 'display') return data || '';

                        if (!data || !data.trim()) {
                            return '<span class="badge badge-outline-warning px-2">User</span>';
                        }

                        return data.split(', ')
                            .map(function (role) {
                                return '<span class="badge badge-outline-secondary px-2">'
                                    + $('<div>').text(role.trim()).html()
                                    + '</span>';
                            })
                            .join(' ');
                    }
                },

                {
                    data: 'created_at',
                    title: 'Created At',
                    defaultContent: ''
                },

                {
                    data: 'is_active',
                    title: 'Status',
                    render: function (data, type) {
                        if (type !== 'display') return data;

                        return Number(data) === 1
                            ? '<span class="badge badge-outline-success px-2">Active</span>'
                            : '<span class="badge badge-outline-danger px-2">Inactive</span>';
                    }
                },

                {
                    data: null,
                    title: 'Action',
                    className: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        if (type !== 'display') return '';

                        const id = encodeURIComponent(row.id);
                        const viewUrl = '/admin/users/' + id;
                        const editUrl = '/admin/users/' + id + '/edit';

                        let html = '';

                        if (canViewUsers) {
                            html += '<a href="' + viewUrl
                                + '" class="btn btn-outline-info btn-sm mr-2" title="View">'
                                + '<i class="fas fa-eye"></i></a>';
                        }

                        if (canUpdateUsers) {
                            html += '<a href="' + editUrl
                                + '" class="btn btn-outline-secondary btn-sm mr-2" title="Edit">'
                                + '<i class="mdi mdi-account-edit"></i></a>';
                        }

                        if (canDeleteUsers) {
                            const deleteUrl = '/admin/users/' + id;

                            html += '<button type="button"'
                                + ' class="btn btn-sm btn-outline-danger deleteUserBtn"'
                                + ' data-id="' + id + '"'
                                + ' data-url="' + deleteUrl + '" title="Delete">'
                                + '<i class="fas fa-trash"></i></button>';
                        }

                        return html;
                    }
                }
            ]
        });

    });
</script>


{{--user delete script--}}

<script>
    $(document).on('click', '.deleteUserBtn', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const url = $btn.data('url');
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }

        // Prevent double click
        $btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',

            data: {
                _method: 'DELETE',
                _token: csrfToken
            },

            success: function (response) {

                if (response.status === true) {

                    // Get DataTable
                    const table = $('#userDatatable').DataTable();

                    // Find current row
                    const $tr = $btn.closest('tr');
                    const row = table.row($tr);

                    // Remove row immediately
                    if (row.length) {
                        row.remove().draw(false);
                    }

                    toastr.success(
                        response.message || 'User deleted successfully!'
                    );
                }

                $btn.prop('disabled', false);
            },

            error: function (xhr) {

                $btn.prop('disabled', false);

                if (xhr.status === 403) {

                    toastr.error(
                        xhr.responseJSON?.message ||
                        'You do not have permission to delete this user.'
                    );

                } else if (xhr.status === 422) {

                    toastr.warning(
                        xhr.responseJSON?.message ||
                        'Validation error occurred.'
                    );

                } else if (xhr.status === 404) {

                    toastr.error('User not found.');

                } else if (xhr.status === 401) {

                    toastr.warning(
                        'Session expired. Please login again.'
                    );

                } else {

                    toastr.error(
                        xhr.responseJSON?.message ||
                        'Something went wrong. Please try again.'
                    );
                }
            }
        });
    });
</script>

