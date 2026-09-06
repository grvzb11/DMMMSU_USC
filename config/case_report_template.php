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
 * Lightweight DOCX header reader for the replaceable USC report template.
 *
 * The report generator intentionally reads only the Word header because that is
 * the part used as the PDF letterhead. ZipArchive is used when available; a
 * small read-only ZIP fallback keeps the feature working on PHP installations
 * where ext-zip is not enabled.
 */
final class CaseReportTemplate
{
    public static function load(string $docxPath): ?array
    {
        if (!is_file($docxPath) || filesize($docxPath) < 100) {
            return null;
        }

        try {
            $headerXml = self::readEntry($docxPath, 'word/header1.xml');
            $relsXml = self::readEntry($docxPath, 'word/_rels/header1.xml.rels');
            $documentXml = self::readEntry($docxPath, 'word/document.xml');
            if ($headerXml === null || $relsXml === null) {
                return null;
            }

            $texts = [];
            if (preg_match_all('/<w:t(?:\s[^>]*)?>(.*?)<\/w:t>/si', $headerXml, $matches)) {
                foreach ($matches[1] as $value) {
                    $value = trim(html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_XML1, 'UTF-8'));
                    if ($value !== '') {
                        $texts[] = $value;
                    }
                }
            }


            $textStyles = [];
            if (preg_match_all('/<w:r\b.*?<w:t(?:\s[^>]*)?>(.*?)<\/w:t>.*?<\/w:r>/si', $headerXml, $runMatches, PREG_SET_ORDER)) {
                foreach ($runMatches as $run) {
                    $plain = trim(html_entity_decode(strip_tags((string)$run[1]), ENT_QUOTES | ENT_XML1, 'UTF-8'));
                    if ($plain === '') continue;
                    $sizePt = null;
                    if (preg_match('/<w:sz\b[^>]*\bw:val="(\d+)"/i', $run[0], $m)) $sizePt = ((int)$m[1]) / 2.0;
                    $color = null;
                    if (preg_match('/<w:color\b[^>]*\bw:val="([0-9A-Fa-f]{6})"/i', $run[0], $m)) {
                        $hex = strtoupper((string)$m[1]);
                        $color = [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
                    }
                    $textStyles[$plain] = ['size_pt'=>$sizePt, 'color_rgb'=>$color];
                }
            }

            $page = [
                'width_pt' => 595.28,
                'height_pt' => 841.89,
                'margin_left_pt' => 58.0,
                'margin_right_pt' => 58.0,
                'margin_top_pt' => 72.0,
                'margin_bottom_pt' => 58.0,
                'header_distance_pt' => 36.0,
            ];
            if (is_string($documentXml)) {
                if (preg_match('/<w:pgSz\b[^>]*\bw:w="(\d+)"[^>]*\bw:h="(\d+)"/i', $documentXml, $m)) {
                    $page['width_pt'] = ((int)$m[1]) / 20.0;
                    $page['height_pt'] = ((int)$m[2]) / 20.0;
                }
                if (preg_match('/<w:pgMar\b([^>]*)>/i', $documentXml, $m)) {
                    $attrs = (string)$m[1];
                    foreach (['left'=>'margin_left_pt','right'=>'margin_right_pt','top'=>'margin_top_pt','bottom'=>'margin_bottom_pt','header'=>'header_distance_pt'] as $attr=>$key) {
                        if (preg_match('/\bw:'.preg_quote($attr,'/').'="(\d+)"/i', $attrs, $am)) $page[$key] = ((int)$am[1]) / 20.0;
                    }
                }
            }

            $rels = [];
            if (preg_match_all('/<Relationship\b[^>]*\bId="([^"]+)"[^>]*\bTarget="([^"]+)"[^>]*\/?\s*>/si', $relsXml, $relMatches, PREG_SET_ORDER)) {
                foreach ($relMatches as $rel) {
                    $rels[(string)$rel[1]] = html_entity_decode((string)$rel[2], ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
            }

            $anchors = [];
            if (preg_match_all('/<wp:anchor\b.*?<\/wp:anchor>/si', $headerXml, $anchorMatches)) {
                foreach ($anchorMatches[0] as $block) {
                    if (!preg_match('/\br:embed="([^"]+)"/i', $block, $embed)) {
                        continue;
                    }
                    $rid = (string)$embed[1];
                    $target = $rels[$rid] ?? '';
                    if ($target === '') {
                        continue;
                    }

                    $posH = 0;
                    $posV = 0;
                    $relativeH = 'column';
                    $relativeV = 'paragraph';
                    if (preg_match('/<wp:positionH\b[^>]*relativeFrom="([^"]+)".*?<wp:posOffset>(-?\d+)<\/wp:posOffset>.*?<\/wp:positionH>/si', $block, $m)) {
                        $relativeH = (string)$m[1];
                        $posH = (int)$m[2];
                    }
                    if (preg_match('/<wp:positionV\b[^>]*relativeFrom="([^"]+)".*?<wp:posOffset>(-?\d+)<\/wp:posOffset>.*?<\/wp:positionV>/si', $block, $m)) {
                        $relativeV = (string)$m[1];
                        $posV = (int)$m[2];
                    }
                    $cx = 0;
                    $cy = 0;
                    if (preg_match('/<wp:extent\b[^>]*\bcx="(\d+)"[^>]*\bcy="(\d+)"/i', $block, $m)) {
                        $cx = (int)$m[1];
                        $cy = (int)$m[2];
                    }

                    $entry = 'word/'.ltrim(str_replace('\\', '/', $target), '/');
                    $bytes = self::readEntry($docxPath, $entry);
                    if ($bytes === null) {
                        continue;
                    }
                    $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
                    $mime = match ($ext) {
                        'png' => 'image/png',
                        'jpg', 'jpeg' => 'image/jpeg',
                        default => '',
                    };
                    if ($mime === '') {
                        continue;
                    }

                    $anchors[] = [
                        'pos_h' => $posH,
                        'pos_v' => $posV,
                        'relative_h' => $relativeH,
                        'relative_v' => $relativeV,
                        'width_pt' => $cx > 0 ? $cx / 12700.0 : null,
                        'height_pt' => $cy > 0 ? $cy / 12700.0 : null,
                        'mime' => $mime,
                        'data' => $bytes,
                    ];
                }
            }

            usort($anchors, static fn(array $a, array $b): int => ($a['pos_h'] ?? 0) <=> ($b['pos_h'] ?? 0));

            $divider = [198, 166, 100];
            if (preg_match('/<w:bottom\b[^>]*\bw:color="([0-9A-Fa-f]{6})"/i', $headerXml, $colorMatch)) {
                $hex = strtoupper((string)$colorMatch[1]);
                $divider = [
                    hexdec(substr($hex, 0, 2)),
                    hexdec(substr($hex, 2, 2)),
                    hexdec(substr($hex, 4, 2)),
                ];
            }

            $title = $texts[0] ?? null;
            $institution = $texts[1] ?? null;
            $email = $texts[2] ?? null;
            return [
                'title' => $title,
                'institution' => $institution,
                'email' => $email,
                'title_style' => $title ? ($textStyles[$title] ?? null) : null,
                'institution_style' => $institution ? ($textStyles[$institution] ?? null) : null,
                'email_style' => $email ? ($textStyles[$email] ?? null) : null,
                'page' => $page,
                'divider_rgb' => $divider,
                'left_image' => $anchors[0] ?? null,
                'right_image' => count($anchors) > 1 ? $anchors[count($anchors) - 1] : null,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function readEntry(string $path, string $entry): ?string
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($path) === true) {
                $data = $zip->getFromName($entry);
                $zip->close();
                if ($data !== false) {
                    return (string)$data;
                }
            }
        }

        return self::readEntryWithoutZipExtension($path, $entry);
    }

    private static function readEntryWithoutZipExtension(string $path, string $wanted): ?string
    {
        $blob = @file_get_contents($path);
        if (!is_string($blob) || strlen($blob) < 22) {
            return null;
        }

        $eocd = strrpos($blob, "PK\x05\x06");
        if ($eocd === false || $eocd + 22 > strlen($blob)) {
            return null;
        }

        $tail = unpack('ventriesDisk/ventriesTotal/VcentralSize/VcentralOffset/vcommentLen', substr($blob, $eocd + 8, 14));
        if (!is_array($tail)) {
            return null;
        }

        $pos = (int)$tail['centralOffset'];
        $total = (int)$tail['entriesTotal'];
        for ($i = 0; $i < $total; $i++) {
            if (substr($blob, $pos, 4) !== "PK\x01\x02") {
                return null;
            }
            $h = unpack(
                'vversionMade/vversionNeed/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vnameLen/vextraLen/vcommentLen/vdisk/vinternal/Vexternal/VlocalOffset',
                substr($blob, $pos + 4, 42)
            );
            if (!is_array($h)) {
                return null;
            }
            $nameLen = (int)$h['nameLen'];
            $extraLen = (int)$h['extraLen'];
            $commentLen = (int)$h['commentLen'];
            $name = substr($blob, $pos + 46, $nameLen);

            if ($name === $wanted) {
                $local = (int)$h['localOffset'];
                if (substr($blob, $local, 4) !== "PK\x03\x04") {
                    return null;
                }
                $lh = unpack('vversion/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vnameLen/vextraLen', substr($blob, $local + 4, 26));
                if (!is_array($lh)) {
                    return null;
                }
                $dataOffset = $local + 30 + (int)$lh['nameLen'] + (int)$lh['extraLen'];
                $compressed = substr($blob, $dataOffset, (int)$h['compressed']);
                $method = (int)$h['method'];
                if ($method === 0) {
                    return $compressed;
                }
                if ($method === 8) {
                    $inflated = @gzinflate($compressed);
                    return $inflated === false ? null : (string)$inflated;
                }
                return null;
            }

            $pos += 46 + $nameLen + $extraLen + $commentLen;
        }
        return null;
    }
}
