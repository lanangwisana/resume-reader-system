<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Models\Competition;
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
        // Fungsi Ekstraksi untuk Competition
        $this->extractCompetition($text); 
        // Fungsi Ekstraksi untuk Certificate
        $this->extractCertificate($text);
        // Tampilkan teks yang diekstrak ke halaman
        return view('result', ['text' => $text]);
    }

    private function extractWorkExperience($text){
        $patternWorkExperience = '/Work experience\s*(?P<content>.*?)\s*(?=Projects|Project|$)/si';
        $patternDetail = '/(?P<company>[^\n]+)\s*-\s*[^\n]*\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<end_date>[a-zA-Z]{3}\s+\d{4})\s*(?P<position>[^\n]+)/';
        
        if (preg_match($patternWorkExperience, $text, $matches)) 
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
            else 
            {
                echo "Bagian Work Experience tidak ditemukan.";
            }
        }
    }

    private function extractProject($text){
        $patternProject = '/Project\s*(?P<content>.*?)\s*(?=Competition|competition|$)/si';
        $patternDetail = '/(?P<project_name>[^\n]+?)\s*(-\s*[^\n]*)?\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<end_date>[a-zA-Z]{3}\s*\d{4})\s*(?P<role>[^\n]+)/i';

        if(preg_match($patternProject, $text, $matches))
        {
            $projectText = $matches['content'];
            // dd($projectText); 
            if(preg_match_all($patternDetail, $projectText, $matches, PREG_SET_ORDER))
            {
                // dd($matches);
                foreach($matches as $match)
                {
                    $project_name = trim($match['project_name']);
                    $role = trim($match['role']);
                    $start_date = $match['start_date'];
                    $end_date = $match['end_date'];

                    // Simpan ke database
                    Project::create
                    ([
                        'project_name' => $project_name, 
                        'role' => $role, 
                        'start_date' => $start_date, 
                        'end_date' => $end_date
                    ]);
                }
            } else {
                echo "Bagian Project tidak ditemukan.";
            }
        }
    }

    private function extractCompetition($text){
        $patternCompetition = '/Competition\s*(?P<content>.*?)\s*(?=certificate|Certificate|$)/s';

        if(preg_match($patternCompetition, $text, $matches)){
            $competitionText = $matches['content'];
            // dd($competitionText);
            $patternDetail ='/(?P<competition_name>[^\n]+?)\s*-\s*(?P<organizer>[^\n]+?)\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<end_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*(?P<achievement>[^\n]+)/i';
            if(preg_match_all($patternDetail, $competitionText, $matches, PREG_SET_ORDER)){
                // dd($matches);
                foreach($matches as $match){
                    $competition_name = trim($match['competition_name']);
                    $organizer = trim($match['organizer']);
                    $start_date = $match['start_date'];
                    $end_date = $match['end_date'];
                    $achievement = trim($match['achievement']);

                    // Simpan ke database
                    Competition::create
                    ([
                        'competition_name' => $competition_name, 
                        'organizer' => $organizer, 
                        'start_date' => $start_date, 
                        'end_date' => $end_date, 
                        'achievement' => $achievement
                    ]);
                }
            }
        } else {
            echo "Bagian Competition tidak ditemukan.";
        }
    }

    private function extractCertificate($text){
        $patternCertificate = '/Certificate\s*(?P<content>.*?)\s*(?=Skill|Skills|$)/si';

        if(preg_match($patternCertificate, $text, $matches)){
            $CertificateText = $matches['content'];
            // dd($CertificateText);
            $patternDetail = '/(?P<certification_name>[^\n]+?)\s*-\s*(?P<organizer>[^\n]+?)\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<end_date>[a-zA-Z]{3}\s*\d{4})/i';
            if(preg_match_all($patternDetail, $CertificateText, $matches, PREG_SET_ORDER)){
                dd($matches);  
                foreach($matches as $match){
                    $certification_name = trim($match['certification_name']);
                    $organizer = trim($match['organizer']);
                    $start_date = $match['start_date'];
                    $end_date = $match['end_date'];
                    // Simpan ke database
                    Certification::create
                    ([
                        'certification_name' => $certification_name, 
                        'organizer' => $organizer, 
                        'start_date' => $start_date, 
                        'end_date' => $end_date
                    ]);
                }
            }
        } 
        else{
            echo "Bagian Certificate tidak ditemukan.";
        }
    }
}