 
@vite(['resources/assets/vendor/fonts/tabler-icons.scss'])

@vite(['resources/assets/vendor/scss' . $configData['rtlSupport'] . '/core' . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss', 'resources/assets/vendor/scss' . $configData['rtlSupport'] . '/' . $configData['theme'] . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss', 'resources/assets/css/tenants/demo.css', 'resources/assets/vendor/libs/node-waves/node-waves.scss',
 'resources/assets/vendor/scss/pages/front-page.scss',
 'resources/assets/css/shared/main.css'])

@yield('vendor-style')


@yield('page-style')
<style>
    body {
        font-family: "Readex Pro", sans-serif !important;
        font-optical-sizing: auto !important;
        font-weight: 300 !important;
        font-style: normal !important;
    }
</style>
