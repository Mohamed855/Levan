 

@vite(['resources/assets/vendor/fonts/tabler-icons.scss', 'resources/assets/vendor/fonts/fontawesome.scss', 'resources/assets/vendor/fonts/flag-icons.scss', 'resources/assets/vendor/libs/node-waves/node-waves.scss'])
<!-- Core CSS -->
@vite(['resources/assets/vendor/scss' . $configData['rtlSupport'] . '/core' . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss', 'resources/assets/vendor/scss' . $configData['rtlSupport'] . '/' . $configData['theme'] . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss'])


<!-- Vendor Styles -->
@vite(['resources/assets/css/tenants/demo.css', 
'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
 'resources/assets/vendor/libs/typeahead-js/typeahead.scss' , 
  'resources/assets/css/shared/main.css'])
@yield('vendor-style')

<!-- Custom Design System -->
@vite(['resources/scss/admin/app.scss'])

<!-- Page Styles -->
@yield('page-style')

