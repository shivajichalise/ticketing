<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Facades\AuditLogger;
use App\Models\Upload;
use App\Traits\RespondsWithJson;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class UploadController extends Controller
{
    use RespondsWithJson;

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $file = $request->file('file');

        $mime = $file->getMimeType();
        if ($mime !== 'application/pdf') {
            return $this->error(
                new HttpException(
                    422,
                    'Invalid file type. Only PDF files are allowed',
                ),
                'Invalid file type. Only PDF files are allowed',
                422,
            );
        }

        $path = $file->store('uploads/pdfs', 'local');

        $upload = Upload::create([
            'user_id' => $request->jwt_user_id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
        ]);

        AuditLogger::log(
            $request->jwt_user_id,
            'pdf_uploaded',
            $upload,
            [
                'filename' => $upload->original_name,
                'size_kb' => round($file->getSize() / 1024, 2),
                'path' => $upload->path,
            ]
        );

        return $this->success(
            [
                'path' => $upload,
            ],
            'PDF uploaded successfully',
            201
        );
    }
}
