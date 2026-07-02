@php
if (!function_exists('sidebarActive')) {
    function sidebarActive($patterns): string {
        $segment = Request()->segment('2');
        foreach ((array)$patterns as $p) {
            if ($segment === $p) return 'active';
        }
        return '';
    }
}
@endphp
