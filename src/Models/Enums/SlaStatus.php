<?php

namespace App\Models\Enums;

enum SlaStatus: string
{
    case HIJAU = 'HIJAU';
    case KUNING = 'KUNING';
    case MERAH = 'MERAH';
}
