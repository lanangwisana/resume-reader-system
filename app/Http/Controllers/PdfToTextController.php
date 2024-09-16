<?php

namespace App\Http\Controllers;

use App\Models\ExtractedText;
use App\Models\Project;
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

        //Fungsi ekstraksi untuk Work Experience
        $this->extractWorkExperience($text);
        // Fungsi ekstraksi untuk Project
        $this->extractProject($text);
        
        // Tampilkan teks yang diekstrak ke halaman
        return view('result', ['text' => $text]);
    }

    private function extractWorkExperience($text){
        //Ekstrak text work experiences
        $extractedText = $text;
        $patternWorkExperience = '/Work experience\s*(?P<content>.*?)\s*(?=Projects|Project|$)/si';
        // pattern untuk menampilakn date dengan format MMM YYYY - MMM YYYY
        $patternDetail = '/(?P<company>[^\n]+)\s*-\s*[^\n]*\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*–\s*(?P<end_date>[a-zA-Z]{3}\s+\d{4})\s*(?P<position>[^\n]+)/';
        // pattern untuk menampilkan date dengan format MMM - MMM YYY
        $patternDetails = '/(?P<company>[^\n]+)\s*-\s*[^\n]*\s*(?P<start_date>[a-zA-Z]{3})\s*-\s*(?P<end_date>[a-zA-Z]{3}\s+\d{4})\s*(?P<position>[^\n]+)/i';

        
        if (preg_match($patternWorkExperience, $extractedText, $matches)) 
        {
            $workExperienceText = $matches['content'];
            if(preg_match_all($patternDetail, $workExperienceText, $matches, PREG_SET_ORDER)) 
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
            else 
            {
                echo "Bagian Work Experience tidak ditemukan.";
            }
        }
    }

    private function extractProject($text){
        $patternProject = '/Project\s*(?P<content>.*?)\s*(?=Competition|competition|$)/si';
        //pattern untuk menampilkan date dengan format MMM - MMM YYYY.
        $patternDetail = '/(?P<project_name>[^\n]+)\s*-\s*[^\n]*\s*(?P<start_date>[a-zA-Z]{3})\s*-\s*(?P<end_date>[a-zA-Z]{3}\s*\d{4})\s*(?P<role>[^\n]+)/i';
        //pattern untuk menampilakn date dengan format MMM YYYY - MMM YYYY
        $patternDetails = '/^(?P<project_name>[\w\s]+)\s*(?P<additional_info>[\w\s]*)\s*(?P<start_date>[a-zA-Z]{3}\d{4})\s*-\s*(?P<end_date>[a-zA-Z]{3}\d{4})?\s*(?P<role>[\w\s]+)?$/im';

        if(preg_match($patternProject, $text, $matches))
        {
            $projectText = $matches['content'];
            // dd($projectText); 
            if(preg_match_all($patternDetails, $projectText, $matches, PREG_SET_ORDER))
            {
                dd($matches);
                foreach($matches as $match)
                {
                    $project_name = trim($match['project_name']);
                    $role = trim($match['role']);
                    $start_date = $match['start_date'];
                    $end_date = $match['end_date'];
                }
                Project::create
                (['project_name' => $project_name, 'role' => $role, 'start_date' => $start_date, 'end_date' => $end_date]);
            } 
            // if (preg_match_all($patternDetails, $projectText, $matches, PREG_SET_ORDER))
            // {
            //     dd($matches);
            //     foreach($matches as $match)
            //     {
            //         $project_name = trim($match['project_name']);
            //         $role = trim($match['role']);
            //         $start_date = $match['start_date'];
            //         $end_date = $match['end_date'];
            //     }
            //     Project::create
            //     (['project_name' => $project_name, 'role' => $role, 'start_date' => $start_date, 'end_date' => $end_date]);
            // } 
            else
            {
                echo "Bagian Project tidak ditemukan.";
            }
        }
    }
}