<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCATS Temporary Password</title>
</head>
<body style="margin:0;background:#f0f3f1;font-family:Segoe UI,Arial,sans-serif;color:#0f1f17;">
    <div style="max-width:560px;margin:0 auto;padding:28px 16px;">
        <div style="background:#ffffff;border:1px solid #dde8e1;border-radius:12px;overflow:hidden;">
            <div style="background:#0d4a1e;color:#ffffff;padding:22px 24px;">
                <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#b7dfc7;">FCATS</div>
                <h1 style="margin:6px 0 0;font-size:22px;line-height:1.25;">Temporary Password Issued</h1>
            </div>

            <div style="padding:24px;">
                <p style="margin:0 0 14px;font-size:15px;line-height:1.55;">Hello {{ $student->first_name }},</p>
                <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#4a6356;">
                    A temporary password has been created for your FCATS account for {{ $organization->name }}.
                    You will be required to change this password immediately after signing in.
                </p>

                <div style="border:1px solid #dde8e1;border-radius:10px;background:#f8fbf9;padding:16px;margin:18px 0;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#4a6356;margin-bottom:6px;">Username</div>
                    <div style="font-family:Consolas,Menlo,monospace;font-size:15px;font-weight:700;color:#124a2b;">{{ $username }}</div>
                    <div style="height:14px;"></div>
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#4a6356;margin-bottom:6px;">Temporary Password</div>
                    <div style="font-family:Consolas,Menlo,monospace;font-size:18px;font-weight:800;color:#124a2b;letter-spacing:.02em;">{{ $temporaryPassword }}</div>
                </div>

                <p style="margin:0 0 12px;font-size:13.5px;line-height:1.6;color:#4a6356;">
                    For your security, do not share this password. If you did not request this reset, contact your organization officer or the SSC administrator.
                </p>
                <p style="margin:18px 0 0;font-size:13px;color:#7a9387;">This is a system-generated FCATS message.</p>
            </div>
        </div>
    </div>
</body>
</html>
