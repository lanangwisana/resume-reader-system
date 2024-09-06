<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Models\Competition;
use App\Models\Project;
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


        // Mengelompokkan data menggunakan pola regex (Regular Expressions)
        $projects = $this-> extractProjects($text);
        $competitions = $this->extractCompetitions($text);
        $certifications = $this->extractCertifications($text);

        //simpan data ke database
        foreach ($projects as $project) {
            Project::create($project);
        }
        foreach ($competitions as $competition) {
            Competition::create($competition);
        }
        foreach ($certifications as $certification) {
            Certification::create($certification);
        }

        // return redirect()->back()->with('success', 'Data berhasil diekstraksi dan disimpan.');
        // Tampilkan teks yang diekstrak ke halaman
        return view('result', ['text' => $text]);
    }

    private function extractProjects($text)
    {
        // Logika sederhana untuk parsing pengalaman proyek
        $projects = [];
        preg_match_all('/Project: (.*?)\nRole: (.*?)\nYear: (.*?)\n/s', $text, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $projects[] = [
                'project_name' => $match[1],
                'role' => $match[2],
                'year_range' => $match[3],
            ];
        }

        return $projects;
    }

    private function extractCompetitions($text)
    {
        // Logika sederhana untuk parsing lomba
        $competitions = [];
        preg_match_all('/Competition: (.*?)\nYear: (.*?)\nOrganizer: (.*?)\nAchievement: (.*?)\n/s', $text, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $competitions[] = [
                'competition_name' => $match[1],
                'year' => $match[2],
                'organizer' => $match[3],
                'achievement' => $match[4],
            ];
        }

        return $competitions;
    }

    private function extractCertifications($text)
    {
        // Logika sederhana untuk parsing sertifikat
        $certifications = [];
        preg_match_all('/Certification: (.*?)\nOrganizer: (.*?)\nYear: (.*?)\n/s', $text, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $certifications[] = [
                'certification_name' => $match[1],
                'organizer' => $match[2],
                'year' => $match[3],
            ];
        }

        return $certifications;
    }
}
