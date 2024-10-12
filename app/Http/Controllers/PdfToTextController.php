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
    protected $workExperienceController;
    protected $projectController;
    protected $competitionController;
    protected $certificationController;
    
    public function index() {
        return view('index');
    }

    public function __construct(
        ExtractWorkExperienceController $workExperienceController,
        ExtractProjectController $projectController,
        ExtractCompetitionController $competitionController,
        ExtractCertificationController $certificationController
    ){
        $this->workExperienceController = $workExperienceController;
        $this->projectController = $projectController;
        $this->competitionController = $competitionController;
        $this->certificationController = $certificationController;
    }
    public function extractText(Request $request) {
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

        if ($text) {
            $this->workExperienceController->extractWorkExperience($text);
            $this->projectController->extractProject($text);
            $this->competitionController->extractCompetition($text);
            $this->certificationController->extractCertificate($text);
            return view('result', [
                'text' => $text,
            ]);
        } else {
            return view('result', ['error' => 'Text extraction failed']);
        }
    }
}