@extends('layouts.admin')

@section('content')
<div class="main-content-inner">
    <div class="main-content-wrap">
        <div class="flex items-center flex-wrap justify-between gap20 mb-27">
            <h3>Users</h3>
            <ul class="breadcrumbs flex items-center flex-wrap justify-start gap10">
                <li>
                    <a href="{{ route('admin.index') }}">
                        <div class="text-tiny">Dashboard</div>
                    </a>
                </li>
                <li>
                    <i class="icon-chevron-right"></i>
                </li>
                <li>
                    <div class="text-tiny">All Users</div>
                </li>
            </ul>
        </div>
        
        <div class="wg-box">
            <div class="flex items-center justify-between gap10 flex-wrap">
                <div class="wg-filter flex-grow">
                    <form class="form-search">
                        <fieldset class="name">
                            <input type="text" placeholder="Search here..." class="" name="name" tabindex="2" value="" aria-required="true" required="">
                        </fieldset>
                        <div class="button-submit">
                            <button class="" type="submit"><i class="icon-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="wg-table table-all-user">
                <div class="table-responsive">
                    
                    <!-- Thông báo status -->
                    @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Utype</th> <!-- Thêm cột Utype -->
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Google ID</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td class="pname">
                                    <div class="name">
                                        <a href="#" class="body-title-2">{{ $user->name }}</a>
                                    </div>
                                </td>
                                <td>
                                    {{ $user->utype ?? 'N/A' }} <!-- Hiển thị giá trị Utype -->
                                    <!-- Icon edit để thay đổi Utype -->
                                    <div class="list-icon-function" style="display:inline-block; margin-left: 10px;">
                                        <a href="#" class="edit-utype" data-id="{{ $user->id }}" data-utype="{{ $user->utype }}">
                                            <div class="item edit">
                                                <i class="icon-edit-3"></i>
                                            </div>
                                        </a>
                                    </div>
                                </td>
                                
                                <td>{{ $user->mobile ?? 'N/A' }}</td>
                                <td>{{ $user->email ?? 'N/A' }}</td>
                                <td>{{ $user->google_id ?? 'N/A' }}</td>
                                <td>
                                    <div class="list-icon-function">
                                        <!-- Form xóa người dùng -->
                                        <form action="{{ route('admin.user.delete', $user->id) }}" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <div class="item text-danger delete">
                                                <i class="icon-trash-2"></i>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="divider"></div>

            <!-- Phân trang -->
            <div class="flex items-center justify-between flex-wrap gap10 wgp-pagination">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function() {
        // Xử lý sự kiện khi nhấn vào biểu tượng edit để thay đổi utype
        $('.edit-utype').on('click', function(e) {
            e.preventDefault();
            var userId = $(this).data('id');
            var currentUtype = $(this).data('utype');
            
            // Hiển thị cửa sổ thông báo với các lựa chọn ADM và USR
            swal({
                title: "You want to change utype of this user?",
                text: "Select the new user type",
                icon: "warning",
                buttons: {
                    "USR": {
                        text: "User",
                        value: "USR"
                    },
                    "ADM": {
                        text: "Admin",
                        value: "ADM"
                    },
                },
                dangerMode: true,
            }).then((value) => {
                if (value) {
                    // Gửi form cập nhật utype
                    var form = $('<form>', {
                        action: "{{ route('admin.user.updateutype', ':id') }}".replace(':id', userId),
                        method: 'POST'
                    }).append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    })).append($('<input>', {
                        type: 'hidden',
                        name: 'utype',
                        value: value
                    })).appendTo('body');

                    form.submit();

                }
            });
        });

        // Xóa người dùng
        $('.delete').on('click', function(e) {
            e.preventDefault();
            var form = $(this).closest('form');
            swal({
                title: "Are you sure?",
                text: "You want to delete this user?",
                type: "warning",
                buttons: ["No", "Yes"],
                confirmButtonColor: "#dc3545"
            }).then(function(result) {
                if (result) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
