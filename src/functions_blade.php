<?php

use Illuminate\Support\Facades\Blade;

Blade::directive('is_active', function ($expression) {
    return "<?php echo ($expression ? 'active' : ''); ?>";
});

Blade::directive('is_invalid', function ($expression) {
    return "<?php echo (empty($expression) ? '' : 'is-invalid'); ?>";
});