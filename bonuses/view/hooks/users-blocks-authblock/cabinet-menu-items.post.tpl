{$config=ConfigLoader::byModule('bonuses')}
<li>
    <a href="{$router->getUrl('bonuses-front-bonushistory')}"
       class="{if $config.default_template=='amazing'}aside-menu__link{/if}">
        {if $config.default_template=='amazing'}
            <svg style="width: 24px; height: 24px" enable-background="new 0 0 512 512" width="24" height="24" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                <g><path d="m512 341.605-.534-144.044c-.081-23.43-19.445-42.804-43.164-43.188-11.704-.187-22.691 4.177-30.92 12.299-7.91 7.807-12.325 18.177-12.48 29.268l-3.661 42.443c-24.109-48.83-64.017-88.235-113.768-111.669v-36.848l35.938-74.585h-23.862c-1.499 0-1.691 0-5.125-3.359-4.554-4.453-12.174-11.906-26.09-11.906s-21.535 7.454-26.088 11.907c-3.434 3.359-3.627 3.359-5.125 3.359-1.497 0-1.69 0-5.123-3.358-4.552-4.453-12.172-11.908-26.087-11.908s-21.535 7.454-26.088 11.908c-3.433 3.358-3.625 3.358-5.123 3.358h-23.357l34.107 74.433v36.955c-50.152 23.371-90.405 62.948-114.659 112.06l-3.691-42.789c-.155-11.092-4.57-21.461-12.48-29.268-8.228-8.122-19.214-12.474-30.919-12.299-23.72.384-43.083 19.758-43.165 43.184l-.536 144.047 72.224 115.408-23.992 54.972h114.228l27.541-52.938.319-.849c.396-1.055.763-2.116 1.118-3.18 20.371 8.083 42.286 12.319 64.339 12.319 22.211 0 44.251-4.286 64.735-12.474.37 1.116.754 2.23 1.169 3.335l.319.849 27.541 52.938h114.228l-23.992-54.972zm-276.571-240.172h42.064v19.99h-42.064zm-14.642-68.079c3.433-3.359 3.625-3.359 5.123-3.359 1.497 0 1.69 0 5.123 3.358 4.552 4.454 12.172 11.908 26.087 11.908 13.916 0 21.536-7.453 26.089-11.907 3.434-3.359 3.626-3.359 5.125-3.359s1.691 0 5.125 3.359c1.49 1.458 3.309 3.236 5.569 4.97l-15.962 33.129h-53.007l-15.092-32.937c2.377-1.794 4.275-3.651 5.82-5.162zm-58.241 413.506-18.284 35.144h-50.234l11.999-27.492-76.015-121.464.502-135.384c.025-7.221 6.157-13.194 13.671-13.316 3.589-.073 6.923 1.241 9.374 3.661 2.308 2.278 3.574 5.324 3.564 8.576l-.002.669 8.995 104.27c-3.363 4.612-5.819 9.864-7.144 15.454-2.645 11.161-.747 22.588 5.344 32.212l40.933 69.191 25.803-15.265-41.06-69.405-.293-.474c-1.776-2.762-2.328-6.081-1.555-9.344.842-3.554 3.102-6.573 6.362-8.501 6.16-3.642 14.127-2.035 17.868 3.565l44.039 74.018.274.443c10.248 15.937 12.368 35.367 5.859 53.442zm33.574-22.398c-.289-16.553-5.048-32.906-14.069-47.031l-44.073-74.074-.274-.443c-7.486-11.641-19.686-18.389-32.621-19.385.182-.569.359-1.121.543-1.7 18.636-58.637 61.521-106.044 117.863-130.428h65.91c56.189 24.617 98.836 72.165 117.255 130.883.132.419.258.818.389 1.232-12.981.961-25.238 7.718-32.747 19.398l-44.346 74.516c-8.982 14.063-13.736 30.336-14.063 46.814-18.741 8.595-39.331 13.111-60.11 13.111-20.596.001-41.044-4.437-59.657-12.893zm171.617 57.543-18.284-35.144c-6.507-18.074-4.388-37.506 5.86-53.444l44.314-74.462c3.741-5.599 11.705-7.206 17.866-3.563 3.26 1.927 5.521 4.946 6.363 8.501.773 3.263.221 6.582-1.555 9.344l-41.354 69.879 25.803 15.265 40.933-69.191c6.091-9.623 7.989-21.05 5.344-32.212-1.325-5.59-3.782-10.843-7.145-15.454l8.995-104.269-.002-.669c-.01-3.252 1.256-6.298 3.564-8.576 2.451-2.419 5.797-3.72 9.374-3.661 7.513.122 13.646 6.095 13.671 13.32l.502 135.38-76.014 121.464 11.998 27.492z"/><path d="m245.163 264.28c0-3.563 3.266-6.531 13.509-6.531 9.055 0 18.704 2.523 28.947 7.719l10.539-25.384c-8.461-4.75-19.149-7.571-30.134-8.61v-15.884h-20.931v16.18c-24.345 3.415-36.517 17.517-36.517 34.291 0 38.744 57.448 28.205 57.448 41.268 0 3.563-3.563 5.641-13.508 5.641-11.43 0-24.493-3.711-34.291-9.5l-11.281 25.533c8.907 5.492 23.306 9.5 38.15 10.391v15.735h20.931v-16.477c23.009-4.008 34.588-17.962 34.588-33.994-.002-38.3-57.45-27.909-57.45-40.378z"/></g>
            </svg>
        {/if}
        {if $config.default_template=='amazing'}<span class="aside-menu__label">{/if}
        {t}История бонусов{/t}
        {if $config.default_template=='amazing'}</span>{/if}
    </a>
</li>
{if $config.show_bonus_card_section}
    <li>
        <a href="{$router->getUrl('bonuses-front-bonuscard')}"
           class="{if $config.default_template=='amazing'}aside-menu__link{/if}">
            {if $config.default_template=='amazing'}
                <svg style="width: 24px; height: 24px" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg">
                    <g><path d="m432.733 208.866h-20.371c2.082-5.314 3.237-11.089 3.237-17.133 0-25.989-21.144-47.134-47.133-47.134-12.41 0-23.708 4.829-32.134 12.696-8.426-7.868-19.723-12.696-32.133-12.696-25.989 0-47.134 21.145-47.134 47.134 0 6.043 1.156 11.818 3.238 17.133h-20.37c-8.284 0-15 6.716-15 15v64.268c0 8.284 6.716 15 15 15h1.067v49.266c0 8.284 6.716 15 15 15h160.667c8.284 0 15-6.716 15-15v-49.267h1.066c8.284 0 15-6.716 15-15v-64.268c0-8.283-6.715-14.999-15-14.999zm-111.4 128.534h-50.333v-34.267h50.333zm0-64.266h-66.399v-34.268h66.399zm0-64.268h-17.133c-9.447 0-17.134-7.686-17.134-17.133s7.687-17.133 17.134-17.133 17.133 7.687 17.133 17.134zm30-17.133c0-9.447 7.687-17.134 17.134-17.134s17.133 7.687 17.133 17.134-7.686 17.133-17.133 17.133h-17.134zm50.334 145.667h-50.334v-34.267h50.334zm16.066-64.266h-66.4v-34.268h66.4z"/><path d="m448.8 80.333h-385.6c-34.849 0-63.2 28.352-63.2 63.2v224.934c0 34.849 28.352 63.2 63.2 63.2h385.6c34.849 0 63.2-28.352 63.2-63.2v-224.934c0-34.848-28.352-63.2-63.2-63.2zm33.2 288.134c0 18.307-14.894 33.2-33.2 33.2h-385.6c-18.307 0-33.2-14.894-33.2-33.2v-224.934c0-18.307 14.894-33.2 33.2-33.2h385.6c18.307 0 33.2 14.894 33.2 33.2z"/><path d="m127.467 337.4h-48.2c-8.284 0-15 6.716-15 15s6.716 15 15 15h48.2c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/><path d="m79.267 174.6h48.2c8.284 0 15-6.716 15-15s-6.716-15-15-15h-48.2c-8.284 0-15 6.716-15 15s6.715 15 15 15z"/><path d="m175.667 273.134h-96.4c-8.284 0-15 6.716-15 15s6.716 15 15 15h96.4c8.284 0 15-6.716 15-15s-6.716-15-15-15z"/></g>
                </svg>
            {/if}
            {if $config.default_template=='amazing'}<span class="aside-menu__label">{/if}
            {t}Бонусная карта{/t}
            {if $config.default_template=='amazing'}</span>{/if}
        </a>
    </li>
{/if}
{if $current_user.is_bonuscard_partner} {* Если это партнер бонусных карт *}
    <li>
        <a href="{$router->getUrl('bonuses-front-bonuspartnercards')}"
           class="{if $config.default_template=='amazing'}aside-menu__link{/if}">
            {if $config.default_template=='amazing'}<span class="aside-menu__label">{/if}
            {t}Бонусные карты партнера{/t}
            {if $config.default_template=='amazing'}</span>{/if}
        </a>
    </li>
    <li>
        <a href="{$router->getUrl('bonuses-front-cashouthistory')}"
           class="{if $config.default_template=='amazing'}aside-menu__link{/if}">
            {if $config.default_template=='amazing'}<span class="aside-menu__label">{/if}
            {t}Запросы на вывод средств{/t}
            {if $config.default_template=='amazing'}</span>{/if}
        </a>
    </li>
{/if}