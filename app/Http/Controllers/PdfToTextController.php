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
        $pattern = '/Work experience\s*(?P<company>[^\n]+)\s*-\s*[^\n]+\s*(?P<start_date>\w+\s+\d{4})\s*–\s*(?P<end_date>\w+\s+\d{4})\s*(?P<position>[^\n]+)/';
        preg_match($pattern, $extractedText, $matches);
        if (!empty($matches)) {
            $position = $matches['position'];
            $company = $matches['company'];
            $startDate = $matches['start_date'];
            $endDate = $matches['end_date'];
            // dd($workExperience);
            ExtractedText::create(['position' => $position, 'company' => $company, 'start_date' => $startDate, 'end_date => $endDate']);
        } else {
            echo "Bagian Work Experience tidak ditemukan.";
        }
        // Tampilkan teks yang diekstrak ke halaman
        return view('result', ['text' => $text]);
    }
}
