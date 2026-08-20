<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Volunteer Certificate</title>
    <style>
        @page { margin: 28px; }
        body {
            margin: 0;
            color: #0F3D35;
            font-family: DejaVu Sans, sans-serif;
            text-align: center;
        }
        .certificate {
            position: relative;
            min-height: 650px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 8px solid #1A5C52;
            padding: 40px 50px 80px 50px;
            background: #e6f0ee;
        }
        .header {
            flex: 0 0 auto;
            margin-bottom: 20px;
        }
        .content {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px 0;
        }
        .footer {
            flex: 0 0 auto;
            border-top: 1px solid #c0d9d4;
            padding-top: 50px;
        }
        .eyebrow {
            color: #1A5C52;
            font-size: 14px;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .organization {
            color: #0F3D35;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 25px;
            text-shadow: 0 2px 4px rgba(31, 109, 94, 0.1);
        }
        h1 {
            margin: 15px 0 20px 0;
            color: #0F3D35;
            font-size: 44px;
            line-height: 1.2;
            font-weight: bold;
        }
        .subtitle { 
            font-size: 18px; 
            color: #1E6B5E;
            margin: 16px 0;
            font-weight: 500;
        }
        .name {
            margin: 40px 0 25px;
            color: #C8960C;
            font-size: 34px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .dedication {
            font-size: 18px;
            color: #1E6B5E;
            margin-bottom: 35px;
            line-height: 1.6;
        }
        .hours {
            margin: 40px auto;
            padding: 25px;
            width: 300px;
            border-top: 3px solid #F2C055;
            border-bottom: 3px solid #F2C055;
            font-size: 26px;
            color: #0F3D35;
            font-weight: 600;
        }
        .details {
            margin: 40px auto 0;
            width: 100%;
            max-width: 550px;
            font-size: 13px;
            color: #1E6B5E;
            border-collapse: collapse;
        }
        .details td { 
            padding: 8px 10px;
            border: none;
        }
        .label { 
            font-weight: bold; 
            color: #0F3D35;
        }
        .verification { 
            font-size: 11px; 
            color: #1E6B5E;
            padding-top: 10px !important;
            padding-bottom: 10px !important;
        }
        .signature {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 6px;
        }
        .line { 
            width: 220px; 
            border-top: 1.5px solid #1E6B5E;
            margin-bottom: 10px;
        }
        .signature-text {
            font-size: 14px;
            color: #0F3D35;
            font-weight: 500;
        }
        .signature-org {
            font-size: 12px;
            color: #1E6B5E;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <div class="eyebrow">Welcome To</div>
            <div class="organization">ATAA ASSOCIATION</div>
            <h1>Certificate of Appreciation</h1>
        </div>

        <div class="content">
            <div class="subtitle">This certificate is proudly presented to</div>
            <div class="name">{{ $volunteer_name }}</div>
            <div class="dedication">in recognition of dedicated volunteer service</div>
            <div class="hours">{{ $total_hours }} volunteer hours</div>

            <table class="details">
                <tr>
                    <td class="label">Certificate number</td>
                    <td>{{ $certificate_number }}</td>
                    <td class="label">Issue date</td>
                    <td>{{ $issued_at }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="verification">Verification token: {{ $token }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div class="signature">
                <div class="line"></div>
                <div class="signature-text">Authorized Representative</div>
                <div class="signature-org">Ataa Association</div>
            </div>
        </div>
    </div>
</body>
</html>