<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb, #16a34a); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e5e7eb; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Simple Ecole</h1>
        </div>
        <div class="content">
            <p>Bonjour {{ $parentName }},</p>
            <p><strong>{{ $studentName }}</strong> vient d'être inscrit(e) à <strong>{{ $schoolName }}</strong> et ajouté(e) à votre espace parent.</p>
            <p>Connectez-vous avec vos identifiants habituels pour suivre sa scolarité, ses présences et ses paiements.</p>
            <a href="{{ $loginUrl }}" class="btn">Accéder à mon espace parent</a>
        </div>
        <div class="footer">
            <p>Cet e-mail vous a été envoyé automatiquement par {{ $schoolName }} via Simple Ecole.</p>
        </div>
    </div>
</body>
</html>
