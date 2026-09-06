<?php
/**
 * Developed by: George Rexy Vincent Z. Bacani
 * College: College of Information Technology
 * Role: System Developer / Front-End Developer
 * Development Year: 2026–2027
 * Institution: Don Mariano Marcos Memorial State University
 * Version: v1.0
 * Email: rexygeorge11@gmail.com
 * Copyright: © 2026–2027. All rights reserved.
 */

/**
 * Small dependency-free PDF writer for the E-Sumbong case report.
 * It intentionally uses the built-in PDF Helvetica family so the live server
 * does not need Composer, browser automation, or external binaries.
 */
final class CasePdfDocument
{
    private float $pageWidth = 595.28;   // A4
    private float $pageHeight = 841.89;
    private float $marginLeft = 58.0;
    private float $marginRight = 58.0;
    private float $marginBottom = 58.0;
    private float $cursorY = 165.0;
    private float $contentStartY = 164.0;
    private array $pages = [];
    private string $current = '';
    private array $images = [];
    private int $pageNo = 0;
    private string $headerTitle = 'UNIVERSITY STUDENT COUNCIL';
    private string $headerInstitution = 'Don Mariano Marcos Memorial State University';
    private string $headerEmail = 'usc@dmmmsu.edu.ph';
    private array $headerDividerRgb = [190, 147, 53];
    private array $headerLeftBox = [58.0, 27.0, 70.0, 70.0];
    private array $headerRightBox = [470.0, 31.0, 64.0, 64.0];
    private float $headerTitleY = 43.0;
    private float $headerInstitutionY = 64.0;
    private float $headerEmailY = 81.0;
    private float $headerDividerY = 106.0;
    private float $headerTitleSize = 15.5;
    private float $headerInstitutionSize = 10.2;
    private float $headerEmailSize = 9.2;
    private array $headerTextRgb = [15, 45, 93];

    public function __construct(private string $sealPath, private string $markPath, ?string $templatePath = null)
    {
        $this->images['Seal'] = $this->loadJpeg($sealPath);
        $this->images['Mark'] = $this->loadJpeg($markPath);
        if ($templatePath && is_file($templatePath) && class_exists('CaseReportTemplate')) {
            $template = CaseReportTemplate::load($templatePath);
            if (is_array($template)) $this->applyHeaderTemplate($template);
        }
        $this->addPage();
    }

    public function addPage(): void
    {
        if ($this->pageNo > 0) {
            $this->pages[] = $this->current;
        }
        $this->pageNo++;
        $this->current = '';
        $this->drawHeader();
        $this->cursorY = $this->contentStartY;
    }

    public function getCursorY(): float
    {
        return $this->cursorY;
    }

    public function reportHeading(string $title, string $reference): void
    {
        $this->ensureSpace(62);
        $this->textCentered($this->marginLeft, $this->cursorY, $this->pageWidth - $this->marginRight, strtoupper($title), 13.2, 'B', [16, 41, 80]);
        $this->cursorY += 27;
        $this->text($this->marginLeft, $this->cursorY, 'REFERENCE NO.', 7.2, 'B', [94, 111, 102]);
        $this->cursorY += 14;
        $this->text($this->marginLeft, $this->cursorY, $reference, 14.0, 'B', [16, 41, 80]);
        $this->cursorY += 26;
    }

    public function ensureSpace(float $needed): void
    {
        if ($this->cursorY + $needed > $this->pageHeight - max(40.0, $this->marginBottom)) {
            $this->addPage();
        }
    }

    public function title(string $text): void
    {
        $this->ensureSpace(35);
        $this->text($this->marginLeft, $this->cursorY, $text, 18, 'B', [16, 41, 80]);
        $this->cursorY += 25;
    }

    public function metaRow(array $items): void
    {
        $items = array_values(array_filter($items, static fn($v) => trim((string)$v) !== ''));
        if (!$items) return;
        $this->ensureSpace(22);
        $x = $this->marginLeft;
        foreach ($items as $i => $item) {
            if ($i > 0) {
                $this->text($x, $this->cursorY, '  |  ', 8.5, 'R', [120, 132, 126]);
                $x += 18;
            }
            $safe = $this->clean((string)$item);
            $this->text($x, $this->cursorY, $safe, 8.5, 'R', [82, 96, 88]);
            $x += min(190, max(40, strlen($safe) * 4.15));
        }
        $this->cursorY += 19;
    }

    public function divider(float $gapTop = 2, float $gapBottom = 13): void
    {
        $this->cursorY += $gapTop;
        $this->line($this->marginLeft, $this->cursorY, $this->pageWidth - $this->marginRight, $this->cursorY, [222, 229, 225], 0.7);
        $this->cursorY += $gapBottom;
    }

    public function spacer(float $height = 8): void
    {
        $this->cursorY += max(0, $height);
    }

    public function detailRow(string $label, ?string $value, float $labelWidth = 118.0): void
    {
        $value = trim((string)$value);
        if ($value === '') $value = 'Not provided';

        $available = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $valueWidth = $available - $labelWidth;
        $valueLines = $this->wrap($value, $valueWidth, 10.0);
        $needed = max(18, count($valueLines) * 13.0) + 6;
        $this->ensureSpace($needed);

        $this->text($this->marginLeft, $this->cursorY, strtoupper($label), 7.2, 'B', [95, 112, 103]);
        $yy = $this->cursorY;
        foreach ($valueLines as $line) {
            $this->text($this->marginLeft + $labelWidth, $yy, $line, 10.0, 'R', [24, 42, 34]);
            $yy += 13.0;
        }
        $this->cursorY = max($this->cursorY + 13.0, $yy) + 3;
    }

    public function paragraph(string $text, float $fontSize = 10.0, float $lineHeight = 14.0, float $indent = 0.0): void
    {
        $text = trim((string)$text);
        if ($text === '') $text = 'Not provided';
        $available = $this->pageWidth - $this->marginLeft - $this->marginRight - $indent;
        $lines = $this->wrap($text, $available, $fontSize);
        while ($lines) {
            $this->ensureSpace($lineHeight + 8);
            $remainingHeight = ($this->pageHeight - max(40.0, $this->marginBottom)) - $this->cursorY;
            $maxLines = max(1, (int)floor($remainingHeight / $lineHeight));
            $chunk = array_splice($lines, 0, $maxLines);
            foreach ($chunk as $line) {
                $this->text($this->marginLeft + $indent, $this->cursorY, $line, $fontSize, 'R', [31, 47, 40]);
                $this->cursorY += $lineHeight;
            }
            if ($lines) {
                $this->addPage();
            }
        }
        $this->cursorY += 2;
    }


    public function justifiedParagraph(string $text, float $fontSize = 10.0, float $lineHeight = 15.0, float $indent = 0.0): void
    {
        $text = trim((string)$text);
        if ($text === '') $text = 'Not provided';
        $available = $this->pageWidth - $this->marginLeft - $this->marginRight - $indent;
        $lines = $this->wrap($text, $available, $fontSize);
        $total = count($lines);
        foreach ($lines as $i => $line) {
            $this->ensureSpace($lineHeight + 4);
            $isLast = ($i === $total - 1);
            if (!$isLast) {
                $this->justifiedLine($this->marginLeft + $indent, $this->cursorY, $line, $available, $fontSize, [31, 47, 40]);
            } else {
                $this->text($this->marginLeft + $indent, $this->cursorY, $line, $fontSize, 'R', [31, 47, 40]);
            }
            $this->cursorY += $lineHeight;
        }
        $this->cursorY += 3;
    }

    public function evidenceImage(string $path, string $caption, float $maxWidth = 235.0, float $maxHeight = 165.0): bool
    {
        if (!is_file($path)) return false;
        $info = @getimagesize($path);
        if (!$info || ($info['mime'] ?? '') !== 'image/jpeg') return false;
        $w0 = max(1, (float)$info[0]);
        $h0 = max(1, (float)$info[1]);
        $scale = min($maxWidth / $w0, $maxHeight / $h0, 1.0);
        $w = $w0 * $scale;
        $h = $h0 * $scale;
        $this->ensureSpace($h + 16);
        $name = 'Evidence'.(count($this->images) + 1);
        $this->images[$name] = $this->loadJpeg($path);
        $this->image($name, $this->marginLeft, $this->cursorY, $w, $h);
        $this->cursorY += $h + 10;
        return true;
    }


    /**
     * Render evidence images as a left-aligned, ratio-aware gallery.
     * Images keep their original aspect ratio and share a row whenever they fit.
     * Returns captions/names for files that could not be rendered as JPEG images.
     */
    public function evidenceGallery(array $items, float $maxImageWidth = 225.0, float $maxImageHeight = 180.0, float $gapX = 12.0, float $gapY = 14.0): array
    {
        $available = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $prepared = [];
        $rejected = [];

        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $path = trim((string)($item['path'] ?? ''));
            $caption = trim((string)($item['name'] ?? ''));
            if ($path === '' || !is_file($path)) {
                if ($caption !== '') $rejected[] = $caption;
                continue;
            }

            $info = @getimagesize($path);
            if (!$info || ($info['mime'] ?? '') !== 'image/jpeg') {
                if ($caption !== '') $rejected[] = $caption;
                continue;
            }

            $sourceW = max(1.0, (float)$info[0]);
            $sourceH = max(1.0, (float)$info[1]);
            $scale = min($maxImageWidth / $sourceW, $maxImageHeight / $sourceH, 1.0);
            $drawW = max(1.0, $sourceW * $scale);
            $drawH = max(1.0, $sourceH * $scale);

            // Never let one image exceed the printable width even if the caller
            // supplies a larger maximum.
            if ($drawW > $available) {
                $fit = $available / $drawW;
                $drawW *= $fit;
                $drawH *= $fit;
            }

            $prepared[] = [
                'path' => $path,
                'caption' => $caption,
                'w' => $drawW,
                'h' => $drawH,
            ];
        }

        if (!$prepared) return $rejected;

        $rows = [];
        $row = [];
        $rowWidth = 0.0;
        $rowHeight = 0.0;

        foreach ($prepared as $image) {
            $nextWidth = $row ? ($rowWidth + $gapX + $image['w']) : $image['w'];
            if ($row && $nextWidth > $available + 0.01) {
                $rows[] = ['items' => $row, 'height' => $rowHeight];
                $row = [];
                $rowWidth = 0.0;
                $rowHeight = 0.0;
            }

            if ($row) $rowWidth += $gapX;
            $row[] = $image;
            $rowWidth += $image['w'];
            $rowHeight = max($rowHeight, $image['h']);
        }

        if ($row) $rows[] = ['items' => $row, 'height' => $rowHeight];

        foreach ($rows as $rowData) {
            $rowHeight = (float)$rowData['height'];
            $this->ensureSpace($rowHeight + $gapY);
            $x = $this->marginLeft;

            foreach ($rowData['items'] as $image) {
                $name = 'Evidence'.(count($this->images) + 1);
                $this->images[$name] = $this->loadJpeg($image['path']);
                $this->image($name, $x, $this->cursorY, (float)$image['w'], (float)$image['h']);
                $x += (float)$image['w'] + $gapX;
            }

            $this->cursorY += $rowHeight + $gapY;
        }

        return $rejected;
    }

    public function simpleNote(string $text): void
    {
        $this->ensureSpace(28);
        $this->divider(2, 8);
        $lines = $this->wrap($text, $this->pageWidth - $this->marginLeft - $this->marginRight, 7.8);
        foreach ($lines as $line) {
            $this->text($this->marginLeft, $this->cursorY, $line, 7.8, 'I', [98, 109, 103]);
            $this->cursorY += 10.5;
        }
        $this->cursorY += 2;
    }

    public function sectionLabel(string $label): void
    {
        $this->ensureSpace(18);
        $this->text($this->marginLeft, $this->cursorY, strtoupper($label), 7.4, 'B', [24, 107, 70]);
        $this->cursorY += 14;
    }

    public function field(string $label, ?string $value, bool $boxed = false): void
    {
        $value = trim((string)$value);
        if ($value === '') $value = 'Not provided';
        $available = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $fontSize = $boxed ? 9.4 : 10.2;
        $lineHeight = $boxed ? 13.0 : 14.0;
        $lines = $this->wrap($value, $available - ($boxed ? 22 : 0), $fontSize);
        $firstChunk = true;

        while ($lines) {
            $labelText = $firstChunk ? strtoupper($label) : strtoupper($label).' (CONTINUED)';
            $minimum = $boxed ? 48.0 : 34.0;
            $this->ensureSpace($minimum);
            $this->text($this->marginLeft, $this->cursorY, $labelText, 7.2, 'B', [95, 112, 103]);
            $this->cursorY += 12;

            $availableHeight = ($this->pageHeight - max(40.0, $this->marginBottom)) - $this->cursorY;
            $maxLines = max(1, (int)floor(($availableHeight - ($boxed ? 16 : 4)) / $lineHeight));
            $chunk = array_splice($lines, 0, $maxLines);

            if ($boxed) {
                $boxTop = $this->cursorY - 1;
                $boxHeight = max(34, count($chunk) * $lineHeight + 14);
                $this->roundRect($this->marginLeft, $boxTop, $available, $boxHeight, [251, 253, 252], [218, 229, 222]);
                $yy = $boxTop + 12;
                foreach ($chunk as $line) {
                    $this->text($this->marginLeft + 11, $yy, $line, $fontSize, 'R', [28, 48, 39]);
                    $yy += $lineHeight;
                }
                $this->cursorY = $boxTop + $boxHeight + 10;
            } else {
                foreach ($chunk as $line) {
                    $this->text($this->marginLeft, $this->cursorY, $line, $fontSize, 'R', [24, 42, 34]);
                    $this->cursorY += $lineHeight;
                }
                $this->cursorY += 6;
            }

            if ($lines) {
                $this->addPage();
            }
            $firstChunk = false;
        }
    }

    public function facts(array $facts): void
    {
        $facts = array_values(array_filter($facts, static fn($fact) => is_array($fact) && trim((string)($fact[0] ?? '')) !== ''));
        if (!$facts) return;

        $available = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $cols = min(2, count($facts));
        $gap = 26.0;
        $w = ($available - (($cols - 1) * $gap)) / $cols;
        $rowCount = (int)ceil(count($facts) / $cols);
        $rowH = 39.0;
        $this->ensureSpace(($rowCount * $rowH) + 8);

        foreach ($facts as $i => $fact) {
            $col = $i % $cols;
            $row = intdiv($i, $cols);
            $x = $this->marginLeft + ($col * ($w + $gap));
            $y = $this->cursorY + ($row * $rowH);
            $label = strtoupper(trim((string)($fact[0] ?? '')));
            $value = trim((string)($fact[1] ?? ''));
            if ($value === '') $value = 'Not provided';

            $this->text($x, $y, $label, 7.0, 'B', [94, 111, 102]);
            $wrapped = $this->wrap($value, $w, 9.2);
            $yy = $y + 14;
            foreach (array_slice($wrapped, 0, 2) as $line) {
                $this->text($x, $yy, $line, 9.2, 'B', [24, 45, 35]);
                $yy += 12;
            }
            $this->line($x, $y + 31, $x + $w, $y + 31, [228, 235, 231], 0.55);
        }
        $this->cursorY += ($rowCount * $rowH) + 2;
    }

    public function attachmentList(array $names): void
    {
        if (!$names) return;
        $this->sectionLabel('Case evidence / attachments');
        foreach ($names as $name) {
            $this->ensureSpace(16);
            $this->text($this->marginLeft + 2, $this->cursorY, '-  '.$this->clean((string)$name), 8.8, 'R', [42, 60, 51]);
            $this->cursorY += 14;
        }
        $this->cursorY += 3;
    }

    public function note(string $text): void
    {
        $this->ensureSpace(35);
        $available = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $lines = $this->wrap($text, $available - 18, 7.8);
        $h = max(28, count($lines) * 11 + 12);
        $this->roundRect($this->marginLeft, $this->cursorY, $available, $h, [247, 249, 248], [225, 231, 227]);
        $y = $this->cursorY + 10;
        foreach ($lines as $line) {
            $this->text($this->marginLeft + 9, $y, $line, 7.8, 'I', [91, 104, 97]);
            $y += 11;
        }
        $this->cursorY += $h + 7;
    }

    public function output(): string
    {
        if ($this->pageNo > 0) $this->pages[] = $this->current;
        $this->current = '';

        $objects = [];
        $add = static function (string $body) use (&$objects): int {
            $objects[] = $body;
            return count($objects);
        };

        $catalogId = $add('');
        $pagesId = $add('');
        $fontR = $add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
        $fontB = $add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');
        $fontI = $add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique >>');

        $imageIds = [];
        foreach ($this->images as $name => $img) {
            $smaskId = null;
            if (!empty($img['smask']) && is_array($img['smask'])) {
                $mask = $img['smask'];
                $maskStream = (string)$mask['data'];
                $maskDict = '<< /Type /XObject /Subtype /Image /Width '.(int)$mask['width'].' /Height '.(int)$mask['height'].' /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode';
                if (!empty($mask['decode_parms'])) $maskDict .= ' /DecodeParms '.$mask['decode_parms'];
                $maskDict .= ' /Length '.strlen($maskStream)." >>\nstream\n".$maskStream."\nendstream";
                $smaskId = $add($maskDict);
            }

            $stream = (string)$img['data'];
            $dict = '<< /Type /XObject /Subtype /Image /Width '.(int)$img['width'].' /Height '.(int)$img['height'].' /ColorSpace '.($img['color_space'] ?? '/DeviceRGB').' /BitsPerComponent '.(int)($img['bits'] ?? 8).' /Filter '.($img['filter'] ?? '/DCTDecode');
            if (!empty($img['decode_parms'])) $dict .= ' /DecodeParms '.$img['decode_parms'];
            if ($smaskId !== null) $dict .= ' /SMask '.$smaskId.' 0 R';
            $dict .= ' /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream";
            $imageIds[$name] = $add($dict);
        }

        $pageIds = [];
        foreach ($this->pages as $content) {
            $contentId = $add('<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream");
            $xobjParts = [];
            foreach ($imageIds as $name => $id) $xobjParts[] = '/'.$name.' '.$id.' 0 R';
            $resources = '<< /Font << /F1 '.$fontR.' 0 R /F2 '.$fontB.' 0 R /F3 '.$fontI.' 0 R >> /XObject << '.implode(' ', $xobjParts).' >> >>';
            $pageIds[] = $add('<< /Type /Page /Parent '.$pagesId.' 0 R /MediaBox [0 0 '.$this->pageWidth.' '.$this->pageHeight.'] /Resources '.$resources.' /Contents '.$contentId.' 0 R >>');
        }

        $kids = implode(' ', array_map(static fn($id) => $id.' 0 R', $pageIds));
        $objects[$pagesId - 1] = '<< /Type /Pages /Kids [ '.$kids.' ] /Count '.count($pageIds).' >>';
        $objects[$catalogId - 1] = '<< /Type /Catalog /Pages '.$pagesId.' 0 R >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$body."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1).' /Root '.$catalogId." 0 R >>\nstartxref\n".$xref."\n%%EOF";
        return $pdf;
    }

    private function drawHeader(): void
    {
        [$lx, $ly, $lw, $lh] = $this->headerLeftBox;
        [$rx, $ry, $rw, $rh] = $this->headerRightBox;
        $this->image('Seal', $lx, $ly, $lw, $lh);
        $this->image('Mark', $rx, $ry, $rw, $rh);
        $this->textCentered($this->marginLeft, $this->headerTitleY, $this->pageWidth - $this->marginRight, $this->headerTitle, $this->headerTitleSize, 'B', $this->headerTextRgb);
        $this->textCentered($this->marginLeft, $this->headerInstitutionY, $this->pageWidth - $this->marginRight, $this->headerInstitution, $this->headerInstitutionSize, 'I', $this->headerTextRgb);
        $this->textCentered($this->marginLeft, $this->headerEmailY, $this->pageWidth - $this->marginRight, $this->headerEmail, $this->headerEmailSize, 'I', $this->headerTextRgb);
        $this->line($this->marginLeft, $this->headerDividerY, $this->pageWidth - $this->marginRight, $this->headerDividerY, $this->headerDividerRgb, 1.0);
        if ($this->pageNo > 1) {
            $this->text($this->pageWidth - $this->marginRight - 38, $this->headerDividerY + 20, 'Page '.$this->pageNo, 7.2, 'R', [115, 126, 120]);
        }
    }

    private function applyHeaderTemplate(array $template): void
    {
        foreach (['title' => 'headerTitle', 'institution' => 'headerInstitution', 'email' => 'headerEmail'] as $key => $property) {
            $value = trim((string)($template[$key] ?? ''));
            if ($value !== '') $this->{$property} = $value;
        }

        $page = $template['page'] ?? null;
        $headerDistance = 36.0;
        if (is_array($page)) {
            $width = (float)($page['width_pt'] ?? 0);
            $height = (float)($page['height_pt'] ?? 0);
            if ($width >= 400 && $height >= 500) {
                $this->pageWidth = $width;
                $this->pageHeight = $height;
            }
            $left = (float)($page['margin_left_pt'] ?? 0);
            $right = (float)($page['margin_right_pt'] ?? 0);
            $bottom = (float)($page['margin_bottom_pt'] ?? 0);
            if ($left >= 20 && $left < $this->pageWidth / 2) $this->marginLeft = $left;
            if ($right >= 20 && $right < $this->pageWidth / 2) $this->marginRight = $right;
            if ($bottom >= 20 && $bottom < $this->pageHeight / 2) $this->marginBottom = $bottom;
            $headerDistance = max(18.0, (float)($page['header_distance_pt'] ?? 36.0));
        }

        // Word's header paragraphs in the replaceable template are centered in the
        // document text column. These offsets reproduce the actual Word geometry
        // instead of the previous hard-coded A4 approximation.
        $this->headerTitleY = $headerDistance + 12.0;
        $this->headerInstitutionY = $headerDistance + 27.0;
        $this->headerEmailY = $headerDistance + 42.0;
        $this->headerDividerY = $headerDistance + 50.0;
        $this->contentStartY = max($this->headerDividerY + 62.0, 148.0);

        foreach (['title_style'=>'headerTitleSize','institution_style'=>'headerInstitutionSize','email_style'=>'headerEmailSize'] as $key=>$prop) {
            $style = $template[$key] ?? null;
            if (is_array($style) && !empty($style['size_pt'])) {
                $size = (float)$style['size_pt'];
                if ($size >= 6 && $size <= 30) $this->{$prop} = $size;
            }
        }
        $titleStyle = $template['title_style'] ?? null;
        if (is_array($titleStyle) && isset($titleStyle['color_rgb']) && is_array($titleStyle['color_rgb']) && count($titleStyle['color_rgb']) === 3) {
            $this->headerTextRgb = array_map(static fn($v)=>max(0,min(255,(int)$v)), array_values($titleStyle['color_rgb']));
        }
        if (isset($template['divider_rgb']) && is_array($template['divider_rgb']) && count($template['divider_rgb']) === 3) {
            $this->headerDividerRgb = array_map(static fn($v) => max(0, min(255, (int)$v)), array_values($template['divider_rgb']));
        }

        foreach ([['left_image', 'Seal', 'headerLeftBox'], ['right_image', 'Mark', 'headerRightBox']] as $spec) {
            [$key, $imageName, $boxProperty] = $spec;
            $image = $template[$key] ?? null;
            if (!is_array($image) || empty($image['data']) || empty($image['mime'])) continue;
            try {
                $loaded = $this->loadImageBytes((string)$image['data'], (string)$image['mime']);
                $this->images[$imageName] = $loaded;
                $w = (float)($image['width_pt'] ?? 0);
                $h = (float)($image['height_pt'] ?? 0);
                if ($w <= 0 || $h <= 0) {
                    $w = $imageName === 'Seal' ? 65.0 : 72.0;
                    $h = $w * ((float)$loaded['height'] / max(1.0, (float)$loaded['width']));
                }

                $relativeH = (string)($image['relative_h'] ?? 'column');
                $relativeV = (string)($image['relative_v'] ?? 'paragraph');
                $posH = ((float)($image['pos_h'] ?? 0)) / 12700.0;
                $posV = ((float)($image['pos_v'] ?? 0)) / 12700.0;
                $x = $relativeH === 'page' ? $posH : $this->marginLeft + $posH;
                $y = $relativeV === 'page' ? $posV : $headerDistance + $posV;

                // Guard against malformed template coordinates without changing
                // valid Word anchors.
                $x = max(0.0, min($this->pageWidth - $w, $x));
                $y = max(0.0, min($this->headerDividerY - max(8.0, $h * .25), $y));
                $this->{$boxProperty} = [$x, $y, $w, $h];
            } catch (Throwable $ignored) {
                // Keep built-in fallbacks if a replacement DOCX contains an unsupported image.
            }
        }
    }

    private function loadJpeg(string $path): array
    {
        if (!is_file($path)) throw new RuntimeException('PDF report image is missing.');
        $info = @getimagesize($path);
        if (!$info || ($info['mime'] ?? '') !== 'image/jpeg') throw new RuntimeException('PDF report image must be JPEG.');
        return [
            'width' => (int)$info[0], 'height' => (int)$info[1],
            'data' => (string)file_get_contents($path),
            'filter' => '/DCTDecode', 'color_space' => '/DeviceRGB', 'bits' => 8,
        ];
    }

    private function loadImageBytes(string $data, string $mime): array
    {
        if ($mime === 'image/jpeg') {
            $info = function_exists('getimagesizefromstring') ? @getimagesizefromstring($data) : false;
            if (!$info || ($info['mime'] ?? '') !== 'image/jpeg') throw new RuntimeException('Invalid JPEG template image.');
            return [
                'width' => (int)$info[0], 'height' => (int)$info[1], 'data' => $data,
                'filter' => '/DCTDecode', 'color_space' => '/DeviceRGB', 'bits' => 8,
            ];
        }
        if ($mime === 'image/png') return $this->loadPngBytes($data);
        throw new RuntimeException('Unsupported template image type.');
    }

    private function loadPngBytes(string $png): array
    {
        if (substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") throw new RuntimeException('Invalid PNG signature.');
        $pos = 8; $idat = ''; $width = $height = $bitDepth = $colorType = $interlace = null; $len = strlen($png);
        while ($pos + 12 <= $len) {
            $chunkLen = unpack('N', substr($png, $pos, 4))[1];
            $type = substr($png, $pos + 4, 4);
            $data = substr($png, $pos + 8, $chunkLen);
            if ($type === 'IHDR') {
                $h = unpack('Nwidth/Nheight/Cbit/Ccolor/Ccompression/Cfilter/Cinterlace', $data);
                $width = (int)$h['width']; $height = (int)$h['height']; $bitDepth = (int)$h['bit'];
                $colorType = (int)$h['color']; $interlace = (int)$h['interlace'];
            } elseif ($type === 'IDAT') $idat .= $data;
            elseif ($type === 'IEND') break;
            $pos += 12 + $chunkLen;
        }
        if (!$width || !$height || $bitDepth !== 8 || !in_array($colorType, [2, 6], true) || $interlace !== 0) {
            throw new RuntimeException('Unsupported PNG template image.');
        }
        if ($colorType === 2) {
            return [
                'width' => $width, 'height' => $height, 'data' => $idat,
                'filter' => '/FlateDecode', 'color_space' => '/DeviceRGB', 'bits' => 8,
                'decode_parms' => '<< /Predictor 15 /Colors 3 /BitsPerComponent 8 /Columns '.$width.' >>',
            ];
        }

        $raw = @gzuncompress($idat);
        if (!is_string($raw)) throw new RuntimeException('Unable to decode PNG template image.');
        $bpp = 4; $stride = $width * $bpp; $offset = 0; $prev = array_fill(0, $stride, 0); $rgbRows = ''; $alphaRows = '';
        for ($y = 0; $y < $height; $y++) {
            if ($offset >= strlen($raw)) throw new RuntimeException('Corrupt PNG scanline data.');
            $filter = ord($raw[$offset++]);
            $rowBytes = substr($raw, $offset, $stride);
            if (strlen($rowBytes) !== $stride) throw new RuntimeException('Corrupt PNG scanline length.');
            $offset += $stride;
            $row = array_values(unpack('C*', $rowBytes));
            $decoded = array_fill(0, $stride, 0);
            for ($x = 0; $x < $stride; $x++) {
                $a = $x >= $bpp ? $decoded[$x - $bpp] : 0;
                $b = $prev[$x] ?? 0;
                $c = $x >= $bpp ? ($prev[$x - $bpp] ?? 0) : 0;
                $v = $row[$x];
                if ($filter === 1) $v = ($v + $a) & 255;
                elseif ($filter === 2) $v = ($v + $b) & 255;
                elseif ($filter === 3) $v = ($v + intdiv($a + $b, 2)) & 255;
                elseif ($filter === 4) $v = ($v + $this->paeth($a, $b, $c)) & 255;
                elseif ($filter !== 0) throw new RuntimeException('Unsupported PNG filter.');
                $decoded[$x] = $v;
            }
            $rgbRows .= "\x00"; $alphaRows .= "\x00";
            for ($x = 0; $x < $width; $x++) {
                $i = $x * 4;
                $rgbRows .= chr($decoded[$i]).chr($decoded[$i + 1]).chr($decoded[$i + 2]);
                $alphaRows .= chr($decoded[$i + 3]);
            }
            $prev = $decoded;
        }
        $rgbData = gzcompress($rgbRows, 6); $alphaData = gzcompress($alphaRows, 6);
        if ($rgbData === false || $alphaData === false) throw new RuntimeException('Unable to compress PNG template image.');
        return [
            'width' => $width, 'height' => $height, 'data' => $rgbData,
            'filter' => '/FlateDecode', 'color_space' => '/DeviceRGB', 'bits' => 8,
            'decode_parms' => '<< /Predictor 15 /Colors 3 /BitsPerComponent 8 /Columns '.$width.' >>',
            'smask' => [
                'width' => $width, 'height' => $height, 'data' => $alphaData,
                'decode_parms' => '<< /Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns '.$width.' >>',
            ],
        ];
    }

    private function paeth(int $a, int $b, int $c): int
    {
        $p = $a + $b - $c; $pa = abs($p - $a); $pb = abs($p - $b); $pc = abs($p - $c);
        if ($pa <= $pb && $pa <= $pc) return $a;
        if ($pb <= $pc) return $b;
        return $c;
    }

    private function image(string $name, float $x, float $y, float $w, float $h): void
    {
        $pdfY = $this->pageHeight - $y - $h;
        $this->current .= "q ".$this->num($w)." 0 0 ".$this->num($h).' '.$this->num($x).' '.$this->num($pdfY).' cm /'.$name." Do Q\n";
    }

    private function textCentered(float $x1, float $y, float $x2, string $text, float $size, string $font, array $rgb): void
    {
        $clean = $this->clean($text);
        $width = $this->textWidth($clean, $size, $font);
        $x = $x1 + (($x2 - $x1) - $width) / 2;
        $this->text($x, $y, $clean, $size, $font, $rgb);
    }

    /**
     * Width of the built-in Helvetica family in PDF points. The previous
     * character-count estimate shifted centered Word-template text to the
     * right, especially uppercase bold headings. These AFM-compatible widths
     * match the standard Helvetica metrics used by the PDF writer.
     */
    private function textWidth(string $text, float $size, string $font = 'R'): float
    {
        static $regular = [278,278,355,556,556,889,667,222,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,222,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584];
        static $bold = [278,333,474,556,556,889,722,278,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,278,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584];
        $widths = $font === 'B' ? $bold : $regular;
        $units = 0;
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $code = ord($text[$i]);
            $units += ($code >= 32 && $code <= 126) ? $widths[$code - 32] : 556;
        }
        return ($units / 1000.0) * $size;
    }

    private function text(float $x, float $y, string $text, float $size = 10, string $font = 'R', array $rgb = [0, 0, 0]): void
    {
        $fontName = match ($font) {'B' => 'F2', 'I' => 'F3', default => 'F1'};
        [$r, $g, $b] = array_map(static fn($n) => max(0, min(255, (int)$n)) / 255, $rgb);
        $pdfY = $this->pageHeight - $y;
        $safe = $this->escape($this->clean($text));
        $this->current .= 'BT /'.$fontName.' '.$this->num($size).' Tf '.$this->num($r).' '.$this->num($g).' '.$this->num($b).' rg '.$this->num($x).' '.$this->num($pdfY)." Td (".$safe.") Tj ET\n";
    }

    private function line(float $x1, float $y1, float $x2, float $y2, array $rgb, float $width): void
    {
        [$r, $g, $b] = array_map(static fn($n) => max(0, min(255, (int)$n)) / 255, $rgb);
        $py1 = $this->pageHeight - $y1;
        $py2 = $this->pageHeight - $y2;
        $this->current .= $this->num($r).' '.$this->num($g).' '.$this->num($b).' RG '.$this->num($width).' w '.$this->num($x1).' '.$this->num($py1).' m '.$this->num($x2).' '.$this->num($py2)." l S\n";
    }

    private function roundRect(float $x, float $y, float $w, float $h, array $fill, array $stroke): void
    {
        // PDF's re operator is intentionally used here instead of a true rounded
        // rectangle to keep the writer tiny and dependency-free. The subtle border
        // and fill still match the admin visual language.
        [$fr, $fg, $fb] = array_map(static fn($n) => max(0, min(255, (int)$n)) / 255, $fill);
        [$sr, $sg, $sb] = array_map(static fn($n) => max(0, min(255, (int)$n)) / 255, $stroke);
        $pdfY = $this->pageHeight - $y - $h;
        $this->current .= $this->num($fr).' '.$this->num($fg).' '.$this->num($fb).' rg '.$this->num($sr).' '.$this->num($sg).' '.$this->num($sb).' RG 0.6 w '.$this->num($x).' '.$this->num($pdfY).' '.$this->num($w).' '.$this->num($h)." re B\n";
    }

    private function wrap(string $text, float $maxWidth, float $fontSize): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') return [''];
        $maxChars = max(10, (int)floor($maxWidth / max(3.3, $fontSize * 0.52)));
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            if (strlen($this->clean($candidate)) <= $maxChars) {
                $line = $candidate;
                continue;
            }
            if ($line !== '') $lines[] = $line;
            while (strlen($this->clean($word)) > $maxChars) {
                $lines[] = substr($this->clean($word), 0, $maxChars);
                $word = substr($this->clean($word), $maxChars);
            }
            $line = $word;
        }
        if ($line !== '') $lines[] = $line;
        return $lines ?: [''];
    }

    private function justifiedLine(float $x, float $y, string $line, float $available, float $fontSize, array $rgb): void
    {
        $words = preg_split('/\s+/', trim($line)) ?: [];
        if (count($words) < 2) {
            $this->text($x, $y, $line, $fontSize, 'R', $rgb);
            return;
        }
        $normalSpace = $fontSize * 0.28;
        $wordWidths = [];
        $base = 0.0;
        foreach ($words as $word) {
            $width = strlen($this->clean($word)) * $fontSize * 0.52;
            $wordWidths[] = $width;
            $base += $width;
        }
        $base += $normalSpace * (count($words) - 1);
        $extra = max(0.0, $available - $base);
        $gap = $normalSpace + ($extra / max(1, count($words) - 1));
        $cx = $x;
        foreach ($words as $i => $word) {
            $this->text($cx, $y, $word, $fontSize, 'R', $rgb);
            $cx += $wordWidths[$i] + ($i < count($words) - 1 ? $gap : 0);
        }
    }

    private function clean(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n", "\t"], [' ', ' ', ' ', ' '], $text);
        $text = str_replace(['–','—','−','“','”','‘','’','•','…'], ['-','-','-','"','"',"'","'",'-','...'], $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) $text = $converted;
        }
        return preg_replace('/[^\x20-\x7E\x80-\xFF]/', '', $text) ?? $text;
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function num(float|int $n): string
    {
        return rtrim(rtrim(number_format((float)$n, 3, '.', ''), '0'), '.');
    }
}
