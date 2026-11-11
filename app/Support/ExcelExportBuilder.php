<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

class ExcelExportBuilder
{
    /**
     * Build a multi-sheet OpenXML (.xlsx) binary string and return as an HTTP download response.
     *
     * @param  array<int, array{
     *     name: string,
     *     rows: array<int, array<int, array{value: mixed, style?: int, type?: string}|scalar|null>>,
     *     widths?: array<int, float|int>,
     *     merges?: array<int, string>
     * }>  $sheets
     */
    public static function download(string $filename, array $sheets): Response
    {
        $binary = self::buildXlsxBinary($sheets);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($binary),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Style index constants for xl/styles.xml:
     * 0 = Default cell
     * 1 = Title Header (14pt Bold Navy Text)
     * 2 = Subtitle Header (10pt Italic Slate Text)
     * 3 = Table Header Blue (10pt Bold White on #1E40AF Blue, Thin Border, Center/Left)
     * 4 = Table Header Slate/Raw (10pt Bold White on #334155 Slate, Thin Border)
     * 5 = Data Cell Normal (10pt Regular, Thin Border)
     * 6 = Data Cell Center (10pt Regular, Thin Border, Center)
     * 7 = Data Cell Number/Right (10pt Bold, Thin Border, Right)
     * 8 = Summary/Total Row (10pt Bold on #EFF6FF Light Blue, Thin Border)
     * 9 = Badge Green / Sukses (10pt Bold #065F46 on #D1FAE5, Thin Border, Center)
     * 10 = Badge Amber / Dipinjam (10pt Bold #92400E on #FEF3C7, Thin Border, Center)
     * 11 = Badge Rose / Terlambat/Denda (10pt Bold #9F1239 on #FFE4E6, Thin Border, Center)
     *
     * @param  array<int, array<string, mixed>>  $sheets
     */
    public static function buildXlsxBinary(array $sheets): string
    {
        if (empty($sheets)) {
            $sheets = [['name' => 'Sheet1', 'rows' => []]];
        }

        $files = [];
        $files['[Content_Types].xml'] = self::buildContentTypesXml(count($sheets));
        $files['_rels/.rels'] = self::buildRootRelsXml();
        $files['xl/workbook.xml'] = self::buildWorkbookXml($sheets);
        $files['xl/_rels/workbook.xml.rels'] = self::buildWorkbookRelsXml(count($sheets));
        $files['xl/styles.xml'] = self::buildStylesXml();

        foreach (array_values($sheets) as $idx => $sheet) {
            $sheetNum = $idx + 1;
            $files["xl/worksheets/sheet{$sheetNum}.xml"] = self::buildWorksheetXml(
                $sheet['rows'] ?? [],
                $sheet['widths'] ?? [],
                $sheet['merges'] ?? []
            );
        }

        return self::packZipArchive($files);
    }

    private static function buildContentTypesXml(int $sheetCount): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$overrides
            .'</Types>';
    }

    private static function buildRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, array<string, mixed>>  $sheets
     */
    private static function buildWorkbookXml(array $sheets): string
    {
        $sheetNodes = '';
        foreach (array_values($sheets) as $idx => $sheet) {
            $sheetNum = $idx + 1;
            $rawName = (string) ($sheet['name'] ?? "Sheet{$sheetNum}");
            $cleanName = preg_replace('/[\\\\\\/?*\\[\\]:]/', ' ', $rawName) ?: "Sheet{$sheetNum}";
            $cleanName = mb_substr(trim($cleanName), 0, 31);
            $sheetNodes .= '<sheet name="'.self::xmlEscape($cleanName).'" sheetId="'.$sheetNum.'" r:id="rId'.$sheetNum.'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheetNodes.'</sheets>'
            .'</workbook>';
    }

    private static function buildWorkbookRelsXml(int $sheetCount): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $rels .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }
        $styleId = $sheetCount + 1;
        $rels .= '<Relationship Id="rId'.$styleId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$rels
            .'</Relationships>';
    }

    private static function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="7">'
            .'<font><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FF1E3A8A"/><name val="Calibri"/></font>'
            .'<font><i/><sz val="10"/><color rgb="FF475569"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FF065F46"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FF9F1239"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="8">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1E40AF"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF334155"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEFF6FF"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFE4E6"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFCBD5E1"/></left>'
            .'<right style="thin"><color rgb="FFCBD5E1"/></right>'
            .'<top style="thin"><color rgb="FFCBD5E1"/></top>'
            .'<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="12">'
            // 0: Default
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1: Title Header
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>'
            // 2: Subtitle Header
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>'
            // 3: Table Header Blue
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            // 4: Table Header Slate (Raw)
            .'<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            // 5: Data Normal
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            // 6: Data Center
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 7: Data Number Bold Right
            .'<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 8: Summary / KPI Row
            .'<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            // 9: Badge Green
            .'<xf numFmtId="0" fontId="5" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 10: Badge Amber
            .'<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 11: Badge Rose
            .'<xf numFmtId="0" fontId="6" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, float|int>  $widths
     * @param  array<int, string>  $merges
     */
    private static function buildWorksheetXml(array $rows, array $widths = [], array $merges = []): string
    {
        $colsXml = '';
        if (! empty($widths)) {
            $colsXml .= '<cols>';
            foreach ($widths as $colIdx => $width) {
                $colNum = $colIdx + 1;
                $colsXml .= '<col min="'.$colNum.'" max="'.$colNum.'" width="'.((float) $width).'" customWidth="1"/>';
            }
            $colsXml .= '</cols>';
        }

        $sheetDataXml = '<sheetData>';
        foreach (array_values($rows) as $rIdx => $row) {
            $rowNum = $rIdx + 1;
            $rowHeight = ($rowNum === 1) ? ' height="26" customHeight="1"' : (($rowNum <= 5) ? ' height="20" customHeight="1"' : ' height="19"');
            $sheetDataXml .= '<row r="'.$rowNum.'"'.$rowHeight.'>';

            foreach (array_values($row) as $cIdx => $cell) {
                $colLetter = self::colIndexToLetter($cIdx);
                $cellRef = $colLetter.$rowNum;

                $val = $cell;
                $style = 0;
                $type = null;

                if (is_array($cell)) {
                    $val = $cell['value'] ?? '';
                    $style = (int) ($cell['style'] ?? 0);
                    $type = $cell['type'] ?? null;
                }

                $styleAttr = $style > 0 ? ' s="'.$style.'"' : '';

                if ($val === null || $val === '') {
                    $sheetDataXml .= '<c r="'.$cellRef.'"'.$styleAttr.'/>';
                } elseif ($type === 'number' || (is_int($val) || is_float($val))) {
                    $sheetDataXml .= '<c r="'.$cellRef.'"'.$styleAttr.'><v>'.((string) $val).'</v></c>';
                } else {
                    $strVal = self::xmlEscape((string) $val);
                    $sheetDataXml .= '<c r="'.$cellRef.'" t="inlineStr"'.$styleAttr.'><is><t xml:space="preserve">'.$strVal.'</t></is></c>';
                }
            }

            $sheetDataXml .= '</row>';
        }
        $sheetDataXml .= '</sheetData>';

        $mergesXml = '';
        if (! empty($merges)) {
            $mergesXml .= '<mergeCells count="'.count($merges).'">';
            foreach ($merges as $ref) {
                $mergesXml .= '<mergeCell ref="'.$ref.'"/>';
            }
            $mergesXml .= '</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .$colsXml
            .$sheetDataXml
            .$mergesXml
            .'</worksheet>';
    }

    private static function colIndexToLetter(int $index): string
    {
        $letter = '';
        $n = $index + 1;
        while ($n > 0) {
            $rem = ($n - 1) % 26;
            $letter = chr(65 + $rem).$letter;
            $n = intdiv($n - 1, 26);
        }

        return $letter;
    }

    private static function xmlEscape(string $str): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $str) ?? '';

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Pack associative array of filename => content into a valid ZIP binary archive in memory.
     *
     * @param  array<string, string>  $files
     */
    private static function packZipArchive(array $files): string
    {
        $localParts = [];
        $centralParts = [];
        $offset = 0;

        foreach ($files as $name => $data) {
            $nameLen = strlen($name);
            $uncompSize = strlen($data);
            $crc = crc32($data);

            $compData = gzdeflate($data, 6);
            if ($compData !== false && strlen($compData) < $uncompSize) {
                $method = 8; // Deflate
                $compSize = strlen($compData);
                $payload = $compData;
            } else {
                $method = 0; // Store
                $compSize = $uncompSize;
                $payload = $data;
            }

            // Local file header (30 bytes + filename + payload)
            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034B50,
                20,
                0,
                $method,
                0,
                0,
                $crc,
                $compSize,
                $uncompSize,
                $nameLen,
                0
            ).$name.$payload;

            $localParts[] = $localHeader;

            // Central directory file header (46 bytes + filename)
            $centralParts[] = pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014B50,
                20,
                20,
                0,
                $method,
                0,
                0,
                $crc,
                $compSize,
                $uncompSize,
                $nameLen,
                0,
                0,
                0,
                0,
                32,
                $offset
            ).$name;

            $offset += strlen($localHeader);
        }

        $centralDir = implode('', $centralParts);
        $centralSize = strlen($centralDir);
        $fileCount = count($files);

        $eocd = pack(
            'VvvvvVVv',
            0x06054B50,
            0,
            0,
            $fileCount,
            $fileCount,
            $centralSize,
            $offset,
            0
        );

        return implode('', $localParts).$centralDir.$eocd;
    }
}
