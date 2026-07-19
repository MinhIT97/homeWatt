@extends('layouts.app')

@section('title', 'Lời mời tham gia nhà')

@section('content')
<div class="max-w-lg mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 text-center">
        <div class="mb-6">
            <span class="text-5xl">🏠</span>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            @if ($valid)
                Bạn được mời tham gia {{ $invitation->home->name }}
            @endif
        </h1>

        @if ($valid)
            <p class="text-gray-600 mb-2">
                <strong>{{ $invitation->inviter->name }}</strong> đã mời bạn tham gia nhà
                <strong>{{ $invitation->home->name }}</strong>
            </p>
            <p class="text-gray-500 mb-2">
                Với vai trò:
                <span class="font-semibold text-blue-600">
                    @switch($invitation->role)
                        @case('manager')
                            Quản lý
                            @break
                        @case('member')
                            Thành viên
                            @break
                        @case('viewer')
                            Người xem
                            @break
                        @default
                            {{ $invitation->role }}
                    @endswitch
                </span>
            </p>
            <p class="text-xs text-gray-400 mb-6">
                Lời mời hết hạn vào {{ $invitation->expires_at->format('d/m/Y H:i') }}
            </p>

            @auth
                <form action="{{ route('invite.accept', $invitation->token) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Tham gia ngay
                    </button>
                </form>
            @else
                <div class="bg-yellow-50 rounded-md p-4 mb-4">
                    <p class="text-sm text-yellow-800">
                        Bạn cần đăng nhập để tham gia.
                    </p>
                </div>
                <a href="{{ route('login') }}"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                    Đăng nhập để tham gia
                </a>
            @endauth
        @else
            <div class="bg-red-50 rounded-md p-4 mb-6">
                <p class="text-lg font-semibold text-red-800 mb-1">Lời mời không còn hiệu lực</p>
                <p class="text-sm text-red-600">
                    @if ($expired)
                        Lời mời này đã hết hạn vào {{ $invitation->expires_at->format('d/m/Y H:i') }}.
                    @elseif ($fullyUsed)
                        Lời mời này đã đạt số lượt sử dụng tối đa ({{ $invitation->use_count }}/{{ $invitation->max_uses }}).
                    @else
                        Lời mời này không hợp lệ.
                    @endif
                </p>
            </div>
            <p class="text-sm text-gray-500">
                Vui lòng liên hệ <strong>{{ $invitation->inviter->name }}</strong> để nhận lời mời mới.
            </p>
        @endif
    </div>
</div>
@endsection
