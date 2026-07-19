@extends('layouts.app')

@section('title', 'Nhập sao kê ngân hàng')

@push('head')
<style>
    .drop-zone {
        border: 2px dashed #d1d5db;
        border-radius: 0.75rem;
        padding: 3rem 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f9fafb;
    }
    .drop-zone:hover, .drop-zone.dragover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .drop-zone.has-file {
        border-color: #10b981;
        background: #f0fdf4;
    }
    .drop-zone-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #9ca3af;
    }
    .drop-zone.has-file .drop-zone-icon {
        color: #10b981;
    }
    .preview-table th {
        position: sticky;
        top: 0;
        background: #f9fafb;
        z-index: 10;
    }
    .preview-container {
        max-height: 500px;
        overflow-y: auto;
    }
    .parser-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    .type-income { background: #d1fae5; color: #065f46; }
    .type-expense { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Nhập sao kê ngân hàng</h1>
        <p class="mt-1 text-sm text-gray-500">
            Tải lên file CSV sao kê từ ngân hàng (VCB, Techcombank) hoặc ví điện tử (Momo) để nhập hàng loạt giao dịch.
        </p>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 p-4">
            <p class="text-sm text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <form id="import-form" action="{{ route('expenses.import.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Step 1: Select Home --}}
        <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <label for="home_id" class="block text-sm font-medium text-gray-700 mb-2">Chọn nhà</label>
            <select name="home_id" id="home_id" required
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">-- Chọn nhà --</option>
                @foreach ($homes as $home)
                    <option value="{{ $home->id }}">{{ $home->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Step 2: Upload --}}
        <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Tải file CSV</h2>

            <div id="drop-zone" class="drop-zone">
                <div class="drop-zone-icon">📄</div>
                <p class="text-sm text-gray-600 mb-1">Kéo thả file CSV vào đây hoặc bấm để chọn file</p>
                <p class="text-xs text-gray-400">Hỗ trợ: VCB, Techcombank, Momo (định dạng .csv)</p>
                <input type="file" name="file" id="file-input" accept=".csv,.txt" class="hidden" required>
                <p id="file-name" class="mt-2 text-sm font-medium text-green-700 hidden"></p>
            </div>
        </div>

        {{-- Step 3: Preview --}}
        <div id="preview-section" class="hidden mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Xem trước dữ liệu</h2>
                <span id="parser-label" class="parser-badge bg-blue-100 text-blue-800"></span>
            </div>

            <div id="preview-errors" class="hidden mb-4 rounded-md bg-yellow-50 p-4"></div>

            <div class="preview-container">
                <table class="preview-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Số tiền</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mô tả</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ví</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Danh mục</th>
                        </tr>
                    </thead>
                    <tbody id="preview-tbody" class="divide-y divide-gray-200"></tbody>
                </table>
            </div>
        </div>

        {{-- Submit --}}
        <div id="submit-section" class="hidden text-center">
            <input type="hidden" name="file_path" id="file-path-input" value="">
            <button type="submit"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Xác nhận nhập
            </button>
            <p class="mt-2 text-xs text-gray-400" id="transaction-count"></p>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const fileName = document.getElementById('file-name');
    const previewSection = document.getElementById('preview-section');
    const submitSection = document.getElementById('submit-section');
    const previewTbody = document.getElementById('preview-tbody');
    const parserLabel = document.getElementById('parser-label');
    const previewErrors = document.getElementById('preview-errors');
    const transactionCount = document.getElementById('transaction-count');
    const homeSelect = document.getElementById('home_id');
    const filePathInput = document.getElementById('file-path-input');
    const importForm = document.getElementById('import-form');

    let wallets = [];
    let categories = [];

    // Drag and drop
    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFile();
        }
    });

    fileInput.addEventListener('change', handleFile);

    function handleFile() {
        const file = fileInput.files[0];
        if (file) {
            fileName.textContent = 'Da chon: ' + file.name;
            fileName.classList.remove('hidden');
            dropZone.classList.add('has-file');
            uploadPreview();
        }
    }

    function uploadPreview() {
        const file = fileInput.files[0];
        const homeId = homeSelect.value;

        if (!file || !homeId) {
            if (!homeId) alert('Vui long chon nha truoc.');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('home_id', homeId);

        previewTbody.innerHTML = '<tr><td colspan="7" class="px-3 py-4 text-center text-sm text-gray-500">Dang xu ly file...</td></tr>';
        previewSection.classList.remove('hidden');
        submitSection.classList.add('hidden');

        fetch('{{ route('expenses.import.preview') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (!data.ok) {
                previewTbody.innerHTML = '<tr><td colspan="7" class="px-3 py-4 text-center text-sm text-red-600">Loi: ' + (data.message || 'Khong the xu ly file') + '</td></tr>';
                return;
            }

            wallets = data.wallets || [];
            categories = data.categories || [];

            parserLabel.textContent = 'Ngan hang: ' + (data.parser || 'Khong xac dinh');

            // Show errors
            if (data.errors && data.errors.length > 0) {
                previewErrors.classList.remove('hidden');
                previewErrors.innerHTML = data.errors.map(e => '<p class="text-sm text-yellow-800">Dong ' + e.row + ': ' + e.message + '</p>').join('');
            } else {
                previewErrors.classList.add('hidden');
            }

            // Render transactions
            renderTransactions(data.transactions || []);

            if (data.transactions && data.transactions.length > 0) {
                submitSection.classList.remove('hidden');
                transactionCount.textContent = data.transactions.length + ' giao dich se duoc nhap.';
                filePathInput.value = file.name;
            }
        })
        .catch(error => {
            previewTbody.innerHTML = '<tr><td colspan="7" class="px-3 py-4 text-center text-sm text-red-600">Loi ket noi: ' + error.message + '</td></tr>';
        });
    }

    function renderTransactions(transactions) {
        previewTbody.innerHTML = transactions.map((t, i) => {
            const typeClass = t.type === 'income' ? 'type-income' : 'type-expense';
            const typeLabel = t.type === 'income' ? 'Thu nhap' : 'Chi tieu';
            const amount = new Intl.NumberFormat('vi-VN').format(t.amount) + ' d';

            const walletOptions = wallets.map(w =>
                '<option value="' + w.id + '"' + (w.id === t.suggested_wallet_id ? ' selected' : '') + '>' + escapeHtml(w.name) + '</option>'
            ).join('');

            const catOptions = categories
                .filter(c => c.type === t.type)
                .map(c => '<option value="' + c.id + '"' + (c.id === t.suggested_category_id ? ' selected' : '') + '>' + escapeHtml(c.name) + '</option>'
            ).join('');

            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 text-sm text-gray-500">${i + 1}</td>
                    <td class="px-3 py-2 text-sm text-gray-900">${t.date}</td>
                    <td class="px-3 py-2 text-sm font-medium text-gray-900">${amount}</td>
                    <td class="px-3 py-2"><span class="parser-badge ${typeClass}">${typeLabel}</span></td>
                    <td class="px-3 py-2 text-sm text-gray-700 max-w-xs truncate" title="${escapeHtml(t.description)}">${escapeHtml(t.description)}</td>
                    <td class="px-3 py-2">
                        <select name="mappings[${i}][wallet_id]" class="text-xs rounded border-gray-300">
                            <option value="">-- Chon vi --</option>
                            ${walletOptions}
                        </select>
                    </td>
                    <td class="px-3 py-2">
                        <select name="mappings[${i}][category_id]" class="text-xs rounded border-gray-300">
                            <option value="">-- Chon danh muc --</option>
                            ${catOptions}
                        </select>
                        <input type="hidden" name="mappings[${i}][type]" value="${t.type}">
                    </td>
                </tr>
            `;
        }).join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Re-upload when home changes
    homeSelect.addEventListener('change', function () {
        if (fileInput.files.length > 0) {
            uploadPreview();
        }
    });
});
</script>
@endpush
