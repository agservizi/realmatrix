<?php
namespace App\Core;

// Minimal PDF generator for simple text documents (no external deps)
class Pdf
{
    public static function simpleText(string $title, array $lines, string $destPath): void
    {
        $buffer = "%PDF-1.1\n";
        $objects = [];
        $addObject = function ($content) use (&$objects) {
            $objects[] = $content;
            return count($objects);
        };

        $text = $title . "\n" . implode("\n", $lines);
        $stream = "BT /F1 12 Tf 50 750 Td (" . self::escape($text) . ") Tj ET";
        $len = strlen($stream);
        $pageObj = $addObject("<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 3 0 R >> >> >>");
        $pagesObj = $addObject("<< /Type /Pages /Kids [" . $pageObj . " 0 R] /Count 1 >>");
        $fontObj = $addObject("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>");
        $contentObj = $addObject("<< /Length $len >>\nstream\n$stream\nendstream");
        $catalogObj = $addObject("<< /Type /Catalog /Pages $pagesObj 0 R >>");

        $offsets = [];
        $bufferParts = [];
        foreach ($objects as $i => $obj) {
            $offsets[$i + 1] = strlen($buffer) + array_sum(array_map('strlen', $bufferParts));
            $bufferParts[] = ($i + 1) . " 0 obj\n$obj\nendobj\n";
        }
        $buffer .= implode('', $bufferParts);
        $xrefOffset = strlen($buffer);
        $buffer .= "xref\n0 " . (count($objects) + 1) . "\n";
        $buffer .= sprintf("%010d %05d f \n", 0, 65535);
        for ($i = 1; $i <= count($objects); $i++) {
            $buffer .= sprintf("%010d %05d n \n", $offsets[$i], 0);
        }
        $buffer .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root $catalogObj 0 R >>\nstartxref\n$xrefOffset\n%%EOF";

        file_put_contents($destPath, $buffer);
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '\\r', '\\n'], $text);
    }
}
