<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Http\Request;

class ExtractCompetitionController extends Controller
{
    public function extractCompetition($text){
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
        } return null;
    }
}
