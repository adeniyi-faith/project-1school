<?php

namespace App\Enums;

enum UserRole: string
{
    case SchoolAdmin = 'school_admin';
    case Teacher = 'teacher';
    case ParentGuardian = 'parent_guardian';
    case Student = 'student';
    case Other = 'other';
}
