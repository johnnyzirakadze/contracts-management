<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentationTitle }}</title>
    <link rel="stylesheet" type="text/css" href="{{ l5_swagger_asset($documentation, 'swagger-ui.css') }}">
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-32x32.png') }}" sizes="32x32"/>
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-16x16.png') }}" sizes="16x16"/>
    <style>
    html
    {
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
    }
    *,
    *:before,
    *:after
    {
        box-sizing: inherit;
    }

    body {
      margin:0;
      background: #fafafa;
    }
    </style>
    @if(config('l5-swagger.defaults.ui.display.dark_mode'))
        <style>
            body#dark-mode,
            #dark-mode .scheme-container {
                background: #1b1b1b;
            }
            #dark-mode .scheme-container,
            #dark-mode .opblock .opblock-section-header{
                box-shadow: 0 1px 2px 0 rgba(255, 255, 255, 0.15);
            }
            #dark-mode .operation-filter-input,
            #dark-mode .dialog-ux .modal-ux,
            #dark-mode input[type=email],
            #dark-mode input[type=file],
            #dark-mode input[type=password],
            #dark-mode input[type=search],
            #dark-mode input[type=text],
            #dark-mode textarea{
                background: #343434;
                color: #e7e7e7;
            }
            #dark-mode .title,
            #dark-mode li,
            #dark-mode p,
            #dark-mode table,
            #dark-mode label,
            #dark-mode .opblock-tag,
            #dark-mode .opblock .opblock-summary-operation-id,
            #dark-mode .opblock .opblock-summary-path,
            #dark-mode .opblock .opblock-summary-path__deprecated,
            #dark-mode h1,
            #dark-mode h2,
            #dark-mode h3,
            #dark-mode h4,
            #dark-mode h5,
            #dark-mode .btn,
            #dark-mode .tab li,
            #dark-mode .parameter__name,
            #dark-mode .parameter__type,
            #dark-mode .prop-format,
            #dark-mode .loading-container .loading:after{
                color: #e7e7e7;
            }
            #dark-mode .opblock-description-wrapper p,
            #dark-mode .opblock-external-docs-wrapper p,
            #dark-mode .opblock-title_normal p,
            #dark-mode .response-col_status,
            #dark-mode table thead tr td,
            #dark-mode table thead tr th,
            #dark-mode .response-col_links,
            #dark-mode .swagger-ui{
                color: wheat;
            }
            #dark-mode .parameter__extension,
            #dark-mode .parameter__in,
            #dark-mode .model-title{
                color: #949494;
            }
            #dark-mode table thead tr td,
            #dark-mode table thead tr th{
                border-color: rgba(120,120,120,.2);
            }
            #dark-mode .opblock .opblock-section-header{
                background: transparent;
            }
            #dark-mode .opblock.opblock-post{
                background: rgba(73,204,144,.25);
            }
            #dark-mode .opblock.opblock-get{
                background: rgba(97,175,254,.25);
            }
            #dark-mode .opblock.opblock-put{
                background: rgba(252,161,48,.25);
            }
            #dark-mode .opblock.opblock-delete{
                background: rgba(249,62,62,.25);
            }
            #dark-mode .loading-container .loading:before{
                border-color: rgba(255,255,255,10%);
                border-top-color: rgba(255,255,255,.6);
            }
            #dark-mode svg:not(:root){
                fill: #e7e7e7;
            }
            #dark-mode .opblock-summary-description {
                color: #fafafa;
            }
        </style>
    @endif
</head>

<body @if(config('l5-swagger.defaults.ui.display.dark_mode')) id="dark-mode" @endif>
<div id="swagger-ui"></div>

@php
    $urlsToDocs = is_array($urlsToDocs ?? null) ? $urlsToDocs : [];
    $__swaggerUrls = [];
    foreach ($urlsToDocs as $title => $url) {
        $__swaggerUrls[] = ['name' => (string) $title, 'url' => (string) $url];
    }
    $__operationsSorter = $operationsSorter ?? null;
    $__configUrl = $configUrl ?? null;
    $__validatorUrl = $validatorUrl ?? null;
    $__docExpansion = config('l5-swagger.defaults.ui.display.doc_expansion', 'none');
    $__filterEnabled = (bool) config('l5-swagger.defaults.ui.display.filter');
    $__persistAuth = (bool) config('l5-swagger.defaults.ui.authorization.persist_authorization');
    $securitySchemes = (array) data_get(config('l5-swagger.defaults.securityDefinitions'), 'securitySchemes', []);
    $schemeTypes = is_array($securitySchemes) ? array_column($securitySchemes, 'type') : [];
    $__oauth2Enabled = in_array('oauth2', $schemeTypes, true);
    $__usePkce = (bool) config('l5-swagger.defaults.ui.authorization.oauth2.use_pkce_with_authorization_code_grant');
    $documentation = $documentation ?? config('l5-swagger.default', 'default');
    $useAbsolutePath = (bool) ($useAbsolutePath ?? config('l5-swagger.documentations.'.$documentation.'.paths.use_absolute_path', false));
    $documentationTitle = $documentationTitle ?? 'API Docs';
@endphp

<meta id="swagger-config"
      data-urls='@json($__swaggerUrls)'
      data-doc-title="{{ $documentationTitle }}"
      data-ops-sorter="{{ $__operationsSorter }}"
      data-config-url="{{ $__configUrl }}"
      data-validator-url="{{ $__validatorUrl }}"
      data-oauth2-redirect-url="{{ route('l5-swagger.'.$documentation.'.oauth2_callback', [], $useAbsolutePath) }}"
      data-csrf="{{ csrf_token() }}"
      data-doc-expansion="{{ $__docExpansion }}"
      data-filter="{{ $__filterEnabled ? '1' : '0' }}"
      data-persist-auth="{{ $__persistAuth ? '1' : '0' }}"
      data-oauth2="{{ $__oauth2Enabled ? '1' : '0' }}"
      data-use-pkce="{{ $__usePkce ? '1' : '0' }}"
/>

<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-bundle.js') }}"></script>
<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-standalone-preset.js') }}"></script>
<script>
    window.onload = function() {
        const cfg = document.getElementById('swagger-config');
        const urls = JSON.parse(cfg.dataset.urls || '[]');

        // Build a system
        const ui = SwaggerUIBundle({
            dom_id: '#swagger-ui',
            urls: urls,
            "urls.primaryName": cfg.dataset.docTitle || '',
            operationsSorter: cfg.dataset.opsSorter || null,
            configUrl: cfg.dataset.configUrl || null,
            validatorUrl: cfg.dataset.validatorUrl || null,
            oauth2RedirectUrl: cfg.dataset.oauth2RedirectUrl || '',

            requestInterceptor: function(request) {
                request.headers['X-CSRF-TOKEN'] = cfg.dataset.csrf || '';
                return request;
            },

            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],

            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],

            layout: "StandaloneLayout",
            docExpansion : cfg.dataset.docExpansion || 'none',
            deepLinking: true,
            filter: cfg.dataset.filter === '1',
            persistAuthorization: cfg.dataset.persistAuth === '1',

        })

        window.ui = ui

        if (cfg.dataset.oauth2 === '1') {
            ui.initOAuth({
                usePkceWithAuthorizationCodeGrant: cfg.dataset.usePkce === '1'
            })
        }
    }
</script>
</body>
</html>
