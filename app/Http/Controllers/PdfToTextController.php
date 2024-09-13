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

        //Fungsi ekstrak work experience
        $this->extractWorkExperience($text);
        
        // Tampilkan teks yang diekstrak ke halaman
        return view('result', ['text' => $text]);
    }

    private function extractWorkExperience($text){
        //Ekstrak text work experiences
        $extractedText = $text;
        $patternWorkExperience = '/Work experience\s*(?P<content>.*?)\s*(?=Projects|Project|$)/si';
        $patternDetails = '/(?P<company>[^\n]+)\s*-\s*[^\n]*\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*–\s*(?P<end_date>[a-zA-Z]{3}\s+\d{4})\s*(?P<position>[^\n]+)/';
        $patternDetails1 = '/(?P<company>[^\n]+)\s*-\s*[^\n]*\s*(?P<start_date>[a-zA-Z]{3})\s*-\s*(?P<end_date>[a-zA-Z]{3}\s+\d{4})\s*(?P<position>[^\n]+)/i';

        
        if (preg_match($patternWorkExperience, $extractedText, $matches)) 
        {
            $workExperienceText = $matches['content'];
            if(preg_match_all($patternDetails, $workExperienceText, $matches, PREG_SET_ORDER)) 
            {
                // dd($matches);
                foreach ($matches as $match) 
                {
                    $position = trim($match['position']);
                    $company = trim($match['company']);
                    $startDate = $match['start_date'];
                    $endDate = $match['end_date'];

                    // Simpan ke database
                    ExtractedText::create
                    ([
                        'position' => $position, 
                        'company' => $company, 
                        'start_date' => $startDate, 
                        'end_date' => $endDate
                    ]);
                }
            } 
            if(preg_match_all($patternDetails1, $workExperienceText, $matches, PREG_SET_ORDER))
            { 
                // dd($matches);
                foreach ($matches as $match) 
                {
                    $position = trim($match['position']);
                    $company = trim($match['company']);
                    $startDate = $match['start_date'];
                    $endDate = $match['end_date'];

                    // Simpan ke database
                    ExtractedText::create
                    ([
                        'position' => $position, 
                        'company' => $company, 
                        'start_date' => $startDate, 
                        'end_date' => $endDate
                    ]);
                }
            } 
            else 
            {
                echo "Bagian Work Experience tidak ditemukan.";
            }
        }
    }
}
