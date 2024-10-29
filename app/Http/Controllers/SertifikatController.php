<?php

namespace App\Http\Controllers;

use App\Models\Sertifikat;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class SertifikatController extends Controller
{
    public function extractSertifikat($text, &$errors){
        $patternCertificate = '/Certificate\s*(?P<content>.*?)\s*(?=Work Experiences|Work Experience|Work experiences|Work experience|work Experiences|work Experience|work experiences|work experience|Projects|Project|projects|project|Competitions|Competition|competitions|competition|Skills|Skill|skills|skill|$)/si';

        if(preg_match($patternCertificate, $text, $matches)){
            $CertificateText = $matches['content'];
            // dd($CertificateText);
            $patternDetail = '/(?P<nama_sertif>[^\n]+)\s*(?P<startdate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*-\s*(?P<enddate>[a-zA-Z]{3}(?:\s+\d{4})?)\s*(?P<penerbit>[^\n]+)/i';
            if(preg_match_all($patternDetail, $CertificateText, $matches, PREG_SET_ORDER)){
                // dd($matches);  
                foreach($matches as $match){
                    $nama_sertif = trim($match['nama_sertif']);
                    $penerbit = trim($match['penerbit']);
                    $startdate = $match['startdate'];
                    $enddate = $match['enddate'];

                    if(!preg_match('/^[a-zA-Z]{3}\s+\d{4}$/', $enddate)){
                        $errors[] ="End Date harus memiliki format MMM YYYY. Data yang tidak valid berada pada: $nama_sertif, $penerbit (Format yang ditemukan: $startdate - $enddate)";
                        continue;
                    }
                    // Validasi start date
                    try{
                        // Khasus ketika start date sudah memiliki format valid.
                        if(preg_match('/^[a-zA-Z]{3}\s+\d{4}$/', $startdate)){
                            //  Melakukan pengecekan nama bulan
                            $startMonth = substr($startdate,0,3); // ambil bulan pada startdate
                            $endMonth = substr($enddate, 0, 3);  // Ambil bulan pada enddate.

                            // Lakukan pengecekan kesesuaian format bulan.
                            $startMonthIndex = array_search($startMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            $endMonthIndex = array_search($endMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);

                            // Lakukan penanganan error jika nama bulan start atau end tidak valid
                            if($startMonthIndex === false || $endMonthIndex === false){
                                $errors[] = "Format bulan tidak valid. Data yang tidak valid berada pada: $nama_sertif, $penerbit. (Format yang ditemukan: $startdate - $enddate)";
                                continue;
                            }
                            $startdate = Carbon::parse($startdate)->format("Y-m-d"); // Lakukan perubahan format pada start date.
                            $enddate = Carbon::parse($enddate)->format("Y-m-d"); // Lakukan perubahan format pada enddate.
                        } else{
                            // Kasus ketika startdate tidak memiliki format tahun.
                            $endYear =(int)substr($enddate, -4); // Ambil tahun dari end date
                            $endMonth = substr($enddate, 0, 3); // Ambil bulan dari end_date

                            $startMonthIndex = array_search($startdate, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                            $endMonthIndex = array_search($endMonth, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', "Jul", 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);

                            // Tangani error ketika nama bulan tidak valid.
                            if ($startMonthIndex === false || $endMonthIndex === false) {
                                $errors[] = "Format bulan tidak valid. Data yang tidak valid berada pada: $nama_sertif, $penerbit. (Format yang ditemukan: $startdate - $enddate)";
                                continue;
                            }

                            // Proses penambahan tahun untuk start date yang tidak memiliki tahun 
                            if ($startMonthIndex > $endMonthIndex) {
                                $startYear = $endYear - 1;
                            } else {
                                $startYear = $endYear;
                            }
                             // Ubah format tanggal Y-m-d
                            $startdate = Carbon::parse($startdate)->format("Y-m-d");
                            $enddate = Carbon::parse($enddate)->format("Y-m-d");
                        }
                         // Simpan ke database
                        Sertifikat::firstOrCreate([
                            'nama_sertif' => $nama_sertif, 
                            'penerbit' => $penerbit, 
                            'startdate' => $startdate, 
                            'enddate' => $enddate
                        ]);
                    }catch(Exception $e){
                        $errors[] = "Tedapat format yang tidak valid pada: $nama_sertif, $penerbit";
                    }
                }
            } else{
                $errors[] = "Detail Sertifikasi tidak ditemukan dalam dokumen.";
            }
        } else{
            $errors[] = "Bagian Sertifikasi tidak ditemukan dalam dokumen.";
        }
    }
}
