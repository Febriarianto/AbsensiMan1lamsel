@can('settings.general.manage')
    <a href="{{ route('settings.emis-sync.index') }}"
        class="flex items-center gap-2 px-3 py-2 rounded-lg transition {{ request()->routeIs('settings.emis-sync.*') ? 'bg-white/15 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
        <i class="fas fa-sync-alt w-4 text-center"></i>
        <span class="sidebar-label text-[14px] font-medium">Sinkronisasi EMIS</span>
    </a>
@endcan
