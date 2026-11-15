@php
    $siteKey = $getSiteKey();
    $theme = $getTheme();
    $size = $getSize();
    $language = $getLanguage();
    $action = $getTurnstileAction();
    $resetEvent = $getResetEvent();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        wire:ignore
        x-data="{
            widgetId: null,
            renderedTheme: null,
            themeObserver: null,
            {{-- 'auto' follows the panel's theme (the `dark` class Filament keeps on <html>), not the operating system. --}}
            resolveTheme() {
                const theme = @js($theme)

                if (theme !== 'auto') {
                    return theme
                }

                return document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            },
            watchPanelTheme() {
                if (@js($theme) !== 'auto' || this.themeObserver !== null) {
                    return
                }

                this.themeObserver = new MutationObserver(() => {
                    if (this.widgetId === null || this.resolveTheme() === this.renderedTheme) {
                        return
                    }

                    turnstile.remove(this.widgetId)
                    this.widgetId = null
                    $wire.set(@js($statePath), null)
                    this.render()
                })

                this.themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
            },
            destroy() {
                this.themeObserver?.disconnect()
            },
            render() {
                if (typeof turnstile === 'undefined') {
                    return
                }

                const el = this.$refs.widget

                if (! el || this.widgetId !== null) {
                    return
                }

                this.renderedTheme = this.resolveTheme()

                this.widgetId = turnstile.render(el, {
                    sitekey: @js($siteKey),
                    theme: this.renderedTheme,
                    size: @js($size),
                    language: @js($language),
                    action: @js($action),
                    callback: (token) => {
                        $wire.set(@js($statePath), token)
                    },
                    'expired-callback': () => {
                        $wire.set(@js($statePath), null)
                        this.reset()
                    },
                    'error-callback': () => {
                        $wire.set(@js($statePath), null)
                    },
                })

                this.watchPanelTheme()
            },
            reset() {
                if (typeof turnstile === 'undefined' || this.widgetId === null) {
                    $wire.set(@js($statePath), null)

                    return
                }

                turnstile.reset(this.widgetId)
                $wire.set(@js($statePath), null)
            },
        }"
        x-init="
            if (typeof turnstile !== 'undefined') {
                render()
            } else {
                window.addEventListener('turnstile-script-loaded', () => render(), { once: true })
            }
        "
        x-on:{{ $resetEvent }}.window="reset()"
        class="fi-fo-turnstile w-full"
    >
        <div x-ref="widget" class="w-full [&_.cf-turnstile]:w-full [&_iframe]:!w-full"></div>
    </div>

    @once
        <script
            src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"
            async
            defer
            onload="window.dispatchEvent(new Event('turnstile-script-loaded'))"
        ></script>
    @endonce
</x-dynamic-component>
