<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Πρόσκληση για εγγραφή</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .content {
            margin-bottom: 30px;
        }

        .button {
            display: inline-block;
            background-color: #3490dc;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 4px;
            margin: 20px 0;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Πρόσκληση για εγγραφή</h1>
        </div>

        <div class="content">
            <p>Αγαπητέ/ή {{ $invitation->name ?? 'συνεργάτη' }},</p>

            <p>Έχετε προσκληθεί να συμμετάσχετε ως {{ $invitation->role === 'guide' ? 'Οδηγός' : 'Προσωπικό' }} στην
                εφαρμογή μας.</p>

            <p>Για να αποδεχτείτε την πρόσκληση, κάντε κλικ στον παρακάτω σύνδεσμο:</p>

            <div style="text-align: center;">
                <a href="{{ $invitation->getAcceptUrl() }}" class="button">Αποδοχή Πρόσκλησης</a>
            </div>

            <p>Ο σύνδεσμος θα λήξει στις {{ $invitation->expires_at->format('d/m/Y H:i') }}</p>

            <p>Εάν δεν ζητήσατε αυτή την πρόσκληση, μπορείτε να αγνοήσετε αυτό το email.</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Excursia - Όλα τα δικαιώματα διατηρούνται.</p>
        </div>
    </div>
</body>

</html>
