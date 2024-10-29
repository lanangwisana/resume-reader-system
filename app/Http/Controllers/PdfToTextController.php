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
use App\Libraries\ExperienceLib;

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
        // Simpan file PDF yang diupload
        $pdfFile = $request->file('pdf_file');
        $pdfPath = $pdfFile->getPathName();
        // Inisialisasi array untuk menampung error
        $errors = [];
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
             // Eksekusi extractWorkExperience
            $this->extractWorkExperience($text, $errors);
            // Eksekusi extractProjects
            // $this->extractProjects($text, $errors);
            // Eksekusi extractCompetitions
            // $this->extractCompetition($text, $errors);
            // Eksekusi extractSertifikasi
            $this->sertifikatController->extractSertifikat($text, $errors);
            // Jika ada error, tampilkan di view
            if (!empty($errors)) {
                return view('result', [
                    'text' => $text,
                    'errors' => $errors,
                ]);
            }
            // Jika berhasil
            return view('result', [
                'text' => $text,
                'success' => 'Data berhasil diproses'
            ]);
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'PDF parsing failed: ' . $e->getMessage()])
                ->withInput();
        }
    }
    
    private function extractWorkExperience($text, &$errors) {
        $patternWorkExperience = '/Work experience\s*(?P<content>.*?)\s*(?=Projects|Project|Competitions|Competition|Certificates|Skills|$)/s';
        if (preg_match($patternWorkExperience, $text, $matchs)) {
            $workExperienceText = $matchs['content'];
            // Pattern untuk menangkap detail work experience
            $patternDetail = '/(?P<name_intitutions>[^\n]+)\s*(?P<startdate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<enddate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*(?P<posisi>[^\n]+)/';
            if (preg_match_all($patternDetail, $workExperienceText, $matchs, PREG_SET_ORDER)) {
                // dd($matchs);
                foreach ($matchs as $match) {
                    $posisi = trim($match['posisi']);
                    $name_intitutions = trim($match['name_intitutions']);
                    $startdate = trim($match['startdate']);
                    $enddate = trim($match['enddate']); 
                    $kategori = 'profesional';
                    // Validasi format end date setelah data ditangkap
                    if (!preg_match('/^[a-zA-Z]{3}\s+\d{4}$/', $enddate)) {
                        $errors[] = "End Date harus memiliki format MMM YYYY. Data yang tidak valid berada pada: $name_intitutions, $posisi (Format yang ditemukan: $startdate - $enddate)";
                        continue;
                    }
                    // Validasi dan pemrosesan start date
                    try {
                        // Handle kasus dimana start date valid dengan format MMM YYYY.
                        if (preg_match('/^[a-zA-Z]{3}\s+\d{4}$/', $startdate)) {
                            $startMonth =  substr($startdate,0, 3); // Mengambil bulan startdate.
                            $endMonth = substr($enddate,0,3); // Mengambil bulan enddate.
                            // Lakukan pengecekan nama bulan startdate dan enddate.
                            $startMonthIndex = array_search($startMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            $endMonthIndex = array_search($endMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            //  Penanganan error untuk nama bulan yang tidak valid
                            if($startMonthIndex === false || $endMonthIndex === false){
                                $errors[] = "Format bulan tidak valid. Data yang tidak valid berada pada: $name_intitutions, $posisi (Format yang ditemukan: $startdate, $enddate)";
                                continue;
                            }
                            // Proses perubahan format date pada bagian startdate dan enddate.
                            $startdate = Carbon::parse($startdate)->format("Y-m-d");
                            $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        } else {
                            // Handle kasus dimana start date hanya bulan
                            $endYear = (int)substr($enddate, -4); // Ambil tahun pada enddate.
                            $endMonth = substr($enddate, 0, 3); // Ambil bulan pada enddate.
                            // Lakukan pengecekan nama bulan startdate dan enddate.
                            $startMonthIndex = array_search($startdate, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            $endMonthIndex = array_search($endMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            //  Penanganan error untuk nama bulan yang tidak valid
                            if ($startMonthIndex === false || $endMonthIndex === false) {
                                $errors[] = "Format bulan tidak valid. Data yang tidak valid berada pada: $name_intitutions, $posisi (Format yang ditemukan: $startdate, $enddate)";
                                continue;
                            }
                            // Pemberian tahun pada bagian startdate.
                            if ($startMonthIndex > $endMonthIndex) {
                                $startYear = $endYear - 1; 
                            } else {
                                $startYear = $endYear;
                            }
                            $startdate = $startdate . ' ' . $startYear;
                            // Proses perubahan format date.
                            $startdate = Carbon::parse($startdate)->format("Y-m-d");
                            $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        }
                        // Simpan data kedalam database.
                        Experience::firstOrCreate([
                            'posisi' => $posisi, 
                            'name_intitutions' => $name_intitutions, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate,
                            'kategori' => $kategori
                        ]);
    
                    } catch (Exception $e) {
                        $errors[] = "Tedapat format yang tidak valid pada: $name_intitutions, $posisi";
                    }
                }
            } else {
                $errors[] = "Detail Work Experience tidak ditemukan dalam dokumen.";
            }
        } else {
            $errors[] = "Bagian Work Experience tidak ditemukan dalam dokumen.";
        }
    }
    
    private function extractProjects($text, &$errors) {
        // Mencari data untuk bagian project
        $patternProject = '/Project\s*(?P<content>.*?)\s*(?=Work Experiences|Work Experience|Work experiences|Work experience|work Experiences|work Experience|work experiences|work experience|Competitions|Competition|competitions|competition|Certificates|Certificate|certificates|certificate|Skills|Skill|skills|skill|$)/si';
        if(preg_match($patternProject, $text, $matches)){
            $projectText = $matches['content'];
            // dd($projectText); 
            $patternDetail = '/(?P<nama>[^\n]+)\s*(?P<startdate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<enddate>[a-zA-Z]{3}\s*(?:\d{4})?)\s*(?P<posisi>[^\n]+)/i';
            if(preg_match_all($patternDetail, $projectText, $matches, PREG_SET_ORDER)){
                // dd($matches);
                foreach($matches as $match){
                    $nama = trim($match['nama']);
                    $posisi = trim($match['posisi']);
                    $startdate = trim($match['startdate']);
                    $enddate = trim($match['enddate']);
                    $kategori = 'project';
                     // Validasi end date
                    if(!preg_match('/^[a-zA-Z]{3}\s+\d{4}$/', trim($enddate))){
                        $errors[] = "End Date harus memiliki format MMM YYYY. Data yang tidak valid berada pada: $nama, $posisi (Format yang ditemukan: $startdate - $enddate)";
                        continue;
                    }
                    // Validasi dan pemrosesan start date
                    try{
                        // Handle kasus dimana start date valid dengan format MMM YYYY.
                        if (preg_match('/^[a-zA-Z]{3}\s+\d{4}$/', $startdate)) {
                            $startMonth = substr($startdate, 0, 3); // Mengambil bagian bulan pada startdate.
                            $endMonth = substr($enddate, 0, 3); // Mengambil bagian bulan pada enddate.
                            // Lakukan pengecekan pada bulan startdate dan enddate.
                            $startMonthIndex = array_search($startMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            $endMonthIndex = array_search($endMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            // Penanganan error pada nama bulan yang tidak valid.
                            if($startMonthIndex === false || $endMonthIndex === false){
                                $errors[] = "Format bulan tidak valid. Data yang tidak valid berada pada: $nama, $posisi. (Format yang tidak valid: $startdate - $enddate";
                                continue;
                            }
                            // Proses perubahan format date pada startdate dan enddate.
                            $startdate = Carbon::parse($startdate)->format("Y-m-d");
                            $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        } else {
                            // Handle kasus dimana start date hanya bulan
                            $endYear = (int)substr($enddate, -4); // Mengambil tahun pada enddate.
                            $endMonth = substr($enddate, 0, 3); // Mengambil bulan pada enddate.
                            // Melakukan pengecekan kesesuaian bulan pada startdate dan enddate
                            $startMonthIndex = array_search($startdate, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            $endMonthIndex = array_search($endMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            // Penanganan error pada nama bulan yang tidak valid
                            if ($startMonthIndex === false || $endMonthIndex === false) {
                                $errors[] = "Format bulan tidak valid. Data yang tidak valid berada pada: $nama, $posisi. (Format yang tidak valid: $startdate - $enddate";
                                continue;
                            }
                            // Proeses penambahan tahun untuk start date yang tidak memiliki tahun 
                            if ($startMonthIndex > $endMonthIndex) {
                                $startYear = $endYear - 1;
                            } else {
                                $startYear = $endYear;
                            }
                            $startdate = $startdate . ' ' . $startYear;
                            // Proses perubahan format date pada startdate dan enddate.
                            $startdate = Carbon::parse($startdate)->format("Y-m-d");
                            $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        }
                        // Simpan ke database
                        Experience::firstOrCreate([
                            'nama' => $nama, 
                            'posisi' => $posisi, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate,
                            'kategori' => $kategori
                        ]);
                    } catch (Exception $e){
                        $errors[] = "Tedapat format yang tidak valid pada: $nama, $posisi";
                    }
                }
            } else {
                $errors[] = "Detail Project tidak ditemukan dalam dokumen.";
            }
        } else {
            $errors[] = "Bagian Project tidak ditemukan dalam dokumen.";
        }
    }

    private function extractCompetition($text, &$errors){
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
                    if(!preg_match('/^[a-zA-Z]{3}\s+\d{4}$/', $enddate)){
                        // dd($enddate);
                        $errors[] = "End Date harus memiliki format MMM YYYY. Data yang tidak valid berada pada: $nama, $name_intitutions (Format yang ditemukan: $startdate - $enddate)";
                        continue;
                    }
                    // Lakukan validasi startdate
                    try{
                        if(preg_match("/^[a-zA-Z]{3}\s+\d{4}$/", $startdate)){
                            $startMonth = substr($startdate,0,3); // Mengambil bulan pada startdate.
                            $endMonth = substr($enddate, 0,3); // Mengambil bulan pada enddate.
                            // Lakukan pengecekan kesesuaian nama bulan pada startdate dan enddate.
                            $startMonthIndex = array_search($startMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            $endMonthIndex = array_search($endMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            // Lakukan penanganan error jika nama bulan pada startdate dan enddate tidak sesuai.
                            if ($startMonthIndex === false || $endMonthIndex === false){
                                $errors[] = "Format bulan tidak valid. Data yang tidak valid berada pada: $nama, $name_intitutions. (Format yang ditemukan: $startdate - $enddate)";
                                continue;
                            }
                            // Lakukan perubahan date format 
                            $startdate = Carbon::parse($startdate)->format("Y-m-d");
                            $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        } else{
                            // Handle khasus untuk strat date hanya bulan
                            $endYear =(int)substr($enddate, -4); // Ambil tahun dari end date
                            $endMonth = substr($enddate, 0, 3); // Ambil bulan dari end_date
                            // Lakukan pengecekan kesesuaian nama startdate dan enddate.
                            $startMonthIndex = array_search($startdate, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            $endMonthIndex = array_search($endMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            // Penanganan error untuk bulan yang tidak valid
                            if ($startMonthIndex === false || $endMonthIndex === false) {
                                $errors[] = "Format bulan tidak valid. Data yang tidak valid berada pada: $nama, $name_intitutions. (Format yang ditemukan: $startdate - $enddate)";
                                continue;
                            }
                            // Penanganan kasus dimana start date lebih besar daripada end date
                            if ($startMonthIndex > $endMonthIndex) {
                                $startYear = $endYear - 1;
                            } else {
                                $startYear = $endYear;
                            }
                            $startdate = $startdate . ' ' . $startYear;
                            // Proses perubahan format date pada startdate dan enddate.
                            $startdate = Carbon::parse($startdate)->format("Y-m-d");
                            $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        }
                        // Simpan ke database
                        Experience::firstOrCreate([
                            'nama' => $nama, 
                            'name_intitutions' => $name_intitutions, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate, 
                            'prestasi' => $prestasi,
                            'kategori' => $kategori
                        ]);
                    } catch(Exception $e){
                        $errors[] = "Tedapat format yang tidak valid pada: $nama, $name_intitutions";
                    } 
                }
            } else{
                $errors[] = "Detail Competition tidak ditemukan dalam dokumen.";
            }
        } else{
            $errors[]= "Bagian Competition tidak ditemukan dalam dokumen.";
        }
    }
}