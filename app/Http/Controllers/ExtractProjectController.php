<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ExtractProjectController extends Controller
{
    public function extractProject($text){
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
        } return null;
    }
}
