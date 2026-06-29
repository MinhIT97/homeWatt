@props(['icon', 'color' => '#10b981', 'class' => 'w-12 h-12 rounded-xl flex items-center justify-center text-xl shrink-0'])

@php
    $cleanIcon = strtolower(trim($icon));
    $isBrand = in_array($cleanIcon, ['vcb', 'vietcombank', 'tcb', 'techcombank', 'vpbank', 'vpb', 'mbbank', 'mb', 'acb', 'tpb', 'tpbank', 'bidv', 'vietinbank', 'vtb', 'sacombank', 'scb', 'visa', 'mastercard', 'mc', 'jcb', 'napas']);
@endphp

<div class="{{ $class }}" style="background-color: {{ $isBrand ? 'transparent' : ($color . '20') }}; color: {{ $color }};">
    @if($cleanIcon === 'vcb' || $cleanIcon === 'vietcombank')
        <!-- Vietcombank stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#009F3C"/>
            <path d="M25 35L45 70L55 70L75 35H62L50 58L38 35H25Z" fill="#FFFFFF"/>
            <path d="M42 35L50 50L58 35H42Z" fill="#A3D95D"/>
        </svg>
    @elseif($cleanIcon === 'tcb' || $cleanIcon === 'techcombank')
        <!-- Techcombank stylized icon -->
        <svg class="w-full h-full p-1.5" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#E02020"/>
            <rect x="25" y="25" width="50" height="50" fill="none" stroke="#FFFFFF" stroke-width="8"/>
            <rect x="35" y="35" width="30" height="30" fill="#FFFFFF"/>
            <rect x="43" y="21" width="14" height="58" fill="#E02020"/>
            <rect x="21" y="43" width="58" height="14" fill="#E02020"/>
        </svg>
    @elseif($cleanIcon === 'vpbank' || $cleanIcon === 'vpb')
        <!-- VPBank stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#009845"/>
            <path d="M50 15C52 32 68 44 82 50C68 56 52 68 50 85C48 68 32 56 18 50C32 44 48 32 50 15Z" fill="#FFFFFF"/>
            <path d="M50 32C51 43 59 49 68 50C59 51 51 57 50 68C49 57 41 51 32 50C41 49 49 43 50 32Z" fill="#FD4F00"/>
        </svg>
    @elseif($cleanIcon === 'mbbank' || $cleanIcon === 'mb')
        <!-- MB Bank stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#004B87"/>
            <path d="M50 20L59 40H81L64 53L70 75L50 62L30 75L36 53L19 40H41L50 20Z" fill="#EE0000"/>
            <path d="M50 28L56 45H74L60 55L65 72L50 61L35 72L40 55L26 45H44L50 28Z" fill="#FFFFFF"/>
        </svg>
    @elseif($cleanIcon === 'acb')
        <!-- ACB stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#0067B2"/>
            <path d="M32 68L44 32H56L68 68H58L50 48L42 68H32ZM40 58H60V64H40V58Z" fill="#FFFFFF"/>
            <circle cx="50" cy="50" r="14" stroke="#00A5E3" stroke-width="4"/>
        </svg>
    @elseif($cleanIcon === 'tpb' || $cleanIcon === 'tpbank')
        <!-- TPBank stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#6F2C91"/>
            <path d="M50 20L80 72H20L50 20Z" fill="none" stroke="#FFFFFF" stroke-width="8" stroke-linejoin="round"/>
            <path d="M50 36L68 66H32L50 36Z" fill="#FFC72C"/>
        </svg>
    @elseif($cleanIcon === 'bidv')
        <!-- BIDV stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#005A3C"/>
            <path d="M28 28H53C63 28 68 33 68 41C68 46 65 50 60 52C66 54 68 59 68 66C68 74 63 78 53 78H28V28ZM40 40V49H50C54 49 56 47 56 45C56 43 54 40 50 40H40ZM40 57V67H51C55 67 57 65 57 62C57 59 55 57 51 57H40Z" fill="#FFFFFF"/>
            <path d="M72 45L78 50L72 55V45Z" fill="#D22630"/>
        </svg>
    @elseif($cleanIcon === 'vietinbank' || $cleanIcon === 'vtb')
        <!-- VietinBank stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#0072BC"/>
            <circle cx="50" cy="50" r="24" stroke="#FFFFFF" stroke-width="6"/>
            <path d="M40 40H60V60H40V40Z" fill="#FD4F00" transform="rotate(45 50 50)"/>
        </svg>
    @elseif($cleanIcon === 'sacombank' || $cleanIcon === 'scb')
        <!-- Sacombank stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#00529B"/>
            <path d="M28 68V28H43C53 28 58 31 58 38C58 44 53 47 44 48L60 68H48L34 50V68H28ZM38 38V42H43C46 42 47 41 47 40C47 39 46 38 43 38H38Z" fill="#FFFFFF"/>
            <path d="M62 28H70V45H62V28Z" fill="#FD5C02"/>
        </svg>
    @elseif($cleanIcon === 'visa')
        <!-- Visa stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#1A1F71"/>
            <path d="M20 38L28 62H35L43 38H36L32 54L28 38H20ZM47 38L44 62H50L53 38H47ZM62 44C58 42 54 43 54 46C54 52 62 50 62 55C62 57 59 58 55 58C51 58 48 56 46 54L44 59C47 61 51 62 55 62C62 62 68 59 68 54C68 48 60 49 60 45C60 43 62 42 65 42C68 42 70 43 71 45L73 40C70 38 66 37 62 37" fill="#F7B600"/>
        </svg>
    @elseif($cleanIcon === 'mastercard' || $cleanIcon === 'mc')
        <!-- Mastercard stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#111111"/>
            <circle cx="40" cy="50" r="22" fill="#EB001B"/>
            <circle cx="60" cy="50" r="22" fill="#FF5F00" fill-opacity="0.85"/>
        </svg>
    @elseif($cleanIcon === 'jcb')
        <!-- JCB stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#FFFFFF"/>
            <rect x="18" y="28" width="20" height="44" rx="4" fill="#002C87"/>
            <rect x="40" y="28" width="20" height="44" rx="4" fill="#D1001C"/>
            <rect x="62" y="28" width="20" height="44" rx="4" fill="#00893C"/>
            <path d="M23 45V55M45 45V55M67 45V55" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round"/>
        </svg>
    @elseif($cleanIcon === 'napas')
        <!-- Napas stylized icon -->
        <svg class="w-full h-full p-1" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="100" height="100" rx="24" fill="#E77817"/>
            <path d="M22 32H32L58 68H48L22 32Z" fill="#FFFFFF"/>
            <path d="M78 32H68L42 68H52L78 32Z" fill="#00529B"/>
            <circle cx="50" cy="50" r="8" fill="#FFFFFF"/>
        </svg>
    @else
        <!-- Fallback to emoji -->
        {{ $icon }}
    @endif
</div>
