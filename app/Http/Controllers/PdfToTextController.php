<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Models\Competition;
use App\Models\Experience;
use App\Models\Project;
use App\Models\WorkExperience;
use Carbon\Carbon;
use Dotenv\Validator;
use Exception;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\Validator as ValidationValidator;
use Smalot\PdfParser\Parser;

class PdfToTextController extends Controller
{
    public function index() {
        return view('index');
    }

    protected $sertifikatController;

    public function __construct(SertifikatController $sertifikatController) {
        $this->sertifikatController = $sertifikatController;
    }

    public function extractText(Request $request) {
        $validator = FacadesValidator::make($request->all(), [
            'pdf_file' => 'required|mimes:pdf|max:2048',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }
    
        // Simpan file PDF yang diupload jika sudah memenuhi requirements
        $pdfFile = $request->file('pdf_file');
        $pdfPath = $pdfFile->getPathName();
        
        // Inisialisasi array untuk menampung error
        $errors = [];

        // Menggunakan PDF Parser untuk ekstraksi teks
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            // Eksekusi setiap method ekstraksi dan catat error jika ada
            $this->extractWorkExperience($text, $errors);
            // $this->extractProjects($text, $errors);
            // $this->extractCompetition($text, $errors);
            // $this->sertifikatController->extractSertifikat($text, $errors);
            // Cek apakah ada error
            if (!empty($errors)) {
                // Mengirimkan error yang terkumpul ke tampilan
                return view('result', ['text'=>$text, 'errors' => $errors]);
            }
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => 'PDF parsing failed: ' . $e->getMessage()])->withInput();
        }    
        // Jika tidak ada error, tampilkan hasil ekstraksi
        return view('result', ['text' => $text]);
    }
    
    // public function extractText(Request $request) {
    //     $validator = FacadesValidator::make($request->all(), [
    //         'pdf_file' => 'required|mimes:pdf|max:2048',
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator);
    //     }
    //     // Simpan file PDF yang diupload jika sudah memenuhi requirements
    //     $pdfFile = $request->file('pdf_file');
    //     $pdfPath = $pdfFile->getPathName();

    //     // Menggunakan PDF Parser untuk ekstraksi teks
    //     try{
    //         $parser = new Parser();
    //         $pdf = $parser->parseFile($pdfPath);
    //         $text = $pdf->getText();
    //     } catch(Exception $e){
    //         return redirect()->back()->withErrors(['error' => 'PDF parsing failed: ' . $e->getMessage()])->withInput();
    //     }

    //     $this->extractWorkExperience($text);
    //     $this->extractProjects($text);
    //     $this->extractCompetition($text);
    //     $this->sertifikatController->extractSertifikat($text);
    //     return view('result', ['text'=>$text]);
    // }

    private function extractWorkExperience($text, &$errors) {
        // Mencari data untuk bagian work experience
        $patternWorkExperience = '/Work experience\s*(?P<content>.*?)\s*(?=Projects|Project|Competitions|Competition|Certificates|Skills|$)/s';
    
        if (preg_match($patternWorkExperience, $text, $matchs)) {
            $workExperienceText = $matchs['content'];
            // dd($workExperienceText);
            $patternDetail = '/(?P<name_intitutions>[^\n]+)\s*(?P<startdate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<enddate>[a-zA-Z]{3}\s+\d{4})\s*(?P<posisi>[^\n]+)/';
    
            if (preg_match_all($patternDetail, $workExperienceText, $matchs, PREG_SET_ORDER)) {
                dd($matchs);
                foreach ($matchs as $match) {
                    $posisi = trim($match['posisi']);
                    $name_intitutions = trim($match['name_intitutions']);
                    $startdate = $match['startdate'];
                    $enddate = $match['enddate']; 
                    $kategori = 'profesional';
                    // dd($match);

                    // Validasi format end date
                    if (!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $enddate)) {
                        // Catat error tetapi lanjutkan proses
                        $errors[] = "End Date harus memiliki format MMM YYYY. Data: $name_intitutions, $posisi";
                        // dd($errors);
                        continue; // Lanjut ke data berikutnya
                    }
    
                    // Validasi format start date
                    if (preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $startdate)) {
                        // Format valid, ubah jadi yyyy-mm-dd
                        $startdate = Carbon::parse($startdate)->format("Y-m-d");
                        $enddate = Carbon::parse($enddate)->format("Y-m-d");
    
                        Experience::create([
                            'posisi' => $posisi, 
                            'name_intitutions' => $name_intitutions, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate,
                            'kategori' => $kategori
                        ]);
                    } else {
                        // Penanganan jika hanya bulan ditemukan tanpa tahun
                        $endYear = (int)substr($enddate, -4);
                        $endMonth = substr($enddate, 0, 3);
                        $startMonthIndex = array_search($startdate, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                        $endMonthIndex = array_search($endMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
    
                        if ($startMonthIndex > $endMonthIndex) {
                            $startYear = $endYear - 1;
                        } else {
                            $startYear = $endYear;
                        }
    
                        $startdate = $startdate . ' ' . $startYear;
                        $startdate = Carbon::parse($startdate)->format("Y-m-d");
                        $enddate = Carbon::parse($enddate)->format("Y-m-d");
    
                        Experience::create([
                            'posisi' => $posisi, 
                            'name_intitutions' => $name_intitutions, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate,
                            'kategori' => $kategori
                        ]);
                    }
                }
            } else{
                // Jika detail work experience tidak ditemukan
                $errors[] = "Detail Work Experience tidak ditemukan dalam dokumen.";
            }
        } else {
            // Jika work experience tidak ditemukan
            $errors[] = "Bagian Work Experience tidak ditemukan dalam dokumen.";
        }
    }
    
    // private function extractWorkExperience($text) {
    //     // Mencari data untuk begian work experience
    //     $patternWorkExperience = '/Work experience\s*(?P<content>.*?)\s*(?=Projects|Project|projects|project|Competitions|Competition|competitions|competition|Certificates|Certificate|certificates|certificate|Skills|Skill|$)/s';

    //     if(preg_match($patternWorkExperience, $text, $matchs)){
    //         $workExperienceText = $matchs['content'];

    //         // Pattern khusus menagmbil data penting bagian work experience
    //         $patternDetail = '/(?P<name_intitutions>[^\n]+)\s*(?P<startdate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<enddate>[a-zA-Z]{3}\s+\d{4})\s*(?P<posisi>[^\n]+)/';

    //         if(preg_match_all($patternDetail, $workExperienceText, $matchs, PREG_SET_ORDER)){
    //             // dd($matchs);
    //             foreach($matchs as $match){
    //                 $posisi = trim($match['posisi']);
    //                 $name_intitutions = trim($match['name_intitutions']);
    //                 $startdate = $match['startdate'];
    //                 $enddate = $match['enddate']; 
    //                 $kategori = 'profesional';

    //                 // Lakukan validasi pada bagian end date agar memiliki format MMM YYYY
    //                 if(!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $enddate)){
    //                     echo("End Date harus memiliki format MMM YYYY. Data: $name_intitutions, $posisi");
    //                     continue;
    //                 } 

    //                 // Lakukan validasi pada bagian start date agar memiliki format MMM YYYY.
    //                 if(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $startdate)){
    //                     // Ubah format tanggal menjadi yyyy-mm-dd
    //                     $startdate = Carbon::parse($startdate)->format("Y-m-d");
    //                     $enddate = Carbon::parse($enddate)->format("Y-m-d");
    //                     // Simpan ke database
    //                     Experience::create
    //                     ([
    //                         'posisi' => $posisi, 
    //                         'name_intitutions' => $name_intitutions, 
    //                         'startdate' => $startdate, 
    //                         'enddate' => $enddate,
    //                         'kategori' => $kategori
    //                     ]);
    //                 }  elseif(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}$/', $startdate)){
    //                 // Ambil tahun dari end date
    //                 $endYear =(int)substr($enddate, -4);
    //                 // Ambil bulan dari end_date
    //                 $endMonth = substr($enddate, 0, 3);
        
    //                 // Logika untuk menentukan tahun pada start_date
    //                 $month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
    //                 $startMonthIndex = array_search($startdate, $month);
    //                 $endMonthIndex = array_search($endMonth, $month);
        
    //                 if ($startMonthIndex > $endMonthIndex) {
    //                     $startYear = $endYear - 1;
    //                 } else {
    //                     $startYear = $endYear;
    //                 }
        
    //                 // Tambahkan tahun kepada start_date
    //                 $startdate = $startdate . ' ' . $startYear;

    //                 // Ubah format tanggal menjadi yyyy-mm-dd
    //                 $startdate = Carbon::parse($startdate)->format("Y-m-d");
    //                 $enddate = Carbon::parse($enddate)->format("Y-m-d");
    //                 // Simpan ke database
    //                 Experience::create
    //                     ([
    //                         'posisi' => $posisi, 
    //                         'name_intitutions' => $name_intitutions, 
    //                         'startdate' => $startdate, 
    //                         'enddate' => $enddate,
    //                         'kategori' => $kategori
    //                     ]);
    //                 }
    //             }
    //         }
    //     }
    // }

    
    private function extractProjects($text) {
        // Mencari data untuk bagian project
        $patternProject = '/Project\s*(?P<content>.*?)\s*(?=Work Experiences|Work Experience|Work experiences|Work experience|work Experiences|work Experience|work experiences|work experience|Competitions|Competition|competitions|competition|Certificates|Certificate|certificates|certificate|Skills|Skill|skills|skill|$)/si';
        
        if(preg_match($patternProject, $text, $matches)){
            $projectText = $matches['content'];
            // dd($projectText); 

            $patternDetail = '/(?P<nama>[^\n]+?)\s*(-\s*[^\n]*)?\s*(?P<startdate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<enddate>[a-zA-Z]{3}\s*\d{4})\s*(?P<posisi>[^\n]+)/i';

            if(preg_match_all($patternDetail, $projectText, $matches, PREG_SET_ORDER)){
                // dd($matches);
                foreach($matches as $match){
                    $nama = trim($match['nama']);
                    $posisi = trim($match['posisi']);
                    $startdate = $match['startdate'];
                    $enddate = $match['enddate'];
                    $kategori = 'project';

                     // Validasi end date
                    if(!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $enddate)){
                        echo "End Date harus memiliki format tanggal MMM YYYY. \n";
                        continue;
                    }
                    // Validasi start date
                    if(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $startdate)){
                        // Start date sudah sesuai dengan format MMM YYYY
                        // Ubah format tanggal menjadi yyyy-mm-dd
                        $startdate = Carbon::parse($startdate)->format("Y-m-d");
                        $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        // Simpan ke database
                        Experience::create
                        ([
                            'nama' => $nama, 
                            'posisi' => $posisi, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate,
                            'kategori' => $kategori
                        ]);
                    } elseif(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}$/', $startdate)){
                        // Ambil tahun dari end date
                        $endYear =(int)substr($enddate, -4);
                        // Ambil bulan dari end_date
                        $endMonth = substr($enddate, 0, 3);

                        // Logika untuk menentukan tahun pada start_date
                        $month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                        $startMonthIndex = array_search($startdate, $month);
                        $endMonthIndex = array_search($endMonth, $month);

                        if($startMonthIndex > $endMonthIndex) {
                            $startYear = $endYear - 1;
                        } else {
                            $startYear = $endYear;
                        }

                        // Tambahkan tahun kepada start_date
                        $startdate = $startdate . ' ' . $startYear;

                        // Ubah format tanggal menjadi yyyy-mm-dd
                        $startdate = Carbon::parse($startdate)->format("Y-m-d");
                        $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        // Simpan ke database
                        Experience::create
                        ([
                            'nama' => $nama, 
                            'posisi' => $posisi, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate,
                            'kategori' => $kategori
                        ]);
                    } 
                }
            } 
        }
    }

    private function extractCompetition($text){
        $patternCompetition = '/Competition\s*(?P<content>.*?)\s*(?=Work Experiences|Work Experience|Work experiences|Work experience|work Experiences|work Experience|work experiences|work experience|Projects|Project|projects|project|Certificates|Certificate|certificates|certificate|Skills|Skill|skills|skill|$)/s';

        if(preg_match($patternCompetition, $text, $matches)){
            $competitionText = $matches['content'];
            // dd($competitionText);
            $patternDetail ='/(?P<nama>[^\n]+?)\s*-\s*(?P<name_intitutions>[^\n]+?)\s*(?P<startdate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<enddate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*(?P<prestasi>[^\n]+)/i';
            if(preg_match_all($patternDetail, $competitionText, $matches, PREG_SET_ORDER)){
                // dd($matches);
                foreach($matches as $match){
                    $nama = trim($match['nama']);
                    $name_intitutions = trim($match['name_intitutions']);
                    $startdate = $match['startdate'];
                    $enddate = $match['enddate'];
                    $prestasi = trim($match['prestasi']);
                    $kategori = 'competition';

                    // Validasi end date
                    if(!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $enddate)){
                        echo "End Date harus memiliki format tanggal MMM YYYY. \n";
                        continue;
                    }
                    // Validasi start date
                    if(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $startdate)){
                        // Start date sudah sesuai dengan format MMM YYYY
                        $startdate = Carbon::parse($startdate)->format("Y-m-d");
                        $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        // Simpan ke database
                        Experience::create
                        ([
                            'nama' => $nama, 
                            'name_intitutions' => $name_intitutions, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate, 
                            'prestasi' => $prestasi,
                            'kategori' => $kategori
                        ]);
                    } elseif(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}$/', $startdate)){
                        // Ambil tahun dari end date
                        $endYear =(int)substr($enddate, -4);
                        // Ambil bulan dari end_date
                        $endMonth = substr($enddate, 0, 3);

                        // Logika untuk menentukan tahun pada start_date
                        $month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                        $startMonthIndex = array_search($startdate, $month);
                        $endMonthIndex = array_search($endMonth, $month);

                        if ($startMonthIndex > $endMonthIndex) {
                            $startYear = $endYear - 1;
                        } else {
                            $startYear = $endYear;
                        }

                        // Tambahkan tahun kepada start_date
                        $startdate = $startdate . ' ' . $startYear;
                        
                        $startdate = Carbon::parse($startdate)->format("Y-m-d");
                        $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        // Simpan ke database
                        Experience::create
                        ([
                            'nama' => $nama, 
                            'name_intitutions' => $name_intitutions, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate, 
                            'prestasi' => $prestasi,
                            'kategori' => $kategori
                        ]);
                    } 
                }
            }
        } 
    }
}