<?php

namespace App\Enums;

enum FileType: string
{
    case Pdf = 'pdf';
    case Doc = 'doc';
    case Docx = 'docx';
    case Xls = 'xls';
    case Xlsx = 'xlsx';
    case Ppt = 'ppt';
    case Pptx = 'pptx';
    case Txt = 'txt';
    case Image = 'image';
    case Other = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromExtension(string $extension): self
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'pdf' => self::Pdf,
            'doc' => self::Doc,
            'docx' => self::Docx,
            'xls' => self::Xls,
            'xlsx' => self::Xlsx,
            'ppt' => self::Ppt,
            'pptx' => self::Pptx,
            'txt' => self::Txt,
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => self::Image,
            default => self::Other,
        };
    }
}
