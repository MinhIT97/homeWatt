<div x-data="{
    showRecoveryCodes: false,
    recoveryCodes: [],
}" class="space-y-6">
    <h3 class="text-lg font-medium text-gray-900">Two-Factor Authentication</h3>

    <p class="text-sm text-gray-600">
        Add additional security to your account using two-factor authentication.
    </p>

    @if (session('status') === 'two-factor-enabled')
        <div class="rounded-md bg-green-50 p-4 mb-4" x-init="recoveryCodes = {{ json_encode(session('recovery_codes', [])) }}; showRecoveryCodes = true;">
            <div class="flex">
                <div class="text-sm font-medium text-green-800">
                    Two-factor authentication has been enabled.
                </div>
            </div>
        </div>

        <div x-show="showRecoveryCodes" class="rounded-md bg-yellow-50 p-4 mb-4">
            <div class="text-sm text-yellow-800">
                <p class="font-medium mb-2">Store these recovery codes in a safe place:</p>
                <div class="grid grid-cols-2 gap-2 bg-white rounded p-3 font-mono text-xs">
                    <template x-for="code in recoveryCodes" :key="code">
                        <code x-text="code" class="block"></code>
                    </template>
                </div>
                <p class="mt-2 text-xs">
                    These codes can be used to recover access to your account if you lose your authenticator app.
                    Each code can only be used once.
                </p>
            </div>
        </div>
    @endif

    @if (session('status') === 'two-factor-disabled')
        <div class="rounded-md bg-yellow-50 p-4 mb-4">
            <div class="flex">
                <div class="text-sm text-yellow-800">
                    Two-factor authentication has been disabled.
                </div>
            </div>
        </div>
    @endif

    @if ($enabled)
        {{-- 2FA Enabled --}}
        <div class="rounded-md bg-green-50 p-4">
            <div class="flex items-center">
                <span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                    Enabled
                </span>
                <span class="ml-3 text-sm text-green-800">
                    Two-factor authentication is active on your account.
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('two-factor.destroy') }}" class="space-y-4">
            @csrf
            @method('DELETE')

            <div>
                <label for="disable_password" class="block text-sm font-medium text-gray-700">
                    Confirm your password to disable two-factor authentication
                </label>
                <input
                    id="disable_password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                Disable Two-Factor Authentication
            </button>
        </form>
    @else
        {{-- Setup 2FA --}}
        <div class="space-y-6">
            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-2">1. Scan the QR code</h4>
                <p class="text-sm text-gray-600 mb-3">
                    Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.).
                </p>
                <div class="bg-white p-4 rounded-md border border-gray-200 inline-block">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="QR Code" class="w-48 h-48">
                </div>
            </div>

            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-2">2. Manual entry key</h4>
                <p class="text-sm text-gray-600 mb-2">
                    If you cannot scan the QR code, enter this key manually:
                </p>
                <code class="inline-block bg-gray-100 px-3 py-2 rounded text-sm font-mono select-all">{{ chunk_split($secret, 4, ' ') }}</code>
            </div>

            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-2">3. Verify setup</h4>
                <p class="text-sm text-gray-600 mb-3">
                    Enter the 6-digit code from your authenticator app to confirm setup.
                </p>

                <form method="POST" action="{{ route('two-factor.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="setup_code" class="sr-only">Verification Code</label>
                        <input
                            id="setup_code"
                            name="code"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            required
                            autocomplete="one-time-code"
                            placeholder="000000"
                            class="block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Enable Two-Factor Authentication
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
