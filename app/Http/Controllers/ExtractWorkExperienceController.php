<?php

namespace App\Http\Controllers;

use App\Models\WorkExperience;
use App\Models\validateWorkExperience;
use Illuminate\Http\Request;

class ExtractWorkExperienceController extends Controller
{
    public function extractWorkExperience($text){
        // Mencari data untuk begian work experience hingga sebelum bagian project
        $patternWorkExperience = '/Work experience\s*(?P<content>.*?)\s*(?=Projects|Project|projects|project|Competitions|Competition|competitions|competition|Certificates|Certificate|certificates|certificate|Skills|Skill|$)/s';

        if (preg_match($patternWorkExperience, $text, $matches)) 
        {
            $workExperienceText = $matches['content'];
            // dd($workExperienceText);

            $patternDetail = '/(?P<company>[^\n]+)\s*(?P<start_date>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<end_date>[a-zA-Z]{3}\s+\d{4})\s*(?P<position>[^\n]+)/';
            
            if(preg_match_all($patternDetail, $workExperienceText, $matches, PREG_SET_ORDER)) 
            {
                // dd($matches);
                foreach ($matches as $match) 
                {
                    $position = trim($match['position']);
                    $company = trim($match['company']);
                    $startDate = $match['start_date'];
                    $endDate = $match['end_date']; 
                    
                    // Validasi bagian work experience dan simpan kedalam database
                    $this->validateWorkExperience($position, $company, $startDate, $endDate);
                }
            } else {
                echo "Detail pengalaman kerja tidak ditemukan.";
            }
        } else{
            echo "Bagian Work Experience tidak ditemukan.";
        }
    }

    // Method untuk validasi data work experience
    private  function validateWorkExperience($position, $company, $startDate, $endDate) {
        // Validasi end date
        if(!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $endDate)){
            echo "End Date harus memiliki format tanggal MMM YYYY. \n";
            return;
        }
        // Validasi start date
        // Start date sudah sesuai dengan format MMM YYYY
        if(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $startDate)){
            // Konversi start_date dan end_date ke format YYYY-MM-dd
            $startDateFormatted = $this->convertDateFormat($startDate);
            $endDateFormatted = $this->convertDateFormat($endDate);

            // Simpan ke database
            WorkExperience::create
            ([
                'position' => $position, 
                'company' => $company, 
                'start_date' => $startDateFormatted, 
                'end_date' => $endDateFormatted
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

            // Konversi start_date dan end_date ke format YYYY-MM-dd
            $startDateFormatted = $this->convertDateFormat($startDate);
            $endDateFormatted = $this->convertDateFormat($endDate);
            // Tambahkan tahun kepada start_date
            $startDate = $startDate . ' ' . $startYear;
            // Simpan ke database
            WorkExperience::create
            ([
                'position' => $position, 
                'company' => $company, 
                'start_date' => $startDateFormatted, 
                'end_date' => $endDateFormatted
            ]);
        } return null;
    }

    // Fungsi untuk mengonversi tanggal dari format MMM YYYY ke YYYY-MM-dd
    private function convertDateFormat($date) {
        $monthMapping = [
            'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
            'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
            'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
        ];

        // Pisahkan bulan dan tahun dari tanggal yang diberikan
        list($month, $year) = explode(' ', $date);

        // Ubah bulan ke dalam format numerik dan set hari menjadi 01
        $month = $monthMapping[$month];
        $day = '01';

        // Gabungkan kembali menjadi format YYYY-MM-dd
        return "$year-$month-$day";
    }
}
