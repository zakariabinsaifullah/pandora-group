( function () {
    'use strict';

    const PANEL_ID   = 'pandora-contact';
    const OVERLAY_ID = 'pandora-form-overlay';
    const OPEN_CLASS = 'is-open';
    const LOCK_CLASS = 'pandora-panel-open';

    // Cache elements once — they never change after footer injection.
    const panelEl   = document.getElementById( PANEL_ID );
    const overlayEl = document.getElementById( OVERLAY_ID );

    if ( ! panelEl || ! overlayEl ) return;

    let lastTrigger = null;

    // ── Focus trap helpers ────────────────────────────────────────────────────

    const FOCUSABLE = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join( ',' );

    function getFocusable() {
        return Array.from( panelEl.querySelectorAll( FOCUSABLE ) );
    }

    function trapFocus( e ) {
        const focusable = getFocusable();
        if ( ! focusable.length ) return;

        const first = focusable[ 0 ];
        const last  = focusable[ focusable.length - 1 ];

        if ( e.shiftKey ) {
            if ( document.activeElement === first ) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if ( document.activeElement === last ) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    // ── Open / close ──────────────────────────────────────────────────────────

    function openPanel() {
        panelEl.classList.add( OPEN_CLASS );
        overlayEl.classList.add( OPEN_CLASS );
        document.body.classList.add( LOCK_CLASS );
        panelEl.setAttribute( 'aria-hidden', 'false' );

        const closeBtn = panelEl.querySelector( '.pandora-form-panel__close' );
        if ( closeBtn ) closeBtn.focus();

        document.addEventListener( 'keydown', onKeyDown );
    }

    function closePanel() {
        panelEl.classList.remove( OPEN_CLASS );
        overlayEl.classList.remove( OPEN_CLASS );
        document.body.classList.remove( LOCK_CLASS );
        panelEl.setAttribute( 'aria-hidden', 'true' );

        document.removeEventListener( 'keydown', onKeyDown );

        if ( lastTrigger ) lastTrigger.focus();
        lastTrigger = null;
    }

    // ── Keyboard handler (attached only while panel is open) ─────────────────

    function onKeyDown( e ) {
        if ( e.key === 'Escape' ) {
            closePanel();
            return;
        }

        if ( e.key === 'Tab' ) {
            trapFocus( e );
        }
    }

    // ── Click delegation ──────────────────────────────────────────────────────

    document.addEventListener( 'click', function ( e ) {
        const trigger = e.target.closest(
            'a[href="#' + PANEL_ID + '"], [data-open="' + PANEL_ID + '"]'
        );

        if ( trigger ) {
            e.preventDefault();
            lastTrigger = trigger;
            openPanel();
            return;
        }

        if ( e.target.closest( '.pandora-form-panel__close' ) ) {
            closePanel();
            return;
        }

        if ( e.target === overlayEl ) {
            closePanel();
        }
    } );
} )();
