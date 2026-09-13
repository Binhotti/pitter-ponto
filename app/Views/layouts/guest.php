<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Pitter Ponto') ?> • Pitter Pan</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="guest-body">
    <main class="guest-shell">
        <section class="guest-brand">
            <img src="<?= asset('images/pitterpan-logo.png') ?>" alt="Pitter Pan Festas" class="guest-logo">
            <div>
                <span class="eyebrow">Controle de jornada</span>
                <h1>Seu ponto, suas horas, tudo organizado.</h1>
                <p>Registro de entrada, almoço, saída, horas extras e sábados 100% em um único lugar.</p>
            </div>
        </section>

        <section class="guest-card">
            <?php require $viewFile; ?>
        </section>
    </main>
</body>
</html>
