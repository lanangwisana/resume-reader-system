<?php

namespace App\Http\Controllers;

use App\Models\WorkExperience;
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
        return null;
    }
}
