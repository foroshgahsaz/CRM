<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case Submit = 'submit';
    case Track = 'track';
    case StaffLogin = 'staff_login';
}
