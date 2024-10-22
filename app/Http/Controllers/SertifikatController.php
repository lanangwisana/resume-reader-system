<?php

namespace App\Http\Controllers;

use App\Models\Sertifikat;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SertifikatController extends Controller
{
    public function extractSertifikat($text){
        $patternCertificate = '/Certificate\s*(?P<content>.*?)\s*(?=Work Experiences|Work Experience|Work experiences|Work experience|work Experiences|work Experience|work experiences|work experience|Projects|Project|projects|project|Competitions|Competition|competitions|competition|Skills|Skill|skills|skill|$)/si';

        if(preg_match($patternCertificate, $text, $matches)){
            $CertificateText = $matches['content'];
            // dd($CertificateText);
            $patternDetail = '/(?P<nama_sertif>[^\n]+?)\s*(-\s*[^\n]*)?\s*(?P<startdate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<enddate>[a-zA-Z]{3}\s*\d{4})\s*(?P<penerbit>[^\n]+)/i';
            if(preg_match_all($patternDetail, $CertificateText, $matches, PREG_SET_ORDER)){
                // dd($matches);  
                foreach($matches as $match){
                    $nama_sertif = trim($match['nama_sertif']);
                    $penerbit = trim($match['penerbit']);
                    $startdate = $match['startdate'];
                    $enddate = $match['enddate'];

                    if(!preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $enddate)){
                        echo "End Date harus memiliki format tanggal MMM YYYY. \n";
                        continue;
                    }
                    // Validasi start date
                    if(preg_match('/^(?:\d{1,2}\s*)?[a-zA-Z]{3}\s+\d{4}$/', $startdate)){
                        // Start date sudah sesuai dengan format MMM YYYY
                        // Ubah format tanggal Y-m-d
                        $startdate = Carbon::parse($startdate)->format
                        ("Y-m-d");
                        $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        // Simpan ke database
                        Sertifikat::create
                        ([
                            'nama_sertif' => $nama_sertif, 
                            'penerbit' => $penerbit, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate
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

                        // Ubah format tanggal Y-m-d
                        $startdate = Carbon::parse($startdate)->format("Y-m-d");
                        $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        // Simpan ke database
                        Sertifikat::create
                        ([
                            'nama_sertif' => $nama_sertif, 
                            'penerbit' => $penerbit, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate
                        ]);
                    }
                }
            }
        } 
    }
}
