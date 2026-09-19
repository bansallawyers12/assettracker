---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## No Blade directives inside x-component tags
Do not put @if/@endif (or other Blade directives) inside an <x-*> opening tag. Laravel does not compile those and the view 500s with `unexpected token endif`. Pass booleans as component props instead, e.g. `:required="$assetsRequired"` on x-tom-select.
