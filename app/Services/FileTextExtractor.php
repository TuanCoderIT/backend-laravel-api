<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class FileTextExtractor
{
    private const MAX_CONTENT_LENGTH = 4000;
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
        'text/plain',
    ];

    public function extractText(UploadedFile $file): string
    {
        $this->validateFile($file);

        $mimeType = $file->getMimeType();
        
        switch ($mimeType) {
            case 'application/pdf':
                return $this->extractFromPdf($file);
            
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            case 'application/msword':
                return $this->extractFromDocx($file);
            
            case 'text/plain':
                return $this->extractFromTxt($file);
            
            default:
                throw new Exception('Unsupported file type: ' . $mimeType);
        }
    }

    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }

        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB limit
            throw new Exception('File size exceeds 10MB limit');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new Exception('File type not supported. Only PDF, DOCX, and TXT files are allowed.');
        }
    }

    private function extractFromPdf(UploadedFile $file): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();
            
            if (empty(trim($text))) {
                throw new Exception('PDF appears to be empty or contains only images');
            }
            
            return $this->cleanText($text);
        } catch (Exception $e) {
            throw new Exception('Failed to extract text from PDF: ' . $e->getMessage());
        }
    }

    private function extractFromDocx(UploadedFile $file): string
    {
        try {
            Settings::setZipClass(Settings::PCLZIP);
            
            $phpWord = IOFactory::load($file->getPathname());
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $childElement) {
                            if (method_exists($childElement, 'getText')) {
                                $text .= $childElement->getText() . "\n";
                            }
                        }
                    }
                }
            }
            
            if (empty(trim($text))) {
                throw new Exception('DOCX file appears to be empty');
            }
            
            return $this->cleanText($text);
        } catch (Exception $e) {
            throw new Exception('Failed to extract text from DOCX: ' . $e->getMessage());
        }
    }

    private function extractFromTxt(UploadedFile $file): string
    {
        try {
            $text = file_get_contents($file->getPathname());
            
            if ($text === false) {
                throw new Exception('Failed to read TXT file');
            }
            
            if (empty(trim($text))) {
                throw new Exception('TXT file is empty');
            }
            
            return $this->cleanText($text);
        } catch (Exception $e) {
            throw new Exception('Failed to extract text from TXT: ' . $e->getMessage());
        }
    }

    private function cleanText(string $text): string
    {
        // Remove extra whitespace and line breaks
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove unreadable characters (keep only printable ASCII and common Unicode)
        $text = preg_replace('/[^\x20-\x7E\x{00A0}-\x{024F}\x{1E00}-\x{1EFF}]/u', '', $text);
        
        // Trim whitespace
        $text = trim($text);
        
        // Limit content length
        if (strlen($text) > self::MAX_CONTENT_LENGTH) {
            $text = substr($text, 0, self::MAX_CONTENT_LENGTH);
            
            // Try to cut at the last complete sentence
            $lastPeriod = strrpos($text, '.');
            $lastQuestion = strrpos($text, '?');
            $lastExclamation = strrpos($text, '!');
            
            $lastSentenceEnd = max($lastPeriod, $lastQuestion, $lastExclamation);
            
            if ($lastSentenceEnd !== false && $lastSentenceEnd > self::MAX_CONTENT_LENGTH * 0.8) {
                $text = substr($text, 0, $lastSentenceEnd + 1);
            }
        }
        
        return $text;
    }
}