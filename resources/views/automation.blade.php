<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BetPawa Automation</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div id="app">
        <automation-dashboard></automation-dashboard>
    </div>

    <script>
        import AutomationDashboard from './components/AutomationDashboard.vue';
        
        new Vue({
            el: '#app',
            components: {
                AutomationDashboard
            }
        });
    </script>
</body>
</html> 