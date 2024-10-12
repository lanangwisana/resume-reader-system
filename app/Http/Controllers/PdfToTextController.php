<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Models\Competition;
use App\Models\Project;
use App\Models\WorkExperience;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

class PdfToTextController extends Controller
{
    protected $workExperienceController;
    protected $projectController;
    protected $competitionController;
    public function index() {
        return view('index');
    }

    public function __construct(
        ExtractWorkExperienceController $workExperienceController,
        ExtractProjectController $projectController,
        ExtractCompetitionController $competitionController,
    ){
        $this->workExperienceController = $workExperienceController;
        $this->projectController = $projectController;
        $this->competitionController = $competitionController;
    }
    public function extractText(Request $request) {
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

        if ($text) {
            $this->workExperienceController->extractWorkExperience($text);
            $this->projectController->extractProject($text);
            $this->competitionController->extractCompetition($text);
            return view('result', [
                'text' => $text,
            ]);
        } else {
            return view('result', ['error' => 'Text extraction failed']);
        }
        // dd($text);
        // Fungsi Ekstraksi untuk Certificate
        // $this->extractCertificate($text);
        // Tampilkan teks yang diekstrak ke halaman
        // return view('result', ['text' => $text]);
    }


    private function extractCertificate($text){
        $patternCertificate = '/Certificate\s*(?P<content>.*?)\s*(?=Work Experiences|Work Experience|Work experiences|Work experience|work Experiences|work Experience|work experiences|work experience|Projects|Project|projects|project|Competitions|Competition|competitions|competition|Skills|Skill|skills|skill|$)/si';

        if(preg_match($patternCertificate, $text, $matches)){
            $CertificateText = $matches['content'];
            // dd($CertificateText);
            $patternDetail = '/(?P<certification_name>[^\n]+?)\s*(-\s*[^\n]*)?\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<end_date>[a-zA-Z]{3}\s*\d{4})\s*(?P<organizer>[^\n]+)/i';
            if(preg_match_all($patternDetail, $CertificateText, $matches, PREG_SET_ORDER)){
                // dd($matches);  
                foreach($matches as $match){
                    $certification_name = trim($match['certification_name']);
                    $organizer = trim($match['organizer']);
                    $start_date = $match['start_date'];
                    $end_date = $match['end_date'];

                    if(!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $end_date)){
                        echo "End Date harus memiliki format tanggal MMM YYYY. \n";
                        continue;
                    }
                    // Validasi start date
                    if(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $start_date)){
                        // Start date sudah sesuai dengan format MMM YYYY
                        // Simpan ke database
                        Certification::create
                        ([
                            'certification_name' => $certification_name, 
                            'organizer' => $organizer, 
                            'start_date' => $start_date, 
                            'end_date' => $end_date
                        ]);
                    } elseif(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}$/', $start_date)){
                        // Ambil tahun dari end date
                        $endYear =(int)substr($end_date, -4);
                        // Ambil bulan dari end_date
                        $endMonth = substr($end_date, 0, 3);

                        // Logika untuk menentukan tahun pada start_date
                        $month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                        $startMonthIndex = array_search($start_date, $month);
                        $endMonthIndex = array_search($endMonth, $month);

                        if ($startMonthIndex > $endMonthIndex) {
                            $startYear = $endYear - 1;
                        } else {
                            $startYear = $endYear;
                        }

                        // Tambahkan tahun kepada start_date
                        $start_date = $start_date . ' ' . $startYear;
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
        } 
        else{
            echo "Bagian Certificate tidak ditemukan.";
        }
    }
}