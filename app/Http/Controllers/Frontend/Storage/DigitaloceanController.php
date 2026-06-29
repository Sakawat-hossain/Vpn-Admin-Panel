<?php

namespace App\Http\Controllers\Frontend\Storage;

use App\Http\Controllers\Controller;
use Exception;
use Storage;
use Str;

class DigitaloceanController extends Controller
{
    public static function setCredentials($data)
    {
        setEnv('DGL_SPACES_KEY', $data->credentials->spaces_key);
        setEnv('DGL_SPACES_SECRET', $data->credentials->spaces_secret);
        setEnv('DGL_SPACES_ENDPOINT', $data->credentials->spaces_endpoint);
        setEnv('DGL_SPACES_REGION', $data->credentials->spaces_region);
        setEnv('DGL_SPACES_BUCKET', $data->credentials->spaces_bucket);
    }

    public static function upload($file)
    {
        try {
            $filename = Str::random(15) . '_' . time() . '.' . imageExtension();
            $location = "images/";
            $path = $location . $filename;
            $disk = Storage::disk('digitalocean');
            $upload = $disk->put($path, $file);
            $data['filename'] = $filename;
            $data['path'] = $path;
            return responseHandler($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public static function getFile($path)
    {
        $disk = Storage::disk('digitalocean');
        $image = $disk->get($path);
        $response = \Response::make($image, 200);
        $response->header("Content-Type", $disk->mimeType($image));
        return $response;
    }

    public static function download($generatedImage)
    {
        try {
            $disk = Storage::disk('digitalocean');
            $filePath = $disk->path($generatedImage->path);
            if ($disk->has($filePath)) {
                return $disk->temporaryUrl($filePath, now()->addHour(), [
                    'ResponseContentDisposition' => 'attachment; filename="' . $generatedImage->filename . '"',
                ]);
            } else {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    public static function delete($filePath)
    {
        $disk = Storage::disk('digitalocean');
        if ($disk->has($filePath)) {
            $disk->delete($filePath);
        }
        return true;
    }
}
