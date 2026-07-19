@extends('layouts.app')

@section('title', 'Tạo lời mời tham gia - ' . $home->name)

@section('content')
<div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('homes.members', $home) }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Quay lại danh sách thành viên</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Tạo lời mời tham gia {{ $home->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Tạo liên kết mời người khác tham gia nhà của bạn.</p>
    </div>

    @if (session('invite_link'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <h3 class="text-sm font-medium text-green-800 mb-2">Lien ket loi moi da duoc tao:</h3>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ session('invite_link') }}"
                    class="block w-full rounded-md border-gray-300 bg-white text-sm text-gray-700 shadow-sm"
                    id="invite-link-input">
                <button onclick="copyInviteLink()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Copy
                </button>
            </div>
            <p class="mt-2 text-xs text-green-600" id="copy-status"></p>
        </div>
    @endif

    @if (session('success') && !session('invite_link'))
        <div class="mb-4 rounded-md bg-green-50 p-4">
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 p-4">
            <p class="text-sm text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Tạo lời mời mới</h2>

        <form action="{{ route('homes.invite.create', $home) }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Vai trò</label>
                <select name="role" id="role" required
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="manager">Quản lý - Có thể chỉnh sửa, mời thành viên, tạo danh mục</option>
                    <option value="member" selected>Thành viên - Có thể thêm chi tiêu, thiết bị</option>
                    <option value="viewer">Người xem - Chỉ xem, không chỉnh sửa</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="max_uses" class="block text-sm font-medium text-gray-700 mb-1">Số lượt sử dụng tối đa</label>
                <input type="number" name="max_uses" id="max_uses" value="1" min="1" max="100"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <p class="mt-1 text-xs text-gray-400">Mặc định 1 lượt. Đặt cao hơn nếu muốn dùng chung 1 link.</p>
            </div>

            <div class="mb-4">
                <label for="expires_in_days" class="block text-sm font-medium text-gray-700 mb-1">Hết hạn sau (ngày)</label>
                <input type="number" name="expires_in_days" id="expires_in_days" value="7" min="1" max="90"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>

            <button type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Tạo liên kết mời
            </button>
        </form>
    </div>

    {{-- Active Invitations --}}
    @if ($invitations->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Lời mời đang hoạt động</h2>
            <div class="divide-y divide-gray-200">
                @foreach ($invitations as $inv)
                    @php $invValid = $inv->isValid(); @endphp
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium {{ $invValid ? 'text-gray-900' : 'text-gray-400 line-through' }}">
                                Vai trò: {{ $inv->role === 'manager' ? 'Quản lý' : ($inv->role === 'member' ? 'Thành viên' : 'Người xem') }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Đã dùng: {{ $inv->use_count }}/{{ $inv->max_uses }} |
                                Hết hạn: {{ $inv->expires_at->format('d/m/Y H:i') }} |
                                @if ($invValid)
                                    <span class="text-green-600">Còn hiệu lực</span>
                                @elseif ($inv->isExpired())
                                    <span class="text-red-600">Hết hạn</span>
                                @else
                                    <span class="text-red-600">Đã dùng hết</span>
                                @endif
                            </p>
                        </div>
                        <form action="{{ route('homes.invite.revoke', $inv) }}" method="POST" onsubmit="return confirm('Thu hồi lời mời này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Thu hồi</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function copyInviteLink() {
    const input = document.getElementById('invite-link-input');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        document.getElementById('copy-status').textContent = 'Da sao chep!';
        setTimeout(() => {
            document.getElementById('copy-status').textContent = '';
        }, 2000);
    });
}
</script>
@endpush
