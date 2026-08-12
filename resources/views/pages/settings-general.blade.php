@extends('layouts.page')

@section('title', 'Pengaturan Umum')

@section('content')
<div class="view-section active animate-fade-in">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/30">
            <h3 class="font-bold text-sm text-gray-800">Pengaturan Umum</h3>
            <p class="text-xs text-gray-500 mt-1">Atur identitas aplikasi, kontak, zona waktu sistem, dan desain kartu siswa.</p>
        </div>

        <div class="p-4">
            <div id="general-setting-error" class="{{ $errors->any() ? '' : 'hidden' }} mb-4 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs">
                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                @endif
            </div>

            <form id="general-setting-form" action="{{ route('settings.general.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Nama Aplikasi</label>
                        <input type="text" name="website_nama" value="{{ old('website_nama', $settings['website_nama'] ?? '') }}" required class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5">
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Nama Madrasah</label>
                        <input type="text" name="website_slogan" value="{{ old('website_slogan', $settings['website_slogan'] ?? '') }}" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5" placeholder="Contoh: Smart Attendance System">
                    </div>
                </div>

                <div>
                    <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Deskripsi</label>
                    <textarea name="website_deskripsi" rows="3" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5" placeholder="Deskripsi website...">{{ old('website_deskripsi', $settings['website_deskripsi'] ?? '') }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Email</label>
                        <input type="email" name="website_email" value="{{ old('website_email', $settings['website_email'] ?? '') }}" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5" placeholder="admin@man1lamsel.sch.id">
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Telepon / WhatsApp</label>
                        <input type="text" name="website_telepon" value="{{ old('website_telepon', $settings['website_telepon'] ?? '') }}" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5" placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Zona Waktu</label>
                        <select name="website_timezone" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5">
                            @foreach (($timezoneOptions ?? []) as $tzValue => $tzLabel)
                                <option value="{{ $tzValue }}" {{ old('website_timezone', $settings['website_timezone'] ?? 'Asia/Jakarta') === $tzValue ? 'selected' : '' }}>
                                    {{ $tzLabel }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-gray-500 mt-1">Berlaku untuk tanggal/jam di dashboard dan laporan.</p>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Tahun Ajaran Kartu Siswa</label>
                        <input type="text" name="student_card_academic_year" value="{{ old('student_card_academic_year', $settings['student_card_academic_year'] ?? '') }}" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5" placeholder="Contoh: 2025/2026">
                        <p class="text-[11px] text-gray-500 mt-1">Dipakai pada header PDF kartu siswa. Kosongkan agar otomatis mengikuti periode Juli-Juni.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                        <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Logo Aplikasi</label>
                        <input id="general-setting-logo-input" type="file" name="website_logo" accept=".png,.jpg,.jpeg,.webp,.svg" class="w-full text-xs text-gray-700">
                        <p class="text-[11px] text-gray-500 mt-1">Format: PNG/JPG/WEBP/SVG. Maks 2MB.</p>
                            <div id="logo-preview-wrapper" class="mt-3 flex items-center gap-3 {{ empty($settings['website_logo_url']) ? 'hidden' : '' }}">
                                <img id="logo-preview-image" src="{{ $settings['website_logo_url'] ?? '' }}" alt="Logo Website" class="w-12 h-12 object-cover rounded-lg border border-gray-200 bg-white">
                                <label class="inline-flex items-center gap-1 text-xs text-red-600">
                                    <input id="remove-logo-checkbox" type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    Hapus logo
                                </label>
                            </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                        <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Favicon</label>
                        <input id="general-setting-favicon-input" type="file" name="website_favicon" accept=".png,.ico,.svg,.webp" class="w-full text-xs text-gray-700">
                        <p class="text-[11px] text-gray-500 mt-1">Format: ICO/PNG/SVG/WEBP. Maks 1MB.</p>
                            <div id="favicon-preview-wrapper" class="mt-3 flex items-center gap-3 {{ empty($settings['website_favicon_url']) ? 'hidden' : '' }}">
                                <img id="favicon-preview-image" src="{{ $settings['website_favicon_url'] ?? '' }}" alt="Favicon" class="w-8 h-8 object-contain rounded border border-gray-200 bg-white p-1">
                                <label class="inline-flex items-center gap-1 text-xs text-red-600">
                                    <input id="remove-favicon-checkbox" type="checkbox" name="remove_favicon" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    Hapus favicon
                                </label>
                            </div>
                    </div>
                </div>

                <div class="border border-indigo-200 rounded-xl p-4 bg-indigo-50/40">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800">Background Cetak Kartu Siswa</h4>
                            <p class="text-[11px] text-gray-600 mt-1">Gambar memenuhi seluruh kartu, sedangkan QR, NISN, nama, tanggal lahir, dan jenis kelamin tetap diambil dari database.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_280px] gap-4 items-start">
                        <div>
                            <label class="block mb-1 text-xs font-bold text-gray-500 uppercase tracking-wide">Upload Desain Background</label>
                            <input id="student-card-background-input" type="file" name="student_card_background" accept=".png,.jpg,.jpeg,.webp" class="w-full text-xs text-gray-700">
                            <p class="text-[11px] text-gray-500 mt-1">Gunakan desain kosong rasio kartu 86 × 54 mm, disarankan 1016 × 638 px. Format PNG/JPG/WEBP, maks 5MB.</p>
                            <p class="text-[11px] text-amber-700 mt-2"><i class="fas fa-triangle-exclamation mr-1"></i>Jangan masukkan contoh nama, NISN, atau QR ke gambar karena data akan ditimpa otomatis saat mencetak.</p>
                        </div>
                        <div id="student-card-background-preview-wrapper" class="{{ empty($settings['student_card_background_url']) ? 'hidden' : '' }}">
                            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm aspect-[86/54]">
                                <img id="student-card-background-preview-image" src="{{ $settings['student_card_background_url'] ?? '' }}" alt="Background kartu siswa" class="w-full h-full object-cover">
                            </div>
                            <label class="mt-2 inline-flex items-center gap-1 text-xs text-red-600">
                                <input id="remove-student-card-background-checkbox" type="checkbox" name="remove_student_card_background" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                Hapus background kartu
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button id="general-setting-submit" type="submit" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">
                        <i class="fas fa-save text-xs"></i>
                        <span id="general-setting-submit-text">Simpan Pengaturan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('general-setting-form');
        if (!form) return;

        const submitButton = document.getElementById('general-setting-submit');
        const submitText = document.getElementById('general-setting-submit-text');
        const errorBox = document.getElementById('general-setting-error');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const cardBackgroundInput = document.getElementById('student-card-background-input');
        const cardBackgroundPreviewWrapper = document.getElementById('student-card-background-preview-wrapper');
        const cardBackgroundPreviewImage = document.getElementById('student-card-background-preview-image');
        const cardBackgroundRemoveCheckbox = document.getElementById('remove-student-card-background-checkbox');

        function hideMessages() {
            if (errorBox) {
                errorBox.classList.add('hidden');
                errorBox.innerHTML = '';
            }
        }

        function showErrorMessages(messages) {
            if (!errorBox) return;
            const list = Array.isArray(messages) ? messages : [String(messages || 'Terjadi kesalahan.')];
            errorBox.innerHTML = list.map((text) => `<div>${String(text)}</div>`).join('');
            errorBox.classList.remove('hidden');
        }

        function setSubmitState(isLoading) {
            if (!submitButton || !submitText) return;
            submitButton.disabled = isLoading;
            if (isLoading) {
                submitButton.classList.add('opacity-80', 'cursor-not-allowed');
                submitText.textContent = 'Menyimpan...';
            } else {
                submitButton.classList.remove('opacity-80', 'cursor-not-allowed');
                submitText.textContent = 'Simpan Pengaturan';
            }
        }

        function updateBrandUI(data) {
            if (!data || typeof data !== 'object') return;

            const websiteName = String(data.website_nama || '').trim();
            const websiteSlogan = String(data.website_slogan || '').trim();
            const logoUrl = data.website_logo_url ? String(data.website_logo_url) : '';
            const faviconUrl = data.website_favicon_url ? String(data.website_favicon_url) : '';
            const websiteTimezone = String(data.website_timezone || window.APP_TIMEZONE || 'Asia/Jakarta');
            const websiteTimezoneLabel = String(data.website_timezone_label || window.APP_TIMEZONE_LABEL || websiteTimezone);
            const studentCardAcademicYear = String(data.student_card_academic_year || '').trim();
            const studentCardBackgroundUrl = data.student_card_background_url ? String(data.student_card_background_url) : '';

            const sidebarBrandName = document.getElementById('sidebarBrandName');
            const sidebarBrandSlogan = document.getElementById('sidebarBrandSlogan');
            const sidebarLogoImg = document.getElementById('sidebarBrandLogoImg');
            const sidebarLogoIcon = document.getElementById('sidebarBrandLogoIcon');

            if (sidebarBrandName && websiteName !== '') {
                sidebarBrandName.textContent = websiteName;
            }
            if (sidebarBrandSlogan) {
                sidebarBrandSlogan.textContent = websiteSlogan || 'School System';
            }
            if (sidebarLogoImg && sidebarLogoIcon) {
                if (logoUrl !== '') {
                    sidebarLogoImg.src = logoUrl;
                    sidebarLogoImg.classList.remove('hidden');
                    sidebarLogoIcon.classList.add('hidden');
                } else {
                    sidebarLogoImg.src = '';
                    sidebarLogoImg.classList.add('hidden');
                    sidebarLogoIcon.classList.remove('hidden');
                }
            }

            const pageTitlePrefix = document.title.split(' - ')[0] || 'Dashboard';
            document.title = `${pageTitlePrefix} - ${websiteName || 'Sistem Absensi Pintar'}`;

            window.APP_TIMEZONE = websiteTimezone;
            window.APP_TIMEZONE_LABEL = websiteTimezoneLabel;
            window.APP_STUDENT_CARD_ACADEMIC_YEAR = studentCardAcademicYear;
            window.APP_STUDENT_CARD_BACKGROUND_URL = studentCardBackgroundUrl;
            if (typeof window.updateHeaderCurrentDate === 'function') {
                window.updateHeaderCurrentDate();
            }

            const faviconLink = document.querySelector('link[rel="icon"]');
            if (faviconUrl !== '') {
                if (faviconLink) {
                    faviconLink.setAttribute('href', faviconUrl);
                } else {
                    const link = document.createElement('link');
                    link.setAttribute('rel', 'icon');
                    link.setAttribute('type', 'image/png');
                    link.setAttribute('href', faviconUrl);
                    document.head.appendChild(link);
                }
            } else if (faviconLink) {
                faviconLink.remove();
            }

            const logoPreviewWrapper = document.getElementById('logo-preview-wrapper');
            const logoPreviewImage = document.getElementById('logo-preview-image');
            const logoRemoveCheckbox = document.getElementById('remove-logo-checkbox');
            if (logoPreviewWrapper && logoPreviewImage) {
                if (logoUrl !== '') {
                    logoPreviewImage.src = logoUrl;
                    logoPreviewWrapper.classList.remove('hidden');
                } else {
                    logoPreviewImage.src = '';
                    logoPreviewWrapper.classList.add('hidden');
                }
            }
            if (logoRemoveCheckbox) logoRemoveCheckbox.checked = false;

            const faviconPreviewWrapper = document.getElementById('favicon-preview-wrapper');
            const faviconPreviewImage = document.getElementById('favicon-preview-image');
            const faviconRemoveCheckbox = document.getElementById('remove-favicon-checkbox');
            if (faviconPreviewWrapper && faviconPreviewImage) {
                if (faviconUrl !== '') {
                    faviconPreviewImage.src = faviconUrl;
                    faviconPreviewWrapper.classList.remove('hidden');
                } else {
                    faviconPreviewImage.src = '';
                    faviconPreviewWrapper.classList.add('hidden');
                }
            }
            if (faviconRemoveCheckbox) faviconRemoveCheckbox.checked = false;

            const cardBackgroundPreviewWrapper = document.getElementById('student-card-background-preview-wrapper');
            const cardBackgroundPreviewImage = document.getElementById('student-card-background-preview-image');
            const cardBackgroundRemoveCheckbox = document.getElementById('remove-student-card-background-checkbox');
            if (cardBackgroundPreviewWrapper && cardBackgroundPreviewImage) {
                if (studentCardBackgroundUrl !== '') {
                    cardBackgroundPreviewImage.src = studentCardBackgroundUrl;
                    cardBackgroundPreviewWrapper.classList.remove('hidden');
                } else {
                    cardBackgroundPreviewImage.src = '';
                    cardBackgroundPreviewWrapper.classList.add('hidden');
                }
            }
            if (cardBackgroundRemoveCheckbox) cardBackgroundRemoveCheckbox.checked = false;
        }

        async function handleSubmit(event) {
            event.preventDefault();
            hideMessages();
            setSubmitState(true);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok || payload.success === false) {
                    if (response.status === 422 && payload.errors && typeof payload.errors === 'object') {
                        const errors = Object.values(payload.errors).flat();
                        showErrorMessages(errors);
                    } else {
                        showErrorMessages(payload.message || 'Gagal menyimpan pengaturan umum.');
                    }
                    if (window.showAlert) {
                        window.showAlert('error', payload.message || 'Gagal menyimpan pengaturan umum.');
                    }
                    return;
                }

                if (window.showAlert) {
                    window.showAlert('success', payload.message || 'Pengaturan umum berhasil disimpan.');
                }

                form.querySelectorAll('input[type="file"]').forEach((input) => {
                    input.value = '';
                });

                updateBrandUI(payload.data || {});
            } catch (error) {
                showErrorMessages(error.message || 'Terjadi kesalahan saat menyimpan.');
                if (window.showAlert) {
                    window.showAlert('error', error.message || 'Terjadi kesalahan saat menyimpan.');
                }
            } finally {
                setSubmitState(false);
            }
        }

        cardBackgroundInput?.addEventListener('change', () => {
            const file = cardBackgroundInput.files?.[0];
            if (!file || !cardBackgroundPreviewWrapper || !cardBackgroundPreviewImage) return;

            cardBackgroundPreviewImage.src = URL.createObjectURL(file);
            cardBackgroundPreviewWrapper.classList.remove('hidden');
            if (cardBackgroundRemoveCheckbox) cardBackgroundRemoveCheckbox.checked = false;
        });

        form.addEventListener('submit', handleSubmit);
    })();
</script>
@endpush
