<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvUpload;
use App\Models\Upload;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv',
        ]);

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $filename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . date('Ymd_His') . '.csv';
        $path = $file->storeAs('uploads', $filename);
        $upload = Upload::create([
            'filename' => $filename,
            'path' => $path,
            'status' => 'pending',
        ]);

        ProcessCsvUpload::dispatch($upload);

        return response()->json([
            'message' => 'File uploaded successfully',
        ]);
    }

    public function index(Request $request)
    {
        $uploads = Upload::orderBy('created_at', 'desc')->get();
        return $uploads;
    }
}
