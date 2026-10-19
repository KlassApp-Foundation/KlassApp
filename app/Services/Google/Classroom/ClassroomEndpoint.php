<?php

namespace App\Services\Google\Classroom;

class ClassroomEndpoint
{
    public static function url(string $path): string
    {
        return rtrim((string) config('services.google.classroom_base_url'), '/').$path;
    }
}
