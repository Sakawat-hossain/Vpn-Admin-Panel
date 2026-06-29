<?php

namespace App\Http\Controllers\Frontend\Storage;

use App\Http\Controllers\Controller;
use Exception;
use Storage;
use Str;

class AmazonController extends Controller
{
    public static function setCredentials($data)
    {
        setEnv('AWS_ACCESS_KEY_ID', $data->credentials->access_key_id);
        setEnv('AWS_SECRET_ACCESS_KEY', $data->credentials->secret_access_key);
        setEnv('AWS_DEFAULT_REGION', $data->credentials->default_region);
        setEnv('AWS_BUCKET', $data->credentials->bucket);
        setEnv('AWS_URL', $data->credentials->url);
    }

    public static function upload($file)
    {
        try {
            $filename = Str::random(15) . '_' . time() . '.' . imageExtension();
            $location = "images/";
            $path = $location . $filename;
            $disk = Storage::disk('s3');
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
        $disk = Storage::disk('s3');
        $image = $disk->get($path);
        $response = \Response::make($image, 200);
        $response->header("Content-Type", $disk->mimeType($image));
        return $response;
    }

    public static function download($generatedImage)
    {
        try {
            $disk = Storage::disk('s3');
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
        $disk = Storage::disk('s3');
        if ($disk->has($filePath)) {
            $disk->delete($filePath);
        }
        return true;
    }
}
