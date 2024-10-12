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

        // dd($text);
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
        // Mencari data untuk begian work experience hingga sebelum bagian project
        $patternWorkExperience = '/Work experience\s*(?P<content>.*?)\s*(?=Projects|Project|projects|project|Competitions|Competition|competitions|competition|Certificates|Certificate|certificates|certificate|Skills|Skill|$)/s';

        if (preg_match($patternWorkExperience, $text, $matches)) 
        {
            $workExperienceText = $matches['content'];
            // dd($workExperienceText);

            // $patternDetail = '/(?P<company>[^\n]+)\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<end_date>[a-zA-Z]{3}\s+\d{4})\s*(?P<position>[^\n]+)/';
            // regex yang paling mendekati.
            $patternDetail = '/(?P<company>[^\n]+)\s*(?:\t)?\s*((?P<start_date>(?:\d{1,2}\s*)?[a-zA-Z]{3,9}(?:\s+\d{4})?))\s*[-–]?\s*(?:\n\s*|\t)?(?P<end_date>(?:\d{1,2}\s*)?(?:[\n\s*])?[a-zA-Z]{3}(?:\s+\d{4}|\n\s*\d{4}))\s*(?P<position>[^\n]+)/i'; //Untuk regex ini sudah berhasil menangkap data tanggal denggan format dd MMM YYYY namun kedalanya adalah terdapat newline setelah tanggal pada bagian end_date sehingga memunculkan pola baru(regex hanya 1 tipe saja)

            if(preg_match_all($patternDetail, $workExperienceText, $matches, PREG_SET_ORDER)) 
            {
                // dd($matches);
                foreach ($matches as $match) 
                {
                    $position = trim($match['position']);
                    $company = trim($match['company']);
                    $startDate = $match['start_date'];
                    $endDate = $match['end_date'];

                    // Validasi end date
                    if(!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $endDate)){
                        echo "End Date harus memiliki format tanggal MMM YYYY. \n";
                        continue;
                    }
                    // Validasi start date
                    if(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $startDate)){
                        // Start date sudah sesuai dengan format MMM YYYY
                        // Simpan ke database
                        WorkExperience::create
                        ([
                            'position' => $position, 
                            'company' => $company, 
                            'start_date' => $startDate, 
                            'end_date' => $endDate
                        ]);
                    } elseif(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}$/', $startDate)){
                        // Ambil tahun dari end date
                        $endYear =(int)substr($endDate, -4);
                        // Ambil bulan dari end_date
                        $endMonth = substr($endDate, 0, 3);

                        // Logika untuk menentukan tahun pada start_date
                        $month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                        $startMonthIndex = array_search($startDate, $month);
                        $endMonthIndex = array_search($endMonth, $month);

                        if ($startMonthIndex > $endMonthIndex) {
                            $startYear = $endYear - 1;
                        } else {
                            $startYear = $endYear;
                        }

                        // Tambahkan tahun kepada start_date
                        $startDate = $startDate . ' ' . $startYear;
                        // Simpan ke database
                        WorkExperience::create
                        ([
                            'position' => $position, 
                            'company' => $company, 
                            'start_date' => $startDate, 
                            'end_date' => $endDate
                        ]);
                    } 
                }
            } else {
                echo "Bagian Work Experience tidak ditemukan.";
            }
        }
    }

    private function extractProject($text){
        $patternProject = '/Project\s*(?P<content>.*?)\s*(?=Work Experiences|Work Experience|Work experiences|Work experience|work Experiences|work Experience|work experiences|work experience|Competitions|Competition|competitions|competition|Certificates|Certificate|certificates|certificate|Skills|Skill|skills|skill|$)/si';

        if(preg_match($patternProject, $text, $matches))
        {
            $projectText = $matches['content'];
            // dd($projectText); 

            $patternDetail = '/(?P<project_name>[^\n]+?)\s*(-\s*[^\n]*)?\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<end_date>[a-zA-Z]{3}\s*\d{4})\s*(?P<role>[^\n]+)/i';

            if(preg_match_all($patternDetail, $projectText, $matches, PREG_SET_ORDER))
            {
                // dd($matches);
                foreach($matches as $match)
                {
                    $project_name = trim($match['project_name']);
                    $role = trim($match['role']);
                    $start_date = $match['start_date'];
                    $end_date = $match['end_date'];

                     // Validasi end date
                    if(!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $end_date)){
                        echo "End Date harus memiliki format tanggal MMM YYYY. \n";
                        continue;
                    }
                    // Validasi start date
                    if(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $start_date)){
                        // Start date sudah sesuai dengan format MMM YYYY
                        // Simpan ke database
                        Project::create
                        ([
                            'project_name' => $project_name, 
                            'role' => $role, 
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
                        Project::create
                        ([
                            'project_name' => $project_name, 
                            'role' => $role, 
                            'start_date' => $start_date, 
                            'end_date' => $end_date
                        ]);
                    } 
                }
            } else {
                echo "Bagian Project tidak ditemukan.";
            }
        }
    }

    private function extractCompetition($text){
        $patternCompetition = '/Competition\s*(?P<content>.*?)\s*(?=Work Experiences|Work Experience|Work experiences|Work experience|work Experiences|work Experience|work experiences|work experience|Projects|Project|projects|project|Certificates|Certificate|certificates|certificate|Skills|Skill|skills|skill|$)/s';

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

                    // Validasi end date
                    if(!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $end_date)){
                        echo "End Date harus memiliki format tanggal MMM YYYY. \n";
                        continue;
                    }
                    // Validasi start date
                    if(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $start_date)){
                        // Start date sudah sesuai dengan format MMM YYYY
                        // Simpan ke database
                        Competition::create
                        ([
                            'competition_name' => $competition_name, 
                            'organizer' => $organizer, 
                            'start_date' => $start_date, 
                            'end_date' => $end_date, 
                            'achievement' => $achievement
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
            }
        } else {
            echo "Bagian Competition tidak ditemukan.";
        }
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