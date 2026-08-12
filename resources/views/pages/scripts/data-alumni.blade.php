<script>
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const listUrl = @json(route('ajax.alumni.index'));
    const classOptionsUrl = @json(route('ajax.shared.classes.index'));
    const restoreUrl = @json(route('ajax.alumni.restore'));
    const destroyUrl = @json(route('ajax.alumni.destroy'));
    const tbody = document.getElementById('tbody-alumni');
    const info = document.getElementById('info-alumni');
    const prevButton = document.getElementById('btn-prev-alumni');
    const nextButton = document.getElementById('btn-next-alumni');
    const perPageSelect = document.getElementById('alumniPerPage');
    const classFilter = document.getElementById('filterKelasAlumni');
    const yearFilter = document.getElementById('filterTahunAlumni');
    const searchInput = document.getElementById('searchAlumniInput');
    const refreshButton = document.getElementById('refreshAlumniBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const canManage = Array.isArray(window.APP_CURRENT_USER?.permissions)
        && window.APP_CURRENT_USER.permissions.includes('alumni.manage');
    const currentRole = String(window.APP_CURRENT_USER?.role || '').toLowerCase();
    const currentKelas = String(window.APP_CURRENT_USER?.kelas || '').trim();

    const state = {
        page: 1,
        perPage: '10',
        kelas: '',
        tahunLulus: '',
        search: '',
        totalPages: 1,
        rowsById: new Map(),
        restoreClassOptions: [],
        restoreClassOptionsPromise: null,
    };

    let searchDebounce = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(`${value}T00:00:00`);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    }

    function showLoading() {
        loadingOverlay?.classList.remove('hidden');
    }

    function hideLoading() {
        loadingOverlay?.classList.add('hidden');
    }

    function getModalShell(create = false) {
        const container = document.getElementById('modalContainer');
        if (!container) {
            return null;
        }

        let shell = container.querySelector('[data-alumni-modal-shell]');
        if (!shell && create) {
            container.insertAdjacentHTML('beforeend', `
                <div data-alumni-modal-shell class="fixed inset-0 z-50 hidden items-center justify-center p-4">
                    <div class="absolute inset-0 bg-gray-900/45 transition-opacity" data-alumni-modal-close></div>
                    <div data-alumni-modal-host class="relative flex w-full justify-center"></div>
                </div>
            `);
            shell = container.querySelector('[data-alumni-modal-shell]');
        }

        return shell;
    }

    function showModal(content) {
        const shell = getModalShell(true);
        if (!shell) {
            return;
        }

        const host = shell.querySelector('[data-alumni-modal-host]');
        if (!host) {
            return;
        }

        if (typeof content === 'string') {
            host.innerHTML = content;
        } else if (content instanceof HTMLElement) {
            host.innerHTML = '';
            host.appendChild(content);
        } else {
            return;
        }

        shell.classList.remove('hidden');
        shell.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        const shell = getModalShell(false);
        if (!shell) {
            return;
        }

        const host = shell.querySelector('[data-alumni-modal-host]');
        if (host) {
            host.innerHTML = '';
        }

        shell.classList.add('hidden');
        shell.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function renderTableMessage(message, icon = 'fa-database') {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                    <div class="flex flex-col items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i class="fas ${icon} text-lg"></i>
                        </div>
                        <div class="max-w-sm text-sm">${escapeHtml(message)}</div>
                    </div>
                </td>
            </tr>
        `;
    }

    function populateSelectOptions(select, values, placeholder, selectedValue) {
        const options = [`<option value="">${escapeHtml(placeholder)}</option>`];
        values.forEach((value) => {
            const normalized = String(value ?? '').trim();
            if (normalized === '') {
                return;
            }

            const isSelected = normalized === String(selectedValue ?? '');
            options.push(
                `<option value="${escapeHtml(normalized)}"${isSelected ? ' selected' : ''}>${escapeHtml(normalized)}</option>`
            );
        });

        select.innerHTML = options.join('');
    }

    function normalizeClassOptions(values) {
        return Array.from(new Set(
            (Array.isArray(values) ? values : [])
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '')
        ));
    }

    async function ensureRestoreClassOptions() {
        if (currentRole === 'wakel' && currentKelas !== '') {
            state.restoreClassOptions = [currentKelas];
            return state.restoreClassOptions;
        }

        if (state.restoreClassOptions.length > 0) {
            return state.restoreClassOptions;
        }

        if (state.restoreClassOptionsPromise) {
            return state.restoreClassOptionsPromise;
        }

        state.restoreClassOptionsPromise = fetch(classOptionsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({}),
        })
            .then(async (response) => {
                const result = await response.json().catch(() => []);
                if (!response.ok) {
                    throw new Error('Gagal memuat daftar kelas.');
                }

                state.restoreClassOptions = normalizeClassOptions(result);

                return state.restoreClassOptions;
            })
            .catch(() => {
                state.restoreClassOptions = [];

                return state.restoreClassOptions;
            })
            .finally(() => {
                state.restoreClassOptionsPromise = null;
            });

        return state.restoreClassOptionsPromise;
    }

    function buildRestoreClassOptions(row) {
        const options = normalizeClassOptions(state.restoreClassOptions);
        const fallbackKelas = currentRole === 'wakel' && currentKelas !== ''
            ? currentKelas
            : String(row?.kelasTerakhir || '').trim();

        if (fallbackKelas !== '' && !options.includes(fallbackKelas)) {
            options.unshift(fallbackKelas);
        }

        return options;
    }

    function renderRows(rows, meta) {
        state.rowsById = new Map(rows.map((row) => [Number(row.id || 0), row]));

        if (!rows.length) {
            renderTableMessage('Belum ada data alumni yang sesuai dengan filter.');
            info.textContent = 'Menampilkan 0 data';
            prevButton.disabled = true;
            nextButton.disabled = true;
            return;
        }

        const startNumber = Number(meta?.from || 1);
        tbody.innerHTML = rows.map((row, index) => `
            <tr class="hover:bg-slate-50/80 transition">
                <td class="p-3 text-center font-semibold text-slate-500">${startNumber + index}</td>
                <td class="p-3">
                    <div class="font-semibold text-slate-800">${escapeHtml(row.nama || '-')}</div>
                    <div class="mt-1 text-[11px] text-slate-500 md:hidden">NISN: ${escapeHtml(row.nisn || '-')}</div>
                </td>
                <td class="p-3 hidden md:table-cell">${escapeHtml(row.nisn || '-')}</td>
                <td class="p-3 hidden sm:table-cell">${escapeHtml(row.kelasTerakhir || '-')}</td>
                <td class="p-3 hidden lg:table-cell">${escapeHtml(row.tahunLulus || '-')}</td>
                <td class="p-3 hidden xl:table-cell">${escapeHtml(row.kontak || '-')}</td>
                <td class="p-3">
                    <div class="flex items-center justify-center gap-2">
                        <button type="button" data-alumni-action="detail" data-alumni-id="${Number(row.id || 0)}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100">
                            <i class="fas fa-eye"></i>
                            Detail
                        </button>
                        ${canManage ? `
                            <button type="button" data-alumni-action="restore" data-alumni-id="${Number(row.id || 0)}" class="inline-flex items-center gap-2 rounded-lg bg-sky-50 px-3 py-1.5 text-[11px] font-bold text-sky-700 transition hover:bg-sky-100" title="Restore ke Siswa">
                                <i class="fas fa-rotate-left"></i>
                            </button>
                            <button type="button" data-alumni-action="delete" data-alumni-id="${Number(row.id || 0)}" class="inline-flex items-center gap-2 rounded-lg bg-rose-50 px-3 py-1.5 text-[11px] font-bold text-rose-700 transition hover:bg-rose-100">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');

        const total = Number(meta?.total || rows.length);
        const from = Number(meta?.from || (rows.length ? 1 : 0));
        const to = Number(meta?.to || rows.length);
        info.textContent = `Menampilkan ${from}-${to} dari ${total} alumni`;
        prevButton.disabled = Number(meta?.page || 1) <= 1;
        nextButton.disabled = Number(meta?.page || 1) >= Number(meta?.total_pages || 1);
    }

    function createModalFrame(title, subtitle, body, actions = '', maxWidth = 'max-w-2xl') {
        return `
            <div class="w-full ${maxWidth}">
                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-200">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">${escapeHtml(title)}</h3>
                            <p class="text-xs text-slate-500">${escapeHtml(subtitle)}</p>
                        </div>
                        <button type="button" data-alumni-modal-close class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="px-5 py-5">${body}</div>
                    ${actions ? `<div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/70 px-5 py-4">${actions}</div>` : ''}
                </div>
            </div>
        `;
    }

    function renderDetailField(label, value) {
        return `
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">${escapeHtml(label)}</div>
                <div class="mt-2 text-sm font-medium text-slate-800 break-words">${escapeHtml(value || '-')}</div>
            </div>
        `;
    }

    function getModalSecondaryButtonClass() {
        return 'inline-flex items-center justify-center gap-2 h-10 px-5 rounded-xl border border-gray-200 bg-white text-gray-700 font-semibold text-sm hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-200 transition';
    }

    function getModalPrimaryButtonClass() {
        return 'inline-flex items-center justify-center gap-2 h-10 px-5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-bold text-sm shadow-md hover:from-indigo-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition transform active:scale-[0.98]';
    }

    function getModalDangerButtonClass() {
        return 'inline-flex items-center justify-center gap-2 h-10 px-5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 text-white font-bold text-sm shadow-md hover:from-rose-700 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-rose-300 transition transform active:scale-[0.98]';
    }

    function openDetailModal(id) {
        const row = state.rowsById.get(Number(id || 0));
        if (!row) {
            return;
        }

        const body = `
            <div class="grid gap-4 sm:grid-cols-2">
                ${renderDetailField('Nama Lengkap', row.nama)}
                ${renderDetailField('NISN', row.nisn)}
                ${renderDetailField('Jenis Kelamin', row.jenisKelamin)}
                ${renderDetailField('Tanggal Lahir', formatDate(row.tanggalLahir))}
                ${renderDetailField('Agama', row.agama)}
                ${renderDetailField('Kelas Terakhir', row.kelasTerakhir)}
                ${renderDetailField('Tahun Lulus', row.tahunLulus)}
                ${renderDetailField('Kontak', row.kontak)}
                ${renderDetailField('Nama Ayah', row.namaAyah)}
                ${renderDetailField('Nama Ibu', row.namaIbu)}
            </div>
            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Alamat</div>
                <div class="mt-2 text-sm leading-6 text-slate-700">${escapeHtml(row.alamat || '-')}</div>
            </div>
        `;

        const actions = canManage ? `
            <button type="button" data-alumni-modal-close class="${getModalSecondaryButtonClass()}">
                <i class="fas fa-times text-xs"></i>Tutup
            </button>
            <button type="button" data-alumni-action="restore" data-alumni-id="${Number(row.id || 0)}" class="${getModalPrimaryButtonClass()}">
                <i class="fas fa-rotate-left text-xs"></i>Restore ke Siswa
            </button>
        ` : `
            <button type="button" data-alumni-modal-close class="${getModalSecondaryButtonClass()}">
                <i class="fas fa-times text-xs"></i>Tutup
            </button>
        `;

        showModal(createModalFrame('Detail Alumni', 'Biodata lengkap arsip lulusan.', body, actions));
    }

    async function openRestoreModal(id) {
        const row = state.rowsById.get(Number(id || 0));
        if (!row) {
            return;
        }

        await ensureRestoreClassOptions();

        const defaultKelas = currentRole === 'wakel' && currentKelas !== ''
            ? currentKelas
            : String(row.kelasTerakhir || '').trim();
        const kelasOptions = buildRestoreClassOptions(row);
        const kelasReadonly = currentRole === 'wakel';
        const kelasOptionHtml = kelasOptions.length
            ? kelasOptions.map((kelas) => `
                <option value="${escapeHtml(kelas)}"${kelas === defaultKelas ? ' selected' : ''}>${escapeHtml(kelas)}</option>
            `).join('')
            : '<option value="">Pilih kelas</option>';

        const body = `
            <form id="alumniRestoreForm" class="space-y-4">
                <input type="hidden" name="id" value="${Number(row.id || 0)}">
                <div class="rounded-2xl border border-sky-100 bg-sky-50/80 px-4 py-4 text-sm text-slate-700">
                    Data alumni <span class="font-semibold text-slate-900">${escapeHtml(row.nama || '-')}</span> akan dipindahkan kembali ke tabel siswa dan dihapus dari daftar alumni.
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">NISN</label>
                        <input type="text" value="${escapeHtml(row.nisn || '')}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-slate-600" readonly>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Kelas Siswa Tujuan</label>
                        <select name="kelas" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100 ${kelasReadonly ? 'bg-gray-50 text-slate-600' : ''}" ${kelasReadonly ? 'disabled' : ''}>
                            ${kelasOptionHtml}
                        </select>
                        ${kelasReadonly ? `<input type="hidden" name="kelas" value="${escapeHtml(defaultKelas)}">` : ''}
                    </div>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50/80 px-4 py-3 text-xs leading-6 text-amber-800">
                    Data absensi lama yang sudah terhapus saat siswa diluluskan tidak ikut kembali. Restore ini hanya mengembalikan biodata alumni ke data siswa aktif.
                </div>
            </form>
        `;

        const actions = `
            <button type="button" data-alumni-modal-close class="${getModalSecondaryButtonClass()}">
                <i class="fas fa-times text-xs"></i>Batal
            </button>
            <button type="button" id="submitRestoreAlumniBtn" class="${getModalPrimaryButtonClass()}">
                <i class="fas fa-rotate-left text-xs"></i>Restore ke Siswa
            </button>
        `;

        showModal(createModalFrame('Restore ke Siswa', 'Kembalikan alumni ini menjadi siswa aktif.', body, actions, 'max-w-xl'));

        document.getElementById('submitRestoreAlumniBtn')?.addEventListener('click', submitRestoreForm);
        document.getElementById('alumniRestoreForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            submitRestoreForm();
        });
    }

    function openDeleteModal(id) {
        const row = state.rowsById.get(Number(id || 0));
        if (!row) {
            return;
        }

        const body = `
            <div class="flex flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 shadow-sm">
                    <i class="fas fa-trash text-xl"></i>
                </div>
                <div class="mt-4 text-base font-bold text-slate-900">Hapus data alumni ini?</div>
                <div class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                    Data alumni <span class="font-semibold text-slate-700">${escapeHtml(row.nama || '-')}</span> akan dihapus permanen dari daftar alumni.
                </div>
            </div>
        `;

        const actions = `
            <button type="button" data-alumni-modal-close class="${getModalSecondaryButtonClass()}">
                <i class="fas fa-times text-xs"></i>Batal
            </button>
            <button type="button" id="confirmDeleteAlumniBtn" class="${getModalDangerButtonClass()}">
                <i class="fas fa-trash text-xs"></i>Hapus
            </button>
        `;

        showModal(createModalFrame('Hapus Alumni', 'Aksi ini tidak bisa dibatalkan.', body, actions, 'max-w-md'));

        document.getElementById('confirmDeleteAlumniBtn')?.addEventListener('click', () => {
            submitDelete(row.id);
        });
    }

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                args: [payload],
            }),
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result?.success) {
            throw new Error(result?.message || 'Permintaan gagal diproses.');
        }

        return result;
    }

    async function submitRestoreForm() {
        const form = document.getElementById('alumniRestoreForm');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        showLoading();
        try {
            const result = await postJson(restoreUrl, payload);
            closeModal();
            await loadAlumni();

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: result?.message || 'Data alumni berhasil direstore ke siswa.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            }
        } catch (error) {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal restore',
                    text: error?.message || 'Terjadi kesalahan saat merestore data alumni.',
                });
            }
        } finally {
            hideLoading();
        }
    }

    async function submitDelete(id) {
        showLoading();
        try {
            const result = await postJson(destroyUrl, { id });
            closeModal();
            await loadAlumni();

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: result?.message || 'Data alumni berhasil dihapus.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            }
        } catch (error) {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal menghapus',
                    text: error?.message || 'Terjadi kesalahan saat menghapus data alumni.',
                });
            }
        } finally {
            hideLoading();
        }
    }

    async function loadAlumni() {
        renderTableMessage('Memuat data alumni...', 'fa-spinner fa-spin');

        const payload = {
            paginated: true,
            page: state.page,
            per_page: state.perPage,
            kelas: state.kelas,
            tahun_lulus: state.tahunLulus,
            search: state.search,
        };

        try {
            const result = await postJson(listUrl, payload);
            const meta = result.meta || {};
            state.totalPages = Number(meta.total_pages || 1);
            state.page = Number(meta.page || 1);

            populateSelectOptions(classFilter, meta.classes || [], 'Semua Kelas Terakhir', state.kelas);
            populateSelectOptions(yearFilter, meta.years || [], 'Semua Tahun', state.tahunLulus);
            renderRows(Array.isArray(result.data) ? result.data : [], meta);
        } catch (error) {
            renderTableMessage(error?.message || 'Gagal memuat data alumni.', 'fa-circle-exclamation');
            info.textContent = 'Terjadi kesalahan saat memuat data';
            prevButton.disabled = true;
            nextButton.disabled = true;
        }
    }

    perPageSelect?.addEventListener('change', () => {
        state.perPage = perPageSelect.value || '10';
        state.page = 1;
        loadAlumni();
    });

    classFilter?.addEventListener('change', () => {
        state.kelas = classFilter.value || '';
        state.page = 1;
        loadAlumni();
    });

    yearFilter?.addEventListener('change', () => {
        state.tahunLulus = yearFilter.value || '';
        state.page = 1;
        loadAlumni();
    });

    searchInput?.addEventListener('input', () => {
        state.search = searchInput.value || '';
        state.page = 1;

        if (searchDebounce) {
            clearTimeout(searchDebounce);
        }

        searchDebounce = window.setTimeout(() => {
            loadAlumni();
        }, 250);
    });

    refreshButton?.addEventListener('click', () => {
        loadAlumni();
    });

    prevButton?.addEventListener('click', () => {
        if (state.page <= 1) {
            return;
        }

        state.page -= 1;
        loadAlumni();
    });

    nextButton?.addEventListener('click', () => {
        if (state.page >= state.totalPages) {
            return;
        }

        state.page += 1;
        loadAlumni();
    });

    tbody?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-alumni-action]');
        if (!button) {
            return;
        }

        const action = String(button.getAttribute('data-alumni-action') || '');
        const id = Number(button.getAttribute('data-alumni-id') || 0);
        if (id <= 0) {
            return;
        }

        if (action === 'detail') {
            openDetailModal(id);
            return;
        }

        if (!canManage) {
            return;
        }

        if (action === 'restore') {
            openRestoreModal(id);
            return;
        }

        if (action === 'delete') {
            openDeleteModal(id);
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-alumni-modal-close]')) {
            closeModal();
            return;
        }

        const actionButton = event.target.closest('[data-alumni-action]');
        if (!actionButton || actionButton.closest('#tbody-alumni')) {
            return;
        }

        const action = String(actionButton.getAttribute('data-alumni-action') || '');
        const id = Number(actionButton.getAttribute('data-alumni-id') || 0);
        if (!canManage || id <= 0) {
            return;
        }

        if (action === 'restore') {
            closeModal();
            openRestoreModal(id);
            return;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    ensureRestoreClassOptions();
    loadAlumni();
})();
</script>
