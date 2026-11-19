@if (! empty($staffPortal))
    @php($portal = $staffPortal)
    <div class="staff-toolbar" role="navigation" aria-label="نوار کاربری نماینده">
        <div class="staff-toolbar-inner">
            <div class="staff-toolbar-start">
                <a href="{{ $portal['dashboard_url'] }}" class="staff-toolbar-item staff-toolbar-brand">
                    <span class="staff-toolbar-logo">◆</span>
                    <span>داشبورد</span>
                </a>
                @if (! empty($portal['create_request_url']))
                    <a href="{{ $portal['create_request_url'] }}" class="staff-toolbar-item">ثبت درخواست</a>
                @endif
                @if (! empty($portal['requests_index_url']))
                    <a href="{{ $portal['requests_index_url'] }}" class="staff-toolbar-item">درخواست‌ها</a>
                @endif
            </div>
            <div class="staff-toolbar-meta">
                <span class="staff-toolbar-name">{{ $portal['name'] }}</span>
                @if (filled($portal['roles_label']))
                    <span class="staff-toolbar-chip">{{ $portal['roles_label'] }}</span>
                @endif
                @if (filled($portal['mobile']))
                    <span class="staff-toolbar-detail" dir="ltr">{{ $portal['mobile'] }}</span>
                @endif
                @if (filled($portal['email']))
                    <span class="staff-toolbar-detail staff-toolbar-detail--email" dir="ltr">{{ $portal['email'] }}</span>
                @endif
                @if (filled($portal['unit_name']))
                    <span class="staff-toolbar-detail">{{ $portal['unit_name'] }}</span>
                @endif
                <a href="{{ $portal['profile_url'] }}" class="staff-toolbar-item">پروفایل</a>
                <form action="{{ $portal['logout_url'] }}" method="post" class="staff-toolbar-logout">
                    @csrf
                    <button type="submit" class="staff-toolbar-item">خروج</button>
                </form>
            </div>
        </div>
    </div>
@endif