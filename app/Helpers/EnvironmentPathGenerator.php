<?php

namespace App\Helpers;

use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\PathGenerator\PathGenerator;

// Customize the path where the image gets stored (on the local filesystem, on S3, etc)
class EnvironmentPathGenerator implements PathGenerator
{
    protected $path;

    public function __construct()
    {
        $this->path = app()->env;
        
       /* if(env('FILESYSTEM_DRIVER')=='s3')
        {
            $this->path=env('AWS_ENDPOINT');
        }*/
        $this->path = \Auth::user()->school->slug.'/files/small/';
    }

    public function getPath(Media $media): string
    {
        return $this->path . $media->id . "/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . "conversions/";
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . "responsive/";
    }
}
