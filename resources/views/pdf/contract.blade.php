<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrat d'Abonnement - {{ $school->name }}</title>
    <style>
        /* Reset et base */
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 11pt; 
            line-height: 1.6; 
            color: #2c3e50; 
            margin: 40px 50px; 
        }
        
        /* En-tête */
        .header { 
            text-align: center; 
            border-bottom: 3px solid #2c3e50; 
            padding-bottom: 15px; 
            margin-bottom: 30px; 
        }
        .header h1 { 
            margin: 0; 
            font-size: 20pt; 
            color: #2c3e50; 
            text-transform: uppercase; 
            letter-spacing: 1px;
        }
        .header .meta { 
            margin-top: 10px; 
            font-size: 10pt; 
            color: #7f8c8d; 
        }
        .header .meta strong { color: #2c3e50; }

        /* Sections */
        h2 { 
            font-size: 12pt; 
            color: #2c3e50; 
            border-bottom: 1px solid #bdc3c7; 
            padding-bottom: 5px; 
            margin-top: 25px; 
            margin-bottom: 15px; 
            text-transform: uppercase;
        }

        /* Blocs des parties */
        .parties { margin-bottom: 25px; }
        .party-block { 
            margin-bottom: 15px; 
            padding: 10px; 
            background-color: #f8f9fa; 
            border-left: 4px solid #3498db;
        }
        .party-block strong { display: block; margin-bottom: 5px; color: #2c3e50; }
        .party-block p { margin: 2px 0; font-size: 10pt; }

        /* Tableau des détails */
        .details-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            font-size: 10pt;
        }
        .details-table th, .details-table td { 
            border: 1px solid #bdc3c7; 
            padding: 10px; 
            text-align: left; 
        }
        .details-table th { 
            background-color: #ecf0f1; 
            color: #2c3e50; 
            font-weight: bold; 
            width: 40%;
        }
        .details-table td { color: #34495e; }
        .text-right { text-align: right !important; }

        /* Engagements */
        .engagements p { text-align: justify; margin-bottom: 10px; }

        /* Signatures */
        .signatures { 
            margin-top: 50px; 
            width: 100%;
        }
        .signature-row {
            display: table;
            width: 100%;
        }
        .signature-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 20px;
        }
        .signature-box { 
            border-top: 2px solid #2c3e50; 
            padding-top: 10px; 
            text-align: center; 
            margin-top: 60px;
        }
        .signature-box strong { display: block; font-size: 11pt; margin-bottom: 5px; }
        .signature-box p { font-size: 9pt; color: #7f8c8d; margin: 0; }

        /* Pied de page */
        .footer { 
            margin-top: 60px; 
            text-align: center; 
            font-size: 8pt; 
            color: #95a5a6; 
            border-top: 1px solid #ecf0f1; 
            padding-top: 15px; 
        }
    </style>
</head>
<body>

    <!-- EN-TÊTE -->
    <div class="header">
        <h1>Contrat d'Abonnement SaaS</h1>
        <div class="meta">
            <p>Contrat N° : <strong>{{ $contract->contract_number }}</strong></p>
            <p>Date de signature : <strong>{{ $contract->signed_at->format('d/m/Y à H:i') }}</strong></p>
        </div>
    </div>

    <!-- PARTIES -->
    <h2>Entre les soussignés</h2>
    <div class="parties">
        <div class="party-block">
            <strong>LE FOURNISSEUR :</strong>
            <p>{{ config('app.name') }}</p>
            <p>Adresse : [Votre Adresse Siège Sociale]</p>
            <p>Email : {{ config('mail.from.address', 'contact@votre-domaine.com') }}</p>
        </div>
        
        <div class="party-block">
            <strong>LE CLIENT (L'ÉCOLE) :</strong>
            <p>{{ $school->name }}</p>
            <p>Adresse : {{ $school->address ?? 'Non renseignée' }}</p>
            <p>Email : {{ $school->email }} | Téléphone : {{ $school->phone ?? 'Non renseigné' }}</p>
        </div>
    </div>

    <!-- OBJET ET DÉTAILS -->
    <h2>Objet et détails de l'abonnement</h2>
    <p>Le présent contrat a pour objet de définir les conditions d'abonnement du Client à la plateforme de gestion scolaire <strong>{{ config('app.name') }}</strong>.</p>

    <table class="details-table">
        <tr>
            <th>Plan d'abonnement</th>
            <td>{{ strtoupper($contract->plan_name) }}</td>
        </tr>
        <tr>
            <th>Date de début d'effet</th>
            <td>{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Date d'expiration</th>
            <td>{{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Montant total facturé</th>
            <td class="text-right"><strong>{{ number_format($contract->amount, 0, ',', ' ') }} FCFA</strong></td>
        </tr>
        <tr>
            <th>Capacité maximale</th>
            <td>{{ $contract->max_students > 0 ? $contract->max_students . ' élèves' : 'Illimité' }}</td>
        </tr>
    </table>

    <!-- ENGAGEMENTS -->
    <h2>Engagements réciproques</h2>
    <div class="engagements">
        <p><strong>Le Fournisseur</strong> s'engage à maintenir le service disponible, à assurer la confidentialité et la sécurité des données scolaires, et à proposer un support technique réactif conformément aux conditions générales de vente.</p>
        <p><strong>Le Client</strong> s'engage à utiliser la plateforme de manière conforme à sa destination, à protéger ses identifiants de connexion, et à ne pas dépasser le nombre d'élèves autorisé sans mettre à jour son abonnement. Le non-respect de ces engagements peut entraîner la suspension du service.</p>
    </div>

    <!-- SIGNATURES -->
    <div class="signatures">
        <div class="signature-row">
            <div class="signature-col">
                <div class="signature-box">
                    <strong>Pour le Fournisseur</strong>
                    <p>(Signature et Cachet)</p>
                </div>
            </div>
            <div class="signature-col">
                <div class="signature-box">
                    <strong>Pour l'École {{ $school->name }}</strong>
                    <p>(Signature et Cachet)</p>
                </div>
            </div>
        </div
    </div>

    <!-- PIED DE PAGE -->
    <div class="footer">
        <p>Ce contrat a été généré électroniquement par {{ config('app.name') }}. Il fait foi entre les deux parties et est soumis au droit en vigueur.</p>
        <p>Fait en deux exemplaires originaux.</p>
    </div>

</body>
</html>