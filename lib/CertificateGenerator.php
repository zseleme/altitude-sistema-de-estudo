<?php
/**
 * Gerador de Certificados em PDF
 * Biblioteca simples para gerar certificados sem dependências externas
 */

class CertificateGenerator {
    private $studentName;
    private $courseName;
    private $completionDate;
    private $courseHours;
    private $totalLessons;

    public function __construct($studentName, $courseName, $completionDate, $courseHours, $totalLessons) {
        $this->studentName = $studentName;
        $this->courseName = $courseName;
        $this->completionDate = $completionDate;
        $this->courseHours = $courseHours;
        $this->totalLessons = $totalLessons;
    }

    /**
     * Gera o certificado em HTML (será convertido para PDF pelo navegador)
     */
    public function generateHTML() {
        $date = date('d/m/Y', strtotime($this->completionDate));
        $monthYear = strftime('%B de %Y', strtotime($this->completionDate));

        // Código de verificação único
        $verificationCode = strtoupper(substr(md5($this->studentName . $this->courseName . $this->completionDate), 0, 10));

        $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado - ' . htmlspecialchars($this->courseName) . '</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Georgia", serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .certificate {
            background: white;
            width: 297mm;
            height: 210mm;
            padding: 40mm;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }

        .certificate::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 15mm;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
        }

        .certificate::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 15mm;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
        }

        .border-decoration {
            position: absolute;
            border: 3px solid #667eea;
            top: 20mm;
            left: 20mm;
            right: 20mm;
            bottom: 20mm;
            pointer-events: none;
        }

        .content {
            position: relative;
            z-index: 1;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10mm;
            letter-spacing: 2px;
        }

        .certificate-title {
            font-size: 48px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8mm;
            text-transform: uppercase;
            letter-spacing: 4px;
        }

        .certificate-text {
            font-size: 18px;
            color: #666;
            margin-bottom: 5mm;
            line-height: 1.6;
        }

        .student-name {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin: 8mm 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
            display: inline-block;
        }

        .course-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 5mm 0;
            font-style: italic;
        }

        .course-details {
            font-size: 16px;
            color: #666;
            margin: 5mm 0;
        }

        .completion-date {
            font-size: 16px;
            color: #666;
            margin-top: 8mm;
        }

        .verification {
            position: absolute;
            bottom: 25mm;
            right: 25mm;
            text-align: right;
            font-size: 11px;
            color: #999;
        }

        .verification-code {
            font-family: "Courier New", monospace;
            font-weight: bold;
            color: #667eea;
            font-size: 12px;
        }

        .signature-section {
            margin-top: 15mm;
            display: flex;
            justify-content: center;
            gap: 50mm;
        }

        .signature {
            text-align: center;
        }

        .signature-line {
            width: 60mm;
            border-top: 2px solid #333;
            margin-bottom: 5px;
        }

        .signature-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .signature-title {
            font-size: 12px;
            color: #666;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .certificate {
                box-shadow: none;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="border-decoration"></div>

        <div class="content">
            <div class="logo">🎓 ALTITUDE</div>

            <div class="certificate-title">Certificado</div>

            <div class="certificate-text">
                Certificamos que
            </div>

            <div class="student-name">' . htmlspecialchars($this->studentName) . '</div>

            <div class="certificate-text">
                concluiu com êxito o curso
            </div>

            <div class="course-name">' . htmlspecialchars($this->courseName) . '</div>

            <div class="course-details">
                Com carga horária de <strong>' . $this->courseHours . ' horas</strong>,
                composto por <strong>' . $this->totalLessons . ' aulas</strong>
            </div>

            <div class="completion-date">
                Concluído em ' . $date . '
            </div>

            <div class="signature-section">
                <div class="signature">
                    <div class="signature-line"></div>
                    <div class="signature-name">Altitude Platform</div>
                    <div class="signature-title">Plataforma de Ensino</div>
                </div>
            </div>
        </div>

        <div class="verification">
            <div>Código de verificação:</div>
            <div class="verification-code">' . $verificationCode . '</div>
        </div>
    </div>

    <!-- Instruções para o usuário (não aparece na impressão) -->
    <div style="position: fixed; top: 20px; right: 20px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); max-width: 300px; z-index: 1000;" class="no-print">
        <h3 style="margin: 0 0 10px; color: #667eea; font-size: 18px;">💡 Como salvar o certificado?</h3>
        <ol style="margin: 0; padding-left: 20px; color: #666; font-size: 14px; line-height: 1.8;">
            <li>Pressione <strong>Ctrl+P</strong> (ou Cmd+P no Mac)</li>
            <li>Selecione <strong>"Salvar como PDF"</strong></li>
            <li>Clique em <strong>Salvar</strong></li>
        </ol>
        <button onclick="window.print()" style="margin-top: 15px; width: 100%; padding: 10px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px;">
            🖨️ Imprimir/Salvar PDF
        </button>
    </div>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

    <script>
        // Auto-abrir diálogo de impressão após 1 segundo (opcional)
        setTimeout(function() {
            // window.print();
        }, 1000);
    </script>
</body>
</html>';

        return $html;
    }

    /**
     * Retorna o HTML do certificado direto no navegador
     */
    public function output() {
        header('Content-Type: text/html; charset=utf-8');
        echo $this->generateHTML();
    }

    /**
     * Força o download do HTML (usuário pode salvar como PDF)
     */
    public function download($filename = null) {
        if (!$filename) {
            $filename = 'certificado-' . date('Y-m-d') . '.html';
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $this->generateHTML();
    }
}
