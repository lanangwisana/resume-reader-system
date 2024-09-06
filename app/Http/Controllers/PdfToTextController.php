<?php

namespace App\Http\Controllers;

use App\Models\ExtractedText;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

class PdfToTextController extends Controller
{
    public function index() {
        return view('index');
    }

    public function extractText(Request $request) 
    {
        $request->validate([
            'pdf_file' => 'required|mimes:pdf|max:2048',
        ]);

        // Simpan file PDF yang diupload
        $pdfFile = $request->file('pdf_file');
        $pdfPath = $pdfFile->getPathName();

        // Menggunakan PDF Parser untuk ekstraksi teks
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();

        $extractedText = $text;
        $pattern = '/Work experience(.+?)(Project|$)/si';
        preg_match($pattern, $extractedText, $matches);

        if (!empty($matches[1])) {
            $workExperience = trim($matches[1]);
            // dd($workExperience);
            ExtractedText::create(['extracted_text' => $workExperience]);
        } else {
            echo "Bagian Work Experience tidak ditemukan.";
        }
        // Tampilkan teks yang diekstrak ke halaman
        return view('result', ['text' => $text]);
    }
}
