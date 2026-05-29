{{-- Shared partial for Premium School Pages --}}
@php
    $schoolName = $school->name;
    $logo = $details['school_logo'] ?? null;
    $moto = $details['moto'] ?? '';
    $aboutUs = $details['about_us'] ?? '';
    $website = $details['website'] ?? '';
    $board = $details['board'] ?? '';
    $affiliatedBy = $details['affiliated_by'] ?? '';
    $established = $details['date_of_establishment'] ?? '';
    $landline = $details['landline_no'] ?? '';
    $address = $school->address;
    $email = $school->email;
    $phone = $school->phone;

    $colors = $page->custom_colors ?? [];
    $primary = $colors['primary'] ?? '#1E6FD9';
    $secondary = $colors['secondary'] ?? '#0D1526';
    $accent = $colors['accent'] ?? '#22C55E';

    $content = $page->custom_content ?? [];

    // WhatsApp link from school phone
    $waPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : '';
    if (substr($waPhone, 0, 1) === '0' && strlen($waPhone) > 1) {
        $waPhone = '256' . substr($waPhone, 1);
    }
    $whatsappLink = $waPhone ? 'https://wa.me/' . $waPhone . '?text=Hi%2C%20I\'m%20interested%20in%20' . urlencode($schoolName) : null;
@endphp
