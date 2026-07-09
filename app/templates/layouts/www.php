<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=$this->e($title)?></title>
    <link rel="stylesheet" href="<?=$this->asset('assets/css/app.css')?>">
    <script type="importmap">
        { "imports": { "vue": "https://unpkg.com/vue@3.4.31/dist/vue.esm-browser.prod.js" } }
    </script>
</head>
<body>

<header class="site-header">
    <?=$this->section('heading')?>
</header>

<main>
    <?=$this->section('content')?>
</main>

<footer class="site-footer">
    <p>Snack — PHP-rendered, Vue-powered.</p>
</footer>

<script type="module" src="<?=$this->asset('assets/js/app.js')?>"></script>
</body>
</html>
